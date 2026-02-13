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
            'is_active' => (bool)($validated['is_active'] ?? true),
        ]);
        return redirect()->route('sms-templates.index', ['service_type' => $template->service_type])->with('status', 'Template updated');
    }
}
