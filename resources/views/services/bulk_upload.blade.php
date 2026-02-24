<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="text-base font-semibold text-gray-900">Bulk Upload</h2>
            <div class="flex gap-2">
                <a href="{{ route('services.index') }}" class="inline-flex items-center px-3 py-2 border rounded-md text-gray-700 hover:bg-gray-100">Back</a>
            </div>
        </div>
    </x-slot>
    <div class="py-4">
        <div class="max-w-3xl mx-auto px-4">
            @if(session('status'))
                <div class="mb-3 text-sm text-gray-700 bg-gray-50 border rounded-md px-3 py-2">{{ session('status') }}</div>
            @endif
            <div class="bg-white border rounded-md p-4">
                <div class="text-sm text-gray-700 mb-2">Upload CSV with the following columns:</div>
                <div class="text-xs text-gray-600 mb-3">citizen_name, mobile_number, service_type, notes</div>
                <a href="{{ route('services.bulk-upload.template') }}" class="inline-flex items-center gap-2 px-3 py-2 border rounded-md text-gray-700 hover:bg-gray-100 mb-4">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path d="M2 2h9l3 3v9a1 1 0 01-1 1H2a1 1 0 01-1-1V3a1 1 0 011-1zm2 3h6V3H4v2zm0 2h8v1H4V7zm0 3h8v1H4v-1z"/></svg>
                    <span>Download sample CSV</span>
                </a>
                <form method="POST" action="{{ route('services.bulk-upload.store') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">CSV File</label>
                        <input type="file" name="file" accept=".csv,text/csv" class="border-gray-300 rounded-md w-full">
                        @error('file')<div class="text-sm text-red-600 mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Valid Service Types</label>
                        <div class="text-xs text-gray-600">
                            @foreach($types as $t)
                                <span class="inline-block px-2 py-0.5 border rounded-md mr-1 mb-1">{{ $t }}</span>
                            @endforeach
                        </div>
                    </div>
                    <div>
                        <button type="submit" class="inline-flex items-center px-3 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Upload</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
