<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Services\SmsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function index(): View
    {
        $services = Service::orderBy('created_at', 'desc')->get();
        return view('services.index', compact('services'));
    }
    public function show(Service $service): View
    {
        return view('services.show', compact('service'));
    }

    public function create(): View
    {
        $categories = [
            'Endorsements',
            'Petitions',
            'Applications',
            'BREQS',
            'Delayed Registration',
        ];
        return view('services.create', compact('categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'citizen_name' => ['required', 'string', 'max:255'],
            'mobile_number' => ['required', 'string', 'max:30'],
            'category' => ['required', 'string', 'max:100'],
            'service_type' => ['required', 'string', 'max:100'],
            'remarks' => ['nullable', 'string'],
        ]);

        $yy = now()->format('y');
        $prefix = "CR{$yy}-";
        $latest = Service::where('reference_no', 'like', $prefix.'%')
            ->orderBy('reference_no', 'desc')
            ->first();

        $seq = 1;
        if ($latest) {
            $parts = explode('-', $latest->reference_no);
            $lastSeq = intval(end($parts));
            $seq = $lastSeq + 1;
        }
        $referenceNo = $prefix.str_pad((string) $seq, 6, '0', STR_PAD_LEFT);

        Service::create([
            'reference_no' => $referenceNo,
            'citizen_name' => $validated['citizen_name'],
            'mobile_number' => $validated['mobile_number'],
            'category' => $validated['category'],
            'service_type' => $validated['service_type'],
            'status' => 'Filed',
            'remarks' => $validated['remarks'] ?? null,
        ]);

        return redirect()->route('services.index')->with('status', 'Service entry filed');
    }

    public function edit(Service $service): View
    {
        $categories = [
            'Endorsements',
            'Petitions',
            'Applications',
            'BREQS',
            'Delayed Registration',
        ];
        return view('services.edit', compact('service', 'categories'));
    }

    public function update(Request $request, Service $service): RedirectResponse
    {
        $validated = $request->validate([
            'citizen_name' => ['required', 'string', 'max:255'],
            'mobile_number' => ['required', 'string', 'max:30'],
            'category' => ['required', 'string', 'max:100'],
            'service_type' => ['required', 'string', 'max:100'],
            'status' => ['required', 'string', 'max:50'],
            'remarks' => ['nullable', 'string'],
        ]);

        $previousStatus = $service->status;
        $service->update([
            'citizen_name' => $validated['citizen_name'],
            'mobile_number' => $validated['mobile_number'],
            'category' => $validated['category'],
            'service_type' => $validated['service_type'],
            'status' => $validated['status'],
            'remarks' => $validated['remarks'] ?? null,
        ]);

        if (
            $validated['service_type'] === 'Application for Marriage License' &&
            $previousStatus !== 'Released' &&
            $validated['status'] === 'Released' &&
            !$service->sms_release_sent
        ) {
            (new SmsService())->send($service, 'released');
            $service->sms_release_sent = true;
            $service->release_date = now()->toDateString();
            $service->save();
        }

        return redirect()->route('services.index')->with('status', 'Service updated');
    }

    public function destroy(Service $service): RedirectResponse
    {
        $id = $service->id;
        $service->delete();
        return redirect()->route('services.index')->with('status', 'Service entry deleted')
            ->with('undo_id', $id);
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
        $service->update(['status' => $validated['status']]);
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
        foreach ($services as $svc) {
            $svc->update(['status' => $validated['status']]);
        }
        return redirect()->route('services.index')->with('status', 'Status updated for selected entries');
    }
}
