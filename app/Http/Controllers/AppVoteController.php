<?php
namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class AppVoteController extends Controller
{
    public function handleJudgeVote(Request $request)
    {
        $request->validate([
            'app_id' => 'required|numeric',
            'score' => 'nullable|integer|in:-1,1,2', // -1 = down, 1 = up, 2 = strong like
            'comment' => 'nullable|string|max:5000'
        ]);

        $appId = $request->app_id;
        $score = $request->score;
        $comment = $request->comment;
        $staffId = auth()->id();

        // Check if user has already voted/commented on this app
        $existingEntry = DB::table('judge_upvotes')
            ->where('app_id', $appId)
            ->where('staff_id', $staffId)
            ->first();

        if ($existingEntry) {
            // Update existing entry
            $updateData = [];
            if ($request->has('score')) {
                $updateData['score'] = $score;
            }
            if ($request->has('comment')) {
                $updateData['comment'] = $comment;
            }
            
            DB::table('judge_upvotes')
                ->where('app_id', $appId)
                ->where('staff_id', $staffId)
                ->update($updateData);
        } else {
            // Insert new entry
            DB::table('judge_upvotes')->insert([
                'app_id' => $appId,
                'staff_id' => $staffId,
                'score' => $score,
                'comment' => $comment
            ]);
        }

        return response()->json(['success' => true]);
    }

    public function getVoteCounts($appId)
    {
        $counts = DB::table('judge_upvotes')
            ->where('app_id', $appId)
            ->selectRaw('
                SUM(CASE WHEN score = 2 THEN 1 ELSE 0 END) as strong_like,
                SUM(CASE WHEN score = 1 THEN 1 ELSE 0 END) as thumbs_up,
                SUM(CASE WHEN score = -1 THEN 1 ELSE 0 END) as thumbs_down
            ')
            ->first();

        return response()->json([
            'strong_like' => $counts->strong_like ?? 0,
            'thumbs_up' => $counts->thumbs_up ?? 0,
            'thumbs_down' => $counts->thumbs_down ?? 0
        ]);
    }

    public function getComments($appId)
    {
        $comments = DB::table('judge_upvotes')
            ->join('users', 'judge_upvotes.staff_id', '=', 'users.id')
            ->where('judge_upvotes.app_id', $appId)
            ->whereNotNull('judge_upvotes.comment')
            ->where('judge_upvotes.comment', '!=', '')
            ->select(
                'judge_upvotes.comment',
                'judge_upvotes.score',
                'users.global_name',
                'judge_upvotes.staff_id'
            )
            ->orderBy('judge_upvotes.staff_id', 'asc')
            ->get();

        return response()->json(['comments' => $comments]);
    }
}