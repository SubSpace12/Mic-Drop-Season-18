<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class JudgeAppController extends Controller
{
    public function store(Request $request)
    {
        // Validate the incoming request
        $validated = $request->validate([
            'fav_artists' => 'required|string',
            'least_fav_artists' => 'nullable|string',
            'fav_genres' => 'required|string',
            'least_fav_genres' => 'required|string',
            'judging_style' => 'required|string',
            'safe_pick_criteria' => 'required|string',
            'extra_streaming' => 'required|string|in:Spotify,Apple Music,Tidal,Deezer,Qobuz,None,Other',
            'extra_streaming_other' => 'nullable|required_if:extra_streaming,Other|string|max:255',
            'bonus' => 'required|boolean',
            'banned_artists' => 'required|string',
            'longer' => 'required|boolean',
        ]);

        // Handle "Other" streaming service - store the actual value
        $extraStreaming = $validated['extra_streaming'];
        if ($extraStreaming === 'Other' && !empty($validated['extra_streaming_other'])) {
            $extraStreaming = $validated['extra_streaming_other'];
        }

        $userId = Auth::id();
        $isEditing = $request->input('is_editing') == '1';

        // Check if user already has an application
        $existingApp = DB::table('apps')
            ->where('user_id', $userId)
            ->first();

        if ($existingApp || $isEditing) {
            // Update existing application
            DB::table('apps')
                ->where('user_id', $userId)
                ->update([
                    'fav_artists' => $validated['fav_artists'],
                    'least_fav_artists' => $validated['least_fav_artists'],
                    'fav_genres' => $validated['fav_genres'],
                    'least_fav_genres' => $validated['least_fav_genres'],
                    'judging_style' => $validated['judging_style'],
                    'safe_pick_criteria' => $validated['safe_pick_criteria'],
                    'extra_streaming' => $extraStreaming,
                    'bonus' => $validated['bonus'],
                    'banned_artists' => $validated['banned_artists'],
                    'longer' => $validated['longer'],
                    'updated_at' => now(),
                ]);

            return redirect()->back()->with('success', 'Your judge application has been updated successfully!');
        } else {
            // Create new application
            DB::table('apps')->insert([
                'user_id' => $userId,
                'fav_artists' => $validated['fav_artists'],
                'least_fav_artists' => $validated['least_fav_artists'],
                'fav_genres' => $validated['fav_genres'],
                'least_fav_genres' => $validated['least_fav_genres'],
                'judging_style' => $validated['judging_style'],
                'safe_pick_criteria' => $validated['safe_pick_criteria'],
                'extra_streaming' => $extraStreaming,
                'bonus' => $validated['bonus'],
                'banned_artists' => $validated['banned_artists'],
                'longer' => $validated['longer'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return redirect()->back()->with('success', 'Your judge application has been submitted successfully!');
        }
    }
}