<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SubmissionController extends Controller
{
    public function update(Request $request)
    {
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

    public function submitSongs(Request $request)
    {
        try {
            $group = $request->input('group', 0);
            $round = $request->input('round', -1);

            if ($group < 0 || $group > 3) {
                return back()->withErrors(['error' => 'Invalid group']);
            }

            $round_info = DB::table('round')->where('round_number', $round)->first();

            if (!$round_info) {
                return back()->withErrors(['error' => 'Invalid round']);
            }

            $contestant_id = auth()->id();
            $season_id = $round_info->season_id;

            // NEW: Check deadline with extension hours
            $contestant = DB::table('contestants')
                ->where('id', $contestant_id)
                ->where('season_id', $season_id)
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

            // Check if already submitted
            $existing = DB::table('submissions')
                ->where('contestant_id', $contestant_id)
                ->where('round', $round)
                ->where('md_group', $group)
                ->exists();

            if ($existing) {
                return back()->withErrors(['error' => 'You have already submitted for this round']);
            }

            // Get all judges for this round/group
            $judges = DB::table('judges')
                ->where('round', $round)
                ->where('md_group', $group)
                ->pluck('id');

            if ($judges->isEmpty()) {
                return back()->withErrors(['error' => 'No judges found for this round/group']);
            }

            $submitted_count = 0;

            // Insert submissions for each judge
            foreach ($judges as $judge_id) {
                $artist = $request->input("artist_{$judge_id}");
                $title = $request->input("title_{$judge_id}");
                $url = $request->input("link_{$judge_id}");

                if ($artist && $title && $url) {
                    DB::table('submissions')->insert([
                        'contestant_id' => $contestant_id,
                        'season_id' => $season_id,
                        'judge_id' => $judge_id,
                        'round' => $round,
                        'md_group' => $group,
                        'artist' => $artist,
                        'title' => $title,
                        'url' => $url,
                        'status' => 0,
                        'override' => false,
                        'is_valid' => false  // Default to false for new submissions
                    ]);
                    $submitted_count++;
                }
            }

            if ($submitted_count === 0) {
                return back()->withErrors(['error' => 'No songs were submitted']);
            }

            // CHANGED: Updated success message for "Thank you for submitting" feature
            return redirect()->back()->with('success', 'Thank you for submitting!');
        } catch (\Exception $e) {
            \Log::error('Submission error: ' . $e->getMessage());
            return back()->withErrors(['error' => 'An error occurred: ' . $e->getMessage()]);
        }
    }
}