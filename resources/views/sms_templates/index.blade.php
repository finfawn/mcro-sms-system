<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="text-base font-semibold text-gray-900">SMS Templates</h2>
        </div>
    </x-slot>
    <div class="py-4">
        <div class="max-w-7xl mx-auto px-4 grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="md:col-span-1">
                <div class="bg-white border rounded-md">
                    <div class="px-3 py-2 border-b text-sm font-medium text-gray-700">Service Types</div>
                    <ul class="divide-y divide-gray-200">
                        @foreach($grouped as $stype => $items)
                            <li>
                                <a href="{{ route('sms-templates.index', ['service_type' => $stype]) }}" class="flex justify-between items-center px-3 py-2 hover:bg-gray-50">
                                    <span class="text-sm text-gray-700">{{ str_replace('_',' ', $stype) }}</span>
                                    <span class="text-xs text-gray-500">{{ $items->count() }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
            <div class="md:col-span-3">
                <div class="bg-white border rounded-md">
                    <div class="px-4 py-3 border-b">
                        <div class="text-sm font-medium text-gray-700">
                            {{ $current ? str_replace('_',' ', $current) : 'Select a service type' }}
                        </div>
                    </div>
                    <div class="p-4">
                        @if($current === 'application_for_marriage_license')
                            @php
                                $posting = $currentTemplates->firstWhere('event_key', 'posting_completed');
                                $released = $currentTemplates->firstWhere('event_key', 'released');
                            @endphp
                            <div class="grid grid-cols-1 gap-6">
                                @if($posting)
                                <div>
                                    <div class="text-sm font-medium text-gray-700 mb-2">Posting Completion SMS Template</div>
                                    <form method="POST" action="{{ route('sms-templates.update', $posting) }}">
                                        @csrf
                                        @method('PUT')
                                        <textarea name="template_body" rows="5" class="w-full border-gray-300 rounded-md">{{ old('template_body', $posting->template_body) }}</textarea>
                                        <div class="flex items-center gap-2 mt-2">
                                            <input type="checkbox" class="rounded border-gray-300" name="is_active" value="1" @checked($posting->is_active)>
                                            <span class="text-sm text-gray-700">Active</span>
                                        </div>
                                        <div class="mt-3">
                                            <button type="submit" class="inline-flex items-center px-3 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Save</button>
                                        </div>
                                    </form>
                                </div>
                                @endif
                                @if($released)
                                <div>
                                    <div class="text-sm font-medium text-gray-700 mb-2">License Issued SMS Template</div>
                                    <form method="POST" action="{{ route('sms-templates.update', $released) }}">
                                        @csrf
                                        @method('PUT')
                                        <textarea name="template_body" rows="5" class="w-full border-gray-300 rounded-md">{{ old('template_body', $released->template_body) }}</textarea>
                                        <div class="flex items-center gap-2 mt-2">
                                            <input type="checkbox" class="rounded border-gray-300" name="is_active" value="1" @checked($released->is_active)>
                                            <span class="text-sm text-gray-700">Active</span>
                                        </div>
                                        <div class="mt-3">
                                            <button type="submit" class="inline-flex items-center px-3 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Save</button>
                                        </div>
                                    </form>
                                </div>
                                @endif
                            </div>
                        @elseif($current)
                            <div class="grid grid-cols-1 gap-6">
                                @foreach($currentTemplates as $tpl)
                                    <div>
                                        <div class="text-sm font-medium text-gray-700 mb-2">{{ ucfirst(str_replace('_',' ', $tpl->event_key)) }}</div>
                                        <form method="POST" action="{{ route('sms-templates.update', $tpl) }}">
                                            @csrf
                                            @method('PUT')
                                            <textarea name="template_body" rows="5" class="w-full border-gray-300 rounded-md">{{ old('template_body', $tpl->template_body) }}</textarea>
                                            <div class="flex items-center gap-2 mt-2">
                                                <input type="checkbox" class="rounded border-gray-300" name="is_active" value="1" @checked($tpl->is_active)>
                                                <span class="text-sm text-gray-700">Active</span>
                                            </div>
                                            <div class="mt-3">
                                                <button type="submit" class="inline-flex items-center px-3 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Save</button>
                                            </div>
                                        </form>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-sm text-gray-500">Choose a service type from the left to edit templates.</div>
                        @endif
                        <div class="text-xs text-gray-500 mt-4">Placeholders: {{'{{citizen_name}}'}}, {{'{{reference_no}}'}}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
