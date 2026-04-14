<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="text-base font-semibold text-gray-900">Service Details</h2>
            <div class="flex gap-2">
                <a href="{{ route('services.edit', $service) }}" class="inline-flex items-center px-3 py-2 border rounded-md text-gray-700 hover:bg-gray-100">Edit</a>
                <a href="{{ route('services.index') }}" class="inline-flex items-center px-3 py-2 border rounded-md text-gray-700 hover:bg-gray-100">Back to List</a>
            </div>
        </div>
    </x-slot>
    <div class="py-4">
        <div class="app-shell-narrow">
            <div class="bg-white border rounded-md p-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <div class="text-sm font-medium text-gray-500">Reference No</div>
                        <div class="text-gray-900">{{ $service->reference_no }}</div>
                    </div>
                    <div>
                        <div class="text-sm font-medium text-gray-500">Status</div>
                        <div id="svcStatusDisplay" class="text-gray-900">{{ $service->status }}</div>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <div class="text-sm font-medium text-gray-500">Citizen Name</div>
                        <div class="text-gray-900">{{ $service->citizen_name }}</div>
                    </div>
                    <div>
                        <div class="text-sm font-medium text-gray-500">Mobile Number</div>
                        @php
                            $num = $service->mobile_number ?? '';
                            $digits = preg_replace('/\D+/', '', $num);
                            $prefix = str_starts_with($num, '+') ? '+' : '';
                            $len = strlen($digits);
                            $first = substr($digits, 0, min(2, $len));
                            $last = $len >= 3 ? substr($digits, $len - 3, 3) : substr($digits, -$len);
                            $middleLen = max(0, $len - strlen($first) - strlen($last));
                            $masked = $prefix.$first.($middleLen ? str_repeat('•', $middleLen) : '').$last;
                        @endphp
                        <div class="text-gray-900">{{ $masked }}</div>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <div class="text-sm font-medium text-gray-500">Service Type</div>
                        <div class="text-gray-900">{{ $service->service_type }}</div>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <div class="text-sm font-medium text-gray-500">Filed</div>
                        <div class="text-gray-900">{{ $service->created_at->format('Y-m-d H:i') }}</div>
                    </div>
                    <div>
                        <div class="text-sm font-medium text-gray-500">Last Updated</div>
                        <div id="svcUpdatedAtDisplay" class="text-gray-900">{{ $service->updated_at->format('Y-m-d H:i') }}</div>
                    </div>
                </div>
                <div class="mb-4">
                    <div class="text-sm font-medium text-gray-500">Notes</div>
                    <div id="svcNotesDisplay" class="border rounded-md p-2 bg-gray-50 text-gray-900">{{ $service->notes ?? '—' }}</div>
                </div>
                @php
                    $timelineStatusColor = [
                        'Filed' => '#6b7280', 'Processing' => '#3b82f6', 'Endorsed' => '#0ea5e9', 'Released' => '#22c55e', 'Rejected' => '#ef4444',
                        'Paid' => '#6366f1', 'Posted' => '#f59e0b', 'Under Verification' => '#eab308', 'Consistent' => '#10b981', 'Inconsistent' => '#f97316',
                        'Ready for Release' => '#14b8a6', 'Ready for Pickup' => '#14b8a6', 'Authenticated' => '#64748b', 'Form Filled' => '#64748b',
                        'Submitted' => '#8b5cf6', 'Claim Stub Issued' => '#06b6d4', 'Sent to PSA' => '#a855f7', 'PSA Has Feedback' => '#f59e0b',
                        'Reworked and Resent' => '#3b82f6', 'PSA Successfully Uploaded' => '#22c55e', 'PSA No Feedback' => '#22c55e',
                        'Drafted' => '#6b7280', 'For Filing' => '#3b82f6', 'Affirmed' => '#22c55e', 'Impugned' => '#ef4444', 'Published' => '#f59e0b', 'Decision Rendered' => '#6366f1',
                    ];
                @endphp
                <div>
                    <div class="text-sm font-semibold text-gray-800 mb-3">Timeline</div>
                    <div class="rounded-lg border border-gray-200 bg-white overflow-hidden">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-0">
                            @forelse($service->statusLogs as $log)
                                @php $accent = $timelineStatusColor[$log->status] ?? '#9ca3af'; @endphp
                                <div class="flex gap-3 px-3 py-2.5 hover:bg-gray-50/80 transition-colors border-l-4 border-b border-gray-100 last:border-b-0" style="border-left-color: {{ $accent }};">
                                    <span class="rounded-full flex-shrink-0 mt-1.5 w-2.5 h-2.5 ring-4 ring-white shadow-sm" style="background-color: {{ $accent }};"></span>
                                    <div class="min-w-0 flex-1">
                                        <div class="font-medium text-gray-900">{{ $log->status }}</div>
                                        <div class="text-xs text-gray-500 mt-0.5">{{ $log->created_at->format('M j, Y · H:i') }}</div>
                                        <div class="text-xs text-gray-400 mt-0.5">{{ $log->user ? $log->user->name : 'System' }}</div>
                                    </div>
                                </div>
                            @empty
                                <div class="px-3 py-4 text-sm text-gray-500 border-l-4 border-gray-200 col-span-full">No activity yet</div>
                            @endforelse
                            @if($service->posting_start_date)
                                <div class="flex gap-3 px-3 py-2.5 hover:bg-gray-50/80 transition-colors border-l-4 border-b border-gray-100 last:border-b-0" style="border-left-color: #9ca3af;">
                                    <span class="rounded-full flex-shrink-0 mt-1.5 w-2.5 h-2.5 ring-4 ring-white bg-gray-400 shadow-sm"></span>
                                    <div class="min-w-0 flex-1">
                                        <div class="font-medium text-gray-500">Ready For Release (estimated)</div>
                                        <div class="text-xs text-gray-500 mt-0.5">{{ $service->posting_start_date->copy()->addWeekdays(10)->format('M j, Y') }}</div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        (function(){
            var statusEl = document.getElementById('svcStatusDisplay');
            var notesEl = document.getElementById('svcNotesDisplay');
            var updatedEl = document.getElementById('svcUpdatedAtDisplay');
            function refresh(){
                fetch("{{ route('services.show', $service) }}", { headers: { 'Accept': 'text/html' } })
                    .then(function(res){
                        if (res.status === 419) { location.reload(); return Promise.reject(); }
                        return res.text();
                    }).then(function(html){
                        var doc = new DOMParser().parseFromString(html, 'text/html');
                        var ns = doc.getElementById('svcNotesDisplay');
                        var ss = doc.getElementById('svcStatusDisplay');
                        var us = doc.getElementById('svcUpdatedAtDisplay');
                        if (ns && notesEl) notesEl.innerHTML = ns.innerHTML;
                        if (ss && statusEl) statusEl.textContent = ss.textContent;
                        if (us && updatedEl) updatedEl.textContent = us.textContent;
                    });
            }
            document.addEventListener('visibilitychange', function(){
                if (!document.hidden) refresh();
            });
            setInterval(refresh, 20000);
        })();
    </script>
</x-app-layout>
