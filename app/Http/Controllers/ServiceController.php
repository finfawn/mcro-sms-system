<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\ServiceStatusLog;
use App\Models\SmsTemplate;
use App\Models\SmsMessage;
use App\Services\SmsService;
use App\Automation\AutomationEngine;
use App\Jobs\SendSmsJob;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServiceController extends Controller
{
    protected AutomationEngine $automation;
    protected SmsService $sms;
    /** Event keys from SMS templates map to these status labels (for status dropdowns driven by templates). */
    protected function eventKeyToStatusMap(): array
    {
        return [
            'ready_for_pickup' => 'Ready for Pickup',
            'released' => 'Released',
            'releasing' => 'Released',
            'posting_notice' => 'Posted',
            'verification_started' => 'Under Verification',
            'requirements_incomplete' => 'Inconsistent',
            'verification_consistent' => 'Consistent',
            'ready_for_release' => 'Ready for Release',
            'psa_sent' => 'Sent to PSA',
            'psa_feedback_received' => 'PSA Has Feedback',
            'psa_resent_for_processing' => 'Reworked and Resent',
            'psa_no_feedback_uploaded' => 'PSA Successfully Uploaded',
            'petition_ready_for_filing' => 'For Filing',
            'petition_ready_for_posting' => 'Posted',
            'sent_to_psa_legal' => 'Sent to PSA',
            'psa_affirmed' => 'Affirmed',
            'psa_impugned' => 'Impugned',
            'petition_published' => 'Published',
        ];
    }

    /** Service types that are frontline services (SMS-driven status dropdown for all of these). */
    protected function frontlineServiceTypes(): array
    {
        return config('sms.frontline_service_types', [
            'Frontline Service',
            'Request for PSA documents through BREQS',
        ]);
    }

    /** Statuses that have an SMS template or trigger SMS for this service type (used for all frontline dropdowns). */
    protected function statusesWithSmsForType(string $type): array
    {
        $map = $this->eventKeyToStatusMap();
        $frontline = $this->frontlineServiceTypes();
        $serviceTypes = in_array($type, $frontline, true)
            ? $frontline
            : [$type];
        $eventKeys = SmsTemplate::whereIn('service_type', $serviceTypes)
            ->where('is_active', true)
            ->distinct()
            ->pluck('event_key')
            ->toArray();
        $statuses = [];
        foreach ($eventKeys as $key) {
            if (isset($map[$key]) && !in_array($map[$key], $statuses, true)) {
                $statuses[] = $map[$key];
            }
        }
        if (in_array($type, $frontline, true)) {
            if (!in_array('Released', $statuses, true)) {
                $statuses[] = 'Released';
            }
            // Prepend Drafted so frontline starts as Drafted (avoids confusion with Filed)
            if (!in_array('Drafted', $statuses, true)) {
                array_unshift($statuses, 'Drafted');
            }
        }
        return $statuses;
    }

    protected function statusesForType(string $type): array
    {
        $default = ['Filed','Processing','Endorsed','Released','Rejected'];
        if ($type === 'Application for Marriage License') {
            return ['Filed','Paid','Posted','Released'];
        }
        if (
            $type === 'Delayed Registration' ||
            $type === 'Delayed Registration of Birth' ||
            $type === 'Delayed Registration of Death' ||
            $type === 'Delayed Registration of Marriage'
        ) {
            return ['Filed','Under Verification','Consistent','Inconsistent','Posted','Ready for Release','Released','Rejected'];
        }
        if (in_array($type, $this->frontlineServiceTypes(), true)) {
            return $this->statusesWithSmsForType($type);
        }
        if ($type === 'Endorsement for Negative PSA - Positive LCRO') {
            return ['Filed','Sent to PSA','PSA Has Feedback','Reworked and Resent','PSA Successfully Uploaded'];
        }
        if ($type === 'Endorsement for Blurred PSA - Clear LCRO File') {
            return ['Filed','Sent to PSA','PSA Has Feedback','Reworked and Resent','PSA Successfully Uploaded'];
        }
        if ($type === 'Endorsement of Legal Instrument & MC 2010-04 & Court Order') {
            return ['Filed','Sent to PSA','PSA Has Feedback','Reworked and Resent','PSA Successfully Uploaded'];
        }
        if ($type === 'Petitions filed under RA 9048 - Clerical Error') {
            return ['Drafted','For Filing','Posted','Sent to PSA','Affirmed','Impugned'];
        }
        if ($type === 'Petitions filed under RA 9048 & RA 10172') {
            return ['Drafted','For Filing','Posted','Sent to PSA','Affirmed','Impugned'];
        }
        return $default;
    }

    /** Use "Drafted" as initial status when creating entries for types that have "For Filing" (avoids confusion with "Filed"). */
    protected function useDraftedAsInitialStatus(string $type): bool
    {
        if (in_array($type, $this->frontlineServiceTypes(), true)) {
            return true;
        }
        return in_array('For Filing', $this->statusesForType($type), true);
    }

    protected function allowsBackwards(string $type): bool
    {
        // Only allow moving back for flows where PSA returns feedback/rework (redo documents, resubmit)
        $backtrackTypes = [
            'Delayed Registration',
            'Delayed Registration of Birth',
            'Delayed Registration of Death',
            'Delayed Registration of Marriage',
            'Endorsement for Negative PSA - Positive LCRO',
            'Endorsement for Blurred PSA - Clear LCRO File',
            'Endorsement of Legal Instrument & MC 2010-04 & Court Order',
            'Petitions filed under RA 9048 - Clerical Error',
            'Petitions filed under RA 9048 & RA 10172',
        ];
        return in_array($type, $backtrackTypes, true);
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
        $name = trim((string) $request->query('name', ''));
        $sort = $request->query('sort', 'updated');
        $direction = strtolower($request->query('direction', 'desc')) === 'asc' ? 'asc' : 'desc';

        if ($serviceType) {
            $query->where('service_type', $serviceType);
        }
        if ($status) {
            $query->where('status', $status);
        }
        if ($name !== '') {
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

        $services = $query->orderBy($column, $direction)->paginate(50)->withQueryString();
        $typesCol = SmsTemplate::select('service_type')->distinct()->orderBy('service_type')->pluck('service_type')->toArray();
        $extra = [
            'Application for Marriage License',
            'Delayed Registration',
            'Delayed Registration of Birth',
            'Delayed Registration of Death',
            'Delayed Registration of Marriage',
            'Frontline Service',
            'Request for PSA documents through BREQS',
            'Endorsement for Negative PSA - Positive LCRO',
            'Endorsement for Blurred PSA - Clear LCRO File',
            'Endorsement of Legal Instrument & MC 2010-04 & Court Order',
            'Petitions filed under RA 9048 - Clerical Error',
            'Petitions filed under RA 9048 & RA 10172',
        ];
        $types = collect(array_values(array_unique(array_merge($typesCol, $extra))))->sort()->values();
        $statusOptions = ['Filed','Processing','Paid','Under Verification','Consistent','Inconsistent','Posted','Ready for Release','Endorsed','Released','Rejected'];

        $statusesByType = [];
        $allTypes = $types->merge($extra)->unique()->values()->all();
        foreach ($allTypes as $t) {
            $statusesByType[$t] = $this->statusesForType($t);
        }

        return view('services.index', [
            'services' => $services,
            'types' => $types,
            'statusOptions' => $statusOptions,
            'statusesByType' => $statusesByType,
            'serviceType' => $serviceType,
            'status' => $status,
            'name' => $name,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }
    public function show(Service $service): View
    {
        $service->load('statusLogs.user');
        return view('services.show', compact('service'));
    }

    public function create(): View
    {
        $typesCol = SmsTemplate::select('service_type')->distinct()->orderBy('service_type')->pluck('service_type')->toArray();
        $typesCol = array_values(array_filter($typesCol, function($t){ return $t !== 'Delayed Registration'; }));
        $extra = [
            'Delayed Registration of Birth',
            'Delayed Registration of Death',
            'Delayed Registration of Marriage',
        ];
        $merged = collect(array_values(array_unique(array_merge($typesCol, $extra))));
        return view('services.create', ['types' => $merged]);
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

        $initialStatus = $this->useDraftedAsInitialStatus($validated['service_type']) ? 'Drafted' : 'Filed';
        Service::create([
            'reference_no' => $referenceNo,
            'citizen_name' => $validated['citizen_name'],
            'mobile_number' => $validated['mobile_number'],
            'service_type' => $validated['service_type'],
            'status' => $initialStatus,
            'notes' => $validated['notes'] ?? null,
        ]);

        $svc = Service::where('reference_no', $referenceNo)->first();
        if ($svc) {
            ServiceStatusLog::create([
                'service_id' => $svc->id,
                'status' => $initialStatus,
                'note' => null,
                'user_id' => optional(auth()->user())->id,
            ]);
        }

        return redirect()->route('services.index')->with('status', 'Service entry filed');
    }

    public function edit(Service $service): View
    {
        $statuses = $this->statusesForType($service->service_type);
        if (!in_array($service->status, $statuses, true)) {
            $statuses = array_merge([$service->status], $statuses);
        }
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
        $isAdmin = (optional(auth()->user())->role ?? 'user') === 'admin';
        $service->update([
            'citizen_name' => $validated['citizen_name'],
            'mobile_number' => $validated['mobile_number'],
            'service_type' => $validated['service_type'],
            'status' => $validated['status'],
            'notes' => $isAdmin ? ($validated['notes'] ?? null) : $service->notes,
        ]);

        if ($previousStatus !== $validated['status']) {
            ServiceStatusLog::create([
                'service_id' => $service->id,
                'status' => $validated['status'],
                'note' => null,
                'user_id' => optional(auth()->user())->id,
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
        if (in_array($validated['service_type'], [
            'Delayed Registration',
            'Delayed Registration of Birth',
            'Delayed Registration of Death',
            'Delayed Registration of Marriage',
        ], true)) {
            if ($previousStatus !== 'Under Verification' && $validated['status'] === 'Under Verification') {
                SendSmsJob::dispatch($service->id, 'verification_started');
            }
            if ($previousStatus !== 'Inconsistent' && $validated['status'] === 'Inconsistent') {
                SendSmsJob::dispatch($service->id, 'requirements_incomplete');
            }
            if ($previousStatus !== 'Consistent' && $validated['status'] === 'Consistent') {
                SendSmsJob::dispatch($service->id, 'verification_consistent');
            }
        }
        if (
            in_array($validated['service_type'], $this->frontlineServiceTypes(), true) &&
            $previousStatus !== 'Ready for Pickup' &&
            $validated['status'] === 'Ready for Pickup' &&
            !$service->sms_ready_sent
        ) {
            SendSmsJob::dispatch($service->id, 'ready_for_pickup');
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
            SendSmsJob::dispatch($service->id, 'posting_notice');
            $service->sms_posting_sent = true;
            $service->save();
        }

        // Apply automation rules for status changes
        $this->automation->handleStatusChange($service);

        return redirect()->route('services.index')->with('status', 'Service updated');
    }

    public function destroy(Service $service): RedirectResponse
    {
        $service->delete();
        return redirect()->route('services.index')
            ->with('status', 'Service entry deleted')
            ->with('undo_id', $service->id)
            ->with('undo_type', 'service');
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
        $headers = ['citizen_name','mobile_number','service_type','notes'];
        $rows = [
            ['Juan Dela Cruz','09171234567','Application for Marriage License',''],
            ['Maria Santos','09181234567','Petitions filed under RA 9048 - Clerical Error','Needs assistance'],
        ];
        if (!class_exists('\\ZipArchive')) {
            $xml = $this->generateExcelXml($headers, $rows);
            return response($xml, 200, [
                'Content-Type' => 'application/vnd.ms-excel',
                'Content-Disposition' => 'attachment; filename=services_template.xls',
            ]);
        }
        $binary = $this->generateXlsx($headers, $rows);
        return response($binary, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename=services_template.xlsx',
        ]);
    }
    public function bulkUploadStore(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls'],
        ]);
        $file = $request->file('file');
        $path = $file->getRealPath();
        $ext = strtolower($file->getClientOriginalExtension() ?? '');
        if ($ext === 'xlsx') {
            [$header, $rows] = $this->parseXlsx($path);
            if ($header === null) {
                return redirect()->route('services.bulk-upload.form')->with('status', 'Invalid Excel file or Excel support not available');
            }
        } elseif ($ext === 'xls') {
            [$header, $rows] = $this->parseExcelXml($path);
            if ($header === null) {
                return redirect()->route('services.bulk-upload.form')->with('status', 'Invalid Excel XML file');
            }
        } else {
            return redirect()->route('services.bulk-upload.form')->with('status', 'Unsupported Excel format');
        }
        $expected = ['citizen_name','mobile_number','service_type','notes'];
        $map = [];
        foreach ($expected as $col) {
            $idx = array_search($col, $header ?? []);
            if ($idx === false) {
                return redirect()->route('services.bulk-upload.form')->with('status', 'Invalid Excel headers');
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
        foreach ($rows as $row) {
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
            $initialStatus = $this->useDraftedAsInitialStatus($stype) ? 'Drafted' : 'Filed';
            Service::create([
                'reference_no' => $referenceNo,
                'citizen_name' => $name,
                'mobile_number' => $mobile,
                'service_type' => $stype,
                'status' => $initialStatus,
                'notes' => $notes !== '' ? $notes : null,
            ]);
            $svc = Service::where('reference_no', $referenceNo)->first();
            if ($svc) {
                ServiceStatusLog::create([
                    'service_id' => $svc->id,
                    'status' => $initialStatus,
                    'note' => null,
                ]);
            }
            $created++;
        }
        return redirect()->route('services.index')->with('status', 'Bulk upload created: '.$created.'. Skipped: '.$skipped);
    }

    public function export()
    {
        $services = Service::orderBy('updated_at', 'desc')->get();
        $headers = ['Reference No','Citizen Name','Mobile Number','Service Type','Status','Payment Date','Posting Start Date','Release Date','Filed','Last Updated','Notes'];
        $rows = [];
        foreach ($services as $s) {
            $rows[] = [
                (string) $s->reference_no,
                (string) $s->citizen_name,
                (string) $s->mobile_number,
                (string) $s->service_type,
                (string) $s->status,
                $s->payment_date ? $s->payment_date->toDateString() : '',
                $s->posting_start_date ? $s->posting_start_date->toDateString() : '',
                $s->release_date ? $s->release_date->toDateString() : '',
                $s->created_at ? $s->created_at->format('Y-m-d H:i') : '',
                $s->updated_at ? $s->updated_at->format('Y-m-d H:i') : '',
                (string) ($s->notes ?? ''),
            ];
        }
        $date = now()->format('Ymd_His');
        $title = [
            'REPUBLIC OF THE PHILIPPINES',
            'PROVINCE OF BENGUET',
            'MUNICIPALITY OF TUBLAY',
            'OFFICE OF THE MUNICIPAL CIVIL REGISTRAR',
        ];
        $xml = $this->generateExcelXml($headers, $rows, $title);
        return response($xml, 200, [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment; filename=services_export_'.$date.'.xls',
        ]);
    }

    private function parseXlsx(string $path): array
    {
        if (!class_exists('\\ZipArchive')) {
            return [null, []];
        }
        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            return [null, []];
        }
        $sheetXml = null;
        $sharedStringsXml = null;
        // Prefer standard paths
        if ($zip->locateName('xl/worksheets/sheet1.xml') !== false) {
            $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        } else {
            // Fallback to first sheet file
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                if (str_starts_with($stat['name'], 'xl/worksheets/sheet') && str_ends_with($stat['name'], '.xml')) {
                    $sheetXml = $zip->getFromIndex($i);
                    break;
                }
            }
        }
        if ($zip->locateName('xl/sharedStrings.xml') !== false) {
            $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');
        }
        $zip->close();
        if (!$sheetXml) {
            return [null, []];
        }
        $shared = [];
        if ($sharedStringsXml) {
            $sx = simplexml_load_string($sharedStringsXml);
            $ns = $sx->getNamespaces(true);
            foreach ($sx->si as $si) {
                $text = '';
                if (isset($si->t)) {
                    $text = (string) $si->t;
                } else {
                    foreach ($si->r as $r) {
                        $text .= (string) $r->t;
                    }
                }
                $shared[] = $text;
            }
        }
        $xml = simplexml_load_string($sheetXml);
        $rows = [];
        $headers = [];
        $rowIndex = 0;
        foreach ($xml->sheetData->row as $row) {
            $rowIndex++;
            $cells = [];
            foreach ($row->c as $c) {
                $ref = (string) $c['r'];
                $colLetters = preg_replace('/\\d+/', '', $ref);
                $colIndex = $this->colLettersToIndex($colLetters);
                $type = (string) $c['t'];
                $value = null;
                if ($type === 's') {
                    $idx = isset($c->v) ? intval((string) $c->v) : 0;
                    $value = $shared[$idx] ?? '';
                } elseif ($type === 'inlineStr') {
                    $value = isset($c->is->t) ? (string) $c->is->t : '';
                } else {
                    $value = isset($c->v) ? (string) $c->v : '';
                }
                $cells[$colIndex] = $value;
            }
            $maxIndex = empty($cells) ? -1 : max(array_keys($cells));
            $rowValues = [];
            for ($i = 0; $i <= $maxIndex; $i++) {
                $rowValues[$i] = $cells[$i] ?? '';
            }
            if ($rowIndex === 1) {
                $headers = array_map(function($s){ return strtolower(trim($s)); }, $rowValues);
            } else {
                $rows[] = $rowValues;
            }
        }
        return [$headers, $rows];
    }
    private function parseExcelXml(string $path): array
    {
        $content = @file_get_contents($path);
        if ($content === false || $content === '') {
            return [null, []];
        }
        $xml = @simplexml_load_string($content);
        if (!$xml) {
            return [null, []];
        }
        $rows = [];
        $headers = [];
        $worksheetNodes = $xml->xpath('//*[local-name()="Worksheet"]');
        if (!$worksheetNodes || !isset($worksheetNodes[0])) {
            return [null, []];
        }
        $tableNodes = $worksheetNodes[0]->xpath('.//*[local-name()="Table"]');
        if (!$tableNodes || !isset($tableNodes[0])) {
            return [null, []];
        }
        $rowNodes = $tableNodes[0]->xpath('.//*[local-name()="Row"]');
        $rowIndex = 0;
        foreach ($rowNodes as $rowNode) {
            $rowIndex++;
            $cells = [];
            $colIndex = 0;
            $cellNodes = $rowNode->xpath('.//*[local-name()="Cell"]');
            foreach ($cellNodes as $cell) {
                $attrs = $cell->attributes();
                $idxAttr = null;
                foreach ($attrs as $k => $v) {
                    if (strtolower((string) $k) === 'index') {
                        $idxAttr = intval((string) $v);
                        break;
                    }
                }
                if ($idxAttr !== null && $idxAttr > 0) {
                    $colIndex = $idxAttr - 1;
                }
                $dataNodes = $cell->xpath('.//*[local-name()="Data"]');
                $val = '';
                if ($dataNodes && isset($dataNodes[0])) {
                    $val = (string) $dataNodes[0];
                }
                $cells[$colIndex] = $val;
                $colIndex++;
            }
            $maxIndex = empty($cells) ? -1 : max(array_keys($cells));
            $rowValues = [];
            for ($i = 0; $i <= $maxIndex; $i++) {
                $rowValues[$i] = $cells[$i] ?? '';
            }
            if ($rowIndex === 1) {
                $headers = array_map(function($s){ return strtolower(trim($s)); }, $rowValues);
            } else {
                $rows[] = $rowValues;
            }
        }
        return [$headers, $rows];
    }
    private function colLettersToIndex(string $letters): int
    {
        $letters = strtoupper($letters);
        $len = strlen($letters);
        $num = 0;
        for ($i = 0; $i < $len; $i++) {
            $num = $num * 26 + (ord($letters[$i]) - 64);
        }
        return $num - 1;
    }
    private function generateXlsx(array $headers, array $rows): string
    {
        $all = array_values($headers);
        foreach ($rows as $r) {
            foreach ($r as $v) {
                $all[] = (string) $v;
            }
        }
        $unique = array_values(array_unique($all));
        $map = array_flip($unique);
        $totalInstances = count($all);
        $zip = new \ZipArchive();
        $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip->open($tmp, \ZipArchive::OVERWRITE);
        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/><Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/><Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/></Types>';
        $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/><Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/></Relationships>';
        $workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Sheet1" sheetId="1" r:id="rId1"/></sheets></workbook>';
        $workbookRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/><Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>';
        $sharedStrings = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="'.$totalInstances.'" uniqueCount="'.count($unique).'">';
        foreach ($unique as $s) {
            $escaped = htmlspecialchars($s, ENT_XML1 | ENT_COMPAT, 'UTF-8');
            $sharedStrings .= '<si><t>'.$escaped.'</t></si>';
        }
        $sharedStrings .= '</sst>';
        $styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="1"><font><sz val="11"/><color theme="1"/><name val="Calibri"/><family val="2"/></font></fonts><fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills><borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/></cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>';
        $zip->addFromString('[Content_Types].xml', $contentTypes);
        $zip->addFromString('_rels/.rels', $rels);
        $zip->addFromString('xl/workbook.xml', $workbook);
        $zip->addFromString('xl/_rels/workbook.xml.rels', $workbookRels);
        $zip->addFromString('xl/sharedStrings.xml', $sharedStrings);
        $zip->addFromString('xl/styles.xml', $styles);
        $lastColLetters = $this->indexToColLetters(count($headers) - 1);
        $totalRows = 1 + count($rows);
        $dimension = 'A1:'.$lastColLetters.$totalRows;
        $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><dimension ref="'.$dimension.'"/><sheetData>';
        $rowNum = 1;
        $sheetXml .= '<row r="'.$rowNum.'">';
        for ($i = 0; $i < count($headers); $i++) {
            $col = $this->indexToColLetters($i);
            $val = $headers[$i];
            $idx = $map[$val] ?? 0;
            $sheetXml .= '<c r="'.$col.$rowNum.'" t="s"><v>'.$idx.'</v></c>';
        }
        $sheetXml .= '</row>';
        foreach ($rows as $r) {
            $rowNum++;
            $sheetXml .= '<row r="'.$rowNum.'">';
            for ($i = 0; $i < count($r); $i++) {
                $col = $this->indexToColLetters($i);
                $val = (string) $r[$i];
                $idx = $map[$val] ?? 0;
                $sheetXml .= '<c r="'.$col.$rowNum.'" t="s"><v>'.$idx.'</v></c>';
            }
            $sheetXml .= '</row>';
        }
        $sheetXml .= '</sheetData></worksheet>';
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);
        $created = gmdate('Y-m-d\TH:i:s\Z');
        $coreProps = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><dc:title>services_template</dc:title><dc:creator>MCRO SMS System</dc:creator><cp:lastModifiedBy>MCRO SMS System</cp:lastModifiedBy><dcterms:created xsi:type="dcterms:W3CDTF">'.$created.'</dcterms:created><dcterms:modified xsi:type="dcterms:W3CDTF">'.$created.'</dcterms:modified></cp:coreProperties>';
        $appProps = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes"><Application>Microsoft Excel</Application><DocSecurity>0</DocSecurity><ScaleCrop>false</ScaleCrop><Company></Company><LinksUpToDate>false</LinksUpToDate><SharedDoc>false</SharedDoc><HyperlinksChanged>false</HyperlinksChanged><AppVersion>16.0000</AppVersion></Properties>';
        $zip->addFromString('docProps/core.xml', $coreProps);
        $zip->addFromString('docProps/app.xml', $appProps);
        $zip->close();
        $binary = file_get_contents($tmp);
        @unlink($tmp);
        return $binary;
    }
    private function indexToColLetters(int $index): string
    {
        $letters = '';
        $index += 1;
        while ($index > 0) {
            $mod = ($index - 1) % 26;
            $letters = chr(65 + $mod).$letters;
            $index = intdiv(($index - 1), 26);
        }
        return $letters;
    }
    private function generateExcelXml(array $headers, array $rows, array $titleLines = []): string
    {
        $cols = max(1, count($headers));
        $mergeAcross = $cols - 1;
        $xml = '<?xml version="1.0"?>';
        $xml .= '<?mso-application progid="Excel.Sheet"?>';
        $xml .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet" xmlns:html="http://www.w3.org/TR/REC-html40">';
        $xml .= '<Styles>';
        $xml .= '<Style ss:ID="sHeader"><Alignment ss:Horizontal="Center"/><Font ss:Bold="1"/></Style>';
        $xml .= '<Style ss:ID="sTh"><Font ss:Bold="1"/></Style>';
        $xml .= '</Styles>';
        $xml .= '<Worksheet ss:Name="Sheet1"><Table>';
        if (!empty($titleLines)) {
            foreach ($titleLines as $line) {
                $xml .= '<Row>';
                $xml .= '<Cell ss:MergeAcross="'.$mergeAcross.'" ss:StyleID="sHeader"><Data ss:Type="String">'.htmlspecialchars($line, ENT_XML1 | ENT_COMPAT, 'UTF-8').'</Data></Cell>';
                $xml .= '</Row>';
            }
            // spacer row
            $xml .= '<Row><Cell ss:MergeAcross="'.$mergeAcross.'"><Data ss:Type="String"></Data></Cell></Row>';
        }
        // column headers
        $xml .= '<Row>';
        foreach ($headers as $h) {
            $xml .= '<Cell ss:StyleID="sTh"><Data ss:Type="String">'.htmlspecialchars($h, ENT_XML1 | ENT_COMPAT, 'UTF-8').'</Data></Cell>';
        }
        $xml .= '</Row>';
        // data rows
        foreach ($rows as $r) {
            $xml .= '<Row>';
            foreach ($r as $v) {
                $xml .= '<Cell><Data ss:Type="String">'.htmlspecialchars((string) $v, ENT_XML1 | ENT_COMPAT, 'UTF-8').'</Data></Cell>';
            }
            $xml .= '</Row>';
        }
        $xml .= '</Table></Worksheet></Workbook>';
        return $xml;
    }
    public function scheduled(Request $request): View
    {
        $services = Service::with('statusLogs')->get();
        $items = [];
        foreach ($services as $s) {
            if ($s->service_type === 'Application for Marriage License') {
                if ($s->posting_start_date) {
                    $due = $s->posting_start_date->copy()->addWeekdays(10);
                    if (!$s->sms_release_sent && $s->status !== 'Released') {
                        $items[] = [
                            'due' => $due,
                            'service' => $s,
                            'label' => 'Releasing notice',
                            'event' => 'releasing',
                        ];
                    }
                }
            }
            if (in_array($s->service_type, ['Petitions filed under RA 9048 - Clerical Error','Petitions filed under RA 9048 & RA 10172'])) {
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
        }
        usort($items, function ($a, $b) {
            return $a['due'] <=> $b['due'];
        });
        $type = (string) $request->query('service_type', '');
        $startDate = (string) $request->query('start_date', '');
        $endDate = (string) $request->query('end_date', '');
        $start = $startDate !== '' ? Carbon::parse($startDate)->startOfDay() : null;
        $end = $endDate !== '' ? Carbon::parse($endDate)->endOfDay() : null;
        $filtered = array_filter($items, function ($it) use ($type, $start, $end) {
            if ($type && $it['service']->service_type !== $type) return false;
            if ($start && $it['due']->lt($start)) return false;
            if ($end && $it['due']->gt($end)) return false;
            return true;
        });
        $today = Carbon::today();
        $week = $today->copy()->addDays(7);
        $month = $today->copy()->addDays(30);
        $todayCount = count(array_filter($filtered, function ($it) use ($today) { return $it['due']->isSameDay($today); }));
        $weekCount = count(array_filter($filtered, function ($it) use ($today, $week) { return $it['due']->gt($today) && $it['due']->lte($week); }));
        $monthCount = count(array_filter($filtered, function ($it) use ($today, $month) { return $it['due']->gte($today) && $it['due']->lte($month); }));
        $overdueCount = count(array_filter($filtered, function ($it) use ($today) { return $it['due']->lt($today); }));
        $types = collect($items)
            ->map(function($it){ return $it['service']->service_type; })
            ->unique()
            ->sort()
            ->values();
        return view('scheduled.index', [
            'items' => $filtered,
            'types' => $types,
            'selectedType' => $type,
            'todayCount' => $todayCount,
            'weekCount' => $weekCount,
            'monthCount' => $monthCount,
            'overdueCount' => $overdueCount,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }
    
    public function dashboard(Request $request): View
    {
        $services = Service::with('statusLogs')->get();
        $triggerMap = [
            'Application for Marriage License' => ['Posted'],
            'Frontline Service' => ['Ready for Pickup'],
            'Request for PSA documents through BREQS' => ['Ready for Pickup'],
            'Delayed Registration' => ['Under Verification','Inconsistent','Consistent'],
            'Endorsement for Negative PSA - Positive LCRO' => ['Sent to PSA','PSA Has Feedback','Reworked and Resent','PSA Successfully Uploaded'],
            'Endorsement for Blurred PSA - Clear LCRO File' => ['Sent to PSA','PSA Has Feedback','Reworked and Resent','PSA Successfully Uploaded'],
            'Endorsement of Legal Instrument & MC 2010-04 & Court Order' => ['Sent to PSA','PSA Has Feedback','Reworked and Resent','PSA Successfully Uploaded'],
            'Petitions filed under RA 9048 - Clerical Error' => ['For Filing','Posted','Sent to PSA','Affirmed','Impugned'],
            'Petitions filed under RA 9048 & RA 10172' => ['For Filing','Posted','Sent to PSA','Affirmed','Impugned'],
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
        if (Schema::hasTable('sms_messages')) {
            $recentSmsQuery = SmsMessage::with('service')->orderBy('created_at', 'desc');
            if ($request->query('service_type')) {
                $recentSmsQuery->whereHas('service', fn ($q) => $q->where('service_type', $request->query('service_type')));
            }
            $recentSms = $recentSmsQuery->paginate(50)->withQueryString();
        } else {
            $recentSms = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 50);
        }
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
            'recentSms' => $recentSms,
        ]));
    }

    public function runScheduledAction(Request $request, Service $service): RedirectResponse
    {
        $validated = $request->validate([
            'event' => ['required', 'string', 'max:50'],
        ]);

        if ($validated['event'] !== 'releasing') {
            return back()->with('status', 'Manual action is not available for this scheduled item yet');
        }

        if ($service->service_type !== 'Application for Marriage License') {
            return back()->with('status', 'Only marriage license release notices can be sent from here');
        }

        if (!$service->posting_start_date) {
            return back()->with('status', 'Posting start date is missing for this service');
        }

        $due = $service->posting_start_date->copy()->addWeekdays(10);
        if ($due->isFuture()) {
            return back()->with('status', 'This release notice is not due yet');
        }

        if ($service->sms_release_sent || $service->status === 'Released') {
            return back()->with('status', 'This release notice was already processed');
        }

        $service->status = 'Released';
        $service->release_date = Carbon::today();
        $service->sms_release_sent = true;
        $service->save();

        $this->sms->send($service, 'releasing');

        ServiceStatusLog::create([
            'service_id' => $service->id,
            'status' => 'Released',
            'note' => 'Release notice sent manually from scheduled messages',
            'user_id' => optional(auth()->user())->id,
        ]);

        return back()->with('status', 'Release notice sent and the service was marked Released');
    }

    public function updateStatus(Request $request, Service $service): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'max:50'],
        ]);
        $allowed = $this->statusesForType($service->service_type);
        if (!in_array($validated['status'], $allowed, true) && $validated['status'] !== $service->status) {
            return redirect()->route('services.index')->with('status', 'Status not allowed for '.$service->service_type);
        }
        $prev = $service->status;
        if (!$this->allowsBackwards($service->service_type)) {
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
                'user_id' => optional(auth()->user())->id,
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
                SendSmsJob::dispatch($service->id, 'verification_started');
            }
            if ($prev !== 'Inconsistent' && $validated['status'] === 'Inconsistent') {
                SendSmsJob::dispatch($service->id, 'requirements_incomplete');
            }
            if ($prev !== 'Consistent' && $validated['status'] === 'Consistent') {
                SendSmsJob::dispatch($service->id, 'verification_consistent');
            }
        }
        if (
            in_array($service->service_type, $this->frontlineServiceTypes(), true) &&
            $prev !== 'Ready for Pickup' &&
            $validated['status'] === 'Ready for Pickup' &&
            !$service->sms_ready_sent
        ) {
            SendSmsJob::dispatch($service->id, 'ready_for_pickup');
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
            SendSmsJob::dispatch($service->id, 'posting_notice');
            $service->sms_posting_sent = true;
            $service->save();
        }
        if ($service->service_type === 'Endorsement for Negative PSA - Positive LCRO') {
            if ($prev !== 'Sent to PSA' && $validated['status'] === 'Sent to PSA') {
                    SendSmsJob::dispatch($service->id, 'psa_sent');
            }
            if ($prev !== 'PSA Has Feedback' && $validated['status'] === 'PSA Has Feedback') {
                    SendSmsJob::dispatch($service->id, 'psa_feedback_received');
            }
            if ($prev !== 'Reworked and Resent' && $validated['status'] === 'Reworked and Resent') {
                    SendSmsJob::dispatch($service->id, 'psa_resent_for_processing');
            }
            if ($prev !== 'PSA Successfully Uploaded' && $validated['status'] === 'PSA Successfully Uploaded' && !$service->sms_ready_sent) {
                    SendSmsJob::dispatch($service->id, 'psa_no_feedback_uploaded');
                $service->sms_ready_sent = true;
                $service->save();
            }
        }
        if ($service->service_type === 'Endorsement for Blurred PSA - Clear LCRO File') {
            if ($prev !== 'Sent to PSA' && $validated['status'] === 'Sent to PSA') {
                $this->sms->send($service, 'psa_sent');
            }
            if ($prev !== 'PSA Has Feedback' && $validated['status'] === 'PSA Has Feedback') {
                $this->sms->send($service, 'psa_feedback_received');
            }
            if ($prev !== 'Reworked and Resent' && $validated['status'] === 'Reworked and Resent') {
                $this->sms->send($service, 'psa_resent_for_processing');
            }
            if ($prev !== 'PSA Successfully Uploaded' && $validated['status'] === 'PSA Successfully Uploaded' && !$service->sms_ready_sent) {
                $this->sms->send($service, 'psa_no_feedback_uploaded');
                $service->sms_ready_sent = true;
                $service->save();
            }
        }
        if ($service->service_type === 'Endorsement of Legal Instrument & MC 2010-04 & Court Order') {
            if ($prev !== 'Sent to PSA' && $validated['status'] === 'Sent to PSA') {
                $this->sms->send($service, 'psa_sent');
            }
            if ($prev !== 'PSA Has Feedback' && $validated['status'] === 'PSA Has Feedback') {
                $this->sms->send($service, 'psa_feedback_received');
            }
            if ($prev !== 'Reworked and Resent' && $validated['status'] === 'Reworked and Resent') {
                $this->sms->send($service, 'psa_resent_for_processing');
            }
            if ($prev !== 'PSA Successfully Uploaded' && $validated['status'] === 'PSA Successfully Uploaded' && !$service->sms_ready_sent) {
                $this->sms->send($service, 'psa_no_feedback_uploaded');
                $service->sms_ready_sent = true;
                $service->save();
            }
        }
        if ($service->service_type === 'Petitions filed under RA 9048 - Clerical Error') {
            if ($prev !== 'For Filing' && $validated['status'] === 'For Filing') {
                $this->sms->send($service, 'petition_ready_for_filing');
            }
            if ($prev !== 'Posted' && $validated['status'] === 'Posted' && !$service->sms_posting_sent) {
                $service->posting_start_date = Carbon::today()->nextWeekday();
                $this->sms->send($service, 'petition_ready_for_posting');
                $service->sms_posting_sent = true;
                $service->save();
            }
            if ($prev !== 'Sent to PSA' && $validated['status'] === 'Sent to PSA') {
                $this->sms->send($service, 'sent_to_psa_legal');
            }
            if ($prev !== 'Affirmed' && $validated['status'] === 'Affirmed') {
                $this->sms->send($service, 'psa_affirmed');
            }
            if ($prev !== 'Impugned' && $validated['status'] === 'Impugned') {
                $this->sms->send($service, 'psa_impugned');
            }
        }
        if ($service->service_type === 'Petitions filed under RA 9048 & RA 10172') {
            if ($prev !== 'For Filing' && $validated['status'] === 'For Filing') {
                $this->sms->send($service, 'petition_ready_for_filing');
            }
            if ($prev !== 'Posted' && $validated['status'] === 'Posted' && !$service->sms_posting_sent) {
                $service->posting_start_date = Carbon::today()->nextWeekday();
                $this->sms->send($service, 'petition_ready_for_posting');
                $service->sms_posting_sent = true;
                $service->save();
            }
            if ($prev !== 'Sent to PSA' && $validated['status'] === 'Sent to PSA') {
                $this->sms->send($service, 'sent_to_psa_legal');
            }
            if ($prev !== 'Affirmed' && $validated['status'] === 'Affirmed') {
                $this->sms->send($service, 'psa_affirmed');
            }
            if ($prev !== 'Impugned' && $validated['status'] === 'Impugned') {
                $this->sms->send($service, 'psa_impugned');
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
            if (!$this->allowsBackwards($svc->service_type)) {
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
                    'user_id' => optional(auth()->user())->id,
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
                    SendSmsJob::dispatch($svc->id, 'verification_started');
                }
                if ($prevStatus !== 'Inconsistent' && $validated['status'] === 'Inconsistent') {
                    SendSmsJob::dispatch($svc->id, 'requirements_incomplete');
                }
                if ($prevStatus !== 'Consistent' && $validated['status'] === 'Consistent') {
                    SendSmsJob::dispatch($svc->id, 'verification_consistent');
                }
            }
            if (
                in_array($svc->service_type, $this->frontlineServiceTypes(), true) &&
                $prevStatus !== 'Ready for Pickup' &&
                $validated['status'] === 'Ready for Pickup' &&
                !$svc->sms_ready_sent
            ) {
                SendSmsJob::dispatch($svc->id, 'ready_for_pickup');
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
                SendSmsJob::dispatch($svc->id, 'posting_notice');
                $svc->sms_posting_sent = true;
                $svc->save();
            }
            if ($svc->service_type === 'Endorsement for Negative PSA - Positive LCRO') {
                if ($prevStatus !== 'Sent to PSA' && $validated['status'] === 'Sent to PSA') {
                    SendSmsJob::dispatch($svc->id, 'psa_sent');
                }
                if ($prevStatus !== 'PSA Has Feedback' && $validated['status'] === 'PSA Has Feedback') {
                    SendSmsJob::dispatch($svc->id, 'psa_feedback_received');
                }
                if ($prevStatus !== 'Reworked and Resent' && $validated['status'] === 'Reworked and Resent') {
                    SendSmsJob::dispatch($svc->id, 'psa_resent_for_processing');
                }
                if ($prevStatus !== 'PSA Successfully Uploaded' && $validated['status'] === 'PSA Successfully Uploaded' && !$svc->sms_ready_sent) {
                    SendSmsJob::dispatch($svc->id, 'psa_no_feedback_uploaded');
                    $svc->sms_ready_sent = true;
                    $svc->save();
                }
            }
            if ($svc->service_type === 'Endorsement for Blurred PSA - Clear LCRO File') {
                if ($prevStatus !== 'Sent to PSA' && $validated['status'] === 'Sent to PSA') {
                    SendSmsJob::dispatch($svc->id, 'psa_sent');
                }
                if ($prevStatus !== 'PSA Has Feedback' && $validated['status'] === 'PSA Has Feedback') {
                    SendSmsJob::dispatch($svc->id, 'psa_feedback_received');
                }
                if ($prevStatus !== 'Reworked and Resent' && $validated['status'] === 'Reworked and Resent') {
                    SendSmsJob::dispatch($svc->id, 'psa_resent_for_processing');
                }
                if ($prevStatus !== 'PSA Successfully Uploaded' && $validated['status'] === 'PSA Successfully Uploaded' && !$svc->sms_ready_sent) {
                    SendSmsJob::dispatch($svc->id, 'psa_no_feedback_uploaded');
                    $svc->sms_ready_sent = true;
                    $svc->save();
                }
            }
            if ($svc->service_type === 'Endorsement of Legal Instrument & MC 2010-04 & Court Order') {
                if ($prevStatus !== 'Sent to PSA' && $validated['status'] === 'Sent to PSA') {
                    $this->sms->send($svc, 'psa_sent');
                }
                if ($prevStatus !== 'PSA Has Feedback' && $validated['status'] === 'PSA Has Feedback') {
                    $this->sms->send($svc, 'psa_feedback_received');
                }
                if ($prevStatus !== 'Reworked and Resent' && $validated['status'] === 'Reworked and Resent') {
                    $this->sms->send($svc, 'psa_resent_for_processing');
                }
                if ($prevStatus !== 'PSA Successfully Uploaded' && $validated['status'] === 'PSA Successfully Uploaded' && !$svc->sms_ready_sent) {
                    $this->sms->send($svc, 'psa_no_feedback_uploaded');
                    $svc->sms_ready_sent = true;
                    $svc->save();
                }
            }
            if ($svc->service_type === 'Petitions filed under RA 9048 - Clerical Error') {
                if ($prevStatus !== 'For Filing' && $validated['status'] === 'For Filing') {
                    SendSmsJob::dispatch($svc->id, 'petition_ready_for_filing');
                }
                if ($prevStatus !== 'Posted' && $validated['status'] === 'Posted' && !$svc->sms_posting_sent) {
                    $svc->posting_start_date = \Illuminate\Support\Carbon::today()->nextWeekday();
                    SendSmsJob::dispatch($svc->id, 'petition_ready_for_posting');
                    $svc->sms_posting_sent = true;
                    $svc->save();
                }
                if ($prevStatus !== 'Sent to PSA' && $validated['status'] === 'Sent to PSA') {
                    SendSmsJob::dispatch($svc->id, 'sent_to_psa_legal');
                }
                if ($prevStatus !== 'Affirmed' && $validated['status'] === 'Affirmed') {
                    SendSmsJob::dispatch($svc->id, 'psa_affirmed');
                }
                if ($prevStatus !== 'Impugned' && $validated['status'] === 'Impugned') {
                    SendSmsJob::dispatch($svc->id, 'psa_impugned');
                }
            }
            if ($svc->service_type === 'Petitions filed under RA 9048 & RA 10172') {
                if ($prevStatus !== 'For Filing' && $validated['status'] === 'For Filing') {
                    SendSmsJob::dispatch($svc->id, 'petition_ready_for_filing');
                }
                if ($prevStatus !== 'Posted' && $validated['status'] === 'Posted' && !$svc->sms_posting_sent) {
                    $svc->posting_start_date = \Illuminate\Support\Carbon::today()->nextWeekday();
                    SendSmsJob::dispatch($svc->id, 'petition_ready_for_posting');
                    $svc->sms_posting_sent = true;
                    $svc->save();
                }
                if ($prevStatus !== 'Sent to PSA' && $validated['status'] === 'Sent to PSA') {
                    SendSmsJob::dispatch($svc->id, 'sent_to_psa_legal');
                }
                if ($prevStatus !== 'Affirmed' && $validated['status'] === 'Affirmed') {
                    SendSmsJob::dispatch($svc->id, 'psa_affirmed');
                }
                if ($prevStatus !== 'Impugned' && $validated['status'] === 'Impugned') {
                    SendSmsJob::dispatch($svc->id, 'psa_impugned');
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

    public function bulkDelete(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        $deletedCount = Service::whereIn('id', $validated['ids'])
            ->whereNull('deleted_at')
            ->delete();

        return redirect()->route('services.index')->with('status', 'Deleted: '.$deletedCount.' service entr'.($deletedCount === 1 ? 'y' : 'ies'));
    }

    public function clearSmsHistory(): RedirectResponse
    {
        if (!\Schema::hasTable('sms_messages')) {
            return redirect()->route('dashboard')->with('status', 'SMS history table not found');
        }
        \App\Models\SmsMessage::query()->delete();
        return redirect()->route('dashboard')->with('status', 'SMS history cleared');
    }
}
