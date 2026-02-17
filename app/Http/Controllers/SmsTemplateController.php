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
        if ($current === 'Delayed Registration') {
            $order = [
                'requirements_incomplete' => 1,
                'verification_started' => 2,
                'verification_inconsistent' => 3,
                'verification_consistent' => 4,
                'ready_for_release' => 5,
            ];
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
