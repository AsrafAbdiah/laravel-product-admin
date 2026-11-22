<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsureTokenCanAct;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
}
