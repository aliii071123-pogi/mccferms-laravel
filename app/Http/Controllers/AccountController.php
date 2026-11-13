<?php

namespace App\Http\Controllers;

use App\Models\MobileAuthCode;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class AccountController extends Controller
{
    private const MAX_IT_ADMINISTRATORS = 3;

    public function index()
    {
        $users = User::whereIn('role', ['staff', 'admin', 'super_admin'])->orderBy('role')->orderBy('name')->get();
        $students = User::where('role', 'student')->orderBy('name')->get();
        $organizations = Organization::orderBy('name')->get();
        $itAdministratorCount = User::where('role', 'super_admin')->count();

        return view('accounts.index', compact('users', 'students', 'organizations', 'itAdministratorCount'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'ends_with:@mcc.edu.ph', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::in(['staff', 'admin', 'super_admin'])],
        ]);
        $attributes = [...$data, 'password' => Str::random(64)];

        if ($data['role'] === 'super_admin') {
            DB::transaction(function () use ($attributes): void {
                User::where('role', 'super_admin')->orderBy('id')->lockForUpdate()->firstOrFail();

                if (User::where('role', 'super_admin')->count() >= self::MAX_IT_ADMINISTRATORS) {
                    throw ValidationException::withMessages([
                        'role' => 'A maximum of three IT Administrators is allowed.',
                    ]);
                }

                User::create($attributes);
            });
        } else {
            User::create($attributes);
        }

        return to_route('accounts.index')->with(
            'success',
            $data['role'] === 'super_admin'
                ? 'IT Administrator account added. They can now sign in with Microsoft.'
                : 'MCC account added. They can now sign in with Microsoft.',
        );
    }

    public function update(Request $request, User $user)
    {
        abort_unless($user->role !== 'super_admin' && ! $user->is($request->user()), 403);
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
