<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="text-base font-semibold text-gray-900">Edit SMS Template</h2>
            <a href="{{ route('templates.index') }}" class="inline-flex items-center px-3 py-2 border rounded-md text-gray-700 hover:bg-gray-100">Back to List</a>
        </div>
    </x-slot>
    <div class="py-4">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white border rounded-md p-4">
                        <form method="POST" action="{{ route('templates.update', $template) }}">
                            @csrf
                            @method('PUT')
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Code</label>
                                <input type="text" name="code" class="border-gray-300 rounded-md w-full" value="{{ old('code', $template->code) }}">
                                @error('code')<div class="text-sm text-red-600 mt-1">{{ $message }}</div>@enderror
                            </div>
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                                <input type="text" name="name" class="border-gray-300 rounded-md w-full" value="{{ old('name', $template->name) }}">
                                @error('name')<div class="text-sm text-red-600 mt-1">{{ $message }}</div>@enderror
                            </div>
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Body</label>
                                <textarea name="body" class="border-gray-300 rounded-md w-full" rows="5">{{ old('body', $template->body) }}</textarea>
                                @error('body')<div class="text-sm text-red-600 mt-1">{{ $message }}</div>@enderror
                            </div>
                            <div class="flex items-center gap-2 mb-4">
                                <input class="rounded border-gray-300" type="checkbox" value="1" id="is_active" name="is_active" @checked(old('is_active', $template->is_active))>
                                <label class="text-sm text-gray-700" for="is_active">Active</label>
                            </div>
                            <div>
                                <button type="submit" class="inline-flex items-center px-3 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Save Changes</button>
                            </div>
                        </form>
            </div>
        </div>
    </div>
</x-app-layout>
