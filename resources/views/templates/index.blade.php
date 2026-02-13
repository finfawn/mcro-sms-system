<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="text-base font-semibold text-gray-900">SMS Templates</h2>
            <a href="{{ route('templates.create') }}" class="inline-flex items-center gap-2 px-3 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path d="M8 1a1 1 0 011 1v5h5a1 1 0 110 2H9v5a1 1 0 11-2 0V9H2a1 1 0 110-2h5V2a1 1 0 011-1z"/>
                </svg>
                <span>New Template</span>
            </a>
        </div>
    </x-slot>
    <div class="py-4">
        <div class="max-w-7xl mx-auto px-4">
            <div class="bg-white border rounded-md">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-left text-sm font-medium text-gray-700">Code</th>
                            <th class="px-3 py-2 text-left text-sm font-medium text-gray-700">Name</th>
                            <th class="px-3 py-2 text-left text-sm font-medium text-gray-700">Active</th>
                            <th class="px-3 py-2 text-left text-sm font-medium text-gray-700">Last Updated</th>
                            <th class="px-3 py-2 text-right text-sm font-medium text-gray-700">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($templates as $t)
                            <tr class="hover:bg-gray-50">
                                <td class="px-3 py-2 font-mono">{{ $t->code }}</td>
                                <td class="px-3 py-2">{{ $t->name }}</td>
                                <td class="px-3 py-2">
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs {{ $t->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700' }}">{{ $t->is_active ? 'Active' : 'Disabled' }}</span>
                                </td>
                                <td class="px-3 py-2">{{ $t->updated_at?->format('Y-m-d H:i') }}</td>
                                <td class="px-3 py-2 text-right">
                                    <a class="inline-flex items-center border rounded-md px-2 py-1 text-gray-700 hover:bg-gray-100" href="{{ route('templates.edit', $t) }}" title="Edit" aria-label="Edit">
                                        <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                            <path d="M12.146 0.646a.5.5 0 01.708 0l2.5 2.5a.5.5 0 010 .708l-8.5 8.5-3 1a.5.5 0 01-.638-.638l1-3 8.5-8.5zM11.5 1.5l-8.5 8.5-.5 1.5 1.5-.5 8.5-8.5-1-1z"/>
                                        </svg>
                                    </a>
                                    <form action="{{ route('templates.destroy', $t) }}" method="POST" class="inline" onsubmit="return confirm('Delete this template?');">
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
                                <td colspan="5" class="px-3 py-4 text-center text-gray-500">No templates yet</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
