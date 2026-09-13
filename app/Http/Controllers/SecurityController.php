<?php

namespace App\Http\Controllers;

use App\Models\DeviceSession;
use App\Models\LoginHistory;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SecurityController extends Controller
{
    public function index(Request $request): View
    {
        return view('account.security', [
            'user' => $request->user(),
            'devices' => $request->user()->deviceSessions()->latest('last_active_at')->get(),
            'currentSessionId' => $request->session()->getId(),
        ]);
    }

    public function preferences(Request $request): RedirectResponse
    {
        $data = $request->validate(['locale' => ['required', 'in:en'], 'timezone' => ['required', 'timezone']]);
        $request->user()->update($data);
        return back()->with('success', 'Your preferences were saved.');
    }

    public function revokeDevice(Request $request, DeviceSession $device): RedirectResponse
    {
        abort_unless($device->user_id === $request->user()->id, 404);
        if ($device->session_id === $request->session()->getId()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            $device->delete();
            return to_route('login');
        }
        $device->delete();
        return back()->with('success', 'That device has been signed out.');
    }

    public function revokeAllDevices(Request $request): RedirectResponse
    {
        $current = $request->session()->getId();
        $request->user()->deviceSessions()->where('session_id', '!=', $current)->delete();
        $request->user()->refreshTokens()->update(['revoked_at' => now()]);
        return back()->with('success', 'All other devices have been signed out.');
    }

    public function showTwoFactor(): View
    {
        return view('account.two-factor', ['user' => Auth::user(), 'secret' => session('two_factor.secret')]);
    }

    public function beginTwoFactor(Request $request): RedirectResponse
    {
        $secret = $this->base32Secret();
        $request->session()->put('two_factor.secret', $secret);
        return to_route('two-factor.setup');
    }

    public function confirmTwoFactor(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'digits:6']]);
        $secret = $request->session()->get('two_factor.secret');
        abort_unless($secret && $this->validTotp($secret, $request->string('code')->toString()), 422, 'The security code is invalid.');
        $request->user()->update(['two_factor_enabled' => true, 'two_factor_secret' => Crypt::encryptString($secret), 'two_factor_recovery_codes' => collect(range(1, 8))->map(fn () => Str::upper(Str::random(10)))->all()]);
        $request->session()->forget('two_factor.secret');
        return to_route('account.security')->with('success', 'Two-factor authentication is enabled.');
    }

    public function disableTwoFactor(Request $request): RedirectResponse
    {
        $request->validate(['password' => ['required', 'current_password']]);
        $request->user()->update(['two_factor_enabled' => false, 'two_factor_secret' => null, 'two_factor_recovery_codes' => null]);
        return back()->with('success', 'Two-factor authentication is disabled.');
    }

    public function challenge(Request $request): View { return view('auth.two-factor-challenge'); }

    public function verifyChallenge(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'string', 'size:6']]);
        $user = \App\Models\User::findOrFail($request->session()->get('auth.2fa_user_id'));
        $secret = Crypt::decryptString($user->two_factor_secret);
        $recoveryCodes = $user->two_factor_recovery_codes ?? [];
        $valid = $this->validTotp($secret, $request->string('code')->toString());
        if (! $valid && in_array(Str::upper($request->string('code')->toString()), $recoveryCodes, true)) {
            $user->update(['two_factor_recovery_codes' => array_values(array_diff($recoveryCodes, [Str::upper($request->string('code')->toString())]))]);
            $valid = true;
        }
        abort_unless($valid, 422, 'The security code is invalid.');
        Auth::login($user, $request->session()->pull('auth.2fa_remember', false));
        $request->session()->forget('auth.2fa_user_id');
        $request->session()->regenerate();
        LoginHistory::create(['user_id' => $user->id, 'email' => $user->email, 'ip_address' => $request->ip(), 'user_agent' => $request->userAgent(), 'successful' => true]);
        DeviceSession::updateOrCreate(['session_id' => $request->session()->getId()], ['user_id' => $user->id, 'name' => $request->userAgent(), 'ip_address' => $request->ip(), 'user_agent' => $request->userAgent(), 'last_active_at' => now()]);
        return to_route('dashboard');
    }

    public function reactivate(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email'], 'password' => ['required']]);
        $user = User::where('email', $data['email'])->first();
        abort_unless($user && $user->status === 'DEACTIVATED' && Hash::check($data['password'], $user->password), 422, 'The account details could not be verified.');
        $user->update(['status' => 'ACTIVE', 'deactivated_at' => null]);
        return to_route('login')->with('success', 'Your account has been reactivated.');
    }

    public function deactivate(Request $request): RedirectResponse
    {
        $request->validate(['password' => ['required', 'current_password']]);
        $request->user()->update(['status' => 'DEACTIVATED', 'deactivated_at' => now()]);
        Auth::logout();
        $request->session()->invalidate();
        return to_route('posts.index')->with('success', 'Your account has been deactivated.');
    }

    public function delete(Request $request): RedirectResponse
    {
        $request->validate(['password' => ['required', 'current_password']]);
        $request->user()->update(['status' => 'DELETED', 'deleted_at' => now()]);
        Auth::logout();
        $request->session()->invalidate();
        return to_route('posts.index')->with('success', 'Your account has been deleted.');
    }

    private function base32Secret(): string { return Str::upper(Str::random(16)); }

    private function validTotp(string $secret, string $code): bool
    {
        $counter = intdiv(time(), 30);
        for ($offset = -1; $offset <= 1; $offset++) {
            $binary = pack('N*', 0).pack('N*', $counter + $offset);
            $hash = hash_hmac('sha1', $binary, $secret, true);
            $position = ord($hash[19]) & 0xf;
            $number = ((ord($hash[$position]) & 0x7f) << 24) | ((ord($hash[$position + 1]) & 0xff) << 16) | ((ord($hash[$position + 2]) & 0xff) << 8) | (ord($hash[$position + 3]) & 0xff);
            if (hash_equals(str_pad((string) ($number % 1000000), 6, '0', STR_PAD_LEFT), $code)) return true;
        }
        return false;
    }
}