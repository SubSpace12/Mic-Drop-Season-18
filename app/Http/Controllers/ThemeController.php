<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ThemeController extends Controller
{
    /**
     * Available themes — add new entries here as you create them.
     * The key is the CSS class / DB value, the label+icon are for the UI.
     */
    public const THEMES = [
        'colorland'=> ['label' => 'Color Land','icon' => '◆'],
        'emoticon' => ['label' => ':3 World',   'icon' => ':3'],
        'vscode'   => ['label' => 'The Core',   'icon' => '>_'],
        
    ];

    /**
     * Update the authenticated user's theme preference.
     */
    public function update(Request $request)
    {
        $request->validate([
            'theme' => 'required|string|in:' . implode(',', array_keys(self::THEMES)),
        ]);

        $user = Auth::user();
        $user->theme = $request->theme;
        $user->save();

        return back();
    }
}