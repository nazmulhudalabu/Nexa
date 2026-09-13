<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Auth\Events\Verified;
use App\Models\DeviceSession;
use App\Models\LoginHistory;
use Illuminate\View\View;
use App\Models\User;

class AuthController extends Controller
{
    public function createLogin(): View { return view('auth.login'); }
    public function createRegister(): View { return view('auth.register'); }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:255', 'unique:users'],
            'phone' => ['nullable', 'string', 'max:30', 'unique:users'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);
        $user = User::create($data + ['status' => 'PENDING_VERIFICATION']);
        Auth::login($user);
        $request->session()->regenerate();
        $user->sendEmailVerificationNotification();
        $this->rememberDevice($request, $user);
        return to_route('verification.notice');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate(['email' => ['required', 'email'], 'password' => ['required']]);
        $user = User::where('email', $credentials['email'])->first();
        if ($user?->locked_until?->isFuture()) {
            return back()->withErrors(['email' => 'Your account is temporarily locked. Try again later.'])->onlyInput('email');
        }
        if ($user && ! $user->isAvailable()) {
            LoginHistory::create(['user_id' => $user->id, 'email' => $user->email, 'ip_address' => $request->ip(), 'user_agent' => $request->userAgent(), 'failure_reason' => 'account_unavailable']);
            return back()->withErrors(['email' => 'This account is not available.'])->onlyInput('email');
        }
        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            if ($user) {
                $user->increment('login_attempts');
                if ($user->login_attempts >= 5) {
                    $user->update(['locked_until' => now()->addMinutes(15), 'login_attempts' => 0]);
                }
            }
            LoginHistory::create(['user_id' => $user?->id, 'email' => $credentials['email'], 'ip_address' => $request->ip(), 'user_agent' => $request->userAgent(), 'failure_reason' => 'invalid_credentials']);
            return back()->withErrors(['email' => 'Those credentials did not match our records.'])->onlyInput('email');
        }
        $user->update(['login_attempts' => 0, 'locked_until' => null]);
        if ($user->two_factor_enabled) {
            $request->session()->put('auth.2fa_user_id', $user->id);
            $request->session()->put('auth.2fa_remember', $request->boolean('remember'));
            return to_route('two-factor.challenge');
        }
        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();
        $this->recordSuccessfulLogin($request, $user);
        return to_route('dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        DeviceSession::where('session_id', $request->session()->getId())->delete();
        Auth::logout(); $request->session()->invalidate(); $request->session()->regenerateToken();
        return to_route('posts.index');
    }

    public function verify(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->hasValidSignature(), 403);
        abort_unless($request->user()?->is($user), 403);
        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            $user->update(['status' => 'ACTIVE']);
            event(new Verified($user));
        }
        return to_route('dashboard')->with('success', 'Your email has been verified.');
    }

    public function resendVerification(Request $request): RedirectResponse
    {
        if (! $request->user()->hasVerifiedEmail()) {
            $request->user()->sendEmailVerificationNotification();
        }
        return back()->with('success', 'A new verification link is on its way.');
    }

    public function forgotPassword(): View { return view('auth.forgot-password'); }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);
        $status = Password::sendResetLink($request->only('email'));
        return back()->with('success', __($status));
    }

    public function resetPassword(string $token): View
    {
        return view('auth.reset-password', ['token' => $token, 'email' => request('email')]);
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate(['token' => ['required'], 'email' => ['required', 'email'], 'password' => ['required', 'confirmed', 'min:8']]);
        $status = Password::reset($request->only('email', 'password', 'password_confirmation', 'token'), function (User $user, string $password): void {
            $user->forceFill(['password' => $password, 'remember_token' => Str::random(60), 'login_attempts' => 0, 'locked_until' => null])->save();
            $user->deviceSessions()->delete();
        });
        return $status === Password::PASSWORD_RESET
            ? to_route('login')->with('success', 'Your password has been reset.')
            : back()->withErrors(['email' => __($status)]);
    }

    private function recordSuccessfulLogin(Request $request, User $user): void
    {
        LoginHistory::create(['user_id' => $user->id, 'email' => $user->email, 'ip_address' => $request->ip(), 'user_agent' => $request->userAgent(), 'successful' => true]);
        $this->rememberDevice($request, $user);
    }

    private function rememberDevice(Request $request, User $user): void
    {
        DeviceSession::updateOrCreate(['session_id' => $request->session()->getId()], ['user_id' => $user->id, 'name' => $request->userAgent(), 'ip_address' => $request->ip(), 'user_agent' => $request->userAgent(), 'last_active_at' => now()]);
    }
}