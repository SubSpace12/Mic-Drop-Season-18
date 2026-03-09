<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
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
     * Complete the active round (eliminate contestants and change status to 2)
     */
    public function completeRound(Request $request)
{
    $request->validate([
        'round_number' => 'required|integer',
        'contestants' => 'required|json'
    ]);

    $seasonId = $this->getActiveSeasonId();
    if (!$seasonId) {
        return redirect('/admin')->with('error', 'No active season found.');
    }

    $roundNumber = $request->input('round_number');
    $contestantIds = json_decode($request->input('contestants'), true);

    // Verify the round is active
    $round = DB::table('round')
        ->where('round_number', $roundNumber)
        ->where('season_id', $seasonId)
        ->where('status', 1)
        ->first();

    if (!$round) {
        return redirect('/admin')->with('error', 'Round is not active or does not exist.');
    }

    // Verify deadline has passed
    if (strtotime($round->deadline) >= time()) {
        return redirect('/admin')->with('error', 'Cannot complete round before deadline has passed.');
    }

    // Verify all scores are submitted (no null scores)
    $nullScores = DB::table('submissions')
        ->where('round', $roundNumber)
        ->where('season_id', $seasonId)
        ->whereNull('score')
        ->count();

    if ($nullScores > 0) {
        return redirect('/admin')->with('error', 'Cannot complete round until all judges have submitted scores.');
    }

    try {
        // Begin transaction - all operations must succeed together
        DB::beginTransaction();

        // Step 1: Eliminate contestants if any
        $eliminatedCount = 0;
        if (!empty($contestantIds)) {
            $eliminatedCount = DB::table('contestants')
                ->whereIn('id', $contestantIds)
                ->where('season_id', $seasonId)
                ->where('eliminated', false)
                ->update(['eliminated' => true, 'round_eliminated' => $roundNumber]);
            
            // Reset permissions for eliminated contestants to 0 (spectator)
            DB::table('users')
                ->whereIn('id', $contestantIds)
                ->update(['perms' => 0]);
        }

        // Step 2: Reset all judge permissions (2-5) to 0 (spectator)
        DB::table('users')
            ->whereIn('perms', [2, 3, 4, 5])
            ->update(['perms' => 0]);

        // Step 3: Set all submission_date values to null in contestants table
        DB::table('contestants')
            ->where('season_id', $seasonId)
            ->update(['submission_date' => null]);

        // Step 4: Mark round as completed
        DB::table('round')
            ->where('round_number', $roundNumber)
            ->where('season_id', $seasonId)
            ->where('status', 1)
            ->update(['status' => 2]);

        // Commit transaction - all actions succeed
        DB::commit();

        $message = "Round {$roundNumber} has been completed.";
        if ($eliminatedCount > 0) {
            $message .= " {$eliminatedCount} contestant(s) have been eliminated.";
        }
        $message .= " All judge and eliminated contestant permissions have been reset. Submission dates cleared.";
        
        return redirect('/admin')->with('success', $message);

    } catch (\Exception $e) {
        // Rollback transaction - no changes occur
        DB::rollBack();
        return redirect('/admin')->with('error', 'An error occurred: ' . $e->getMessage());
    }
}

    /**
     * Start a pending round (change status to 1)
     */
    public function startRound(Request $request)
    {
        $request->validate([
            'round_number' => 'required|integer'
        ]);

        $seasonId = $this->getActiveSeasonId();
        if (!$seasonId) {
            return redirect('/admin')->with('error', 'No active season found.');
        }

        $roundNumber = $request->input('round_number');

        try {
            // First, set any currently active rounds to completed
            DB::table('round')
                ->where('season_id', $seasonId)
                ->where('status', 1)
                ->update(['status' => 2]);

            // Start the new round
            DB::table('round')
                ->where('round_number', $roundNumber)
                ->where('season_id', $seasonId)
                ->where('status', 0)
                ->update(['status' => 1]);

            return redirect('/admin')->with('success', "Round {$roundNumber} has been started.");
        } catch (\Exception $e) {
            return redirect('/admin')->with('error', 'An error occurred: ' . $e->getMessage());
        }
    }

    /**
     * Reset judges for a round
     */
    public function resetJudges(Request $request)
    {
        $request->validate([
            'round_number' => 'required|integer'
        ]);

        $seasonId = $this->getActiveSeasonId();
        if (!$seasonId) {
            return redirect('/admin')->with('error', 'No active season found.');
        }

        $roundNumber = $request->input('round_number');

        try {
            DB::table('judges')
                ->where('round', $roundNumber)
                ->where('season_id', $seasonId)
                ->delete();

            return redirect('/admin')->with('success', "Judges for Round {$roundNumber} have been reset.");
        } catch (\Exception $e) {
            return redirect('/admin')->with('error', 'An error occurred: ' . $e->getMessage());
        }
    }

    /**
     * Generate judge assignments for a round
     */
    public function generateRound(Request $request)
    {
        $request->validate([
            'round_number' => 'required|integer',
            'is_merge' => 'required|boolean'
        ]);

        $seasonId = $this->getActiveSeasonId();
        if (!$seasonId) {
            return response()->json(['success' => false, 'message' => 'No active season found.']);
        }

        $roundNumber = $request->input('round_number');
        $isMerge = $request->input('is_merge');

        try {
            DB::beginTransaction();

            $allJudgeIds = [];

            if ($isMerge) {
                // Merge round - single group
                $judges = json_decode($request->input('judges')['merge'], true);
                
                if (count($judges) !== 3) {
                    return response()->json(['success' => false, 'message' => 'Exactly 3 judges required for merge round.']);
                }

                foreach ($judges as $index => $judgeId) {
                    DB::table('judges')->insert([
                        'id' => $judgeId,
                        'season_id' => $seasonId,
                        'round' => $roundNumber,
                        'md_group' => 0,
                        'judge_number' => $index
                    ]);
                    $allJudgeIds[] = $judgeId;
                }

                // Set permission level 2 (judge) for merge round judges
                // Only update if current permission is 0 (spectator)
                DB::table('users')
                    ->whereIn('id', $allJudgeIds)
                    ->where('perms', 0)
                    ->update(['perms' => 2]);

            } else {
                // Group round - 3 groups
                for ($group = 1; $group <= 3; $group++) {
                    $judges = json_decode($request->input('judges')[$group], true);
                    
                    if (count($judges) !== 3) {
                        DB::rollBack();
                        return response()->json(['success' => false, 'message' => "Exactly 3 judges required for Group {$group}."]);
                    }

                    $groupJudgeIds = [];
                    foreach ($judges as $index => $judgeId) {
                        DB::table('judges')->insert([
                            'id' => $judgeId,
                            'season_id' => $seasonId,
                            'round' => $roundNumber,
                            'md_group' => $group,
                            'judge_number' => $index
                        ]);
                        $groupJudgeIds[] = $judgeId;
                    }

                    // Set permission level based on group
                    // Group 1 = perms 3, Group 2 = perms 4, Group 3 = perms 5
                    // Only update if current permission is 0 (spectator)
                    $permLevel = 2 + $group; // 3, 4, or 5
                    DB::table('users')
                        ->whereIn('id', $groupJudgeIds)
                        ->where('perms', 0)
                        ->update(['perms' => $permLevel]);
                }
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Judge assignments created successfully. Judge permissions updated.']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
        }
    }

    /**
     * Update round details
     */
    public function updateRound(Request $request)
    {
        $request->validate([
            'round_number' => 'required|integer',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'theme_details' => 'nullable|string',
            'eliminate_number' => 'required|integer|min:0',
            'deadline' => 'required|date'
        ]);

        $seasonId = $this->getActiveSeasonId();
        if (!$seasonId) {
            return redirect('/admin')->with('error', 'No active season found.');
        }

        $roundNumber = $request->input('round_number');

        try {
            DB::table('round')
                ->where('round_number', $roundNumber)
                ->where('season_id', $seasonId)
                ->update([
                    'title' => $request->input('title'),
                    'description' => $request->input('description'),
                    'theme_details' => $request->input('theme_details'),
                    'eliminate_number' => $request->input('eliminate_number'),
                    'deadline' => $request->input('deadline')
                ]);

            return redirect('/admin')->with('success', "Round {$roundNumber} has been updated.");
        } catch (\Exception $e) {
            return redirect('/admin')->with('error', 'An error occurred: ' . $e->getMessage());
        }
    }

    /**
     * Mark contestant as dropped out
     */
    public function dropoutContestant(Request $request)
    {
        $request->validate([
            'contestant_id' => 'required',
            'round_number' => 'required|integer'
        ]);

        $seasonId = $this->getActiveSeasonId();
        if (!$seasonId) {
            return redirect('/admin')->with('error', 'No active season found.');
        }

        $contestantId = $request->input('contestant_id');
        $roundNumber = $request->input('round_number');

        // Verify the round is active
        $round = DB::table('round')
            ->where('round_number', $roundNumber)
            ->where('season_id', $seasonId)
            ->where('status', 1)
            ->first();

        if (!$round) {
            return redirect('/admin')->with('error', 'Can only mark contestants as dropped out for active rounds.');
        }

        try {
            // Get contestant name for message
            $contestant = DB::table('contestants')
                ->join('users', 'contestants.id', '=', 'users.id')
                ->where('contestants.id', $contestantId)
                ->where('contestants.season_id', $seasonId)
                ->select('users.global_name')
                ->first();

            if (!$contestant) {
                return redirect('/admin')->with('error', 'Contestant not found.');
            }

            // Mark as dropped out (special = true)
            DB::table('contestants')
                ->where('id', $contestantId)
                ->where('season_id', $seasonId)
                ->update(['special' => true]);

            return redirect('/admin')->with('success', "{$contestant->global_name} has been marked as dropped out.");
        } catch (\Exception $e) {
            return redirect('/admin')->with('error', 'An error occurred: ' . $e->getMessage());
        }
    }

    /**
     * Restore contestant from dropped out status
     */
    public function restoreContestant(Request $request)
    {
        $request->validate([
            'contestant_id' => 'required',
            'round_number' => 'required|integer'
        ]);

        $seasonId = $this->getActiveSeasonId();
        if (!$seasonId) {
            return redirect('/admin')->with('error', 'No active season found.');
        }

        $contestantId = $request->input('contestant_id');
        $roundNumber = $request->input('round_number');

        // Verify the round is active
        $round = DB::table('round')
            ->where('round_number', $roundNumber)
            ->where('season_id', $seasonId)
            ->where('status', 1)
            ->first();

        if (!$round) {
            return redirect('/admin')->with('error', 'Can only restore contestants for active rounds.');
        }

        try {
            // Get contestant name for message
            $contestant = DB::table('contestants')
                ->join('users', 'contestants.id', '=', 'users.id')
                ->where('contestants.id', $contestantId)
                ->where('contestants.season_id', $seasonId)
                ->select('users.global_name')
                ->first();

            if (!$contestant) {
                return redirect('/admin')->with('error', 'Contestant not found.');
            }

            // Restore (special = false)
            DB::table('contestants')
                ->where('id', $contestantId)
                ->where('season_id', $seasonId)
                ->update(['special' => false]);

            return redirect('/admin')->with('success', "{$contestant->global_name} has been restored.");
        } catch (\Exception $e) {
            return redirect('/admin')->with('error', 'An error occurred: ' . $e->getMessage());
        }
    }

    /**
     * Grant extension to contestant
     */
    public function grantExtension(Request $request)
    {
        $request->validate([
            'contestant_id' => 'required',
            'round_number' => 'required|integer',
            'extension_hours' => 'required|integer|min:0|max:168'
        ]);

        $seasonId = $this->getActiveSeasonId();
        if (!$seasonId) {
            return redirect('/admin')->with('error', 'No active season found.');
        }

        $contestantId = $request->input('contestant_id');
        $roundNumber = $request->input('round_number');
        $extensionHours = $request->input('extension_hours');

        // Verify the round is active
        $round = DB::table('round')
            ->where('round_number', $roundNumber)
            ->where('season_id', $seasonId)
            ->where('status', 1)
            ->first();

        if (!$round) {
            return redirect('/admin')->with('error', 'Can only grant extensions for active rounds.');
        }

        try {
            // Get contestant name for message
            $contestant = DB::table('contestants')
                ->join('users', 'contestants.id', '=', 'users.id')
                ->where('contestants.id', $contestantId)
                ->where('contestants.season_id', $seasonId)
                ->select('users.global_name')
                ->first();

            if (!$contestant) {
                return redirect('/admin')->with('error', 'Contestant not found.');
            }

            // Update extension hours
            DB::table('contestants')
                ->where('id', $contestantId)
                ->where('season_id', $seasonId)
                ->update(['extension_hours' => $extensionHours]);

            if ($extensionHours > 0) {
                return redirect('/admin')->with('success', "{$contestant->global_name} has been granted a {$extensionHours} hour extension.");
            } else {
                return redirect('/admin')->with('success', "Extension removed for {$contestant->global_name}.");
            }
        } catch (\Exception $e) {
            return redirect('/admin')->with('error', 'An error occurred: ' . $e->getMessage());
        }
    }
}