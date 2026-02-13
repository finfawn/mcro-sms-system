<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="text-base font-semibold text-gray-900">Edit Service</h2>
            <a href="{{ route('services.index') }}" class="inline-flex items-center px-3 py-2 border rounded-md text-gray-700 hover:bg-gray-100">Back to List</a>
        </div>
    </x-slot>
    <div class="py-4">
        <div class="max-w-2xl mx-auto px-4">
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
                                <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                                <select name="category" class="border-gray-300 rounded-md w-full" id="category">
                                    <option value="">Select category</option>
                                    @foreach($categories as $c)
                                        <option value="{{ $c }}" @selected(old('category', $service->category)===$c)>{{ $c }}</option>
                                    @endforeach
                                </select>
                                @error('category')<div class="text-sm text-red-600 mt-1">{{ $message }}</div>@enderror
                            </div>
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Service Type</label>
                                <select name="service_type" class="border-gray-300 rounded-md w-full" id="service_type"></select>
                                @error('service_type')<div class="text-sm text-red-600 mt-1">{{ $message }}</div>@enderror
                            </div>
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                <select name="status" class="border-gray-300 rounded-md w-full">
                                    @php($statuses = ['Filed','Processing','Endorsed','Released','Rejected'])
                                    @foreach($statuses as $st)
                                        <option value="{{ $st }}" @selected(old('status', $service->status)===$st)>{{ $st }}</option>
                                    @endforeach
                                </select>
                                @error('status')<div class="text-sm text-red-600 mt-1">{{ $message }}</div>@enderror
                            </div>
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Remarks</label>
                                <textarea name="remarks" class="border-gray-300 rounded-md w-full" rows="3">{{ old('remarks', $service->remarks) }}</textarea>
                            </div>
                            <div>
                                <button type="submit" class="inline-flex items-center px-3 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Submit</button>
                            </div>
                        </form>
                        <script>
                            document.addEventListener('DOMContentLoaded', function () {
                                var categorySelect = document.getElementById('category');
                                var serviceTypeSelect = document.getElementById('service_type');
                                var endorsements = ['Blurred','Negative','2010.04','Supplemental','Legitimation','Court Order'];
                                var naOnly = ['N/A'];
                                function setOptions(types, selected) {
                                    serviceTypeSelect.innerHTML = '';
                                    var placeholder = document.createElement('option');
                                    placeholder.value = '';
                                    placeholder.textContent = 'Select type';
                                    serviceTypeSelect.appendChild(placeholder);
                                    types.forEach(function (t) {
                                        var opt = document.createElement('option');
                                        opt.value = t;
                                        opt.textContent = t;
                                        serviceTypeSelect.appendChild(opt);
                                    });
                                    if (selected && types.indexOf(selected) !== -1) {
                                        serviceTypeSelect.value = selected;
                                    } else if (types.length === 1) {
                                        serviceTypeSelect.value = types[0];
                                    }
                                }
                                function updateTypes() {
                                    var cat = categorySelect.value;
                                    var oldType = serviceTypeSelect.getAttribute('data-old') || '';
                                    if (cat === 'Endorsements') {
                                        setOptions(endorsements, oldType);
                                    } else if (cat) {
                                        setOptions(naOnly, oldType);
                                    } else {
                                        setOptions([], oldType);
                                    }
                                }
                                serviceTypeSelect.setAttribute('data-old', {!! json_encode(old('service_type', $service->service_type)) !!});
                                updateTypes();
                                categorySelect.addEventListener('change', updateTypes);
                            });
                        </script>
            </div>
        </div>
    </div>
</x-app-layout>
