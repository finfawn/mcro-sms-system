<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $role = (string) $request->query('role', '');
        $query = User::query();
        if ($q !== '') {
            $query->where(function($x) use ($q){
                $x->where('name', 'like', '%'.$q.'%')
                  ->orWhere('email', 'like', '%'.$q.'%');
            });
        }
        if ($role !== '') {
            $query->where('role', $role);
        }
        $users = $query->orderBy('name')->get();
        return view('admin.users.index', [
            'users' => $users,
            'q' => $q,
            'role' => $role,
        ]);
    }

    public function create(): View
    {
        return view('admin.users.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', Rule::in(['admin','user'])],
        ]);
        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
        ]);
        return redirect()->route('admin.users.index')->with('status', 'User created');
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', ['user' => $user]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users','email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8'],
            'role' => ['required', Rule::in(['admin','user'])],
        ]);
        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->role = $validated['role'];
        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }
        $user->save();
        return redirect()->route('admin.users.index')->with('status', 'User updated');
    }

    public function destroy(User $user): RedirectResponse
    {
        if (auth()->id() === $user->id) {
            return redirect()->route('admin.users.index')->with('status', 'Cannot delete own account');
        }
        $user->delete();
        return redirect()->route('admin.users.index')
            ->with('status', 'User deleted')
            ->with('undo_id', $user->id)
            ->with('undo_type', 'user');
    }

    public function restore(string $id): RedirectResponse
    {
        $user = User::withTrashed()->findOrFail($id);
        $user->restore();
        return redirect()->route('admin.users.index')->with('status', 'User restored');
    }

    public function forceDelete(string $id)
    {
        $user = User::withTrashed()->find($id);
        if (!$user) {
            return response()->json(['ok' => false], 404);
        }
        if ($user->deleted_at === null) {
            return response()->json(['ok' => false, 'reason' => 'not_trashed'], 409);
        }
        $user->forceDelete();
        return response()->json(['ok' => true]);
    }
}
