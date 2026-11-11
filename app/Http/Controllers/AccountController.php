<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AccountController extends Controller
{
    public function index()
    {
        $users = User::whereIn('role', ['staff', 'admin', 'super_admin'])->orderBy('role')->orderBy('name')->get();
        $students = User::where('role', 'student')->orderBy('name')->get();

        return view('accounts.index', compact('users', 'students'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'ends_with:@mcc.edu.ph', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::in(['staff', 'admin'])],
        ]);
        User::create([...$data, 'password' => Str::random(64)]);

        return to_route('accounts.index')->with('success', 'MCC account added. They can now sign in with Microsoft.');
    }

    public function update(Request $request, User $user)
    {
        abort_if($user->role === 'super_admin' && $user->is($request->user()), 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'ends_with:@mcc.edu.ph', 'max:255', Rule::unique('users')->ignore($user)],
            'role' => ['required', Rule::in(['student', 'staff', 'admin'])],
            'organization' => ['nullable', 'string', 'max:160'],
            'position' => ['nullable', 'string', 'max:120'],
        ]);
        $user->update($data);

        return to_route('accounts.index')->with('success', 'Account access and organization details updated.');
    }

    public function destroy(Request $request, User $user)
    {
        abort_unless(in_array($user->role, ['staff', 'admin'], true), 403);
        $user->delete();

        return to_route('accounts.index')->with('success', 'Account removed.');
    }
}
