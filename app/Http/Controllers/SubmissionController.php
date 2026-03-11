<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SubmissionController extends Controller
{
    /**
     * Get the active season ID
     */
    private function getActiveSeasonId()
    {
        $activeSeason = DB::table('season')
            ->where('active', true)
            ->first();
        
        return $activeSeason ? $activeSeason->season_id : null;
    }

    /**
     * Save draft submissions (autosave or manual save)
     */
    public function saveDraft(Request $request)
    {
        try {
            if (!auth()->check()) {
                return response()->json(['success' => false, 'message' => 'Not authenticated'], 401);
            }

            $seasonId = $this->getActiveSeasonId();
            if (!$seasonId) {
                return response()->json(['success' => false, 'message' => 'No active season'], 404);
            }

            $group = $request->input('group', 0);
            $round = $request->input('round', -1);
            $contestantId = auth()->id();

            // Validate round is active
            $roundInfo = DB::table('round')
                ->where('round_number', $round)
                ->where('season_id', $seasonId)
                ->first();

            if (!$roundInfo || $roundInfo->status != 1) {
                return response()->json(['success' => false, 'message' => 'Round not active'], 400);
            }

            // Check deadline with extension
            $contestant = DB::table('contestants')
                ->where('id', $contestantId)
                ->where('season_id', $seasonId)
                ->first();

            if ($contestant) {
                $baseDeadline = new \DateTime($roundInfo->deadline);
                $extensionHours = $contestant->extension_hours ?? 0;
                $baseDeadline->modify("+{$extensionHours} hours");
                $now = new \DateTime();
                if ($now > $baseDeadline) {
                    return response()->json(['success' => false, 'message' => 'Deadline passed'], 400);
                }
            }

            // Check if a non-draft submission already exists
            $finalExists = DB::table('submissions')
                ->where('contestant_id', $contestantId)
                ->where('round', $round)
                ->where('md_group', $group)
                ->where('season_id', $seasonId)
                ->where('draft', false)
                ->exists();

            if ($finalExists) {
                return response()->json(['success' => false, 'message' => 'Already submitted'], 400);
            }

            $entries = $request->input('entries', []);
            $savedCount = 0;

            foreach ($entries as $entry) {
                $judgeId = $entry['judge_id'] ?? null;
                $artist = $entry['artist'] ?? '';
                $title = $entry['title'] ?? '';
                $url = $entry['url'] ?? '';

                if (!$judgeId) continue;

                // Skip completely empty entries
                if (empty($artist) && empty($title) && empty($url)) {
                    // Delete existing draft for this judge if all fields cleared
                    DB::table('submissions')
                        ->where('contestant_id', $contestantId)
                        ->where('round', $round)
                        ->where('md_group', $group)
                        ->where('season_id', $seasonId)
                        ->where('judge_id', $judgeId)
                        ->where('draft', true)
                        ->delete();
                    continue;
                }

                // Upsert: update existing draft or create new one
                $existingDraft = DB::table('submissions')
                    ->where('contestant_id', $contestantId)
                    ->where('round', $round)
                    ->where('md_group', $group)
                    ->where('season_id', $seasonId)
                    ->where('judge_id', $judgeId)
                    ->where('draft', true)
                    ->first();

                if ($existingDraft) {
                    DB::table('submissions')
                        ->where('submission_id', $existingDraft->submission_id)
                        ->update([
                            'artist' => $artist,
                            'title' => $title,
                            'url' => $url,
                        ]);
                } else {
                    DB::table('submissions')->insert([
                        'contestant_id' => $contestantId,
                        'season_id' => $seasonId,
                        'judge_id' => $judgeId,
                        'round' => $round,
                        'md_group' => $group,
                        'artist' => $artist,
                        'title' => $title,
                        'url' => $url,
                        'status' => 0,
                        'override' => false,
                        'is_valid' => false,
                        'draft' => true,
                    ]);
                }
                $savedCount++;
            }

            return response()->json(['success' => true, 'saved' => $savedCount]);
        } catch (\Exception $e) {
    \Log::error('Draft save error: ' . $e->getMessage());
    return response()->json([
        'success' => false,
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], 500);
}
    }

    public function update(Request $request)
    {
        if (!auth()->check()) {
            return response()->json(['success' => false, 'message' => 'Not authenticated'], 401);
        }

        $data = $request->only(['submission_id', 'score', 'review', 'is_valid']);
        $updateData = [];

        // Handle score - explicitly check if the key exists and allow null
        if (isset($data['score'])) {
            $updateData['score'] = $data['score'] !== '' ? $data['score'] : null;
        }

        // Handle review - explicitly check if the key exists and allow null
        if (isset($data['review'])) {
            $updateData['review'] = $data['review'] !== '' ? $data['review'] : null;
        }

        // Handle is_valid - convert to boolean
        if (isset($data['is_valid'])) {
            $updateData['is_valid'] = (bool) $data['is_valid'];
        }

        if (!empty($updateData)) {
            DB::table('submissions')
                ->where('submission_id', $data['submission_id'])
                ->update($updateData);
        }

        return response()->json(['status' => 'success', 'success' => true]);
    }

    public function updateSubmissionDetails(Request $request)
    {
        try {
            // Check permissions first
            if (auth()->user()->perms < 6) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Unauthorized - insufficient permissions'
                ], 403);
            }

            $validated = $request->validate([
                'submission_id' => 'required|integer',
                'artist' => 'required|string|max:255',
                'title' => 'required|string|max:255',
                'url' => 'required|string|max:500',
                'marked_for_resub' => 'required|boolean'
            ]);

            // Check if the column exists, if not update without it
            $updateData = [
                'artist' => $validated['artist'],
                'title' => $validated['title'],
                'url' => $validated['url'],
            ];

            // Try to check if marked_for_resub column exists
            try {
                $columns = Schema::getColumnListing('submissions');
                if (in_array('marked_for_resub', $columns)) {
                    $updateData['marked_for_resub'] = $validated['marked_for_resub'];
                }
            } catch (\Exception $e) {
                // Column doesn't exist, continue without it
            }

            $updated = DB::table('submissions')
                ->where('submission_id', $validated['submission_id'])
                ->update($updateData);

            return response()->json([
                'success' => $updated > 0,
                'message' => $updated > 0 ? 'Submission updated successfully' : 'No changes made or submission not found'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error: ' . json_encode($e->errors())
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function submitSongs(Request $request)
    {
        try {
            $seasonId = $this->getActiveSeasonId();
            if (!$seasonId) {
                return back()->withErrors(['error' => 'No active season found']);
            }

            $group = $request->input('group', 0);
            $round = $request->input('round', -1);

            if ($group < 0 || $group > 3) {
                return back()->withErrors(['error' => 'Invalid group']);
            }

            $round_info = DB::table('round')
                ->where('round_number', $round)
                ->where('season_id', $seasonId)
                ->first();

            if (!$round_info) {
                return back()->withErrors(['error' => 'Invalid round']);
            }

            $contestant_id = auth()->id();

            // Check deadline with extension hours
            $contestant = DB::table('contestants')
                ->where('id', $contestant_id)
                ->where('season_id', $seasonId)
                ->first();

            if ($contestant) {
                $baseDeadline = new \DateTime($round_info->deadline);
                $extensionHours = $contestant->extension_hours ?? 0;
                $baseDeadline->modify("+{$extensionHours} hours");
                $now = new \DateTime();

                if ($now > $baseDeadline) {
                    return back()->withErrors(['error' => 'The submission deadline has passed.']);
                }
            }

            // Check if already submitted (non-draft)
            $existing = DB::table('submissions')
                ->where('contestant_id', $contestant_id)
                ->where('round', $round)
                ->where('md_group', $group)
                ->where('season_id', $seasonId)
                ->where('draft', false)
                ->exists();

            if ($existing) {
                return back()->withErrors(['error' => 'You have already submitted for this round']);
            }

            // Get all judges for this round/group
            $judges = DB::table('judges')
                ->where('round', $round)
                ->where('md_group', $group)
                ->where('season_id', $seasonId)
                ->pluck('id');

            if ($judges->isEmpty()) {
                return back()->withErrors(['error' => 'No judges found for this round/group']);
            }

            $submitted_count = 0;

            // Insert/update submissions for each judge
            foreach ($judges as $judge_id) {
                $artist = $request->input("artist_{$judge_id}");
                $title = $request->input("title_{$judge_id}");
                $url = $request->input("link_{$judge_id}");

                if ($artist && $title && $url) {
                    // Check if a draft exists for this judge
                    $existingDraft = DB::table('submissions')
                        ->where('contestant_id', $contestant_id)
                        ->where('round', $round)
                        ->where('md_group', $group)
                        ->where('season_id', $seasonId)
                        ->where('judge_id', $judge_id)
                        ->where('draft', true)
                        ->first();

                    if ($existingDraft) {
                        // Update the draft to a final submission
                        DB::table('submissions')
                            ->where('submission_id', $existingDraft->submission_id)
                            ->update([
                                'artist' => $artist,
                                'title' => $title,
                                'url' => $url,
                                'draft' => false,
                                'status' => 0,
                                'is_valid' => false,
                            ]);
                    } else {
                        // Insert new final submission
                        DB::table('submissions')->insert([
                            'contestant_id' => $contestant_id,
                            'season_id' => $seasonId,
                            'judge_id' => $judge_id,
                            'round' => $round,
                            'md_group' => $group,
                            'artist' => $artist,
                            'title' => $title,
                            'url' => $url,
                            'status' => 0,
                            'override' => false,
                            'is_valid' => false,
                            'draft' => false,
                        ]);
                    }
                    $submitted_count++;
                }
            }

            if ($submitted_count === 0) {
                return back()->withErrors(['error' => 'No songs were submitted']);
            }

            // Update contestant's submission_date to current timestamp
            DB::table('contestants')
                ->where('id', $contestant_id)
                ->where('season_id', $seasonId)
                ->update(['submission_date' => now()]);

            return redirect()->back()->with('success', 'Thank you for submitting!');
        } catch (\Exception $e) {
            \Log::error('Submission error: ' . $e->getMessage());
            return back()->withErrors(['error' => 'An error occurred: ' . $e->getMessage()]);
        }
    }
}