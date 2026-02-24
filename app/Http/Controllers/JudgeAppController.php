<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class JudgeAppController extends Controller
{
    /**
     * Save draft judge application (autosave or manual)
     */
    public function saveDraft(Request $request)
    {
        try {
            if (!auth()->check()) {
                return response()->json(['success' => false, 'message' => 'Not authenticated'], 401);
            }

            $userId = Auth::id();

            // Check if a final (non-draft) submission already exists
            $finalExists = DB::table('apps')
                ->where('user_id', $userId)
                ->where('draft', false)
                ->exists();

            if ($finalExists) {
                return response()->json(['success' => false, 'message' => 'Already submitted'], 400);
            }

            $data = $request->only([
                'fav_artists', 'least_fav_artists', 'fav_genres', 'least_fav_genres',
                'judging_style', 'safe_pick_criteria', 'extra_streaming',
                'bonus', 'banned_artists', 'longer'
            ]);

            // Check if all fields are empty
            $hasData = false;
            foreach ($data as $value) {
                if (!empty($value) && $value !== null) {
                    $hasData = true;
                    break;
                }
            }

            if (!$hasData) {
                return response()->json(['success' => false, 'message' => 'Nothing to save'], 200);
            }

            // Handle nullable fields
            $bonus = isset($data['bonus']) && $data['bonus'] !== '' ? (bool) $data['bonus'] : null;
            $longer = isset($data['longer']) && $data['longer'] !== '' ? (bool) $data['longer'] : null;

            $appData = [
                'fav_artists' => $data['fav_artists'] ?? '',
                'least_fav_artists' => $data['least_fav_artists'] ?? '',
                'fav_genres' => $data['fav_genres'] ?? '',
                'least_fav_genres' => $data['least_fav_genres'] ?? '',
                'judging_style' => $data['judging_style'] ?? '',
                'safe_pick_criteria' => $data['safe_pick_criteria'] ?? '',
                'extra_streaming' => $data['extra_streaming'] ?? '',
                'bonus' => $bonus,
                'banned_artists' => $data['banned_artists'] ?? '',
                'longer' => $longer,
                'updated_at' => now(),
            ];

            $existingDraft = DB::table('apps')
                ->where('user_id', $userId)
                ->where('draft', true)
                ->first();

            if ($existingDraft) {
                DB::table('apps')
                    ->where('id', $existingDraft->id)
                    ->update($appData);
            } else {
                DB::table('apps')->insert(array_merge($appData, [
                    'user_id' => $userId,
                    'draft' => true,
                    'created_at' => now(),
                ]));
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            \Log::error('Judge app draft save error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

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
            'longer' => 'nullable|boolean',
        ]);

        // Handle "Other" streaming service - store the actual value
        $extraStreaming = $validated['extra_streaming'];
        if ($extraStreaming === 'Other' && !empty($validated['extra_streaming_other'])) {
            $extraStreaming = $validated['extra_streaming_other'];
        }

        // Handle nullable longer field
        $longer = isset($validated['longer']) ? $validated['longer'] : null;

        $userId = Auth::id();
        $isEditing = $request->input('is_editing') == '1';

        $appData = [
            'fav_artists' => $validated['fav_artists'],
            'least_fav_artists' => $validated['least_fav_artists'],
            'fav_genres' => $validated['fav_genres'],
            'least_fav_genres' => $validated['least_fav_genres'],
            'judging_style' => $validated['judging_style'],
            'safe_pick_criteria' => $validated['safe_pick_criteria'],
            'extra_streaming' => $extraStreaming,
            'bonus' => $validated['bonus'],
            'banned_artists' => $validated['banned_artists'],
            'longer' => $longer,
            'updated_at' => now(),
        ];

        // Check if user already has a final application
        $existingApp = DB::table('apps')
            ->where('user_id', $userId)
            ->where('draft', false)
            ->first();

        if ($existingApp || $isEditing) {
            // Update existing final application
            DB::table('apps')
                ->where('user_id', $userId)
                ->where('draft', false)
                ->update($appData);

            return redirect()->back()->with('success', 'Your judge application has been updated successfully!');
        } else {
            // Check if a draft exists — promote it
            $existingDraft = DB::table('apps')
                ->where('user_id', $userId)
                ->where('draft', true)
                ->first();

            if ($existingDraft) {
                DB::table('apps')
                    ->where('id', $existingDraft->id)
                    ->update(array_merge($appData, [
                        'draft' => false,
                    ]));
            } else {
                DB::table('apps')->insert(array_merge($appData, [
                    'user_id' => $userId,
                    'draft' => false,
                    'created_at' => now(),
                ]));
            }

            return redirect()->back()->with('success', 'Your judge application has been submitted successfully!');
        }
    }
}