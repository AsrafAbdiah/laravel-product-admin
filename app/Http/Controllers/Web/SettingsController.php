<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Account settings pages. The forms post to Fortify's routes, which do the
 * actual work (profile, password, two-factor authentication).
 */
class SettingsController extends Controller
{
    public function profile(Request $request): View
    {
        return view('settings.profile', [
            'user' => $request->user(),
        ]);
    }
}
