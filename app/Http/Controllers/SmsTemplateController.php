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
        $templates = SmsTemplate::orderBy('name')->get();
        return view('templates.index', compact('templates'));
    }

    public function create(): View
    {
        return view('templates.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:32', 'unique:sms_templates,code'],
            'name' => ['required', 'string', 'max:120'],
            'body' => ['required', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        SmsTemplate::create([
            'code' => $validated['code'],
            'name' => $validated['name'],
            'body' => $validated['body'],
            'is_active' => (bool)($validated['is_active'] ?? true),
        ]);
        return redirect()->route('templates.index')->with('status', 'Template created');
    }

    public function edit(SmsTemplate $template): View
    {
        return view('templates.edit', compact('template'));
    }

    public function update(Request $request, SmsTemplate $template): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:32', 'unique:sms_templates,code,'.$template->id],
            'name' => ['required', 'string', 'max:120'],
            'body' => ['required', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $template->update([
            'code' => $validated['code'],
            'name' => $validated['name'],
            'body' => $validated['body'],
            'is_active' => (bool)($validated['is_active'] ?? true),
        ]);
        return redirect()->route('templates.index')->with('status', 'Template updated');
    }

    public function destroy(SmsTemplate $template): RedirectResponse
    {
        $template->delete();
        return redirect()->route('templates.index')->with('status', 'Template deleted');
    }
}
