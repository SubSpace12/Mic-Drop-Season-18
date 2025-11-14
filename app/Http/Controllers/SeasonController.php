<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SeasonController extends Controller
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
     * Join the active season
     */
    public function joinSeason(Request $request)
    {
        // Must be authenticated
        if (!auth()->check()) {
            return redirect()->route('login')->with('error', 'You must be logged in to join a season.');
        }

        $seasonId = $this->getActiveSeasonId();
        if (!$seasonId) {
            return redirect()->back()->with('error', 'No active season found.');
        }

        $userId = auth()->id();

        // Check if user is already in the season
        $alreadyJoined = DB::table('contestants')
            ->where('id', $userId)
            ->where('season_id', $seasonId)
            ->exists();

        if ($alreadyJoined) {
            return redirect()->route('dashboard')->with('info', 'You are already registered for this season.');
        }

        // Check if first round deadline has passed
        $firstRound = DB::table('round')
            ->where('season_id', $seasonId)
            ->where('round_number', 1)
            ->first();

        if ($firstRound) {
            $firstRoundDeadline = new \DateTime($firstRound->deadline);
            $now = new \DateTime();

            if ($now > $firstRoundDeadline) {
                return redirect()->route('dashboard')->with('error', 'Registration has closed. The first round deadline has passed.');
            }
        }

        // Create contestant record
        try {
            DB::table('contestants')->insert([
                'id' => $userId,
                'season_id' => $seasonId,
                'eliminated' => false,
                'md_group' => 0,
                'extension_hours' => 0,
                'special' => false,
                'submission_date' => null
            ]);

            // Set user permission level to 1 (contestant)
            DB::table('users')
                ->where('id', $userId)
                ->update(['perms' => 1]);

            return redirect()->route('dashboard')->with('success', 'Successfully joined Season ' . $seasonId . '! Welcome aboard! 🎵');
        } catch (\Exception $e) {
            \Log::error('Error joining season: ' . $e->getMessage());
            return redirect()->back()->with('error', 'An error occurred while joining the season. Please try again.');
        }
    }
}