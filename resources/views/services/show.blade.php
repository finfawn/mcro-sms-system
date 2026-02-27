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
        <div class="max-w-4xl mx-auto px-4">
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
                <div>
                    <div class="text-sm font-medium text-gray-500">Notes</div>
                    <div id="svcNotesDisplay" class="border rounded-md p-2 bg-gray-50 text-gray-900">{{ $service->notes ?? '—' }}</div>
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
