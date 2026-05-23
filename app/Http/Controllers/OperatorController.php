<?php

namespace App\Http\Controllers;

use App\Models\AppUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class OperatorController extends Controller
{
    public function index()
    {
        Gate::authorize('superuser-only');
        $operators = AppUser::where('role', 'operator')
            ->orderByDesc('created_at')
            ->paginate(20);
        return view('operators.index', compact('operators'));
    }

    public function create()
    {
        Gate::authorize('superuser-only');
        return view('operators.create');
    }

    public function store(Request $request)
    {
        Gate::authorize('superuser-only');

        $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => 'required|email|unique:app_users,email',
            'password' => 'required|min:8|confirmed',
        ]);

        AppUser::create([
            'name'      => $request->name,
            'email'     => $request->email,
            'password'  => $request->password,
            'role'      => 'operator',
            'is_active' => true,
        ]);

        return redirect()->route('operators.index')->with('success', 'Operator berhasil ditambahkan.');
    }

    public function edit(AppUser $operator)
    {
        Gate::authorize('superuser-only');
        abort_if($operator->role !== 'operator', 404);
        return view('operators.edit', compact('operator'));
    }

    public function update(Request $request, AppUser $operator)
    {
        Gate::authorize('superuser-only');
        abort_if($operator->role !== 'operator', 404);

        $rules = [
            'name'  => 'required|string|max:100',
            'email' => 'required|email|unique:app_users,email,' . $operator->id,
        ];
        if ($request->filled('password')) {
            $rules['password'] = 'min:8|confirmed';
        }
        $request->validate($rules);

        $data = [
            'name'  => $request->name,
            'email' => $request->email,
        ];
        if ($request->filled('password')) {
            $data['password'] = $request->password;
        }
        $operator->update($data);

        return redirect()->route('operators.index')->with('success', 'Data operator diperbarui.');
    }

    public function toggleActive(AppUser $operator)
    {
        Gate::authorize('superuser-only');
        abort_if($operator->role !== 'operator', 404);

        $operator->update(['is_active' => ! $operator->is_active]);

        $msg = $operator->is_active ? 'Operator diaktifkan.' : 'Operator dinonaktifkan.';
        return back()->with('success', $msg);
    }

    public function destroy(AppUser $operator)
    {
        Gate::authorize('superuser-only');
        abort_if($operator->role !== 'operator', 404);

        $operator->delete();
        return redirect()->route('operators.index')->with('success', 'Operator dihapus.');
    }
}
