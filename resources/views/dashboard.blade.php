<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="text-base font-semibold text-gray-900">Dashboard</h2>
        </div>
    </x-slot>
    <div class="py-4">
        <div class="max-w-7xl mx-auto px-4">
            <div class="mb-4 rounded-xl overflow-hidden bg-white text-gray-900 shadow ring-1 ring-gray-200 relative">
                <div class="absolute inset-y-0 left-0 w-2 bg-blue-600"></div>
                <img src="{{ asset('logo/MCR TUBLAY LOGO..png') }}" alt="" class="absolute right-6 top-1/2 -translate-y-1/2 h-16 w-16 opacity-10 pointer-events-none">
                <div class="p-6 pl-8 flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <x-application-logo class="h-12 w-auto rounded-full ring-2 ring-indigo-200 bg-white" />
                        <div>
                            <div class="text-xs text-gray-500">MCRO SMS Notification</div>
                            <div class="text-2xl font-semibold">Operational Dashboard</div>
                            <div class="text-xs text-gray-500">Real-time status and upcoming schedules</div>
                        </div>
                    </div>
                    <div class="grid grid-cols-3 gap-6">
                        <div class="text-center bg-blue-600 text-white rounded-md px-4 py-2">
                            <div class="flex items-center justify-center gap-2 text-xs">
                                <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path d="M8 1a1 1 0 011 1v2h2a1 1 0 110 2H9v2a1 1 0 11-2 0V6H5a1 1 0 110-2h2V2a1 1 0 011-1z"/></svg>
                                <span>Today</span>
                            </div>
                            <div class="text-2xl font-bold">{{ $messagesToday ?? 0 }}</div>
                        </div>
                        <div class="text-center bg-blue-600 text-white rounded-md px-4 py-2">
                            <div class="flex items-center justify-center gap-2 text-xs">
                                <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path d="M2 3a1 1 0 011-1h10a1 1 0 011 1v10a1 1 0 01-1 1H3a1 1 0 01-1-1V3zm3 2a1 1 0 000 2h6a1 1 0 100-2H5z"/></svg>
                                <span>Last 30 Days</span>
                            </div>
                            <div class="text-2xl font-bold">{{ $messages30Days ?? 0 }}</div>
                        </div>
                        <div class="text-center bg-blue-600 text-white rounded-md px-4 py-2">
                            <div class="flex items-center justify-center gap-2 text-xs">
                                <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path d="M2 2h12v3H2V2zm0 5h12v3H2V7zm0 5h12v2H2v-2z"/></svg>
                                <span>Total Sent</span>
                            </div>
                            <div class="text-2xl font-bold">{{ $messagesTotal ?? 0 }}</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl ring-1 ring-gray-200 shadow-sm mb-4">
                <form method="GET" action="{{ route('dashboard') }}" class="px-4 py-3 flex items-end gap-3" id="dash_filter_form">
                    <div class="flex items-center gap-2 w-full md:w-80">
                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-md bg-blue-50 text-blue-600">
                            <svg width="18" height="18" viewBox="0 0 18 18" fill="currentColor" aria-hidden="true"><path d="M3 4a1 1 0 011-1h10a1 1 0 01.8 1.6L11 9.5V14a1 1 0 01-1.447.894l-2-1A1 1 0 017 13V9.5L3.2 4.6A1 1 0 013 4z"/></svg>
                        </span>
                        <div class="flex-1">
                            <label class="block text-xs text-gray-500 mb-1">Service Type</label>
                            <select name="service_type" class="border-gray-300 rounded-md w-full text-sm" id="dash_service_select">
                                <option value="">All</option>
                                @if(isset($types))
                                    @foreach($types as $t)
                                        <option value="{{ $t }}" @selected(($selectedType ?? '') === $t)>{{ $t }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                    </div>
                </form>
            </div>
            <script>
                (function(){
                    var sel = document.getElementById('dash_service_select');
                    var form = document.getElementById('dash_filter_form');
                    if (sel && form) {
                        sel.addEventListener('change', function(){ form.submit(); });
                    }
                })();
            </script>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 px-4 pb-4">
                <div class="rounded-md ring-1 ring-red-200 bg-gradient-to-b from-red-50 to-white">
                    <div class="px-3 py-2 bg-red-600 text-white text-sm font-medium flex items-center justify-between">
                        <span class="inline-flex items-center gap-2">
                            <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path d="M8 1l7 13H1L8 1zm0 4a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1zm1 7a1 1 0 10-2 0 1 1 0 002 0z"/></svg>
                            <span>Overdue</span>
                        </span>
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs bg-white/20 text-white ring-1 ring-white/40">{{ $countOverdue ?? 0 }}</span>
                    </div>
                    <div class="p-3 space-y-2 max-h-96 overflow-y-auto">
                        @forelse(($schedOverdue ?? []) as $it)
                            @php $due = $it['due']; @endphp
                            <div class="rounded-md p-2 bg-white shadow-sm ring-1 ring-red-200 border-l-4 border-red-400">
                                <div class="flex justify-between">
                                    <div class="text-sm font-semibold text-gray-900">{{ $due->format('Y-m-d') }}</div>
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs bg-red-100 text-red-700">Overdue</span>
                                </div>
                                <div class="text-xs text-gray-600">{{ $it['service']->service_type }}</div>
                                <div class="text-xs">
                                    <a href="{{ route('services.show', $it['service']) }}" class="text-indigo-600 hover:text-indigo-700">{{ $it['service']->reference_no }}</a> · {{ $it['service']->citizen_name }}
                                </div>
                                <div class="text-xs text-gray-700 mt-1">{{ $it['label'] }}</div>
                            </div>
                        @empty
                            <div class="text-xs text-gray-500">None</div>
                        @endforelse
                    </div>
                </div>
                <div class="rounded-md ring-1 ring-yellow-200 bg-gradient-to-b from-yellow-50 to-white">
                    <div class="px-3 py-2 bg-amber-500 text-white text-sm font-medium flex items-center justify-between">
                        <span class="inline-flex items-center gap-2">
                            <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path d="M8 3a5 5 0 015 5h2a7 7 0 10-7 7v-2a5 5 0 010-10z"/></svg>
                            <span>Today</span>
                        </span>
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs bg-white/20 text-white ring-1 ring-white/40">{{ $countToday ?? 0 }}</span>
                    </div>
                    <div class="p-3 space-y-2 max-h-96 overflow-y-auto">
                        @forelse(($schedToday ?? []) as $it)
                            @php $due = $it['due']; @endphp
                            <div class="rounded-md p-2 bg-white shadow-sm ring-1 ring-yellow-200 border-l-4 border-yellow-400">
                                <div class="flex justify-between">
                                    <div class="text-sm font-semibold text-gray-900">{{ $due->format('Y-m-d') }}</div>
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs bg-yellow-100 text-yellow-700">Due Today</span>
                                </div>
                                <div class="text-xs text-gray-600">{{ $it['service']->service_type }}</div>
                                <div class="text-xs">
                                    <a href="{{ route('services.show', $it['service']) }}" class="text-indigo-600 hover:text-indigo-700">{{ $it['service']->reference_no }}</a> · {{ $it['service']->citizen_name }}
                                </div>
                                <div class="text-xs text-gray-700 mt-1">{{ $it['label'] }}</div>
                            </div>
                        @empty
                            <div class="text-xs text-gray-500">None</div>
                        @endforelse
                    </div>
                </div>
                <div class="rounded-md ring-1 ring-green-200 bg-gradient-to-b from-green-50 to-white">
                    <div class="px-3 py-2 bg-emerald-600 text-white text-sm font-medium flex items-center justify-between">
                        <span class="inline-flex items-center gap-2">
                            <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path d="M3 2a1 1 0 011-1h2a1 1 0 011 1v1h4V2a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1H3a1 1 0 01-1-1V2zm2 5h6v2H5V7z"/></svg>
                            <span>Next 7 Days</span>
                        </span>
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs bg-white/20 text-white ring-1 ring-white/40">{{ $countWeek ?? 0 }}</span>
                    </div>
                    <div class="p-3 space-y-2 max-h-96 overflow-y-auto">
                        @forelse(($schedWeek ?? []) as $it)
                            @php $due = $it['due']; @endphp
                            <div class="rounded-md p-2 bg-white shadow-sm ring-1 ring-green-200 border-l-4 border-green-400">
                                <div class="flex justify-between">
                                    <div class="text-sm font-semibold text-gray-900">{{ $due->format('Y-m-d') }}</div>
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs bg-green-100 text-green-700">Due Soon</span>
                                </div>
                                <div class="text-xs text-gray-600">{{ $it['service']->service_type }}</div>
                                <div class="text-xs">
                                    <a href="{{ route('services.show', $it['service']) }}" class="text-indigo-600 hover:text-indigo-700">{{ $it['service']->reference_no }}</a> · {{ $it['service']->citizen_name }}
                                </div>
                                <div class="text-xs text-gray-700 mt-1">{{ $it['label'] }}</div>
                            </div>
                        @empty
                            <div class="text-xs text-gray-500">None</div>
                        @endforelse
                    </div>
                </div>
                <div class="rounded-md ring-1 ring-blue-200 bg-gradient-to-b from-blue-50 to-white">
                    <div class="px-3 py-2 bg-blue-600 text-white text-sm font-medium flex items-center justify-between">
                        <span class="inline-flex items-center gap-2">
                            <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path d="M2 3a1 1 0 011-1h10a1 1 0 011 1v10a1 1 0 01-1 1H3a1 1 0 01-1-1V3zm2 3h8v2H4V6zm0 3h8v2H4V9z"/></svg>
                            <span>Next 30 Days</span>
                        </span>
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs bg-white/20 text-white ring-1 ring-white/40">{{ $countMonth ?? 0 }}</span>
                    </div>
                    <div class="p-3 space-y-2 max-h-96 overflow-y-auto">
                        @forelse(($schedMonth ?? []) as $it)
                            @php $due = $it['due']; @endphp
                            <div class="rounded-md p-2 bg-white shadow-sm ring-1 ring-blue-200 border-l-4 border-blue-400">
                                <div class="flex justify-between">
                                    <div class="text-sm font-semibold text-gray-900">{{ $due->format('Y-m-d') }}</div>
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs bg-blue-100 text-blue-700">Scheduled</span>
                                </div>
                                <div class="text-xs text-gray-600">{{ $it['service']->service_type }}</div>
                                <div class="text-xs">
                                    <a href="{{ route('services.show', $it['service']) }}" class="text-blue-600 hover:text-blue-700">{{ $it['service']->reference_no }}</a> · {{ $it['service']->citizen_name }}
                                </div>
                                <div class="text-xs text-gray-700 mt-1">{{ $it['label'] }}</div>
                            </div>
                        @empty
                            <div class="text-xs text-gray-500">None</div>
                        @endforelse
                    </div>
                </div>
            </div>
            <style>
                .tw-surface{
                    background:
                     radial-gradient(circle at 20px 20px, rgba(99,102,241,.08) 2px, transparent 2px) 0 0/24px 24px,
                     linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
                }
            </style>
        </div>
    </div>
</x-app-layout>
