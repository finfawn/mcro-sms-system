<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-base font-semibold text-gray-900">New User</h2>
        </div>
    </x-slot>
    <div class="py-4">
        <div class="app-shell-form">
            <div class="bg-white border rounded-md p-4">
                <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Name</label>
                        <input type="text" name="name" class="border-gray-300 rounded-md w-full text-sm" required>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Email</label>
                        <input type="email" name="email" class="border-gray-300 rounded-md w-full text-sm" required>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Password</label>
                        <input type="password" name="password" class="border-gray-300 rounded-md w-full text-sm" required>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Role</label>
                        <select name="role" class="border-gray-300 rounded-md w-full text-sm" required>
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="pt-2 flex justify-end gap-2">
                        <a href="{{ route('admin.users.index') }}" class="inline-flex items-center px-3 py-2 border rounded-md text-gray-700 hover:bg-gray-100">Cancel</a>
                        <button type="submit" class="inline-flex items-center px-3 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Create</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
