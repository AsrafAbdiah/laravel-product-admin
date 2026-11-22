<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsureTokenCanAct;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Account settings pages. The profile, password and two-factor forms post
 * to Fortify's routes; API tokens are handled here.
 */
class SettingsController extends Controller
{
    public function profile(Request $request): View
    {
        return view('settings.profile', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Where the account is signed in, from the "sessions" table (used by the
     * database session driver, the default for this app).
     */
    public function sessions(Request $request): View
    {
        $sessions = DB::table(config('session.table', 'sessions'))
            ->where('user_id', $request->user()->id)
            ->orderByDesc('last_activity')
            ->get()
            ->map(fn (object $session) => (object) [
                'device' => $this->describeDevice((string) $session->user_agent),
                'ip' => $session->ip_address,
                'lastActive' => Carbon::createFromTimestamp($session->last_activity),
                'isCurrent' => $session->id === $request->session()->getId(),
            ]);

        return view('settings.sessions', ['sessions' => $sessions]);
    }

    /**
     * Sign out everywhere except this browser. Asks for the password, so
     * someone at an unlocked computer can't kick the real owner out.
     */
    public function logoutOtherSessions(Request $request): RedirectResponse
    {
        $request->validateWithBag('logoutOtherSessions', [
            'password' => ['required', 'current_password'],
        ]);

        // Invalidates "remember me" cookies and sessions on other devices...
        Auth::logoutOtherDevices($request->input('password'));

        // ...and removes their session records outright.
        DB::table(config('session.table', 'sessions'))
            ->where('user_id', $request->user()->id)
            ->where('id', '!=', $request->session()->getId())
            ->delete();

        return back()->with('success', 'Logged out of all other devices.');
    }

    public function tokens(Request $request): View
    {
        return view('settings.tokens', [
            'tokens' => $request->user()->tokens()->latest()->get(),
            // Shown once, right after creating a token; it can't be read back later.
            'plainToken' => session('plainToken'),
        ]);
    }

    public function storeToken(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'access' => ['required', Rule::in(['read', 'write'])],
        ]);

        $abilities = $data['access'] === 'write'
            ? [EnsureTokenCanAct::READ, EnsureTokenCanAct::WRITE]
            : [EnsureTokenCanAct::READ];

        $token = $request->user()->createToken($data['name'], $abilities);

        return redirect()->route('settings.tokens')
            ->with('plainToken', $token->plainTextToken)
            ->with('success', 'Token created. Copy it now: it will not be shown again.');
    }

    public function destroyToken(Request $request, int $tokenId): RedirectResponse
    {
        // Only the user's own tokens: anyone else's id is simply "not found".
        $request->user()->tokens()->whereKey($tokenId)->firstOrFail()->delete();

        return back()->with('success', 'Token revoked. Apps using it can no longer connect.');
    }

    /**
     * A rough "Chrome on Windows" from a user agent string. Good enough to
     * recognise your own devices; not meant to be exact.
     */
    private function describeDevice(string $userAgent): string
    {
        $browser = match (true) {
            str_contains($userAgent, 'Edg/') => 'Edge',
            str_contains($userAgent, 'OPR/') => 'Opera',
            str_contains($userAgent, 'Firefox/') => 'Firefox',
            str_contains($userAgent, 'Chrome/') => 'Chrome',
            str_contains($userAgent, 'Safari/') => 'Safari',
            default => 'Unknown browser',
        };

        $platform = match (true) {
            str_contains($userAgent, 'iPhone') || str_contains($userAgent, 'iPad') => 'iOS',
            str_contains($userAgent, 'Android') => 'Android',
            str_contains($userAgent, 'Windows') => 'Windows',
            str_contains($userAgent, 'Mac OS X') => 'macOS',
            str_contains($userAgent, 'Linux') => 'Linux',
            default => 'unknown system',
        };

        return "{$browser} on {$platform}";
    }
}
