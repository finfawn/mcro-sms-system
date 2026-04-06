<x-app-layout>
    <div class="py-4">
        <div class="max-w-7xl mx-auto px-4">
            <div class="mb-4 rounded-xl overflow-hidden bg-white text-gray-900 shadow ring-1 ring-gray-200 relative">
                <div class="absolute inset-y-0 left-0 w-2 bg-blue-600"></div>
                <img src="{{ asset('logo/MCR TUBLAY LOGO..png') }}" alt="" class="hidden md:block absolute right-6 top-1/2 -translate-y-1/2 h-16 w-16 opacity-10 pointer-events-none">
                <div class="p-4 md:p-6 md:pl-8 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <x-application-logo class="h-12 w-auto rounded-full ring-2 ring-indigo-200 bg-white" />
                        <div>
                            <div class="text-xs text-gray-500">MCRO SMS Notification</div>
                            <div class="text-2xl font-semibold">Operational Dashboard</div>
                            <div class="text-xs text-gray-500">Real-time status and upcoming schedules</div>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 md:gap-6 w-full md:w-auto">
                        <div class="text-center bg-blue-600 text-white rounded-md px-4 py-2">
                            <div class="flex items-center justify-center gap-2 text-xs">
                                <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path d="M8 1a1 1 0 011 1v2h2a1 1 0 110 2H9v2a1 1 0 11-2 0V6H5a1 1 0 110-2h2V2a1 1 0 011-1z"/></svg>
                                <span>Messages sent today</span>
                            </div>
                            <div class="text-2xl font-bold">{{ $messagesToday ?? 0 }}</div>
                        </div>
                        <div class="text-center bg-blue-600 text-white rounded-md px-4 py-2">
                            <div class="flex items-center justify-center gap-2 text-xs">
                                <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path d="M2 3a1 1 0 011-1h10a1 1 0 011 1v10a1 1 0 01-1 1H3a1 1 0 01-1-1V3zm3 2a1 1 0 000 2h6a1 1 0 100-2H5z"/></svg>
                                <span>Messages sent (last 30 days)</span>
                            </div>
                            <div class="text-2xl font-bold">{{ $messages30Days ?? 0 }}</div>
                        </div>
                        <div class="text-center bg-blue-600 text-white rounded-md px-4 py-2">
                            <div class="flex items-center justify-center gap-2 text-xs">
                                <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path d="M2 2h12v3H2V2zm0 5h12v3H2V7zm0 5h12v2H2v-2z"/></svg>
                                <span>Total messages sent</span>
                            </div>
                            <div class="text-2xl font-bold">{{ $messagesTotal ?? 0 }}</div>
                        </div>
                        
                    </div>
                </div>
            </div>
            
            <div class="mb-2 px-4">
                <form method="GET" action="{{ route('dashboard') }}" class="flex items-center justify-end gap-2" id="dash_filter_form">
                    <label for="dash_service_select" class="sr-only">Service Type</label>
                    <select name="service_type" id="dash_service_select" class="border-gray-300 rounded-md text-xs h-8 px-2 w-64">
                        <option value="">All</option>
                        @if(isset($types))
                            @foreach($types as $t)
                                <option value="{{ $t }}" @selected(($selectedType ?? '') === $t)>{{ $t }}</option>
                            @endforeach
                        @endif
                    </select>
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
            <div class="px-4 pb-6">
                <div class="bg-white border rounded-md">
                    <div class="px-4 py-3 border-b flex items-center justify-between">
                        <div class="text-sm font-medium text-gray-900">Recent SMS</div>
                        <div class="flex items-center gap-3">
                            <div class="text-xs text-gray-500">
                                @if(isset($recentSms) && method_exists($recentSms, 'total') && $recentSms->total() > 0)
                                    Showing {{ $recentSms->firstItem() }}–{{ $recentSms->lastItem() }} of {{ $recentSms->total() }}
                                @else
                                    {{ ($recentSms ?? collect())->count() }} messages
                                @endif
                            </div>
                            @if((Auth::user()->role ?? 'user') === 'admin')
                                <form id="dashClearSmsForm" method="POST" action="{{ route('dashboard.clear-sms') }}">
                                    @csrf
                                    <button type="submit" class="text-xs text-red-600 hover:text-red-700">Clear History</button>
                                </form>
                            @endif
                        </div>
                    </div>
                    <div class="p-2 overflow-x-auto overflow-y-auto relative max-h-[60vh] hidden md:block">
                        <table class="min-w-full">
                            <thead class="bg-gray-50 sticky top-0 z-10">
                                <tr class="text-xs text-gray-500">
                                    <th class="px-3 py-2 text-left">Date</th>
                                    <th class="px-3 py-2 text-left">Recipient</th>
                                    <th class="px-3 py-2 text-left">Service</th>
                                    <th class="px-3 py-2 text-left">Event</th>
                                    <th class="px-3 py-2 text-left">Provider</th>
                                    <th class="px-3 py-2 text-left">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse(($recentSms ?? []) as $m)
                                    <tr class="text-sm">
                                        <td class="px-3 py-2 text-gray-700">{{ $m->created_at->format('Y-m-d H:i') }}</td>
                                        <td class="px-3 py-2">
                                            @php
                                                $num = $m->to ?? '';
                                                $digits = preg_replace('/\D+/', '', $num);
                                                $prefix = str_starts_with($num, '+') ? '+' : '';
                                                $len = strlen($digits);
                                                $first = substr($digits, 0, min(2, $len));
                                                $last = $len >= 3 ? substr($digits, $len - 3, 3) : substr($digits, -$len);
                                                $middleLen = max(0, $len - strlen($first) - strlen($last));
                                                $masked = $prefix.$first.($middleLen ? str_repeat('•', $middleLen) : '').$last;
                                            @endphp
                                            <div class="text-gray-900">{{ $masked }}</div>
                                        </td>
                                        <td class="px-3 py-2">
                                            @if($m->service)
                                                <div class="text-gray-900">{{ $m->service->reference_no }}</div>
                                                <div class="text-xs text-gray-500">{{ $m->service->citizen_name }}</div>
                                                <div class="text-xs text-gray-700">{{ $m->service->service_type }}</div>
                                            @else
                                                <div class="text-gray-500">—</div>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 text-gray-700">{{ $m->event_key ?? '—' }}</td>
                                        <td class="px-3 py-2 text-gray-700">{{ strtoupper($m->provider) }}</td>
                                        <td class="px-3 py-2">
                                            @php
                                                $prov = strtoupper($m->provider ?? '');
                                                $st = $m->status ?? '';
                                                $label = 'Failed';
                                                $cls = 'bg-red-100 text-red-700';
                                                if ($prov === 'LOG' || $st === 'mock') { $label = 'Simulated'; $cls = 'bg-gray-100 text-gray-700'; }
                                                elseif ($st === 'dispatched' || $st === 'queued') { $label = 'Dispatched'; $cls = 'bg-yellow-100 text-yellow-700'; }
                                                elseif ($st === 'sent') { $label = 'Sent'; $cls = 'bg-green-100 text-green-700'; }
                                            @endphp
                                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs {{ $cls }}">{{ $label }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-3 py-3 text-sm text-gray-500">No SMS activity yet</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="p-2 md:hidden space-y-2">
                        @forelse(($recentSms ?? []) as $m)
                                @php
                                    $num = $m->to ?? '';
                                    $digits = preg_replace('/\D+/', '', $num);
                                    $prefix = str_starts_with($num, '+') ? '+' : '';
                                    $len = strlen($digits);
                                    $first = substr($digits, 0, min(2, $len));
                                    $last = $len >= 3 ? substr($digits, $len - 3, 3) : substr($digits, -$len);
                                    $middleLen = max(0, $len - strlen($first) - strlen($last));
                                    $masked = $prefix.$first.($middleLen ? str_repeat('•', $middleLen) : '').$last;
                                    $prov = strtoupper($m->provider ?? '');
                                    $st = $m->status ?? '';
                                    $label = 'Failed';
                                    $cls = 'bg-red-100 text-red-700';
                                    if ($prov === 'LOG' || $st === 'mock') { $label = 'Simulated'; $cls = 'bg-gray-100 text-gray-700'; }
                                    elseif ($st === 'dispatched' || $st === 'queued') { $label = 'Dispatched'; $cls = 'bg-yellow-100 text-yellow-700'; }
                                    elseif ($st === 'sent') { $label = 'Sent'; $cls = 'bg-green-100 text-green-700'; }
                                @endphp
                            <div class="bg-white border rounded-md p-3">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <div class="text-sm text-gray-900">{{ $masked }}</div>
                                        @if($m->service)
                                            <div class="text-xs text-gray-500">{{ $m->service->reference_no }}</div>
                                            <div class="text-xs text-gray-700">{{ $m->service->service_type }}</div>
                                        @endif
                                    </div>
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs {{ $cls }}">{{ $label }}</span>
                                </div>
                                <div class="mt-1 text-xs text-gray-500">{{ $m->created_at->format('Y-m-d H:i') }}</div>
                                <div class="mt-1 text-xs text-gray-700">{{ strtoupper($m->provider) }} · {{ $m->event_key ?? '—' }}</div>
                            </div>
                        @empty
                            <div class="bg-white border rounded-md p-3 text-sm text-gray-500">No SMS activity yet</div>
                        @endforelse
                    </div>
                    @if(isset($recentSms) && method_exists($recentSms, 'hasPages') && $recentSms->hasPages())
                    <div class="mt-3 px-4 pb-3 flex flex-wrap items-center justify-between gap-2 border-t pt-3">
                        <div class="text-sm text-gray-600">
                            Page {{ $recentSms->currentPage() }} of {{ $recentSms->lastPage() }}
                        </div>
                        <nav class="flex items-center gap-1" aria-label="SMS history pagination">
                            @if ($recentSms->onFirstPage())
                                <span class="inline-flex items-center px-3 py-1.5 border border-gray-200 rounded-md text-gray-400 cursor-not-allowed text-sm">Previous</span>
                            @else
                                <a href="{{ $recentSms->withQueryString()->previousPageUrl() }}" class="inline-flex items-center px-3 py-1.5 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 text-sm">Previous</a>
                            @endif
                            @if ($recentSms->hasMorePages())
                                <a href="{{ $recentSms->withQueryString()->nextPageUrl() }}" class="inline-flex items-center px-3 py-1.5 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 text-sm">Next</a>
                            @else
                                <span class="inline-flex items-center px-3 py-1.5 border border-gray-200 rounded-md text-gray-400 cursor-not-allowed text-sm">Next</span>
                            @endif
                        </nav>
                    </div>
                    @endif
                </div>
            </div>
            <div id="twClearSmsModal" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true" aria-labelledby="twClearSmsTitle">
                <div class="absolute inset-0 bg-black/30"></div>
                <div class="relative max-w-md mx-auto mt-24 bg-white rounded-lg shadow ring-1 ring-gray-200">
                    <div class="px-4 py-3 border-b">
                        <div id="twClearSmsTitle" class="text-sm font-medium text-gray-900">Clear SMS History</div>
                    </div>
                    <div class="px-4 py-3">
                        <div class="text-sm text-gray-700">This will remove all SMS history records. This action cannot be undone.</div>
                    </div>
                    <div class="px-4 py-3 border-t flex justify-end gap-2">
                        <button type="button" id="twClearSmsCancel" class="inline-flex items-center px-3 py-2 border rounded-md text-gray-700 hover:bg-gray-100">Cancel</button>
                        <button type="button" id="twClearSmsConfirm" class="inline-flex items-center px-3 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">Clear</button>
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
    <script>
        (function(){
            var form = document.getElementById('dashClearSmsForm');
            var modal = document.getElementById('twClearSmsModal');
            var btnCancel = document.getElementById('twClearSmsCancel');
            var btnConfirm = document.getElementById('twClearSmsConfirm');
            function openModal(){ if (modal) modal.classList.remove('hidden'); }
            function closeModal(){ if (modal) modal.classList.add('hidden'); }
            if (form) {
                form.addEventListener('submit', function(e){
                    e.preventDefault();
                    openModal();
                });
            }
            if (btnCancel) btnCancel.addEventListener('click', closeModal);
            if (btnConfirm) btnConfirm.addEventListener('click', function(){
                closeModal();
                var csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                var fd = new FormData(form);
                fetch(form.action, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'text/html' }
                }).then(function(res){
                    if (res.status === 419) { location.reload(); return; }
                    if (window.twShowToast) window.twShowToast('SMS history cleared');
                    location.href = "{{ route('dashboard') }}";
                }).catch(function(){
                    if (window.twShowToast) window.twShowToast('Clear failed');
                });
            });
        })();
    </script>
</x-app-layout>
