<?php

namespace App\Http\Controllers;

use App\Models\MobileAuthCode;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Laravel\Sanctum\PersonalAccessToken;

class AccountController extends Controller
{
    public function index()
    {
        $users = User::whereIn('role', ['staff', 'admin', 'super_admin'])->orderBy('role')->orderBy('name')->get();
        $students = User::where('role', 'student')->orderBy('name')->get();
        $organizations = Organization::orderBy('name')->get();

        return view('accounts.index', compact('users', 'students', 'organizations'));
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
            'organization' => [
                'nullable',
                'string',
                'max:160',
                function (string $attribute, mixed $value, \Closure $fail) use ($user): void {
                    if ($value !== null && $value !== $user->organization && ! Organization::where('name', $value)->exists()) {
                        $fail('Choose a listed organization or leave the current custom organization unchanged.');
                    }
                },
            ],
            'position' => ['nullable', 'string', 'max:120'],
        ]);
        $user->update($data);

        return to_route('accounts.index')->with('success', 'Account access and organization details updated.');
    }

    public function destroy(Request $request, User $user)
    {
        abort_unless($user->role !== 'super_admin' && ! $user->is($request->user()), 403);
        PersonalAccessToken::where('tokenable_type', $user::class)->where('tokenable_id', $user->id)->delete();
        MobileAuthCode::where('user_id', $user->id)->delete();
        $user->delete();

        return to_route('accounts.index')->with('success', 'Account removed.');
    }

    public function setSuspended(Request $request, User $user)
    {
        abort_unless($user->role !== 'super_admin' && ! $user->is($request->user()), 403);
        $data = $request->validate(['is_suspended' => ['required', 'boolean']]);
        $user->update($data);

        if ($user->is_suspended) {
            PersonalAccessToken::where('tokenable_type', $user::class)->where('tokenable_id', $user->id)->delete();
            MobileAuthCode::where('user_id', $user->id)->delete();
        }

        return to_route('accounts.index')->with('success', $user->is_suspended ? 'Account suspended.' : 'Account reactivated.');
    }
}
