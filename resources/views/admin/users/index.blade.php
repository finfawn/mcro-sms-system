<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-base font-semibold text-gray-900">User Management</h2>
            <a href="{{ route('admin.users.create') }}" class="inline-flex items-center gap-2 px-3 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path d="M8 1a1 1 0 011 1v5h5a1 1 0 110 2H9v5a1 1 0 11-2 0V9H2a1 1 0 110-2h5V2a1 1 0 011-1z"/></svg>
                <span>New User</span>
            </a>
        </div>
    </x-slot>
    <div class="py-4">
        <div class="app-shell">
            <div class="bg-white border rounded-md mb-3">
                <form id="adminUsersFilterForm" method="GET" action="{{ route('admin.users.index') }}" class="px-4 py-3 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="w-full md:w-96">
                        <input id="adminUsersQ" type="text" name="q" value="{{ $q ?? '' }}" class="border-gray-300 rounded-md w-full text-sm" placeholder="Search name or email" autocomplete="off" autocapitalize="none" autocorrect="off" spellcheck="false">
                    </div>
                    <div class="w-full md:w-48">
                        <select id="adminUsersRole" name="role" class="border-gray-300 rounded-md w-full text-sm">
                            <option value="">All Roles</option>
                            <option value="admin" @selected(($role ?? '')==='admin')>Admin</option>
                            <option value="user" @selected(($role ?? '')==='user')>User</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="bg-white border rounded-md overflow-x-auto overflow-y-auto relative max-h-[70vh]">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50 sticky top-0 z-10">
                        <tr>
                            <th class="px-3 py-2 text-left text-sm font-medium text-gray-700">Name</th>
                            <th class="px-3 py-2 text-left text-sm font-medium text-gray-700">Email</th>
                            <th class="px-3 py-2 text-left text-sm font-medium text-gray-700">Role</th>
                            <th class="px-3 py-2 text-right text-sm font-medium text-gray-700">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="adminUsersTbody" class="divide-y divide-gray-200">
                        @forelse($users as $u)
                            <tr data-user-name="{{ $u->name }}" data-user-email="{{ $u->email }}">
                                <td class="px-3 py-2">{{ $u->name }}</td>
                                <td class="px-3 py-2">{{ $u->email }}</td>
                                <td class="px-3 py-2">
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs {{ $u->role === 'admin' ? 'bg-indigo-100 text-indigo-700' : 'bg-gray-100 text-gray-700' }}">{{ ucfirst($u->role ?? 'user') }}</span>
                                </td>
                                <td class="px-3 py-2 text-right">
                                    <a href="{{ route('admin.users.edit', $u) }}" class="inline-flex items-center border rounded-md px-2 py-1 text-gray-700 hover:bg-gray-100">Edit</a>
                                    @if(auth()->id() !== $u->id)
                                    <form action="{{ route('admin.users.destroy', $u) }}" method="POST" class="inline admin-delete-form">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex items-center border rounded-md px-2 py-1 text-red-700 hover:bg-red-50">Delete</button>
                                    </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-3 py-4 text-center text-gray-500">No users</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div id="twUserDeleteModal" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true" aria-labelledby="twUserDeleteTitle">
        <div class="absolute inset-0 bg-black/30"></div>
        <div class="relative max-w-md mx-auto mt-24 bg-white rounded-lg shadow ring-1 ring-gray-200">
            <div class="px-4 py-3 border-b">
                <div id="twUserDeleteTitle" class="text-sm font-medium text-gray-900">Confirm Deletion</div>
            </div>
            <div class="px-4 py-3">
                <div class="text-sm text-gray-700">You are about to delete this user. This action cannot be undone.</div>
                <div class="mt-2 text-sm text-gray-900"><span id="twUserDeleteName"></span> · <span id="twUserDeleteEmail"></span></div>
            </div>
            <div class="px-4 py-3 border-t flex justify-end gap-2">
                <button type="button" id="twUserDeleteCancel" class="inline-flex items-center px-3 py-2 border rounded-md text-gray-700 hover:bg-gray-100">Cancel</button>
                <button type="button" id="twUserDeleteConfirm" class="inline-flex items-center px-3 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">Delete</button>
            </div>
        </div>
    </div>
    <script>
        (function(){
            var form = document.getElementById('adminUsersFilterForm');
            var q = document.getElementById('adminUsersQ');
            var role = document.getElementById('adminUsersRole');
            var tbody = document.getElementById('adminUsersTbody');
            var t;
            function buildQuery(){
                var params = new URLSearchParams();
                if (q && q.value) params.set('q', q.value);
                if (role && role.value) params.set('role', role.value);
                return params.toString();
            }
            function updateList(){
                if (!form || !tbody) return;
                var qs = buildQuery();
                var url = form.action + (qs ? (form.action.indexOf('?') === -1 ? '?' : '&') + qs : '');
                history.replaceState({}, '', url);
                fetch(url, { headers: { 'Accept': 'text/html', 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(function(res){
                        if (res.status === 419) { location.reload(); return Promise.reject(); }
                        return res.text();
                    })
                    .then(function(html){
                        var doc = new DOMParser().parseFromString(html, 'text/html');
                        var newTbody = doc.getElementById('adminUsersTbody');
                        if (newTbody) {
                            tbody.innerHTML = newTbody.innerHTML;
                            if (window.attachAdminDeleteModals) window.attachAdminDeleteModals();
                        }
                    });
            }
            if (q) {
                q.addEventListener('input', function(){
                    clearTimeout(t);
                    t = setTimeout(updateList, 250);
                });
            }
            if (role) {
                role.addEventListener('change', updateList);
            }
        })();
    </script>
    <script>
        (function(){
            var forms = document.querySelectorAll('.admin-delete-form');
            var modal = document.getElementById('twUserDeleteModal');
            var btnCancel = document.getElementById('twUserDeleteCancel');
            var btnConfirm = document.getElementById('twUserDeleteConfirm');
            var nameEl = document.getElementById('twUserDeleteName');
            var emailEl = document.getElementById('twUserDeleteEmail');
            var pendingForm = null;
            function openModal(f){
                pendingForm = f;
                var tr = f.closest('tr');
                var name = tr ? (tr.dataset.userName || '') : '';
                var email = tr ? (tr.dataset.userEmail || '') : '';
                if (nameEl) nameEl.textContent = name;
                if (emailEl) emailEl.textContent = email;
                modal.classList.remove('hidden');
            }
            function closeModal(){
                modal.classList.add('hidden');
                pendingForm = null;
            }
            function bind(){
                var forms2 = document.querySelectorAll('.admin-delete-form');
                forms2.forEach(function(f){
                    f.addEventListener('submit', function(e){
                        e.preventDefault();
                        openModal(f);
                    });
                });
            }
            bind();
            window.attachAdminDeleteModals = bind;
            if (btnCancel) btnCancel.addEventListener('click', closeModal);
            if (btnConfirm) btnConfirm.addEventListener('click', function(){
                if (!pendingForm) return;
                var f = pendingForm;
                closeModal();
                var tr = f.closest('tr');
                if (tr) tr.classList.add('tw-row-out');
                var csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                var fd = new FormData(f);
                fetch(f.action, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'text/html', 'X-Requested-With': 'XMLHttpRequest' },
                    body: fd
                }).then(function(res){
                    if (res.status === 419) { location.reload(); return Promise.reject(); }
                    location.reload();
                }).catch(function(){
                    if (window.twShowToast) window.twShowToast('Delete failed');
                });
            });
        })();
    </script>
</x-app-layout>
