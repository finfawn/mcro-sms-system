<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-base font-semibold text-gray-900">Edit User</h2>
        </div>
    </x-slot>
    <div class="py-4">
        <div class="max-w-3xl mx-auto px-4">
            <div class="bg-white border rounded-md p-4">
                <form method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-3">
                    @csrf
                    @method('PUT')
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Name</label>
                        <input type="text" name="name" class="border-gray-300 rounded-md w-full text-sm" value="{{ $user->name }}" required>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Email</label>
                        <input type="email" name="email" class="border-gray-300 rounded-md w-full text-sm" value="{{ $user->email }}" required>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Password (leave blank to keep)</label>
                        <input type="password" name="password" class="border-gray-300 rounded-md w-full text-sm">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Role</label>
                        <select name="role" class="border-gray-300 rounded-md w-full text-sm" required>
                            <option value="user" @selected(($user->role ?? 'user')==='user')>User</option>
                            <option value="admin" @selected(($user->role ?? 'user')==='admin')>Admin</option>
                        </select>
                    </div>
                    <div class="pt-2 flex justify-end gap-2">
                        <a href="{{ route('admin.users.index') }}" class="inline-flex items-center px-3 py-2 border rounded-md text-gray-700 hover:bg-gray-100">Cancel</a>
                        <button type="submit" class="inline-flex items-center px-3 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
