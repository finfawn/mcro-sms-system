<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="text-base font-semibold text-gray-900">Scheduled Messages</h2>
        </div>
    </x-slot>
    <div class="py-4">
        <div class="app-shell">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div class="bg-white border rounded-md p-4">
                    <div class="text-xs text-gray-500">Due Today</div>
                    <div class="text-2xl font-semibold text-gray-900">{{ $todayCount }}</div>
                </div>
                <div class="bg-white border rounded-md p-4">
                    <div class="text-xs text-gray-500">Next 7 Days</div>
                    <div class="text-2xl font-semibold text-gray-900">{{ $weekCount }}</div>
                </div>
                <div class="bg-white border rounded-md p-4">
                    <div class="text-xs text-gray-500">Overdue</div>
                    <div class="text-2xl font-semibold text-red-600">{{ $overdueCount }}</div>
                </div>
            </div>
            <div class="bg-white border rounded-md mb-4">
                <form method="GET" action="{{ route('scheduled.index') }}" class="px-4 py-3 grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Service Type</label>
                        <select name="service_type" class="border-gray-300 rounded-md w-full text-sm">
                            <option value="">All</option>
                            @if(isset($types))
                                @foreach($types as $t)
                                    <option value="{{ $t }}" @selected(($selectedType ?? '') === $t)>{{ $t }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Start</label>
                        <input type="date" name="start_date" value="{{ $startDate ?? '' }}" class="border-gray-300 rounded-md w-full text-sm">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">End</label>
                        <input type="date" name="end_date" value="{{ $endDate ?? '' }}" class="border-gray-300 rounded-md w-full text-sm">
                    </div>
                    <div>
                        <button type="submit" class="inline-flex items-center px-3 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 w-full">Apply</button>
                    </div>
                </form>
            </div>
            <div class="bg-white border rounded-md">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-left text-sm font-medium text-gray-700">Due</th>
                            <th class="px-3 py-2 text-left text-sm font-medium text-gray-700">Service Type</th>
                            <th class="px-3 py-2 text-left text-sm font-medium text-gray-700">Reference</th>
                            <th class="px-3 py-2 text-left text-sm font-medium text-gray-700">Citizen</th>
                            <th class="px-3 py-2 text-left text-sm font-medium text-gray-700">Planned Message</th>
                            <th class="px-3 py-2 text-left text-sm font-medium text-gray-700">Status</th>
                            <th class="px-3 py-2 text-left text-sm font-medium text-gray-700">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($items as $it)
                            @php
                                $due = $it['due'];
                                $isOverdue = $due->isPast() && !$due->isToday();
                                $isSoon = !$isOverdue && $due->diffInDays(\Illuminate\Support\Carbon::today()) <= 3;
                                $badge = $isOverdue ? 'bg-red-100 text-red-700' : ($isSoon ? 'bg-yellow-100 text-yellow-700' : 'bg-green-100 text-green-700');
                                $rel = $due->diffForHumans();
                            @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="px-3 py-2">
                                    <div class="text-gray-900">{{ $it['due']->format('Y-m-d') }}</div>
                                    <div class="text-xs text-gray-500">{{ $rel }}</div>
                                </td>
                                <td class="px-3 py-2">{{ $it['service']->service_type }}</td>
                                <td class="px-3 py-2">
                                    <a href="{{ route('services.show', $it['service']) }}" class="text-indigo-600 hover:text-indigo-700">{{ $it['service']->reference_no }}</a>
                                </td>
                                <td class="px-3 py-2">{{ $it['service']->citizen_name }}</td>
                                <td class="px-3 py-2">{{ $it['label'] }}</td>
                                <td class="px-3 py-2">
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs {{ $badge }}">
                                        {{ $isOverdue ? 'Overdue' : ($isSoon ? 'Due Soon' : 'Scheduled') }}
                                    </span>
                                </td>
                                <td class="px-3 py-2">
                                    @if(($it['event'] ?? null) === 'releasing' && $due->lte(\Illuminate\Support\Carbon::today()))
                                        <form method="POST" action="{{ route('services.scheduled-action', $it['service']) }}">
                                            @csrf
                                            <input type="hidden" name="event" value="{{ $it['event'] }}">
                                            <button type="submit" class="inline-flex items-center rounded-md bg-blue-600 px-2.5 py-1 text-xs font-medium text-white hover:bg-blue-700">
                                                Send now
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-xs text-gray-400">â€”</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-3 py-4 text-center text-gray-500">No scheduled messages yet</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
