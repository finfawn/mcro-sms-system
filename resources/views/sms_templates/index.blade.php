<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="text-base font-semibold text-gray-900">SMS Settings</h2>
        </div>
    </x-slot>
    <div class="py-4">
        <div class="max-w-7xl mx-auto px-4 grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="md:col-span-1">
                <div class="bg-white border rounded-md">
                    <div class="px-3 py-2 border-b text-sm font-medium text-gray-700">Frontline Services
                    </div>
                    <ul class="divide-y divide-gray-200">
                        @php
                            $displayMap = [
                                'Frontline Service' => 'Request for PSA documents through BREQS',
                            ];
                        @endphp
                        @foreach($grouped as $stype => $items)
                            <li>
                                <a href="{{ route('sms-templates.index', ['service_type' => $stype]) }}" class="flex justify-between items-center px-3 py-2 hover:bg-gray-50">
                                    <span class="text-sm text-gray-700">{{ $displayMap[$stype] ?? str_replace('_',' ', $stype) }}</span>
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
                            {{ $current ? ($displayMap[$current] ?? str_replace('_',' ', $current)) : 'Select a service type' }}
                        </div>
                    </div>
                    <div class="p-4">
                        <div class="text-xs text-gray-500 mb-4 bg-gray-50 p-2 rounded">Placeholders: @{{ citizen_name }}, @{{ reference_no }}</div>
                        @if($current)
                            <div class="divide-y divide-gray-200">
                                @foreach($currentTemplates as $tpl)
                                    <div class="tpl-row cursor-pointer hover:bg-gray-50">
                                        <div class="flex items-center justify-between px-3 py-2">
                                            <div class="text-sm font-medium text-gray-700">{{ $loop->iteration }}. {{ ucfirst(str_replace('_',' ', $tpl->event_key)) }}</div>
                                            <div class="text-xs {{ $tpl->is_active ? 'text-green-600' : 'text-gray-500' }}">{{ $tpl->is_active ? 'Active' : 'Inactive' }}</div>
                                        </div>
                                    </div>
                                    <div class="details-row hidden">
                                        <div class="px-3 py-3 border-t details-content">
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
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-sm text-gray-500">Choose a service type from the left to edit templates.</div>
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
                        var openRow = Array.prototype.find.call(document.querySelectorAll('.details-row'), function(dr){ return !dr.classList.contains('hidden'); });
                        if (openRow && openRow !== details) {
                            var openContent = openRow.querySelector('.details-content');
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
                        }
                    }
                });
            });
        })();
    </script>
</x-app-layout>
