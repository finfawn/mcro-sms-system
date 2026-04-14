<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="text-base font-semibold text-gray-900">SMS Settings</h2>
        </div>
    </x-slot>
    <div class="py-4">
        <div class="app-shell grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="md:col-span-1">
                <div class="bg-white border rounded-md">
                    <div class="px-3 py-2 border-b text-sm font-medium text-gray-700">Frontline Services</div>
                    <ul class="divide-y divide-gray-200">
                        @php
                            $displayMap = [
                                'Frontline Service' => 'Request for PSA documents through BREQS',
                            ];
                        @endphp
                        @foreach($grouped as $stype => $items)
                            <li class="service-item">
                                <a href="{{ route('sms-templates.index', ['service_type' => $stype]) }}" class="flex justify-between items-center px-3 py-2 hover:bg-gray-50 {{ ($current ?? null) === $stype ? 'bg-blue-50 ring-1 ring-blue-200 border-l-2 border-blue-600' : '' }}">
                                    <span class="text-sm {{ ($current ?? null) === $stype ? 'text-blue-700' : 'text-gray-700' }}">{{ $displayMap[$stype] ?? str_replace('_',' ', $stype) }}</span>
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs {{ ($current ?? null) === $stype ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600' }}">{{ $items->count() }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
            <div class="md:col-span-3">
                <div class="bg-white border rounded-md">
                    <div class="px-4 py-3 border-b flex items-center justify-between">
                        <div class="text-sm font-medium text-gray-900">
                            {{ $current ? ($displayMap[$current] ?? str_replace('_',' ', $current)) : 'Select a service type' }}
                        </div>
                        @if($current)
                        <div class="inline-flex items-center rounded-full px-2 py-0.5 text-xs bg-gray-100 text-gray-700">{{ ($currentTemplates ?? collect())->count() }} templates</div>
                        @endif
                    </div>
                    <div class="p-4">
                        @if($current)
                            <div class="divide-y divide-gray-200">
                                @foreach($currentTemplates as $tpl)
                                    <div class="tpl-row cursor-pointer hover:bg-gray-50">
                                        <div class="flex items-center justify-between px-3 py-2">
                                            <div class="flex items-center gap-2">
                                                <svg class="chev transition-transform duration-200" width="14" height="14" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path d="M4.646 6.646a.5.5 0 01.708 0L8 9.293l2.646-2.647a.5.5 0 01.708.708l-3 3a.5.5 0 01-.708 0l-3-3a.5.5 0 010-.708z"/></svg>
                                                <div class="text-sm font-medium text-gray-900">{{ $loop->iteration }}. {{ ucfirst(str_replace('_',' ', $tpl->event_key)) }}</div>
                                            </div>
                                            <div class="inline-flex items-center rounded-full px-2 py-0.5 text-xs {{ $tpl->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">{{ $tpl->is_active ? 'Active' : 'Inactive' }}</div>
                                        </div>
                                    </div>
                                    <div class="details-row hidden">
                                        <div class="px-3 py-3 border-t details-content">
                                            @php $canEdit = (auth()->user()->role ?? 'user') === 'admin'; @endphp
                                            @if($canEdit)
                                                <form method="POST" action="{{ route('sms-templates.update', $tpl) }}">
                                                    @csrf
                                                    @method('PUT')
                                                    <div class="flex items-center justify-between mb-2">
                                                        <div class="flex items-center gap-2">
                                                            <span class="text-xs text-gray-500">Placeholders</span>
                                                            <button type="button" class="inline-flex items-center px-2 py-1 rounded-md text-xs bg-blue-50 text-blue-700 hover:bg-blue-100 placeholder-btn" data-target="tpl-body-{{ $tpl->id }}" data-placeholder="&#123;&#123;citizen_name&#125;&#125;">@{{ citizen_name }}</button>
                                                            <button type="button" class="inline-flex items-center px-2 py-1 rounded-md text-xs bg-blue-50 text-blue-700 hover:bg-blue-100 placeholder-btn" data-target="tpl-body-{{ $tpl->id }}" data-placeholder="&#123;&#123;reference_no&#125;&#125;">@{{ reference_no }}</button>
                                                        </div>
                                                        <div class="flex items-center gap-2">
                                                            <input type="checkbox" class="rounded border-gray-300" name="is_active" value="1" @checked($tpl->is_active)>
                                                            <span class="text-xs text-gray-700">Active</span>
                                                        </div>
                                                    </div>
                                                    <textarea id="tpl-body-{{ $tpl->id }}" name="template_body" rows="5" class="w-full border-gray-300 rounded-md focus:ring-2 focus:ring-blue-200 focus:border-blue-300">{{ old('template_body', $tpl->template_body) }}</textarea>
                                                    <div class="flex items-center justify-end mt-3">
                                                        <button type="submit" class="inline-flex items-center px-3 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Save</button>
                                                    </div>
                                                </form>
                                            @else
                                                <div class="flex items-center justify-between mb-2">
                                                    <div class="flex items-center gap-2">
                                                        <span class="text-xs text-gray-500">Placeholders</span>
                                                        <span class="text-xs text-gray-400">(view only)</span>
                                                    </div>
                                                    <div class="flex items-center gap-2">
                                                        <input type="checkbox" class="rounded border-gray-300" disabled @checked($tpl->is_active)>
                                                        <span class="text-xs text-gray-700">Active</span>
                                                    </div>
                                                </div>
                                                <div class="w-full border-gray-200 rounded-md bg-gray-50 p-2 text-sm text-gray-800">{{ $tpl->template_body }}</div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            @php $canEdit = (auth()->user()->role ?? 'user') === 'admin'; @endphp
                            <div class="text-sm text-gray-500">
                                {{ $canEdit ? 'Choose a service type from the left to edit templates.' : 'Choose a service type from the left to view templates.' }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    <style>
        .details-content {
            overflow: hidden;
            height: 0;
            opacity: 0;
            transition: height 220ms ease, opacity 220ms ease;
        }
    </style>
    <script>
        (function(){
            var rows = document.querySelectorAll('.tpl-row');
            rows.forEach(function(row){
                row.addEventListener('click', function(e){
                    if (e.target.closest('a') || e.target.closest('button') || e.target.closest('select') || e.target.closest('input') || e.target.closest('textarea')) {
                        return;
                    }
                    var details = row.nextElementSibling;
                    if (details && details.classList.contains('details-row')) {
                        var content = details.querySelector('.details-content');
                        var chev = row.querySelector('.chev');
                        var openRow = Array.prototype.find.call(document.querySelectorAll('.details-row'), function(dr){ return !dr.classList.contains('hidden'); });
                        if (openRow && openRow !== details) {
                            var openContent = openRow.querySelector('.details-content');
                            var openChev = openRow.previousElementSibling && openRow.previousElementSibling.querySelector('.chev');
                            if (openContent) {
                                openContent.style.height = openContent.scrollHeight + 'px';
                                requestAnimationFrame(function(){
                                    openContent.style.height = '0px';
                                    openContent.style.opacity = '0';
                                    openContent.addEventListener('transitionend', function te(){ 
                                        openContent.removeEventListener('transitionend', te);
                                        openRow.classList.add('hidden');
                                        openContent.style.height = '';
                                    }, { once: true });
                                });
                            } else {
                                openRow.classList.add('hidden');
                            }
                            if (openChev) { openChev.classList.remove('rotate-180'); }
                        }
                        var isHidden = details.classList.contains('hidden');
                        if (isHidden) {
                            details.classList.remove('hidden');
                            if (content) {
                                content.style.height = '0px';
                                content.style.opacity = '0';
                                var target = content.scrollHeight;
                                requestAnimationFrame(function(){
                                    content.style.height = target + 'px';
                                    content.style.opacity = '1';
                                    content.addEventListener('transitionend', function te(){
                                        content.removeEventListener('transitionend', te);
                                        content.style.height = 'auto';
                                        content.style.opacity = '1';
                                    }, { once: true });
                                });
                            }
                            if (chev) { chev.classList.add('rotate-180'); }
                        } else {
                            if (content) {
                                content.style.height = content.scrollHeight + 'px';
                                requestAnimationFrame(function(){
                                    content.style.height = '0px';
                                    content.style.opacity = '0';
                                    content.addEventListener('transitionend', function te(){
                                        content.removeEventListener('transitionend', te);
                                        details.classList.add('hidden');
                                        content.style.height = '';
                                    }, { once: true });
                                });
                            } else {
                                details.classList.add('hidden');
                            }
                            if (chev) { chev.classList.remove('rotate-180'); }
                        }
                    }
                });
            });

            function insertAtCursor(textarea, text) {
                if (!textarea) return;
                textarea.focus();
                var start = textarea.selectionStart || 0;
                var end = textarea.selectionEnd || 0;
                try {
                    if (typeof document.execCommand === 'function') {
                        textarea.setSelectionRange(start, end);
                        var ok = document.execCommand('insertText', false, text);
                        if (ok) return;
                    }
                } catch (_) {}
                if (typeof textarea.setRangeText === 'function') {
                    textarea.setRangeText(text, start, end, 'end');
                } else {
                    var before = textarea.value.substring(0, start);
                    var after = textarea.value.substring(end, textarea.value.length);
                    textarea.value = before + text + after;
                    var newPos = start + text.length;
                    textarea.selectionStart = textarea.selectionEnd = newPos;
                }
            }
            document.querySelectorAll('.placeholder-btn').forEach(function(btn){
                btn.addEventListener('click', function(e){
                    e.preventDefault();
                    var ph = btn.getAttribute('data-placeholder') || '';
                    var targetId = btn.getAttribute('data-target');
                    var textarea = null;
                    if (targetId) {
                        textarea = document.getElementById(targetId);
                    }
                    insertAtCursor(textarea, ph);
                });
            });
        })();
    </script>
</x-app-layout>
