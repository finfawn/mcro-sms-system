<x-app-layout>
    <div class="flex justify-between items-center px-4 sm:px-6 lg:px-8 pt-3">
        <h2 class="text-base font-semibold text-gray-900 whitespace-nowrap flex-shrink-0">Service List</h2>
        <div class="flex items-center gap-2 flex-1">
            <div class="flex-1 hidden sm:flex justify-center">
                <form method="GET" action="{{ route('services.index') }}" class="flex items-center w-full justify-center">
                    <input type="hidden" name="service_type" value="{{ $serviceType ?? '' }}">
                    <input type="hidden" name="status" value="{{ $status ?? '' }}">
                    <input type="hidden" name="sort" value="{{ $sort ?? 'updated' }}">
                    <input type="hidden" name="direction" value="{{ $direction ?? 'desc' }}">
                    <input type="text" name="name" value="{{ $name ?? '' }}" class="border-gray-300 rounded-md w-full max-w-md text-center" placeholder="Search name or reference" id="header_search" autocomplete="off" autocapitalize="none" autocorrect="off" spellcheck="false">
                </form>
            </div>
            <a href="{{ route('services.bulk-upload.form') }}" class="inline-flex items-center gap-2 px-3 py-2 border rounded-md text-gray-700 hover:bg-gray-100">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path d="M2 2h12v3H2V2zm0 4h12v3H2V6zm0 4h12v2H2v-2z"/>
                </svg>
                <span>Bulk Upload</span>
            </a>
            <a href="{{ route('services.export') }}" class="inline-flex items-center gap-2 px-3 py-2 border rounded-md text-gray-700 hover:bg-gray-100">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path d="M8 1a1 1 0 011 1v6.586l2.146-2.147a1 1 0 111.415 1.415l-3.853 3.853a1 1 0 01-1.415 0L2.44 8.854a1 1 0 111.415-1.415L6 9.586V2a1 1 0 112 0z"/>
                    <path d="M2 13a1 1 0 011-1h10a1 1 0 110 2H3a1 1 0 01-1-1z"/>
                </svg>
                <span>Export</span>
            </a>
            <a href="{{ route('services.create') }}" class="inline-flex items-center gap-2 px-3 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path d="M8 1a1 1 0 011 1v5h5a1 1 0 110 2H9v5a1 1 0 11-2 0V9H2a1 1 0 110-2h5V2a1 1 0 011-1z"/>
                </svg>
                <span>New Entry</span>
            </a>
        </div>
    </div>
    
    <div class="py-4">
        <div class="max-w-7xl mx-auto px-4">
            @php
                $defaultStatuses = ['Filed','Processing','Endorsed','Released','Rejected'];
                $mlStatuses = ['Filed','Paid','Posted','Released'];
                $delayedStatuses = ['Filed','Under Verification','Consistent','Inconsistent','Posted','Ready for Release','Released','Rejected'];
                $frontlineStatuses = ['Authenticated','Form Filled','Submitted','Paid','Claim Stub Issued','Ready for Pickup','Released'];
                $endorsementStatuses = ['Filed','Sent to PSA','PSA Has Feedback','Reworked and Resent','PSA Successfully Uploaded'];
                $endorsementBlurredStatuses = ['Filed','Sent to PSA','PSA Has Feedback','Reworked and Resent','PSA Successfully Uploaded'];
                $endorsementLegalStatuses = ['Filed','Sent to PSA','PSA Has Feedback','Reworked and Resent','PSA Successfully Uploaded'];
                $ra9048Statuses = ['Drafted','For Filing','Posted','Sent to PSA','Affirmed','Impugned'];
                $ra9048_10172Statuses = ['Drafted','For Filing','Posted','Sent to PSA','Affirmed','Impugned'];
            @endphp
            
            <div class="bg-white border rounded-md mb-3">
                <form id="filter_form" method="GET" action="{{ route('services.index') }}" class="px-4 py-3 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="w-full md:hidden">
                        <label for="filter_search_mobile" class="sr-only">Search</label>
                        <input type="text" id="filter_search_mobile" value="{{ $name ?? '' }}" class="border-gray-300 rounded-md w-full text-sm" placeholder="Search name or reference" autocomplete="off" autocapitalize="none" autocorrect="off" spellcheck="false">
                    </div>
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
                        <input type="hidden" name="name" id="filter_name" value="{{ $name ?? '' }}">
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
                .tw-timeline .item {
                    display: flex;
                    align-items: flex-start;
                    gap: 8px;
                    position: relative;
                    padding-left: 2px;
                    margin-bottom: 8px;
                }
                .tw-timeline .item .dot {
                    display: inline-block;
                    width: 6px;
                    height: 6px;
                    border-radius: 9999px;
                    background-color: #9CA3AF; /* gray-400 */
                    margin-top: 6px;
                }
            </style>
            
            <form id="bulkContextForm" action="{{ route('services.bulk-status') }}" method="POST" class="d-none">
                @csrf
                <input type="hidden" name="status" id="bulkContextStatus">
            </form>
            
            <div id="services_table_container" class="bg-white border rounded-md overflow-x-auto overflow-y-auto hidden md:block relative max-h-[70vh]">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50 sticky top-0 z-10 shadow-sm">
                        <tr>
                            <th class="w-9 px-3 py-2">
                                <input type="checkbox" id="select_all" class="rounded border-gray-300">
                            </th>
                            <th class="px-3 py-2 text-left text-sm font-medium text-gray-700">Reference</th>
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
                                } elseif ($s->service_type === 'Frontline Service') {
                                    $rowStatuses = $frontlineStatuses;
                                } elseif ($s->service_type === 'Endorsement for Negative PSA - Positive LCRO') {
                                    $rowStatuses = $endorsementStatuses;
                                } elseif ($s->service_type === 'Endorsement for Blurred PSA - Clear LCRO File') {
                                    $rowStatuses = $endorsementBlurredStatuses;
                                } elseif ($s->service_type === 'Endorsement of Legal Instrument & MC 2010-04 & Court Order') {
                                    $rowStatuses = $endorsementLegalStatuses;
                                } elseif ($s->service_type === 'Petitions filed under RA 9048 - Clerical Error') {
                                    $rowStatuses = $ra9048Statuses;
                                } elseif ($s->service_type === 'Petitions filed under RA 9048 & RA 10172') {
                                    $rowStatuses = $ra9048_10172Statuses;
                                }
                                $currentIndex = array_search($s->status, $rowStatuses);
                            @endphp
                            @php
                                $accentMap = [
                                    'Filed' => 'border-l-gray-300',
                                    'Processing' => 'border-l-blue-400',
                                    'Paid' => 'border-l-indigo-400',
                                    'Posted' => 'border-l-amber-400',
                                    'Ready for Release' => 'border-l-emerald-400',
                                    'Released' => 'border-l-green-500',
                                    'Rejected' => 'border-l-red-500',
                                ];
                                $accent = $accentMap[$s->status] ?? 'border-l-gray-200';
                            @endphp
                            <tr class="hover:bg-gray-50 svc-row cursor-pointer {{ $accent }} border-l-4"
                                data-id="{{ $s->id }}"
                                data-ref="{{ $s->reference_no }}"
                                data-name="{{ $s->citizen_name }}"
                                data-type="{{ $s->service_type }}"
                                data-status="{{ $s->status }}"
                                data-filed="{{ $s->created_at->format('Y-m-d H:i') }}"
                                data-paid="{{ $s->payment_date ? $s->payment_date->toDateString() : '' }}"
                                data-posted="{{ $s->posting_start_date ? $s->posting_start_date->toDateString() : '' }}"
                                data-ready="{{ $s->posting_start_date ? $s->posting_start_date->copy()->addWeekdays(10)->toDateString() : '' }}"
                                data-released="{{ $s->release_date ? $s->release_date->toDateString() : '' }}"
                                data-notes="{{ $s->notes ?? '' }}"
                            >
                                <td class="px-3 py-2">
                                    <input type="checkbox" name="ids[]" value="{{ $s->id }}" class="rounded border-gray-300 row-select">
                                </td>
                                <td class="px-3 py-2">
                                    <div class="flex items-center gap-2">
                                        <svg class="svc-chev text-gray-400 transition-transform duration-200" width="14" height="14" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path d="M4.646 6.646a.5.5 0 01.708 0L8 9.293l2.646-2.647a.5.5 0 01.708.708l-3 3a.5.5 0 01-.708 0l-3-3a.5.5 0 010-.708z"/></svg>
                                    <a href="{{ route('services.show', $s) }}" class="text-indigo-600 hover:text-indigo-700">{{ $s->reference_no }}</a>
                                    </div>
                                </td>
                                <td class="px-3 py-2">{{ $s->citizen_name }}</td>
                                <td class="px-3 py-2">{{ $s->service_type }}</td>
                                <td class="px-3 py-2">
                                    <form action="{{ route('services.update-status', $s) }}" method="POST">
                                        @csrf
                                        @method('PUT')
                                        @php
                                            $rowStatuses = $defaultStatuses;
                                            if ($s->service_type === 'Application for Marriage License') {
                                                $rowStatuses = $mlStatuses;
                                            } elseif ($s->service_type === 'Delayed Registration') {
                                                $rowStatuses = $delayedStatuses;
                                            } elseif ($s->service_type === 'Frontline Service') {
                                                $rowStatuses = $frontlineStatuses;
                                            } elseif ($s->service_type === 'Endorsement for Negative PSA - Positive LCRO') {
                                                $rowStatuses = $endorsementStatuses;
                                            } elseif ($s->service_type === 'Endorsement for Blurred PSA - Clear LCRO File') {
                                                $rowStatuses = $endorsementBlurredStatuses;
                                            } elseif ($s->service_type === 'Endorsement of Legal Instrument & MC 2010-04 & Court Order') {
                                                $rowStatuses = $endorsementLegalStatuses;
                                            } elseif ($s->service_type === 'Petitions filed under RA 9048 - Clerical Error') {
                                                $rowStatuses = $ra9048Statuses;
                                            }
                                        @endphp
                                        <select name="status" class="border-gray-300 rounded-md text-sm px-2 py-1" onchange="this.form.submit()">
                                            @foreach($rowStatuses as $st)
                                                @php
                                                    $idx = array_search($st, $rowStatuses);
                                                    $allowBackTypes = [
                                                        'Delayed Registration',
                                                        'Frontline Service',
                                                        'Request for PSA documents through BREQS',
                                                        'Endorsement for Negative PSA - Positive LCRO',
                                                        'Endorsement for Blurred PSA - Clear LCRO File',
                                                        'Endorsement of Legal Instrument & MC 2010-04 & Court Order',
                                                        'Petitions filed under RA 9048 - Clerical Error',
                                                        'Petitions filed under RA 9048 & RA 10172',
                                                    ];
                                                    $disableBackwards = (!in_array($s->service_type, $allowBackTypes));
                                                    $disabled = $disableBackwards && ($idx !== false && $currentIndex !== false && $idx < $currentIndex);
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
                                    @if((Auth::user()->role ?? 'user') === 'admin')
                                        <form action="{{ route('services.destroy', $s) }}" method="POST" class="inline svc-delete-form">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="tw-pressable inline-flex items-center border rounded-md px-2 py-1 text-red-700 hover:bg-red-50" title="Delete" aria-label="Delete">
                                                <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                                    <path d="M5.5 5.5a.5.5 0 01.5.5v6a.5.5 0 01-1 0v-6a.5.5 0 01.5-.5zm5 0a.5.5 0 01.5.5v6a.5.5 0 01-1 0v-6a.5.5 0 01.5-.5z"/>
                                                    <path fill-rule="evenodd" d="M14.5 3a1 1 0 01-1 1H13v9a2 2 0 01-2 2H5a2 2 0 01-2-2V4H2.5a1 1 0 110-2H6a1 1 0 011-1h2a1 1 0 011 1h3.5a1 1 0 011 1zM4 4v9a1 1 0 001 1h6a1 1 0 001-1V4H4z"/>
                                                </svg>
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                            <tr class="details-row hidden">
                                <td colspan="6" class="bg-gray-50">
                                    <div class="p-3 details-content">
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                                            <div class="md:col-span-2">
                                                <div class="text-sm font-medium text-gray-700 mb-2">Timeline</div>
                                                <div class="tw-timeline">
                                                    @forelse($s->statusLogs as $log)
                                                        <div class="item">
                                                            <span class="dot"></span>
                                                            <div>
                                                                <div class="text-gray-500">{{ $log->status }}</div>
                                                                <div class="text-gray-900">{{ $log->created_at->format('Y-m-d H:i') }}</div>
                                                                <div class="text-xs text-gray-700">{{ $log->user ? $log->user->name : 'System' }}</div>
                                                            </div>
                                                        </div>
                                                    @empty
                                                        <div class="item">
                                                            <span class="dot"></span>
                                                            <div>
                                                                <div class="text-gray-500">No activity yet</div>
                                                            </div>
                                                        </div>
                                                    @endforelse
                                                    @if($s->posting_start_date)
                                                        <div class="item">
                                                            <span class="dot"></span>
                                                            <div>
                                                                <div class="text-gray-500">Ready For Release (estimated)</div>
                                                                <div class="text-gray-900">{{ $s->posting_start_date->copy()->addWeekdays(10)->format('Y-m-d') }}</div>
                                                            </div>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                            <div>
                                                <div class="text-sm font-medium text-gray-700 mb-2">Notes</div>
                                                <div class="text-gray-900 bg-white rounded-md ring-1 ring-gray-200 p-2">{{ $s->notes ?? '—' }}</div>
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
            <div id="twDeleteModal" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true" aria-labelledby="twDeleteTitle">
                <div class="absolute inset-0 bg-black/30"></div>
                <div class="relative max-w-md mx-auto mt-24 bg-white rounded-lg shadow ring-1 ring-gray-200">
                    <div class="px-4 py-3 border-b">
                        <div id="twDeleteTitle" class="text-sm font-medium text-gray-900">Confirm Deletion</div>
                    </div>
                    <div class="px-4 py-3">
                        <div class="text-sm text-gray-700">You are about to delete this service entry. This action cannot be undone.</div>
                        <div class="mt-2 text-sm text-gray-900"><span id="twDeleteRef"></span> · <span id="twDeleteName"></span></div>
                    </div>
                    <div class="px-4 py-3 border-t flex justify-end gap-2">
                        <button type="button" id="twDeleteCancel" class="inline-flex items-center px-3 py-2 border rounded-md text-gray-700 hover:bg-gray-100">Cancel</button>
                        <button type="button" id="twDeleteConfirm" class="inline-flex items-center px-3 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">Delete</button>
                    </div>
                </div>
            </div>
            <div id="services_mobile_container" class="md:hidden space-y-2">
                @forelse($services as $s)
                    @php
                        $rowStatuses = ['Filed','Processing','Endorsed','Released','Rejected'];
                        if ($s->service_type === 'Application for Marriage License') {
                            $rowStatuses = ['Filed','Paid','Posted','Released'];
                        } elseif ($s->service_type === 'Delayed Registration') {
                            $rowStatuses = $delayedStatuses;
                        } elseif ($s->service_type === 'Frontline Service') {
                            $rowStatuses = $frontlineStatuses;
                        } elseif ($s->service_type === 'Endorsement for Negative PSA - Positive LCRO') {
                            $rowStatuses = $endorsementStatuses;
                        } elseif ($s->service_type === 'Endorsement for Blurred PSA - Clear LCRO File') {
                            $rowStatuses = $endorsementBlurredStatuses;
                        } elseif ($s->service_type === 'Endorsement of Legal Instrument & MC 2010-04 & Court Order') {
                            $rowStatuses = $endorsementLegalStatuses;
                        } elseif ($s->service_type === 'Petitions filed under RA 9048 - Clerical Error') {
                            $rowStatuses = $ra9048Statuses;
                        } elseif ($s->service_type === 'Petitions filed under RA 9048 & RA 10172') {
                            $rowStatuses = $ra9048_10172Statuses;
                        }
                    @endphp
                    <div class="bg-white border rounded-md p-3">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <a href="{{ route('services.show', $s) }}" class="text-indigo-600">{{ $s->reference_no }}</a>
                                <div class="text-xs text-gray-500">{{ $s->service_type }}</div>
                            </div>
                            <div class="text-xs text-gray-500">{{ $s->updated_at->format('Y-m-d H:i') }}</div>
                        </div>
                        <div class="mt-1 text-sm text-gray-900">{{ $s->citizen_name }}</div>
                        <div class="mt-2 flex items-center justify-between">
                            <form action="{{ route('services.update-status', $s) }}" method="POST" aria-label="Update status">
                                @csrf
                                @method('PUT')
                                <label class="sr-only" for="status-{{ $s->id }}">Status</label>
                                <select id="status-{{ $s->id }}" name="status" class="border-gray-300 rounded-md text-sm px-2 py-1" onchange="this.form.submit()">
                                    @foreach($rowStatuses as $st)
                                        <option value="{{ $st }}" @selected($s->status === $st)>{{ $st }}</option>
                                    @endforeach
                                </select>
                            </form>
                            <div class="flex items-center gap-2">
                                <a class="inline-flex items-center border rounded-md px-2 py-1 text-gray-700 hover:bg-gray-100" href="{{ route('services.edit', $s) }}" title="Edit" aria-label="Edit">
                                    <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path d="M12.146 0.646a.5.5 0 01.708 0l2.5 2.5a.5.5 0 010 .708l-8.5 8.5-3 1a.5.5 0 01-.638-.638l1-3 8.5-8.5zM11.5 1.5l-8.5 8.5-.5 1.5 1.5-.5 8.5-8.5-1-1z"/></svg>
                                </a>
                                @if((Auth::user()->role ?? 'user') === 'admin')
                                    <form action="{{ route('services.destroy', $s) }}" method="POST" class="inline svc-delete-form" onsubmit="return confirm('Delete this service entry?');" aria-label="Delete entry">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex items-center border rounded-md px-2 py-1 text-red-700 hover:bg-red-50" title="Delete" aria-label="Delete">
                                            <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path d="M5.5 5.5a.5.5 0 01.5.5v6a.5.5 0 01-1 0v-6a.5.5 0 01.5-.5zm5 0a.5.5 0 01.5.5v6a.5.5 0 01-1 0v-6a.5.5 0 01.5-.5z"/><path fill-rule="evenodd" d="M14.5 3a1 1 0 01-1 1H13v9a2 2 0 01-2 2H5a2 2 0 01-2-2V4H2.5a1 1 0 110-2H6a1 1 0 011-1h2a1 1 0 011 1h3.5a1 1 0 011 1zM4 4v9a1 1 0 001 1h6a1 1 0 001-1V4H4z"/></svg>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="bg-white border rounded-md p-3 text-sm text-gray-500">No services filed</div>
                @endforelse
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
        <div id="bulkActions"></div>
        <div class="px-3 py-2 text-gray-500 hidden" id="noSelectionNote">Select rows first</div>
        <div class="px-3 py-2 text-gray-500 hidden" id="noCommonNote">No common statuses</div>
    </div>
    
    <script>
        (function(){
            document.addEventListener('DOMContentLoaded', function(){
                var qs = new URLSearchParams(location.search);
                var n = qs.get('name') || '';
                var hs = document.getElementById('header_search');
                var ms = document.getElementById('filter_search_mobile');
                if (hs) hs.value = n;
                if (ms) ms.value = n;
            });
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
            var noCommon = document.getElementById('noCommonNote');
            var actions = document.getElementById('bulkActions');
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
                    noCommon.classList.add('hidden');
                    while (actions.firstChild) actions.removeChild(actions.firstChild);
                    if (hasSelection) {
                        var types = selected.map(function(cb){ 
                            var tr = cb.closest('tr'); 
                            return tr ? tr.dataset.type : ''; 
                        }).filter(function(t){ return !!t; });
                        var lists = types.map(function(t){ return STATUS_MAP[t] || DEFAULT_STATUSES; });
                        var common = lists.length ? lists[0].slice() : [];
                        for (var i=1;i<lists.length;i++){
                            common = common.filter(function(x){ return lists[i].indexOf(x) !== -1; });
                        }
                        if (common.length) {
                            common.forEach(function(s){
                                var btn = document.createElement('button');
                                btn.type = 'button';
                                btn.className = 'w-full text-left px-3 py-1 hover:bg-gray-100 context-action';
                                btn.setAttribute('data-status', s);
                                btn.textContent = s;
                                btn.addEventListener('click', function(){
                                    var existing = bulkForm.querySelectorAll('input[name=\"ids[]\"]');
                                    existing.forEach(function(el){ el.remove(); });
                                    selected.forEach(function(cb){
                                        var hidden = document.createElement('input');
                                        hidden.type = 'hidden';
                                        hidden.name = 'ids[]';
                                        hidden.value = cb.value;
                                        bulkForm.appendChild(hidden);
                                    });
                                    statusField.value = s;
                                    bulkForm.submit();
                                    hideMenu();
                                });
                                actions.appendChild(btn);
                            });
                        } else {
                            noCommon.classList.remove('hidden');
                        }
                    }
                    
                    var x = e.clientX;
                    var y = e.clientY;
                    var maxX = window.innerWidth - 220;
                    var maxY = window.innerHeight - 200;
                    
                    menu.style.left = Math.min(x, maxX) + 'px';
                    menu.style.top = Math.min(y, maxY) + 'px';
                    menu.style.display = 'block';
                });
            }
            
            // buttons are bound dynamically above
            rebindRowClicks();
            var typeSelect = document.getElementById('filter_service_type');
            var statusSelect = document.getElementById('filter_status');
            var sortSelect = document.querySelector('select[name="sort"]');
            var directionSelect = document.querySelector('select[name="direction"]');
            var tableContainer = document.getElementById('services_table_container');
            var STATUS_MAP = {
                'Application for Marriage License': ['Filed','Paid','Posted','Released'],
                'Delayed Registration': ['Filed','Under Verification','Consistent','Inconsistent','Posted','Ready for Release','Released','Rejected'],
                'Frontline Service': ['Authenticated','Form Filled','Submitted','Paid','Claim Stub Issued','Ready for Pickup','Released'],
                'Request for PSA documents through BREQS': ['Authenticated','Form Filled','Submitted','Paid','Claim Stub Issued','Ready for Pickup','Released'],
                'Endorsement for Negative PSA - Positive LCRO': ['Authenticated','Documents Submitted','Processing','Sent to PSA','PSA Feedback','Reworked and Resent','PSA No Feedback','Released'],
                'Endorsement for Blurred PSA - Clear LCRO File': ['Authenticated','Documents Submitted','Processing','Sent to PSA','PSA Feedback','Reworked and Resent','PSA No Feedback','Released'],
                'Endorsement of Legal Instrument & MC 2010-04 & Court Order': ['Authenticated','Documents Submitted','Processing','Sent to PSA','PSA Feedback','Reworked and Resent','PSA No Feedback','Released'],
                'Petitions filed under RA 9048 - Clerical Error': ['Authenticated','Requirements Submitted','Processing','Petition Ready for Filing','Filed','Sent to PSA Legal Services','PSA Impugned','Motion Prepared','Resent to PSA Legal Services','PSA Affirmed','Released'],
                'Petitions filed under RA 9048 & RA 10172': ['Authenticated','Requirements Submitted','Processing','Petition Ready for Filing','Filed','Published','Decision Rendered','Sent to PSA Legal Services','PSA Impugned','Motion Prepared','Resent to PSA Legal Services','PSA Affirmed','Released']
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
                                var chev = row.querySelector('.svc-chev');
                            var openRow = Array.prototype.find.call(document.querySelectorAll('.details-row'), function(dr){ return !dr.classList.contains('hidden'); });
                            if (openRow && openRow !== details) {
                                var openContent = openRow.querySelector('.details-content');
                                    var openChev = openRow.previousElementSibling && openRow.previousElementSibling.querySelector('.svc-chev');
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
                var modal = document.getElementById('twDeleteModal');
                var btnCancel = document.getElementById('twDeleteCancel');
                var btnConfirm = document.getElementById('twDeleteConfirm');
                var refEl = document.getElementById('twDeleteRef');
                var nameEl = document.getElementById('twDeleteName');
                var pendingForm = null;
                function openModal(form){
                    pendingForm = form;
                    var tr = form.closest('tr');
                    var ref = tr ? (tr.dataset.ref || '') : '';
                    var name = tr ? (tr.dataset.name || '') : '';
                    if (refEl) refEl.textContent = ref;
                    if (nameEl) nameEl.textContent = name;
                    modal.classList.remove('hidden');
                }
                function closeModal(){
                    modal.classList.add('hidden');
                    pendingForm = null;
                }
                deleteForms.forEach(function(f){
                    f.addEventListener('submit', function(e){
                        e.preventDefault();
                        openModal(f);
                    });
                });
                if (btnCancel) btnCancel.addEventListener('click', closeModal);
                if (btnConfirm) btnConfirm.addEventListener('click', function(){
                    if (!pendingForm) return;
                    var form = pendingForm;
                    closeModal();
                    var tr = form.closest('tr');
                    if (tr) tr.classList.add('tw-row-out');
                    var csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                    var fd = new FormData(form);
                    fetch(form.action, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'text/html' },
                        body: fd
                    }).then(function(res){
                        if (res.status === 419) { location.reload(); return Promise.reject(); }
                        var id = tr ? (tr.dataset.id || null) : null;
                        if (id && typeof window.twShowUndoToast === 'function') {
                            window.twShowUndoToast(id, 'service', 'Service entry deleted');
                        } else {
                            if (window.twShowToast) window.twShowToast('Service entry deleted');
                        }
                    }).catch(function(){
                        if (window.twShowToast) window.twShowToast('Delete failed');
                    });
                });
            }
            function updateResults(){
                var base = "{{ route('services.index') }}";
                var typeSelect = document.getElementById('filter_service_type');
                var statusSelect = document.getElementById('filter_status');
                var sortSelect = document.querySelector('select[name=\"sort\"]');
                var directionSelect = document.querySelector('select[name=\"direction\"]');
                var headerSearch = document.getElementById('header_search');
                var mobileSearch = document.getElementById('filter_search_mobile');
                var q = '';
                if (headerSearch) q = headerSearch.value || '';
                if (!q && mobileSearch) q = mobileSearch.value || '';
                var params = new URLSearchParams();
                var typeVal = typeSelect ? (typeSelect.value || '') : '';
                var statusVal = statusSelect ? (statusSelect.value || '') : '';
                var sortVal = sortSelect ? (sortSelect.value || '') : '';
                var dirVal = directionSelect ? (directionSelect.value || '') : '';
                if (typeVal) params.set('service_type', typeVal);
                if (statusVal) params.set('status', statusVal);
                if (sortVal) params.set('sort', sortVal);
                if (dirVal) params.set('direction', dirVal);
                if (q.trim() !== '') params.set('name', q.trim());
                var url = base + (params.toString() ? ('?' + params.toString()) : '');
                history.replaceState({}, '', url);
                fetch(url, { headers: { 'Accept': 'text/html' } })
                    .then(function(res){
                        if (res.status === 419) { location.reload(); return Promise.reject(); }
                        return res.text();
                    })
                    .then(function(html){
                        var doc = new DOMParser().parseFromString(html, 'text/html');
                        var newContainer = doc.getElementById('services_table_container');
                        var oldContainer = document.getElementById('services_table_container');
                        var newMobile = doc.getElementById('services_mobile_container');
                        var oldMobile = document.getElementById('services_mobile_container');
                        if (newContainer && oldContainer) {
                            oldContainer.innerHTML = newContainer.innerHTML;
                            rebindRowClicks();
                            if (typeof window.rebindStatusAjax === 'function') window.rebindStatusAjax();
                        }
                        if (newMobile && oldMobile) {
                            oldMobile.innerHTML = newMobile.innerHTML;
                            if (typeof window.rebindStatusAjax === 'function') window.rebindStatusAjax();
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
                headerSearch2.form.addEventListener('submit', function(e){ /* allow default */ });
                var debounceTimer;
                headerSearch2.addEventListener('input', function(){
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(function(){ updateResults(); }, 300);
                });
            }
            var mobileSearch2 = document.getElementById('filter_search_mobile');
            if (mobileSearch2) {
                var debounceTimer2;
                mobileSearch2.addEventListener('input', function(){
                    clearTimeout(debounceTimer2);
                    debounceTimer2 = setTimeout(function(){ updateResults(); }, 300);
                });
            }
            window.updateServicesTable = updateResults;
        })();
    </script>
    <script>
        (function(){
            window.rebindStatusAjax = function(){
                var csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                var forms = document.querySelectorAll('form');
                forms.forEach(function(f){
                    var sel = f.querySelector('select[name="status"]');
                    if (!sel) return;
                    f.addEventListener('submit', function(e){
                        e.preventDefault();
                        if (!csrf) { location.reload(); return; }
                        var fd = new FormData(f);
                        fetch(f.action, {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'text/html' },
                            body: fd
                        }).then(function(res){
                            if (res.status === 419) { location.reload(); return; }
                            if (window.twShowToast) window.twShowToast('Status updated');
                            if (window.updateServicesTable) window.updateServicesTable();
                        }).catch(function(){
                            if (window.twShowToast) window.twShowToast('Update failed');
                        });
                    });
                });
            }
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
            window.rebindStatusAjax();
            var tableContainer = document.getElementById('services_table_container');
            var mobileContainer = document.getElementById('services_mobile_container');
            var interacting = false;
            function markInteract(){ interacting = true; setTimeout(function(){ interacting = false; }, 2000); }
            if (tableContainer) {
                tableContainer.addEventListener('mousedown', markInteract, true);
                tableContainer.addEventListener('keydown', markInteract, true);
            }
            if (mobileContainer) {
                mobileContainer.addEventListener('mousedown', markInteract, true);
                mobileContainer.addEventListener('keydown', markInteract, true);
            }
            function autoRefresh(){
                if (interacting) return;
                if (typeof window.updateServicesTable === 'function') window.updateServicesTable();
            }
            document.addEventListener('visibilitychange', function(){
                if (!document.hidden) autoRefresh();
            });
            setInterval(autoRefresh, 20000);
        })();
    </script>
</x-app-layout>
