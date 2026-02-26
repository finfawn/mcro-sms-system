<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\SmsTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SmsTemplateController extends Controller
{
    public function index(): View
    {
        $grouped = SmsTemplate::orderBy('service_type')
            ->orderBy('event_key')
            ->get()
            ->groupBy('service_type');
        $current = request('service_type');
        $currentTemplates = $current ? ($grouped[$current] ?? collect()) : collect();
        $orders = [
            'Application for Marriage License' => [
                'posting_notice' => 1,
                'releasing' => 2,
            ],
            'Request for PSA documents through BREQS' => [
                'ready_for_pickup' => 1,
            ],
            'Endorsement for Negative PSA - Positive LCRO' => [
                'psa_sent' => 1,
                'psa_feedback_received' => 2,
                'psa_resent_for_processing' => 3,
                'psa_no_feedback_uploaded' => 4,
            ],
            'Endorsement for Blurred PSA - Clear LCRO File' => [
                'psa_sent' => 1,
                'psa_feedback_received' => 2,
                'psa_resent_for_processing' => 3,
                'psa_no_feedback_uploaded' => 4,
            ],
            'Endorsement of Legal Instrument & MC 2010-04 & Court Order' => [
                'psa_sent' => 1,
                'psa_feedback_received' => 2,
                'psa_resent_for_processing' => 3,
                'psa_no_feedback_uploaded' => 4,
            ],
            'Delayed Registration' => [
                'requirements_incomplete' => 1,
                'verification_started' => 2,
                'verification_inconsistent' => 3,
                'verification_consistent' => 4,
                'ready_for_release' => 5,
            ],
            'Petitions filed under RA 9048 - Clerical Error' => [
                'petition_ready_for_filing' => 1,
                'petition_ready_for_posting' => 2,
                'sent_to_psa_legal' => 3,
                'psa_affirmed' => 4,
                'psa_impugned' => 5,
            ],
            'Petitions filed under RA 9048 & RA 10172' => [
                'petition_ready_for_filing' => 1,
                'petition_ready_for_posting' => 2,
                'petition_published' => 3,
                'sent_to_psa_legal' => 4,
                'psa_affirmed' => 5,
                'psa_impugned' => 6,
            ],
        ];
        if ($current && isset($orders[$current])) {
            $order = $orders[$current];
            $currentTemplates = $currentTemplates->sortBy(function ($tpl) use ($order) {
                return $order[$tpl->event_key] ?? 999;
            })->values();
        }
        return view('sms_templates.index', [
            'grouped' => $grouped,
            'current' => $current,
            'currentTemplates' => $currentTemplates,
        ]);
    }

    public function update(Request $request, SmsTemplate $template): RedirectResponse
    {
        $validated = $request->validate([
            'template_body' => ['required', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $template->update([
            'template_body' => $validated['template_body'],
            'is_active' => (bool)($validated['is_active'] ?? false),
        ]);
        return redirect()->route('sms-templates.index', ['service_type' => $template->service_type])->with('status', 'Template updated');
    }
}
