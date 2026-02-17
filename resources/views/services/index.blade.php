<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="text-base font-semibold text-gray-900">Service List</h2>
            <div class="flex items-center gap-2 w-full">
                <div class="flex-1 hidden sm:flex justify-center">
                    <form method="GET" action="{{ route('services.index') }}" class="flex items-center w-full justify-center">
                        <input type="hidden" name="service_type" value="{{ $serviceType ?? '' }}">
                        <input type="hidden" name="status" value="{{ $status ?? '' }}">
                        <input type="hidden" name="sort" value="{{ $sort ?? 'updated' }}">
                        <input type="hidden" name="direction" value="{{ $direction ?? 'desc' }}">
                        <input type="text" name="name" value="{{ $name ?? '' }}" class="border-gray-300 rounded-md w-full max-w-md text-center" placeholder="Search name or reference" id="header_search">
                    </form>
                </div>
                <a href="{{ route('services.create') }}" class="inline-flex items-center gap-2 px-3 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M8 1a1 1 0 011 1v5h5a1 1 0 110 2H9v5a1 1 0 11-2 0V9H2a1 1 0 110-2h5V2a1 1 0 011-1z"/>
                    </svg>
                    <span>New Entry</span>
                </a>
            </div>
        </div>
    </x-slot>
    
    <div class="py-4">
        <div class="max-w-7xl mx-auto px-4">
            @php
                $defaultStatuses = ['Filed','Processing','Endorsed','Released','Rejected'];
                $mlStatuses = ['Filed','Paid','Posted','Released'];
                $delayedStatuses = ['Filed','Under Verification','Consistent','Inconsistent','Posted','Ready for Release','Released','Rejected'];
            @endphp
            
            <div class="bg-white border rounded-md mb-3">
                <form id="filter_form" method="GET" action="{{ route('services.index') }}" class="px-4 py-3 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="flex flex-col md:flex-row gap-4 w-full md:w-auto">
                        <div class="w-full md:w-48">
                            <label class="block text-xs text-gray-500 mb-1">Service Type</label>
                            <select name="service_type" id="filter_service_type" class="border-gray-300 rounded-md w-full text-sm">
                                <option value="">All</option>
                                @if(isset($types))
                                    @foreach($types as $t)
                                        <option value="{{ $t }}" @selected(($serviceType ?? '') === $t)>{{ $t }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div class="w-full md:w-48">
                        <label class="block text-xs text-gray-500 mb-1">Status</label>
                        <select name="status" id="filter_status" class="border-gray-300 rounded-md w-full opacity-50" disabled data-selected="{{ $status ?? '' }}">
                                <option value="">Select service type</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="flex flex-col md:flex-row gap-4 w-full md:w-auto">
                        <div class="w-full md:w-40">
                            <label class="block text-xs text-gray-500 mb-1">Sort By</label>
                            <select name="sort" class="border-gray-300 rounded-md w-full text-sm">
                                <option value="updated" @selected(($sort ?? '')==='updated')>Last Updated</option>
                                <option value="name" @selected(($sort ?? '')==='name')>Alphabetical</option>

                            </select>
                        </div>
                        <div class="w-full md:w-32">
                            <label class="block text-xs text-gray-500 mb-1">Order</label>
                            <select name="direction" class="border-gray-300 rounded-md w-full text-sm">
                                <option value="asc" @selected(($direction ?? '')==='asc')>Ascending</option>
                                <option value="desc" @selected(($direction ?? 'desc')==='desc')>Descending</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
            
            <style>
                /* Make all status dropdowns in the table the same width */
                table select[name="status"] {
                    width: 130px;
                    min-width: 130px;
                    max-width: 130px;
                    transition: box-shadow 150ms ease, background-color 150ms ease, transform 100ms ease;
                }
                table select[name="status"]:focus {
                    transform: scale(1.01);
                    background-color: #f8fafc;
                }
                #filter_status {
                    transition: opacity 200ms ease;
                }
                .details-content {
                    overflow: hidden;
                    height: 0;
                    opacity: 0;
                    transition: height 220ms ease, opacity 220ms ease;
                }
            </style>
            
            <form id="bulkContextForm" action="{{ route('services.bulk-status') }}" method="POST" class="d-none">
                @csrf
                <input type="hidden" name="status" id="bulkContextStatus">
            </form>
            
            <div id="services_table_container" class="bg-white border rounded-md">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="w-9 px-3 py-2">
                                <input type="checkbox" id="select_all" class="rounded border-gray-300">
                            </th>
                            <th class="px-3 py-2 text-left text-sm font-medium text-gray-700">Reference No</th>
                            <th class="px-3 py-2 text-left text-sm font-medium text-gray-700">Citizen Name</th>
                            <th class="px-3 py-2 text-left text-sm font-medium text-gray-700">Service Type</th>
                            <th class="px-3 py-2 text-left text-sm font-medium text-gray-700">Status</th>
                            <th class="px-3 py-2 text-right text-sm font-medium text-gray-700">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($services as $s)
                            @php
                                $rowStatuses = ['Filed','Processing','Endorsed','Released','Rejected'];
                                if ($s->service_type === 'Application for Marriage License') {
                                    $rowStatuses = ['Filed','Paid','Posted','Released'];
                                } elseif ($s->service_type === 'Delayed Registration') {
                                    $rowStatuses = $delayedStatuses;
                                }
                                $currentIndex = array_search($s->status, $rowStatuses);
                            @endphp
                            <tr class="hover:bg-gray-50 svc-row cursor-pointer"
                                data-ref="{{ $s->reference_no }}"
                                data-name="{{ $s->citizen_name }}"
                                data-type="{{ $s->service_type }}"
                                data-status="{{ $s->status }}"
                                data-filed="{{ $s->created_at->format('Y-m-d H:i') }}"
                                data-paid="{{ $s->payment_date ? $s->payment_date->toDateString() : '' }}"
                                data-posted="{{ $s->posting_start_date ? $s->posting_start_date->toDateString() : '' }}"
                                data-ready="{{ $s->posting_start_date ? $s->posting_start_date->copy()->addDays(10)->toDateString() : '' }}"
                                data-released="{{ $s->release_date ? $s->release_date->toDateString() : '' }}"
                                data-notes="{{ $s->notes ?? '' }}"
                            >
                                <td class="px-3 py-2">
                                    <input type="checkbox" name="ids[]" value="{{ $s->id }}" class="rounded border-gray-300 row-select">
                                </td>
                                <td class="px-3 py-2">
                                    <a href="{{ route('services.show', $s) }}" class="text-indigo-600 hover:text-indigo-700">{{ $s->reference_no }}</a>
                                </td>
                                <td class="px-3 py-2">{{ $s->citizen_name }}</td>
                                <td class="px-3 py-2">{{ $s->service_type }}</td>
                                <td class="px-3 py-2">
                                    <form action="{{ route('services.update-status', $s) }}" method="POST">
                                        @csrf
                                        @method('PUT')
                                        @php
                                            $rowStatuses = $s->service_type === 'Application for Marriage License' ? $mlStatuses : $defaultStatuses;
                                        @endphp
                                        <select name="status" class="border-gray-300 rounded-md text-sm px-2 py-1" onchange="this.form.submit()">
                                            @foreach($rowStatuses as $st)
                                                @php
                                                    $idx = array_search($st, $rowStatuses);
                                                    $disabled = ($idx !== false && $currentIndex !== false && $idx < $currentIndex);
                                                @endphp
                                                <option value="{{ $st }}" @selected($s->status === $st) @if($disabled) disabled @endif>{{ $st }}</option>
                                            @endforeach
                                        </select>
                                    </form>
                                </td>
                                <td class="px-3 py-2 text-right">
                                    <a class="tw-pressable inline-flex items-center border rounded-md px-2 py-1 text-gray-700 hover:bg-gray-100" href="{{ route('services.edit', $s) }}" title="Edit" aria-label="Edit">
                                        <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                            <path d="M12.146 0.646a.5.5 0 01.708 0l2.5 2.5a.5.5 0 010 .708l-8.5 8.5-3 1a.5.5 0 01-.638-.638l1-3 8.5-8.5zM11.5 1.5l-8.5 8.5-.5 1.5 1.5-.5 8.5-8.5-1-1z"/>
                                        </svg>
                                    </a>
                                    <form action="{{ route('services.destroy', $s) }}" method="POST" class="inline svc-delete-form" onsubmit="return confirm('Delete this service entry?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="tw-pressable inline-flex items-center border rounded-md px-2 py-1 text-red-700 hover:bg-red-50" title="Delete" aria-label="Delete">
                                            <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                                <path d="M5.5 5.5a.5.5 0 01.5.5v6a.5.5 0 01-1 0v-6a.5.5 0 01.5-.5zm5 0a.5.5 0 01.5.5v6a.5.5 0 01-1 0v-6a.5.5 0 01.5-.5z"/>
                                                <path fill-rule="evenodd" d="M14.5 3a1 1 0 01-1 1H13v9a2 2 0 01-2 2H5a2 2 0 01-2-2V4H2.5a1 1 0 110-2H6a1 1 0 011-1h2a1 1 0 011 1h3.5a1 1 0 011 1zM4 4v9a1 1 0 001 1h6a1 1 0 001-1V4H4z"/>
                                            </svg>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <tr class="details-row hidden">
                                <td colspan="6" class="bg-gray-50">
                                    <div class="p-3 details-content">
                                        <div class="text-sm font-medium text-gray-700 mb-2">Timeline</div>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm">
                                            <div>
                                                <div class="text-gray-500">Filed</div>
                                                <div class="text-gray-900">{{ $s->created_at->format('Y-m-d H:i') }}</div>
                                            </div>
                                            <div>
                                                <div class="text-gray-500">Paid</div>
                                                <div class="text-gray-900">
                                                    {{ $s->payment_date ? $s->payment_date->format('Y-m-d') : '—' }}
                                                </div>
                                            </div>
                                            <div>
                                                <div class="text-gray-500">Posted</div>
                                                <div class="text-gray-900">
                                                    {{ $s->posting_start_date ? $s->posting_start_date->format('Y-m-d') : '—' }}
                                                </div>
                                            </div>
                                            <div>
                                                <div class="text-gray-500">Ready For Release (estimated)</div>
                                                <div class="text-gray-900">
                                                    {{ $s->posting_start_date ? $s->posting_start_date->copy()->addDays(10)->format('Y-m-d') : '—' }}
                                                </div>
                                            </div>
                                            <div>
                                                <div class="text-gray-500">Released</div>
                                                <div class="text-gray-900">
                                                    {{ $s->release_date ? $s->release_date->format('Y-m-d') : '—' }}
                                                </div>
                                            </div>
                                            <div class="md:col-span-2">
                                                <div class="text-gray-500">Notes</div>
                                                <div class="text-gray-900">{{ $s->notes ?? '—' }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-3 py-4 text-center text-gray-500">No services filed</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div id="rowDetailsPanel" class="bg-white border rounded-md mt-3 p-4 hidden">
                <div class="text-sm font-medium text-gray-700 mb-2">Selected Entry Details</div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <div class="text-gray-500">Reference No</div>
                        <div id="detailsRef" class="text-gray-900"></div>
                    </div>
                    <div>
                        <div class="text-gray-500">Citizen Name</div>
                        <div id="detailsName" class="text-gray-900"></div>
                    </div>
                    <div>
                        <div class="text-gray-500">Service Type</div>
                        <div id="detailsType" class="text-gray-900"></div>
                    </div>
                    <div>
                        <div class="text-gray-500">Current Status</div>
                        <div id="detailsStatus" class="text-gray-900"></div>
                    </div>
                    <div>
                        <div class="text-gray-500">Filed At</div>
                        <div id="detailsFiled" class="text-gray-900"></div>
                    </div>
                    <div>
                        <div class="text-gray-500">Paid At</div>
                        <div id="detailsPaid" class="text-gray-900"></div>
                    </div>
                    <div>
                        <div class="text-gray-500">Posted At</div>
                        <div id="detailsPosted" class="text-gray-900"></div>
                    </div>
                    <div>
                        <div class="text-gray-500">Ready For Release</div>
                        <div id="detailsReady" class="text-gray-900"></div>
                    </div>
                    <div>
                        <div class="text-gray-500">Released At</div>
                        <div id="detailsReleased" class="text-gray-900"></div>
                    </div>
                    <div class="md:col-span-2">
                        <div class="text-gray-500">Remarks</div>
                        <div id="detailsRemarks" class="text-gray-900"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div id="bulkContextMenu" class="bg-white border rounded shadow-sm text-sm" style="position:fixed; display:none; z-index:1000; min-width: 200px;">
        <div class="px-3 py-2 text-gray-500">Set status</div>
        @foreach($defaultStatuses as $st)
            <button type="button" class="w-full text-left px-3 py-1 hover:bg-gray-100 context-action" data-status="{{ $st }}">{{ $st }}</button>
        @endforeach
        <div class="px-3 py-2 text-gray-500 hidden" id="noSelectionNote">Select rows first</div>
    </div>
    
    <script>
        (function(){
            function bindSelectAll(){
                var selectAll = document.getElementById('select_all');
                var rowCheckboxes = document.querySelectorAll('.row-select');
                if (selectAll) {
                    selectAll.addEventListener('change', function() {
                        rowCheckboxes.forEach(function(cb) { 
                            cb.checked = selectAll.checked; 
                        });
                    });
                }
            }
            bindSelectAll();
            
            var tableWrapper = document.getElementById('services_table_container') || document.querySelector('.table-responsive');
            var menu = document.getElementById('bulkContextMenu');
            var note = document.getElementById('noSelectionNote');
            var bulkForm = document.getElementById('bulkContextForm');
            var statusField = document.getElementById('bulkContextStatus');
            
            function hideMenu() { 
                menu.style.display = 'none'; 
            }
            
            document.addEventListener('click', hideMenu);
            document.addEventListener('keydown', function(e) { 
                if(e.key === 'Escape') hideMenu(); 
            });
            
            if (tableWrapper) {
                tableWrapper.addEventListener('contextmenu', function(e){
                    e.preventDefault();
                    
                    var selected = Array.prototype.slice.call(document.querySelectorAll('.row-select:checked'));
                    var hasSelection = selected.length > 0;
                    
                    note.classList.toggle('hidden', hasSelection);
                    
                    var x = e.clientX;
                    var y = e.clientY;
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
                    existing.forEach(function(el) { 
                        el.remove(); 
                    });
                    
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
            rebindRowClicks();
            var typeSelect = document.getElementById('filter_service_type');
            var statusSelect = document.getElementById('filter_status');
            var sortSelect = document.querySelector('select[name="sort"]');
            var directionSelect = document.querySelector('select[name="direction"]');
            var tableContainer = document.getElementById('services_table_container');
            var STATUS_MAP = {
                'Application for Marriage License': ['Filed','Paid','Posted','Released'],
                'Delayed Registration': ['Filed','Under Verification','Consistent','Inconsistent','Posted','Ready for Release','Released','Rejected']
            };
            var DEFAULT_STATUSES = ['Filed','Processing','Endorsed','Released','Rejected'];
            function populateStatuses(){
                var type = typeSelect ? typeSelect.value : '';
                var selected = statusSelect ? statusSelect.getAttribute('data-selected') : '';
                statusSelect.disabled = true;
                statusSelect.classList.add('opacity-50');
                while (statusSelect.firstChild) statusSelect.removeChild(statusSelect.firstChild);
                if (!type) {
                    var opt = document.createElement('option');
                    opt.value = '';
                    opt.textContent = 'Select service type';
                    statusSelect.appendChild(opt);
                    return;
                }
                var list = STATUS_MAP[type] || DEFAULT_STATUSES;
                var allOpt = document.createElement('option');
                allOpt.value = '';
                allOpt.textContent = 'All';
                statusSelect.appendChild(allOpt);
                list.forEach(function(s){
                    var o = document.createElement('option');
                    o.value = s;
                    o.textContent = s;
                    if (selected && selected === s) o.selected = true;
                    statusSelect.appendChild(o);
                });
                statusSelect.disabled = false;
                statusSelect.classList.remove('opacity-50');
            }
            function rebindRowClicks(){
                var rows = document.querySelectorAll('.svc-row');
                rows.forEach(function(row){
                    row.addEventListener('click', function(e){
                        if (e.target.closest('a') || e.target.closest('button') || e.target.closest('select') || e.target.closest('input')) {
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
                                        });
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
                var detailsPanel = document.getElementById('rowDetailsPanel');
                function updateDetailsPanel(){
                    var selected = Array.prototype.slice.call(document.querySelectorAll('.row-select:checked'));
                    if (selected.length === 1) {
                        var row = selected[0].closest('tr');
                        var ds = row.dataset;
                        document.getElementById('detailsRef').textContent = ds.ref || '';
                        document.getElementById('detailsName').textContent = ds.name || '';
                        document.getElementById('detailsType').textContent = ds.type || '';
                        document.getElementById('detailsStatus').textContent = ds.status || '';
                        document.getElementById('detailsFiled').textContent = ds.filed || '';
                        document.getElementById('detailsPaid').textContent = ds.paid || '';
                        document.getElementById('detailsPosted').textContent = ds.posted || '';
                        document.getElementById('detailsReady').textContent = ds.ready || '';
                        document.getElementById('detailsReleased').textContent = ds.released || '';
                        document.getElementById('detailsNotes').textContent = ds.notes || '';
                        detailsPanel.style.display = 'block';
                        detailsPanel.classList.remove('hidden');
                    } else {
                        detailsPanel.style.display = 'none';
                        detailsPanel.classList.add('hidden');
                    }
                }
                var rowCheckboxes2 = document.querySelectorAll('.row-select');
                rowCheckboxes2.forEach(function(cb){
                    cb.addEventListener('change', updateDetailsPanel);
                });
                attachDeleteAnimations();
            }
            function attachDeleteAnimations(){
                var deleteForms = document.querySelectorAll('.svc-delete-form');
                deleteForms.forEach(function(f){
                    f.addEventListener('submit', function(e){
                        e.preventDefault();
                        var tr = f.closest('tr');
                        if (tr) tr.classList.add('tw-row-out');
                        var csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                        var fd = new FormData(f);
                        fetch(f.action, {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'text/html' },
                            body: fd
                        }).then(function(){
                            if (window.updateServicesTable) window.updateServicesTable();
                            if (window.twShowToast) window.twShowToast('Service entry deleted');
                        }).catch(function(){
                            if (window.twShowToast) window.twShowToast('Delete failed');
                        });
                    });
                });
            }
            function updateResults(){
                var headerSearch = document.getElementById('header_search');
                var caretStart = headerSearch ? headerSearch.selectionStart : 0;
                var caretEnd = headerSearch ? headerSearch.selectionEnd : 0;
                var params = new URLSearchParams();
                if (typeSelect) params.set('service_type', typeSelect.value || '');
                if (statusSelect) params.set('status', statusSelect.value || '');
                if (sortSelect) params.set('sort', sortSelect.value || 'updated');
                if (directionSelect) params.set('direction', directionSelect.value || 'desc');
                if (headerSearch) params.set('name', headerSearch.value || '');
                var url = (document.getElementById('filter_form') || headerSearch.form).getAttribute('action') + '?' + params.toString();
                if (tableContainer) {
                    tableContainer.innerHTML = '<div class=\"px-3 py-2 text-sm text-gray-500\">Loading...</div>';
                }
                fetch(url, { method: 'GET' })
                    .then(function(res){ return res.text(); })
                    .then(function(html){
                        var doc = new DOMParser().parseFromString(html, 'text/html');
                        var newContainer = doc.getElementById('services_table_container');
                        if (newContainer && tableContainer) {
                            tableContainer.innerHTML = newContainer.innerHTML;
                            rebindRowClicks();
                            bindSelectAll();
                            attachDeleteAnimations();
                        }
                        if (headerSearch) {
                            headerSearch.focus();
                            try { headerSearch.setSelectionRange(caretStart, caretEnd); } catch(e){}
                        }
                    });
            }
            if (typeSelect && statusSelect) {
                populateStatuses();
                typeSelect.addEventListener('change', function(){
                    statusSelect.setAttribute('data-selected', '');
                    populateStatuses();
                    updateResults();
                });
                statusSelect.addEventListener('change', updateResults);
            }
            if (sortSelect) sortSelect.addEventListener('change', updateResults);
            if (directionSelect) directionSelect.addEventListener('change', updateResults);
            var headerSearch2 = document.getElementById('header_search');
            if (headerSearch2 && headerSearch2.form) {
                headerSearch2.form.addEventListener('submit', function(e){ e.preventDefault(); });
                var debounceTimer;
                headerSearch2.addEventListener('input', function(){
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(function(){
                        updateResults();
                    }, 300);
                });
            }
            window.updateServicesTable = updateResults;
        })();
    </script>
    <script>
        (function(){
            var detailsPanel = document.getElementById('rowDetailsPanel');
            function updateDetailsPanel(){
                var selected = Array.prototype.slice.call(document.querySelectorAll('.row-select:checked'));
                if (selected.length === 1) {
                    var row = selected[0].closest('tr');
                    var ds = row.dataset;
                    document.getElementById('detailsRef').textContent = ds.ref || '';
                    document.getElementById('detailsName').textContent = ds.name || '';
                    document.getElementById('detailsType').textContent = ds.type || '';
                    document.getElementById('detailsStatus').textContent = ds.status || '';
                    document.getElementById('detailsFiled').textContent = ds.filed || '';
                    document.getElementById('detailsPaid').textContent = ds.paid || '';
                    document.getElementById('detailsPosted').textContent = ds.posted || '';
                    document.getElementById('detailsReady').textContent = ds.ready || '';
                    document.getElementById('detailsReleased').textContent = ds.released || '';
                    document.getElementById('detailsRemarks').textContent = ds.remarks || '';
                    detailsPanel.style.display = 'block';
                    detailsPanel.classList.remove('hidden');
                } else {
                    detailsPanel.style.display = 'none';
                    detailsPanel.classList.add('hidden');
                }
            }
            var rowCheckboxes = document.querySelectorAll('.row-select');
            rowCheckboxes.forEach(function(cb){
                cb.addEventListener('change', updateDetailsPanel);
            });
        })();
    </script>
</x-app-layout>
