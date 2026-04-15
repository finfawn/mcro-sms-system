<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="text-base font-semibold text-gray-900">Edit Service</h2>
            <a href="{{ route('services.index') }}" class="inline-flex items-center px-3 py-2 border rounded-md text-gray-700 hover:bg-gray-100">Back to List</a>
        </div>
    </x-slot>
    <div class="py-4">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white border rounded-md p-4">
                        <form method="POST" action="{{ route('services.update', $service) }}">
                            @csrf
                            @method('PUT')
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Citizen Name</label>
                                <input type="text" name="citizen_name" class="border-gray-300 rounded-md w-full" value="{{ old('citizen_name', $service->citizen_name) }}">
                                @error('citizen_name')<div class="text-sm text-red-600 mt-1">{{ $message }}</div>@enderror
                            </div>
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Mobile Number</label>
                                <input type="text" name="mobile_number" class="border-gray-300 rounded-md w-full" value="{{ old('mobile_number', $service->mobile_number) }}">
                                @error('mobile_number')<div class="text-sm text-red-600 mt-1">{{ $message }}</div>@enderror
                            </div>
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Service Type</label>
                                <select name="service_type" class="border-gray-300 rounded-md w-full" id="service_type">
                                    <option value="">Select type</option>
                                    @php($types = [
                                        'Application for Marriage License',
                                        'Delayed Registration of Birth',
                                        'Delayed Registration of Death',
                                        'Delayed Registration of Marriage',
                                        'Petition',
                                        'Legal Instrument - Legitimation',
                                        'Supplemental Report',
                                        'BREQS',
                                    ])
                                    @foreach($types as $t)
                                        <option value="{{ $t }}" @selected(old('service_type', $service->service_type)===$t)>{{ $t }}</option>
                                    @endforeach
                                    @if(old('service_type', $service->service_type)==='Delayed Registration')
                                        <option value="Delayed Registration" selected disabled>Delayed Registration (legacy)</option>
                                    @endif
                                </select>
                                @error('service_type')<div class="text-sm text-red-600 mt-1">{{ $message }}</div>@enderror
                            </div>
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                <select name="status" class="border-gray-300 rounded-md w-full">
                                    @foreach(($statuses ?? []) as $st)
                                        <option value="{{ $st }}" @selected(old('status', $service->status)===$st)>{{ $st }}</option>
                                    @endforeach
                                </select>
                                @error('status')<div class="text-sm text-red-600 mt-1">{{ $message }}</div>@enderror
                            </div>
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                                @if((Auth::user()->role ?? 'user') === 'admin')
                                    <textarea name="notes" class="border-gray-300 rounded-md w-full" rows="3">{{ old('notes', $service->notes) }}</textarea>
                                @else
                                    <div class="border-gray-300 rounded-md w-full p-2 bg-gray-50">{{ $service->notes ?? '—' }}</div>
                                @endif
                            </div>
                            <div>
                                <button type="submit" class="inline-flex items-center px-3 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Submit</button>
                            </div>
                        </form>
            </div>
        </div>
    </div>
</x-app-layout>
