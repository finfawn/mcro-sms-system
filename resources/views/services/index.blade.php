<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="text-base font-semibold text-gray-900">Service List</h2>
            <a href="{{ route('services.create') }}" class="inline-flex items-center gap-2 px-3 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path d="M8 1a1 1 0 011 1v5h5a1 1 0 110 2H9v5a1 1 0 11-2 0V9H2a1 1 0 110-2h5V2a1 1 0 011-1z"/>
                </svg>
                <span>New Entry</span>
            </a>
        </div>
    </x-slot>
    <div class="py-4">
        <div class="max-w-7xl mx-auto px-4">
            @php($statuses = ['Filed','Processing','Endorsed','Released','Rejected'])
            <form id="bulkContextForm" action="{{ route('services.bulk-status') }}" method="POST" class="d-none">
                @csrf
                <input type="hidden" name="status" id="bulkContextStatus">
            </form>
            <div class="bg-white border rounded-md">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="w-9 px-3 py-2">
                                <input type="checkbox" id="select_all" class="rounded border-gray-300">
                            </th>
                            <th class="px-3 py-2 text-left text-sm font-medium text-gray-700">Reference No</th>
                            <th class="px-3 py-2 text-left text-sm font-medium text-gray-700">Citizen Name</th>
                            <th class="px-3 py-2 text-left text-sm font-medium text-gray-700">Mobile Number</th>
                            <th class="px-3 py-2 text-left text-sm font-medium text-gray-700">Category</th>
                            <th class="px-3 py-2 text-left text-sm font-medium text-gray-700">Service Type</th>
                            <th class="px-3 py-2 text-left text-sm font-medium text-gray-700">Status</th>
                            <th class="px-3 py-2 text-left text-sm font-medium text-gray-700">Filed</th>
                            <th class="px-3 py-2 text-right text-sm font-medium text-gray-700">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($services as $s)
                            <tr class="hover:bg-gray-50">
                                <td class="px-3 py-2">
                                    <input type="checkbox" name="ids[]" value="{{ $s->id }}" class="rounded border-gray-300 row-select">
                                </td>
                                <td class="px-3 py-2"><a href="{{ route('services.show', $s) }}" class="text-indigo-600 hover:text-indigo-700">{{ $s->reference_no }}</a></td>
                                <td class="px-3 py-2">{{ $s->citizen_name }}</td>
                                <td class="px-3 py-2">{{ $s->mobile_number }}</td>
                                <td class="px-3 py-2">{{ $s->category }}</td>
                                <td class="px-3 py-2">{{ $s->service_type }}</td>
                                <td class="px-3 py-2">
                                    <form action="{{ route('services.update-status', $s) }}" method="POST">
                                        @csrf
                                        @method('PUT')
                                        <select name="status" class="border-gray-300 rounded-md text-sm px-2 py-1" onchange="this.form.submit()">
                                            @foreach($statuses as $st)
                                                <option value="{{ $st }}" @selected($s->status===$st)>{{ $st }}</option>
                                            @endforeach
                                        </select>
                                    </form>
                                </td>
                                <td class="px-3 py-2">{{ $s->created_at->format('Y-m-d H:i') }}</td>
                                <td class="px-3 py-2 text-right">
                                    <a class="inline-flex items-center border rounded-md px-2 py-1 text-gray-700 hover:bg-gray-100" href="{{ route('services.edit', $s) }}" title="Edit" aria-label="Edit">
                                        <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                            <path d="M12.146 0.646a.5.5 0 01.708 0l2.5 2.5a.5.5 0 010 .708l-8.5 8.5-3 1a.5.5 0 01-.638-.638l1-3 8.5-8.5zM11.5 1.5l-8.5 8.5-.5 1.5 1.5-.5 8.5-8.5-1-1z"/>
                                        </svg>
                                    </a>
                                    <form action="{{ route('services.destroy', $s) }}" method="POST" class="inline"
                                          onsubmit="return confirm('Delete this service entry?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex items-center border rounded-md px-2 py-1 text-red-700 hover:bg-red-50" title="Delete" aria-label="Delete">
                                            <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                                <path d="M5.5 5.5a.5.5 0 01.5.5v6a.5.5 0 01-1 0v-6a.5.5 0 01.5-.5zm5 0a.5.5 0 01.5.5v6a.5.5 0 01-1 0v-6a.5.5 0 01.5-.5z"/>
                                                <path fill-rule="evenodd" d="M14.5 3a1 1 0 01-1 1H13v9a2 2 0 01-2 2H5a2 2 0 01-2-2V4H2.5a1 1 0 110-2H6a1 1 0 011-1h2a1 1 0 011 1h3.5a1 1 0 011 1zM4 4v9a1 1 0 001 1h6a1 1 0 001-1V4H4z"/>
                                            </svg>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-3 py-4 text-center text-gray-500">No services filed</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div id="bulkContextMenu" class="bg-white border rounded shadow-sm text-sm" style="position:fixed; display:none; z-index:1000; min-width: 200px;">
        <div class="px-3 py-2 text-gray-500">Set status</div>
        @foreach($statuses as $st)
            <button type="button" class="w-full text-left px-3 py-1 hover:bg-gray-100 context-action" data-status="{{ $st }}">{{ $st }}</button>
        @endforeach
        <div class="px-3 py-2 text-gray-500 hidden" id="noSelectionNote">Select rows first</div>
    </div>
    <script>
        (function(){
            var selectAll = document.getElementById('select_all');
            var rowCheckboxes = document.querySelectorAll('.row-select');
            if (selectAll) {
                selectAll.addEventListener('change', function() {
                    rowCheckboxes.forEach(function(cb){ cb.checked = selectAll.checked; });
                });
            }
            var tableWrapper = document.querySelector('.bg-white.border.rounded-md') || document.querySelector('.table-responsive');
            var menu = document.getElementById('bulkContextMenu');
            var note = document.getElementById('noSelectionNote');
            var bulkForm = document.getElementById('bulkContextForm');
            var statusField = document.getElementById('bulkContextStatus');
            function hideMenu(){ menu.style.display='none'; }
            document.addEventListener('click', hideMenu);
            document.addEventListener('keydown', function(e){ if(e.key==='Escape') hideMenu(); });
            if (tableWrapper) {
                tableWrapper.addEventListener('contextmenu', function(e){
                    e.preventDefault();
                    var selected = Array.prototype.slice.call(document.querySelectorAll('.row-select:checked'));
                    var hasSelection = selected.length > 0;
                    note.classList.toggle('hidden', hasSelection);
                    var rect = tableWrapper.getBoundingClientRect();
                    var x = e.clientX, y = e.clientY;
                    var maxX = window.innerWidth - 220;
                    var maxY = window.innerHeight - 200;
                    menu.style.left = Math.min(x, maxX) + 'px';
                    menu.style.top = Math.min(y, maxY) + 'px';
                    menu.style.display = 'block';
                });
            }
            var actionButtons = document.querySelectorAll('.context-action');
            actionButtons.forEach(function(btn){
                btn.addEventListener('click', function(){
                    var selected = Array.prototype.slice.call(document.querySelectorAll('.row-select:checked'));
                    if (selected.length === 0) {
                        return;
                    }
                    var existing = bulkForm.querySelectorAll('input[name="ids[]"]');
                    existing.forEach(function(el){ el.remove(); });
                    selected.forEach(function(cb){
                        var hidden = document.createElement('input');
                        hidden.type = 'hidden';
                        hidden.name = 'ids[]';
                        hidden.value = cb.value;
                        bulkForm.appendChild(hidden);
                    });
                    statusField.value = btn.getAttribute('data-status');
                    bulkForm.submit();
                    hideMenu();
                });
            });
        })();
    </script>
</x-app-layout>
