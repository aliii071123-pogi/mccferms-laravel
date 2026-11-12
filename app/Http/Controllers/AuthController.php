<?php

namespace App\Http\Controllers;

use App\Models\MobileAuthCode;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function redirectToMicrosoft(Request $request)
    {
        abort_unless(config('services.microsoft.client_id') && config('services.microsoft.client_secret'), 503, 'Microsoft sign-in is not configured.');

        $client = match ($request->query('client')) {
            'mobile' => 'mobile',
            'web', 'flutter_web' => 'flutter_web',
            default => 'laravel_web',
        };

        if ($client === 'flutter_web') {
            $this->flutterWebUrl();
        }

        $request->session()->put('ferms_auth_client', $client);

        return Socialite::driver('microsoft')->redirect();
    }

    public function microsoftCallback()
    {
        try {
            $identity = Socialite::driver('microsoft')->user();
        } catch (Throwable $exception) {
            report($exception);

            return to_route('login')->withErrors(['microsoft' => 'Microsoft sign-in could not be completed. Please try again.']);
        }

        $email = strtolower((string) $identity->getEmail());
        abort_unless(str_ends_with($email, '@mcc.edu.ph'), 403, 'Use your MCC Microsoft account to sign in.');

        $superAdminEmail = config('ferms.super_admin_email');
        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $identity->getName() ?: $email,
                'password' => Str::random(64),
                'role' => $superAdminEmail && $email === $superAdminEmail ? 'super_admin' : 'student',
            ],
        );

        if ($superAdminEmail && $email === $superAdminEmail && $user->role !== 'super_admin') {
            $user->update(['role' => 'super_admin']);
        }

        if ($user->is_suspended) {
            return to_route('login')->withErrors(['account' => 'This account has been suspended.']);
        }

        Auth::login($user, true);
        request()->session()->regenerate();

        $client = request()->session()->pull('ferms_auth_client', 'laravel_web');
        if (in_array($client, ['mobile', 'flutter_web'], true)) {
            $code = $this->issueMobileAuthCode($user);

            if ($client === 'mobile') {
                return redirect()->away(config('ferms.mobile_callback_uri').'?'.http_build_query(['code' => $code]));
            }

            return redirect()->away($this->flutterWebUrl().'?'.http_build_query(['code' => $code]));
        }

        return $user->role === 'student' && (! $user->organization || ! $user->position)
            ? to_route('profile.edit')
            : to_route('dashboard');
    }

    public function logout()
    {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return to_route('login');
    }

    private function issueMobileAuthCode(User $user): string
    {
        $code = bin2hex(random_bytes(32));
        MobileAuthCode::create([
            'code_hash' => hash('sha256', $code),
            'user_id' => $user->id,
            'expires_at' => now()->addMinutes(3),
        ]);

        return $code;
    }

    private function flutterWebUrl(): string
    {
        $url = config('ferms.flutter_web_url');
        $parts = is_string($url) ? parse_url($url) : false;

        abort_unless(
            is_string($url)
            && filter_var($url, FILTER_VALIDATE_URL)
            && is_array($parts)
            && in_array($parts['scheme'] ?? null, ['http', 'https'], true)
            && isset($parts['host'])
            && ! isset($parts['query'])
            && ! isset($parts['fragment']),
            503,
            'Flutter Web sign-in is not configured.',
        );

        return rtrim($url, '/');
    }
}
