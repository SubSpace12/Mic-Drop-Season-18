<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use MathPHP\Statistics\Descriptive;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    /**
     * Display a user's public profile by Discord ID.
     */
    public function show(string $discordId)
    {
        // ── 1. User ───────────────────────────────────────────────
        $user = DB::table('users')->where('id', $discordId)->first();

        if (!$user) {
            abort(404, 'User not found.');
        }

        // ── 2. Active season ──────────────────────────────────────
        // status: 0 = upcoming, 1 = active, 2 = finished
        $activeSeason = DB::table('season')->where('active', true)->first();

        $seasonId = $activeSeason?->season_id;

        // ── 3. Status label ───────────────────────────────────────
        $statusMap = [
            0 => ['label' => 'Spectator',   'class' => 'status-spectator'],
            1 => ['label' => 'Contestant',  'class' => 'status-contestant'],
            2 => ['label' => 'Judge',       'class' => 'status-judge'],
            3 => ['label' => 'Judge',       'class' => 'status-judge'],
            4 => ['label' => 'Judge',       'class' => 'status-judge'],
            5 => ['label' => 'Judge',       'class' => 'status-judge'],
            6 => ['label' => 'Staff',       'class' => 'status-staff'],
            7 => ['label' => 'Host',        'class' => 'status-host'],
        ];

        $perms = (int) ($user->perms ?? 0);
        $statusInfo = $statusMap[$perms] ?? $statusMap[0];

        // ── 4. Discord avatar URL ─────────────────────────────────
        $avatarUrl = null;
        if (!empty($user->avatar)) {
            $avatarUrl = "https://cdn.discordapp.com/avatars/{$discordId}/{$user->avatar}.png?size=256";
        } else {
            // Default Discord avatar (based on discriminator / new system)
            $index = (intval($discordId) >> 22) % 6;
            $avatarUrl = "https://cdn.discordapp.com/embed/avatars/{$index}.png";
        }

        // ── 5. Contestant stats (received scores) ─────────────────
        $contestantScores  = null;
        $contestantHighest = [];
        $contestantLowest  = [];

        if ($seasonId) {
            $isContestant = DB::table('contestants')
                ->where('id', $discordId)
                ->where('season_id', $seasonId)
                ->exists();

            if ($isContestant) {
                // Only from finished rounds (status = 2)
                $scores = DB::table('submissions')
                    ->join('round', function ($join) use ($seasonId) {
                        $join->on('round.round_number', '=', 'submissions.round')
                             ->where('round.season_id', '=', $seasonId)
                             ->where('round.status', '=', 2);
                    })
                    ->join('users as judge_users', 'judge_users.id', '=', 'submissions.judge_id')
                    ->where('submissions.contestant_id', $discordId)
                    ->where('submissions.season_id', $seasonId)
                    ->whereNotNull('submissions.score')
                    ->where('submissions.is_valid', true)
                    ->select(
                        'submissions.score',
                        'submissions.artist',
                        'submissions.title',
                        'submissions.review',
                        'submissions.round',
                        'judge_users.global_name as judge_name',
                        'judge_users.username as judge_username'
                    )
                    ->orderByDesc('submissions.score')
                    ->get();

                $contestantHighest = $scores->take(3)->values();
                $contestantLowest  = $scores->sortBy('score')->take(3)->values();
                $contestantScores  = $scores;
            }
        }

        // ── 6. Judge stats (given scores) ─────────────────────────
        $judgeScores  = null;
        $judgeHighest = [];
        $judgeLowest  = [];

        if ($seasonId) {
            $isJudge = DB::table('judges')
                ->where('id', $discordId)
                ->where('season_id', $seasonId)
                ->exists();

            if ($isJudge) {
                $givenScores = DB::table('submissions')
                    ->join('round', function ($join) use ($seasonId) {
                        $join->on('round.round_number', '=', 'submissions.round')
                             ->where('round.season_id', '=', $seasonId)
                             ->where('round.status', '=', 2);
                    })
                    ->join('users as contestant_users', 'contestant_users.id', '=', 'submissions.contestant_id')
                    ->where('submissions.judge_id', $discordId)
                    ->where('submissions.season_id', $seasonId)
                    ->whereNotNull('submissions.score')
                    ->where('submissions.is_valid', true)
                    ->select(
                        'submissions.score',
                        'submissions.artist',
                        'submissions.title',
                        'submissions.review',
                        'submissions.round',
                        'contestant_users.global_name as contestant_name',
                        'contestant_users.username as contestant_username'
                    )
                    ->orderByDesc('submissions.score')
                    ->get();

                $judgeHighest = $givenScores->take(3)->values();
                $judgeLowest  = $givenScores->sortBy('score')->take(3)->values();
                $judgeScores  = $givenScores;
            }
        }

        return view('profile', compact(
            'user',
            'discordId',
            'avatarUrl',
            'statusInfo',
            'perms',
            'activeSeason',
            'contestantScores',
            'contestantHighest',
            'contestantLowest',
            'judgeScores',
            'judgeHighest',
            'judgeLowest'
        ));
    }
}