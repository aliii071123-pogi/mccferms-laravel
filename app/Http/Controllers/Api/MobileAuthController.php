<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MobileAuthCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MobileAuthController extends Controller
{
    public function exchange(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'size:64', 'regex:/\A[a-f0-9]{64}\z/i'],
        ]);

        $result = DB::transaction(function () use ($data) {
            $authCode = MobileAuthCode::whereKey(hash('sha256', $data['code']))
                ->whereNull('consumed_at')
                ->where('expires_at', '>', now())
                ->lockForUpdate()
                ->firstOrFail();
            $authCode->update(['consumed_at' => now()]);
            abort_if($authCode->user->is_suspended, 403, 'This account has been suspended.');

            $expiresAt = now()->addDays(config('ferms.api_token_days'));
            $token = $authCode->user->createToken('flutter-mobile', ['*'], $expiresAt);

            return [
                'token' => $token->plainTextToken,
                'expires_at' => $expiresAt->toISOString(),
                'user' => $this->userPayload($authCode->user),
            ];
        });

        return response()->json($result);
    }

    public function me(Request $request)
    {
        return response()->json(['data' => $this->userPayload($request->user())]);
    }

    public function updateProfile(Request $request)
    {
        abort_unless($request->user()->role === 'student', 403);
        $profile = $request->validate([
            'organization' => ['required', 'string', 'max:160', Rule::exists('organizations', 'name')],
            'position' => ['required', 'string', 'max:120'],
        ]);
        $request->user()->update($profile);

        return response()->json(['data' => $this->userPayload($request->user()->fresh())]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Signed out.']);
    }

    private function userPayload($user): array
    {
        return $user->only(['id', 'name', 'email', 'role', 'organization', 'position']);
    }
}
