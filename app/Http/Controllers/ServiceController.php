<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\ServiceStatusLog;
use App\Models\SmsTemplate;
use App\Services\SmsService;
use App\Automation\AutomationEngine;
use Illuminate\Support\Carbon;
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
        if ($type === 'Frontline Service') {
            return ['Authenticated','Form Filled','Submitted','Paid','Claim Stub Issued','Ready for Pickup','Released'];
        }
        if ($type === 'Request for PSA documents through BREQS') {
            return ['Authenticated','Form Filled','Submitted','Paid','Claim Stub Issued','Ready for Pickup','Released'];
        }
        if ($type === 'Endorsement for Negative PSA - Positive LCRO') {
            return ['Authenticated','Documents Submitted','Processing','Sent to PSA','PSA Feedback','Reworked and Resent','PSA No Feedback','Released'];
        }
        if ($type === 'Endorsement for Blurred PSA - Clear LCRO File') {
            return ['Authenticated','Documents Submitted','Processing','Sent to PSA','PSA Feedback','Reworked and Resent','PSA No Feedback','Released'];
        }
        if ($type === 'Endorsement of Legal Instrument & MC 2010-04 & Court Order') {
            return ['Authenticated','Documents Submitted','Processing','Sent to PSA','PSA Feedback','Reworked and Resent','PSA No Feedback','Released'];
        }
        if ($type === 'Petitions filed under RA 9048 - Clerical Error') {
            return ['Authenticated','Requirements Submitted','Processing','Petition Ready for Filing','Filed','Sent to PSA Legal Services','PSA Impugned','Motion Prepared','Resent to PSA Legal Services','PSA Affirmed','Released'];
        }
        if ($type === 'Petitions filed under RA 9048 & RA 10172') {
            return ['Authenticated','Requirements Submitted','Processing','Petition Ready for Filing','Filed','Published','Decision Rendered','Sent to PSA Legal Services','PSA Impugned','Motion Prepared','Resent to PSA Legal Services','PSA Affirmed','Released'];
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
        $types = SmsTemplate::select('service_type')->distinct()->orderBy('service_type')->pluck('service_type');
        return view('services.create', ['types' => $types]);
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
            $validated['service_type'] === 'Request for PSA documents through BREQS' &&
            $previousStatus !== 'Ready for Pickup' &&
            $validated['status'] === 'Ready for Pickup' &&
            !$service->sms_ready_sent
        ) {
            $this->sms->send($service, 'ready_for_pickup');
            $service->sms_ready_sent = true;
            $service->save();
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

    public function bulkUploadForm(): View
    {
        $types = SmsTemplate::select('service_type')->distinct()->orderBy('service_type')->pluck('service_type');
        return view('services.bulk_upload', ['types' => $types]);
    }
    public function bulkUploadTemplate()
    {
        $content = "citizen_name,mobile_number,service_type,notes\n";
        $content .= "Juan Dela Cruz,09171234567,Application for Marriage License,\n";
        $content .= "Maria Santos,09181234567,Petitions filed under RA 9048 - Clerical Error,Needs assistance\n";
        return response($content, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename=\"services_template.csv\"',
        ]);
    }
    public function bulkUploadStore(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt'],
        ]);
        $file = $request->file('file');
        $path = $file->getRealPath();
        $fh = fopen($path, 'r');
        $header = fgetcsv($fh);
        $expected = ['citizen_name','mobile_number','service_type','notes'];
        $map = [];
        foreach ($expected as $col) {
            $idx = array_search($col, $header ?? []);
            if ($idx === false) {
                return redirect()->route('services.bulk-upload.form')->with('status', 'Invalid CSV headers');
            }
            $map[$col] = $idx;
        }
        $types = SmsTemplate::select('service_type')->distinct()->pluck('service_type')->toArray();
        $yy = now()->format('y');
        $prefix = "CR{$yy}-";
        $latest = Service::withTrashed()->where('reference_no', 'like', $prefix.'%')->orderBy('reference_no', 'desc')->first();
        $seq = 1;
        if ($latest) {
            $parts = explode('-', $latest->reference_no);
            $lastSeq = intval(end($parts));
            $seq = $lastSeq + 1;
        }
        $created = 0;
        $skipped = 0;
        while (($row = fgetcsv($fh)) !== false) {
            $name = trim($row[$map['citizen_name']] ?? '');
            $mobile = trim($row[$map['mobile_number']] ?? '');
            $stype = trim($row[$map['service_type']] ?? '');
            $notes = trim($row[$map['notes']] ?? '');
            if ($name === '' || $mobile === '' || $stype === '' || !in_array($stype, $types, true)) {
                $skipped++;
                continue;
            }
            $referenceNo = $prefix.str_pad((string) $seq, 6, '0', STR_PAD_LEFT);
            while (Service::withTrashed()->where('reference_no', $referenceNo)->exists()) {
                $seq++;
                $referenceNo = $prefix.str_pad((string) $seq, 6, '0', STR_PAD_LEFT);
            }
            $seq++;
            Service::create([
                'reference_no' => $referenceNo,
                'citizen_name' => $name,
                'mobile_number' => $mobile,
                'service_type' => $stype,
                'status' => 'Filed',
                'notes' => $notes !== '' ? $notes : null,
            ]);
            $svc = Service::where('reference_no', $referenceNo)->first();
            if ($svc) {
                ServiceStatusLog::create([
                    'service_id' => $svc->id,
                    'status' => 'Filed',
                    'note' => null,
                ]);
            }
            $created++;
        }
        fclose($fh);
        return redirect()->route('services.index')->with('status', 'Bulk upload created: '.$created.'. Skipped: '.$skipped);
    }
    public function scheduled(Request $request): View
    {
        $services = Service::with('statusLogs')->get();
        $items = [];
        foreach ($services as $s) {
            if ($s->service_type === 'Application for Marriage License') {
                if ($s->posting_start_date) {
                    $due = $s->posting_start_date->copy()->addDays(10);
                    if (!$s->sms_ready_sent) {
                        $items[] = [
                            'due' => $due,
                            'service' => $s,
                            'label' => 'Releasing notice',
                            'event' => 'releasing',
                        ];
                    }
                }
            }
            if ($s->service_type === 'Petitions filed under RA 9048 - Clerical Error') {
                if ($s->posting_start_date) {
                    $due = $s->posting_start_date->copy()->addWeekdays(10);
                    $items[] = [
                        'due' => $due,
                        'service' => $s,
                        'label' => 'Posting ends (10 business days)',
                        'event' => 'posting_end',
                    ];
                }
            }
            if ($s->service_type === 'Petitions filed under RA 9048 & RA 10172') {
                $published = $s->statusLogs->where('status', 'Published')->last();
                if ($published) {
                    $pubAt = Carbon::parse($published->created_at);
                    $due = $pubAt->copy()->addWeekdays(7);
                    $items[] = [
                        'due' => $due,
                        'service' => $s,
                        'label' => 'Decision window (7 business days)',
                        'event' => 'decision_window',
                    ];
                }
            }
        }
        usort($items, function ($a, $b) {
            return $a['due'] <=> $b['due'];
        });
        $type = (string) $request->query('service_type', '');
        $filtered = array_filter($items, function ($it) use ($type) {
            if ($type && $it['service']->service_type !== $type) return false;
            return true;
        });
        $today = Carbon::today();
        $month = $today->copy()->addDays(30);
        $todayCount = count(array_filter($filtered, function ($it) use ($today) { return $it['due']->isSameDay($today); }));
        $monthCount = count(array_filter($filtered, function ($it) use ($today, $month) { return $it['due']->gte($today) && $it['due']->lte($month); }));
        $overdueCount = count(array_filter($filtered, function ($it) use ($today) { return $it['due']->lt($today); }));
        $types = Service::select('service_type')->distinct()->orderBy('service_type')->pluck('service_type');
        return view('scheduled.index', [
            'items' => $filtered,
            'types' => $types,
            'selectedType' => $type,
            'todayCount' => $todayCount,
            'monthCount' => $monthCount,
            'overdueCount' => $overdueCount,
        ]);
    }
    
    public function dashboard(Request $request): View
    {
        $services = Service::with('statusLogs')->get();
        $triggerMap = [
            'Application for Marriage License' => ['Posted'],
            'Request for PSA documents through BREQS' => ['Ready for Pickup'],
            'Delayed Registration' => ['Under Verification','Inconsistent','Consistent'],
            'Endorsement for Negative PSA - Positive LCRO' => ['Sent to PSA','PSA Feedback','Reworked and Resent','PSA No Feedback'],
            'Endorsement for Blurred PSA - Clear LCRO File' => ['Sent to PSA','PSA Feedback','Reworked and Resent','PSA No Feedback'],
            'Endorsement of Legal Instrument & MC 2010-04 & Court Order' => ['Sent to PSA','PSA Feedback','Reworked and Resent','PSA No Feedback'],
            'Petitions filed under RA 9048 - Clerical Error' => ['Petition Ready for Filing','Filed','Sent to PSA Legal Services','PSA Affirmed','PSA Impugned','Resent to PSA Legal Services'],
            'Petitions filed under RA 9048 & RA 10172' => ['Petition Ready for Filing','Filed','Published','Sent to PSA Legal Services','PSA Affirmed','PSA Impugned','Resent to PSA Legal Services'],
        ];
        $today = Carbon::today();
        $monthStart = $today->copy()->subDays(30)->startOfDay();
        $messagesToday = 0;
        $messages30Days = 0;
        $messagesTotal = 0;
        foreach ($services as $s) {
            $triggers = $triggerMap[$s->service_type] ?? [];
            foreach ($s->statusLogs as $log) {
                if (!in_array($log->status, $triggers, true)) {
                    continue;
                }
                $messagesTotal++;
                $dt = Carbon::parse($log->created_at);
                if ($dt->isSameDay($today)) {
                    $messagesToday++;
                }
                if ($dt->gte($monthStart)) {
                    $messages30Days++;
                }
                $dateKey = $dt->format('Y-m-d');
                if ($dt->gte($monthStart)) {
                    $daily[$dateKey] = ($daily[$dateKey] ?? 0) + 1;
                }
            }
        }
        $dailyCounts = [];
        $maxDaily = 0;
        for ($i = 29; $i >= 0; $i--) {
            $d = $today->copy()->subDays($i)->format('Y-m-d');
            $val = (int)($daily[$d] ?? 0);
            $dailyCounts[] = ['date' => $d, 'count' => $val];
            if ($val > $maxDaily) $maxDaily = $val;
        }
        $request->merge([
            'service_type' => $request->query('service_type'),
        ]);
        $scheduledView = $this->scheduled($request);
        $data = $scheduledView->getData();
        $items = $data['items'] ?? [];
        $weekEnd = $today->copy()->addDays(7);
        $monthEnd = $today->copy()->addDays(30);
        $schedOverdue = array_filter($items, function ($it) use ($today) { return $it['due']->lt($today); });
        $schedToday = array_filter($items, function ($it) use ($today) { return $it['due']->isSameDay($today); });
        $schedWeek = array_filter($items, function ($it) use ($today, $weekEnd) { return $it['due']->gt($today) && $it['due']->lte($weekEnd); });
        $schedMonth = array_filter($items, function ($it) use ($weekEnd, $monthEnd) { return $it['due']->gt($weekEnd) && $it['due']->lte($monthEnd); });
        $countOverdue = count($schedOverdue);
        $countToday = count($schedToday);
        $countWeek = count($schedWeek);
        $countMonth = count($schedMonth);
        return view('dashboard', array_merge($data, [
            'messagesToday' => $messagesToday,
            'messages30Days' => $messages30Days,
            'messagesTotal' => $messagesTotal,
            'schedOverdue' => $schedOverdue,
            'schedToday' => $schedToday,
            'schedWeek' => $schedWeek,
            'schedMonth' => $schedMonth,
            'countOverdue' => $countOverdue,
            'countToday' => $countToday,
            'countWeek' => $countWeek,
            'countMonth' => $countMonth,
            'dailyCounts' => $dailyCounts,
            'maxDaily' => $maxDaily,
        ]));
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
        if (!in_array($service->service_type, ['Endorsement for Negative PSA - Positive LCRO','Endorsement for Blurred PSA - Clear LCRO File','Endorsement of Legal Instrument & MC 2010-04 & Court Order','Petitions filed under RA 9048 - Clerical Error','Petitions filed under RA 9048 & RA 10172'], true)) {
            $idxPrev = array_search($prev, $allowed);
            $idxNew = array_search($validated['status'], $allowed);
            if ($idxPrev !== false && $idxNew !== false && $idxNew < $idxPrev) {
                return redirect()->route('services.index')->with('status', 'Cannot move back in status');
            }
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
            $service->service_type === 'Request for PSA documents through BREQS' &&
            $prev !== 'Ready for Pickup' &&
            $validated['status'] === 'Ready for Pickup' &&
            !$service->sms_ready_sent
        ) {
            $this->sms->send($service, 'ready_for_pickup');
            $service->sms_ready_sent = true;
            $service->save();
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
        if ($service->service_type === 'Endorsement for Negative PSA - Positive LCRO') {
            if ($prev !== 'Sent to PSA' && $validated['status'] === 'Sent to PSA') {
                $this->sms->send($service, 'psa_sent');
            }
            if ($prev !== 'PSA Feedback' && $validated['status'] === 'PSA Feedback') {
                $this->sms->send($service, 'psa_feedback_received');
            }
            if ($prev !== 'Reworked and Resent' && $validated['status'] === 'Reworked and Resent') {
                $this->sms->send($service, 'psa_resent_for_processing');
            }
            if ($prev !== 'PSA No Feedback' && $validated['status'] === 'PSA No Feedback' && !$service->sms_ready_sent) {
                $this->sms->send($service, 'psa_no_feedback_uploaded');
                $service->sms_ready_sent = true;
                $service->save();
            }
        }
        if ($service->service_type === 'Endorsement for Blurred PSA - Clear LCRO File') {
            if ($prev !== 'Sent to PSA' && $validated['status'] === 'Sent to PSA') {
                $this->sms->send($service, 'psa_sent');
            }
            if ($prev !== 'PSA Feedback' && $validated['status'] === 'PSA Feedback') {
                $this->sms->send($service, 'psa_feedback_received');
            }
            if ($prev !== 'Reworked and Resent' && $validated['status'] === 'Reworked and Resent') {
                $this->sms->send($service, 'psa_resent_for_processing');
            }
            if ($prev !== 'PSA No Feedback' && $validated['status'] === 'PSA No Feedback' && !$service->sms_ready_sent) {
                $this->sms->send($service, 'psa_no_feedback_uploaded');
                $service->sms_ready_sent = true;
                $service->save();
            }
        }
        if ($service->service_type === 'Endorsement of Legal Instrument & MC 2010-04 & Court Order') {
            if ($prev !== 'Sent to PSA' && $validated['status'] === 'Sent to PSA') {
                $this->sms->send($service, 'psa_sent');
            }
            if ($prev !== 'PSA Feedback' && $validated['status'] === 'PSA Feedback') {
                $this->sms->send($service, 'psa_feedback_received');
            }
            if ($prev !== 'Reworked and Resent' && $validated['status'] === 'Reworked and Resent') {
                $this->sms->send($service, 'psa_resent_for_processing');
            }
            if ($prev !== 'PSA No Feedback' && $validated['status'] === 'PSA No Feedback' && !$service->sms_ready_sent) {
                $this->sms->send($service, 'psa_no_feedback_uploaded');
                $service->sms_ready_sent = true;
                $service->save();
            }
        }
        if ($service->service_type === 'Petitions filed under RA 9048 - Clerical Error') {
            if ($prev !== 'Petition Ready for Filing' && $validated['status'] === 'Petition Ready for Filing') {
                $this->sms->send($service, 'petition_ready_for_filing');
            }
            if ($prev !== 'Filed' && $validated['status'] === 'Filed' && !$service->sms_posting_sent) {
                $service->posting_start_date = Carbon::today()->nextWeekday();
                $this->sms->send($service, 'petition_ready_for_posting');
                $service->sms_posting_sent = true;
                $service->save();
            }
            if ($prev !== 'Sent to PSA Legal Services' && $validated['status'] === 'Sent to PSA Legal Services') {
                $this->sms->send($service, 'sent_to_psa_legal');
            }
            if ($prev !== 'PSA Affirmed' && $validated['status'] === 'PSA Affirmed') {
                $this->sms->send($service, 'psa_affirmed');
            }
            if ($prev !== 'PSA Impugned' && $validated['status'] === 'PSA Impugned') {
                $this->sms->send($service, 'psa_impugned');
            }
            if ($prev !== 'Resent to PSA Legal Services' && $validated['status'] === 'Resent to PSA Legal Services') {
                $this->sms->send($service, 'psa_resent_for_review');
            }
        }
        if ($service->service_type === 'Petitions filed under RA 9048 & RA 10172') {
            if ($prev !== 'Petition Ready for Filing' && $validated['status'] === 'Petition Ready for Filing') {
                $this->sms->send($service, 'petition_ready_for_filing');
            }
            if ($prev !== 'Filed' && $validated['status'] === 'Filed' && !$service->sms_posting_sent) {
                $service->posting_start_date = Carbon::today()->nextWeekday();
                $this->sms->send($service, 'petition_ready_for_posting');
                $service->sms_posting_sent = true;
                $service->save();
            }
            if ($prev !== 'Published' && $validated['status'] === 'Published') {
                $this->sms->send($service, 'petition_published');
                $due = Carbon::today()->addWeekdays(7)->toDateString();
                $service->notes = trim(($service->notes ? $service->notes . "\n" : '') . 'Decision expected by ' . $due);
                $service->save();
            }
            if ($prev !== 'Sent to PSA Legal Services' && $validated['status'] === 'Sent to PSA Legal Services') {
                $this->sms->send($service, 'sent_to_psa_legal');
            }
            if ($prev !== 'PSA Affirmed' && $validated['status'] === 'PSA Affirmed') {
                $this->sms->send($service, 'psa_affirmed');
            }
            if ($prev !== 'PSA Impugned' && $validated['status'] === 'PSA Impugned') {
                $this->sms->send($service, 'psa_impugned');
            }
            if ($prev !== 'Resent to PSA Legal Services' && $validated['status'] === 'Resent to PSA Legal Services') {
                $this->sms->send($service, 'psa_resent_for_review');
            }
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
            if (!in_array($svc->service_type, ['Endorsement for Negative PSA - Positive LCRO','Endorsement for Blurred PSA - Clear LCRO File','Endorsement of Legal Instrument & MC 2010-04 & Court Order','Petitions filed under RA 9048 - Clerical Error','Petitions filed under RA 9048 & RA 10172'], true)) {
                $idxPrev = array_search($prevStatus, $allowed);
                $idxNew = array_search($validated['status'], $allowed);
                if ($idxPrev !== false && $idxNew !== false && $idxNew < $idxPrev) {
                    $skippedCount++;
                    continue;
                }
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
                $svc->service_type === 'Request for PSA documents through BREQS' &&
                $prevStatus !== 'Ready for Pickup' &&
                $validated['status'] === 'Ready for Pickup' &&
                !$svc->sms_ready_sent
            ) {
                $this->sms->send($svc, 'ready_for_pickup');
                $svc->sms_ready_sent = true;
                $svc->save();
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
            if ($svc->service_type === 'Endorsement for Negative PSA - Positive LCRO') {
                if ($prevStatus !== 'Sent to PSA' && $validated['status'] === 'Sent to PSA') {
                    $this->sms->send($svc, 'psa_sent');
                }
                if ($prevStatus !== 'PSA Feedback' && $validated['status'] === 'PSA Feedback') {
                    $this->sms->send($svc, 'psa_feedback_received');
                }
                if ($prevStatus !== 'Reworked and Resent' && $validated['status'] === 'Reworked and Resent') {
                    $this->sms->send($svc, 'psa_resent_for_processing');
                }
                if ($prevStatus !== 'PSA No Feedback' && $validated['status'] === 'PSA No Feedback' && !$svc->sms_ready_sent) {
                    $this->sms->send($svc, 'psa_no_feedback_uploaded');
                    $svc->sms_ready_sent = true;
                    $svc->save();
                }
            }
            if ($svc->service_type === 'Endorsement for Blurred PSA - Clear LCRO File') {
                if ($prevStatus !== 'Sent to PSA' && $validated['status'] === 'Sent to PSA') {
                    $this->sms->send($svc, 'psa_sent');
                }
                if ($prevStatus !== 'PSA Feedback' && $validated['status'] === 'PSA Feedback') {
                    $this->sms->send($svc, 'psa_feedback_received');
                }
                if ($prevStatus !== 'Reworked and Resent' && $validated['status'] === 'Reworked and Resent') {
                    $this->sms->send($svc, 'psa_resent_for_processing');
                }
                if ($prevStatus !== 'PSA No Feedback' && $validated['status'] === 'PSA No Feedback' && !$svc->sms_ready_sent) {
                    $this->sms->send($svc, 'psa_no_feedback_uploaded');
                    $svc->sms_ready_sent = true;
                    $svc->save();
                }
            }
            if ($svc->service_type === 'Endorsement of Legal Instrument & MC 2010-04 & Court Order') {
                if ($prevStatus !== 'Sent to PSA' && $validated['status'] === 'Sent to PSA') {
                    $this->sms->send($svc, 'psa_sent');
                }
                if ($prevStatus !== 'PSA Feedback' && $validated['status'] === 'PSA Feedback') {
                    $this->sms->send($svc, 'psa_feedback_received');
                }
                if ($prevStatus !== 'Reworked and Resent' && $validated['status'] === 'Reworked and Resent') {
                    $this->sms->send($svc, 'psa_resent_for_processing');
                }
                if ($prevStatus !== 'PSA No Feedback' && $validated['status'] === 'PSA No Feedback' && !$svc->sms_ready_sent) {
                    $this->sms->send($svc, 'psa_no_feedback_uploaded');
                    $svc->sms_ready_sent = true;
                    $svc->save();
                }
            }
            if ($svc->service_type === 'Petitions filed under RA 9048 - Clerical Error') {
                if ($prevStatus !== 'Petition Ready for Filing' && $validated['status'] === 'Petition Ready for Filing') {
                    $this->sms->send($svc, 'petition_ready_for_filing');
                }
                if ($prevStatus !== 'Filed' && $validated['status'] === 'Filed' && !$svc->sms_posting_sent) {
                    $svc->posting_start_date = \Illuminate\Support\Carbon::today()->nextWeekday();
                    $this->sms->send($svc, 'petition_ready_for_posting');
                    $svc->sms_posting_sent = true;
                    $svc->save();
                }
                if ($prevStatus !== 'Sent to PSA Legal Services' && $validated['status'] === 'Sent to PSA Legal Services') {
                    $this->sms->send($svc, 'sent_to_psa_legal');
                }
                if ($prevStatus !== 'PSA Affirmed' && $validated['status'] === 'PSA Affirmed') {
                    $this->sms->send($svc, 'psa_affirmed');
                }
                if ($prevStatus !== 'PSA Impugned' && $validated['status'] === 'PSA Impugned') {
                    $this->sms->send($svc, 'psa_impugned');
                }
                if ($prevStatus !== 'Resent to PSA Legal Services' && $validated['status'] === 'Resent to PSA Legal Services') {
                    $this->sms->send($svc, 'psa_resent_for_review');
                }
            }
            if ($svc->service_type === 'Petitions filed under RA 9048 & RA 10172') {
                if ($prevStatus !== 'Petition Ready for Filing' && $validated['status'] === 'Petition Ready for Filing') {
                    $this->sms->send($svc, 'petition_ready_for_filing');
                }
                if ($prevStatus !== 'Filed' && $validated['status'] === 'Filed' && !$svc->sms_posting_sent) {
                    $svc->posting_start_date = \Illuminate\Support\Carbon::today()->nextWeekday();
                    $this->sms->send($svc, 'petition_ready_for_posting');
                    $svc->sms_posting_sent = true;
                    $svc->save();
                }
                if ($prevStatus !== 'Published' && $validated['status'] === 'Published') {
                    $this->sms->send($svc, 'petition_published');
                    $due = \Illuminate\Support\Carbon::today()->addWeekdays(7)->toDateString();
                    $svc->notes = trim(($svc->notes ? $svc->notes . "\n" : '') . 'Decision expected by ' . $due);
                    $svc->save();
                }
                if ($prevStatus !== 'Sent to PSA Legal Services' && $validated['status'] === 'Sent to PSA Legal Services') {
                    $this->sms->send($svc, 'sent_to_psa_legal');
                }
                if ($prevStatus !== 'PSA Affirmed' && $validated['status'] === 'PSA Affirmed') {
                    $this->sms->send($svc, 'psa_affirmed');
                }
                if ($prevStatus !== 'PSA Impugned' && $validated['status'] === 'PSA Impugned') {
                    $this->sms->send($svc, 'psa_impugned');
                }
                if ($prevStatus !== 'Resent to PSA Legal Services' && $validated['status'] === 'Resent to PSA Legal Services') {
                    $this->sms->send($svc, 'psa_resent_for_review');
                }
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
