<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class AppVoteController extends Controller
{

        


    public function handleJudgeVote(Request $request)
    {
        $request->validate([
            'app_id' => 'required|numeric', // Changed from 'integer' to 'numeric'
            'score' => 'required|boolean'
        ]);

        $appId = $request->app_id;
        $score = $request->score;
        $staffId = auth()->id();

        // Check if user has already voted on this app
        $existingVote = DB::table('judge_upvotes')
            ->where('app_id', $appId)
            ->where('staff_id', $staffId)
            ->first();

        if ($existingVote) {
            // Update existing vote
            DB::table('judge_upvotes')
                ->where('app_id', $appId)
                ->where('staff_id', $staffId)
                ->update(['score' => $score]);
        } else {
            // Insert new vote
            DB::table('judge_upvotes')->insert([
                'app_id' => $appId,
                'staff_id' => $staffId,
                'score' => $score
            ]);
        }

        return response()->json(['success' => true]);
    }

    public function getVoteCounts($appId)
    {
        $counts = DB::table('judge_upvotes')
            ->where('app_id', $appId)
            ->selectRaw('
                SUM(CASE WHEN score = true THEN 1 ELSE 0 END) as thumbs_up,
                SUM(CASE WHEN score = false THEN 1 ELSE 0 END) as thumbs_down
            ')
            ->first();

        return response()->json([
            'thumbs_up' => $counts->thumbs_up ?? 0,
            'thumbs_down' => $counts->thumbs_down ?? 0
        ]);
    }
}
