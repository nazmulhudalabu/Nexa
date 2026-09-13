<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RefreshToken;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthApiController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:80'], 'email' => ['required', 'email', 'unique:users'], 'password' => ['required', 'confirmed', 'min:8']]);
        $user = User::create($data + ['status' => 'PENDING_VERIFICATION']);
        return $this->tokens($user, 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate(['email' => ['required', 'email'], 'password' => ['required']]);
        $user = User::where('email', $data['email'])->first();
        abort_unless($user && $user->isAvailable() && Hash::check($data['password'], $user->password), 422, 'Those credentials did not match our records.');
        return $this->tokens($user);
    }

    public function me(Request $request): JsonResponse { return response()->json($request->user()); }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->forceFill(['api_token_hash' => null, 'api_token_expires_at' => null])->save();
        $request->user()->refreshTokens()->whereNull('revoked_at')->update(['revoked_at' => now()]);
        return response()->json(['message' => 'Logged out.']);
    }

    public function refresh(Request $request): JsonResponse
    {
        $data = $request->validate(['refresh_token' => ['required', 'string']]);
        $refresh = RefreshToken::where('token_hash', hash('sha256', $data['refresh_token']))->whereNull('revoked_at')->first();
        abort_unless($refresh && $refresh->expires_at->isFuture(), 401, 'The refresh token is invalid or expired.');
        $refresh->update(['revoked_at' => now()]);
        return $this->tokens($refresh->user);
    }

    private function tokens(User $user, int $status = 200): JsonResponse
    {
        $access = Str::random(64);
        $refresh = Str::random(96);
        $user->forceFill(['api_token_hash' => hash('sha256', $access), 'api_token_expires_at' => now()->addHour()])->save();
        $user->refreshTokens()->create(['token_hash' => hash('sha256', $refresh), 'expires_at' => now()->addDays(30)]);
        return response()->json(['user' => $user, 'access_token' => $access, 'token_type' => 'Bearer', 'expires_in' => 3600, 'refresh_token' => $refresh], $status);
    }
}