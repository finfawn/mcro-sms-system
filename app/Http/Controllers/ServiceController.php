<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\ServiceStatusLog;
use App\Services\SmsService;
use App\Automation\AutomationEngine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServiceController extends Controller
{
    protected AutomationEngine $automation;
    protected SmsService $sms;
    protected function statusesForType(string $type): array
    {
        $default = ['Filed','Processing','Endorsed','Released','Rejected'];
        if ($type === 'Application for Marriage License') {
            return ['Filed','Paid','Posted','Released'];
        }
        if ($type === 'Delayed Registration') {
            return ['Filed','Under Verification','Consistent','Inconsistent','Posted','Ready for Release','Released','Rejected'];
        }
        return $default;
    }

    public function __construct(AutomationEngine $automation, SmsService $sms)
    {
        $this->automation = $automation;
        $this->sms = $sms;
    }

    public function index(Request $request): View
    {
        $query = Service::query()->with('statusLogs');
        $serviceType = $request->query('service_type');
        $status = $request->query('status');
        $name = $request->query('name');
        $sort = $request->query('sort', 'updated');
        $direction = strtolower($request->query('direction', 'desc')) === 'asc' ? 'asc' : 'desc';

        if ($serviceType) {
            $query->where('service_type', $serviceType);
        }
        if ($status) {
            $query->where('status', $status);
        }
        if ($name) {
            $query->where(function($q) use ($name) {
                $q->where('citizen_name', 'like', '%'.$name.'%')
                  ->orWhere('reference_no', 'like', '%'.$name.'%');
            });
        }

        $sortMap = [
            'name' => 'citizen_name',
            'updated' => 'updated_at',
            'created' => 'created_at',
            'reference' => 'reference_no',
            'type' => 'service_type',
            'status' => 'status',
        ];
        $column = $sortMap[$sort] ?? 'updated_at';

        $services = $query->orderBy($column, $direction)->get();
        $types = Service::select('service_type')->distinct()->orderBy('service_type')->pluck('service_type');
        $statusOptions = ['Filed','Processing','Paid','Under Verification','Consistent','Inconsistent','Posted','Ready for Release','Endorsed','Released','Rejected'];

        return view('services.index', [
            'services' => $services,
            'types' => $types,
            'statusOptions' => $statusOptions,
            'serviceType' => $serviceType,
            'status' => $status,
            'name' => $name,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }
    public function show(Service $service): View
    {
        return view('services.show', compact('service'));
    }

    public function create(): View
    {
        return view('services.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'citizen_name' => ['required', 'string', 'max:255'],
            'mobile_number' => ['required', 'string', 'max:30'],
            'service_type' => ['required', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);

        $yy = now()->format('y');
        $prefix = "CR{$yy}-";
        $latest = Service::withTrashed()
            ->where('reference_no', 'like', $prefix.'%')
            ->orderBy('reference_no', 'desc')
            ->first();

        $seq = 1;
        if ($latest) {
            $parts = explode('-', $latest->reference_no);
            $lastSeq = intval(end($parts));
            $seq = $lastSeq + 1;
        }
        $referenceNo = $prefix.str_pad((string) $seq, 6, '0', STR_PAD_LEFT);
        while (Service::withTrashed()->where('reference_no', $referenceNo)->exists()) {
            $seq++;
            $referenceNo = $prefix.str_pad((string) $seq, 6, '0', STR_PAD_LEFT);
        }

        Service::create([
            'reference_no' => $referenceNo,
            'citizen_name' => $validated['citizen_name'],
            'mobile_number' => $validated['mobile_number'],
            'service_type' => $validated['service_type'],
            'status' => 'Filed',
            'notes' => $validated['notes'] ?? null,
        ]);

        $svc = Service::where('reference_no', $referenceNo)->first();
        if ($svc) {
            ServiceStatusLog::create([
                'service_id' => $svc->id,
                'status' => 'Filed',
                'note' => null,
            ]);
        }

        return redirect()->route('services.index')->with('status', 'Service entry filed');
    }

    public function edit(Service $service): View
    {
        $statuses = $this->statusesForType($service->service_type);
        return view('services.edit', [
            'service' => $service,
            'statuses' => $statuses,
        ]);
    }

    public function update(Request $request, Service $service): RedirectResponse
    {
        $validated = $request->validate([
            'citizen_name' => ['required', 'string', 'max:255'],
            'mobile_number' => ['required', 'string', 'max:30'],
            'service_type' => ['required', 'string', 'max:100'],
            'status' => ['required', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);

        $previousStatus = $service->status;
        $service->update([
            'citizen_name' => $validated['citizen_name'],
            'mobile_number' => $validated['mobile_number'],
            'service_type' => $validated['service_type'],
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? null,
        ]);

        if ($previousStatus !== $validated['status']) {
            ServiceStatusLog::create([
                'service_id' => $service->id,
                'status' => $validated['status'],
                'note' => null,
            ]);
        }
        if ($previousStatus !== 'Paid' && $validated['status'] === 'Paid') {
            $service->payment_date = now();
            $service->save();
        }
        if ($previousStatus !== 'Released' && $validated['status'] === 'Released') {
            $service->release_date = now();
            $service->save();
        }
        if ($validated['service_type'] === 'Delayed Registration') {
            if ($previousStatus !== 'Under Verification' && $validated['status'] === 'Under Verification') {
                $this->sms->send($service, 'verification_started');
            }
            if ($previousStatus !== 'Inconsistent' && $validated['status'] === 'Inconsistent') {
                $this->sms->send($service, 'requirements_incomplete');
            }
            if ($previousStatus !== 'Consistent' && $validated['status'] === 'Consistent') {
                $this->sms->send($service, 'verification_consistent');
            }
        }
        if (
            $validated['service_type'] === 'Application for Marriage License' &&
            $previousStatus !== 'Posted' &&
            $validated['status'] === 'Posted' &&
            !$service->sms_posting_sent
        ) {
            $service->posting_start_date = now()->toDateString();
            $this->sms->send($service, 'posting_notice');
            $service->sms_posting_sent = true;
            $service->save();
        }

        // Apply automation rules for status changes
        $this->automation->handleStatusChange($service);

        return redirect()->route('services.index')->with('status', 'Service updated');
    }

    public function destroy(Service $service): RedirectResponse
    {
        $service->forceDelete();
        return redirect()->route('services.index')->with('status', 'Service entry deleted');
    }

    public function restore($id): RedirectResponse
    {
        $service = Service::withTrashed()->findOrFail($id);
        $service->restore();
        return redirect()->route('services.index')->with('status', 'Service entry restored');
    }
    public function forceDelete($id)
    {
        $service = Service::withTrashed()->find($id);
        if (!$service) {
            return response()->json(['ok' => false], 404);
        }
        if ($service->deleted_at === null) {
            return response()->json(['ok' => false, 'reason' => 'not_trashed'], 409);
        }
        $service->forceDelete();
        return response()->json(['ok' => true]);
    }

    public function updateStatus(Request $request, Service $service): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'max:50'],
        ]);
        $allowed = $this->statusesForType($service->service_type);
        if (!in_array($validated['status'], $allowed, true)) {
            return redirect()->route('services.index')->with('status', 'Status not allowed for '.$service->service_type);
        }
        $prev = $service->status;
        $idxPrev = array_search($prev, $allowed);
        $idxNew = array_search($validated['status'], $allowed);
        if ($idxPrev !== false && $idxNew !== false && $idxNew < $idxPrev) {
            return redirect()->route('services.index')->with('status', 'Cannot move back in status');
        }
        $service->update(['status' => $validated['status']]);
        if ($prev !== $validated['status']) {
            ServiceStatusLog::create([
                'service_id' => $service->id,
                'status' => $validated['status'],
                'note' => null,
            ]);
        }
        if ($prev !== 'Paid' && $validated['status'] === 'Paid') {
            $service->payment_date = now();
            $service->save();
        }
        if ($prev !== 'Released' && $validated['status'] === 'Released') {
            $service->release_date = now();
            $service->save();
        }
        if ($service->service_type === 'Delayed Registration') {
            if ($prev !== 'Under Verification' && $validated['status'] === 'Under Verification') {
                $this->sms->send($service, 'verification_started');
            }
            if ($prev !== 'Inconsistent' && $validated['status'] === 'Inconsistent') {
                $this->sms->send($service, 'requirements_incomplete');
            }
            if ($prev !== 'Consistent' && $validated['status'] === 'Consistent') {
                $this->sms->send($service, 'verification_consistent');
            }
        }
        if (
            $service->service_type === 'Application for Marriage License' &&
            $prev !== 'Posted' &&
            $validated['status'] === 'Posted' &&
            !$service->sms_posting_sent
        ) {
            $service->posting_start_date = now()->toDateString();
            $this->sms->send($service, 'posting_notice');
            $service->sms_posting_sent = true;
            $service->save();
        }
        // Apply automation rules on status change
        $this->automation->handleStatusChange($service);
        return redirect()->route('services.index')->with('status', 'Status updated');
    }

    public function bulkStatus(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
            'status' => ['required', 'string', 'max:50'],
        ]);
        $services = Service::whereIn('id', $validated['ids'])->get();
        $updatedCount = 0;
        $skippedCount = 0;
        foreach ($services as $svc) {
            $allowed = $this->statusesForType($svc->service_type);
            if (!in_array($validated['status'], $allowed, true)) {
                $skippedCount++;
                continue;
            }
            $prevStatus = $svc->status;
            $idxPrev = array_search($prevStatus, $allowed);
            $idxNew = array_search($validated['status'], $allowed);
            if ($idxPrev !== false && $idxNew !== false && $idxNew < $idxPrev) {
                $skippedCount++;
                continue;
            }
            $svc->update(['status' => $validated['status']]);
            if ($prevStatus !== $validated['status']) {
                ServiceStatusLog::create([
                    'service_id' => $svc->id,
                    'status' => $validated['status'],
                    'note' => null,
                ]);
            }
            if ($prevStatus !== 'Paid' && $validated['status'] === 'Paid') {
                $svc->payment_date = now();
                $svc->save();
            }
            if ($prevStatus !== 'Released' && $validated['status'] === 'Released') {
                $svc->release_date = now();
                $svc->save();
            }
            if ($svc->service_type === 'Delayed Registration') {
                if ($prevStatus !== 'Under Verification' && $validated['status'] === 'Under Verification') {
                    $this->sms->send($svc, 'verification_started');
                }
                if ($prevStatus !== 'Inconsistent' && $validated['status'] === 'Inconsistent') {
                    $this->sms->send($svc, 'requirements_incomplete');
                }
                if ($prevStatus !== 'Consistent' && $validated['status'] === 'Consistent') {
                    $this->sms->send($svc, 'verification_consistent');
                }
            }
            if (
                $svc->service_type === 'Application for Marriage License' &&
                $prevStatus !== 'Posted' &&
                $validated['status'] === 'Posted' &&
                !$svc->sms_posting_sent
            ) {
                $svc->posting_start_date = now()->toDateString();
                $this->sms->send($svc, 'posting_notice');
                $svc->sms_posting_sent = true;
                $svc->save();
            }
            $updatedCount++;
        }
        // Apply automation rules after bulk updates
        foreach ($services as $svc) {
            $this->automation->handleStatusChange($svc);
        }
        return redirect()->route('services.index')->with('status', 'Updated: '.$updatedCount.'. Skipped: '.$skippedCount);
    }
}
