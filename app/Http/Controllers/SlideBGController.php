<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SlideBGController extends Controller
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

    public function update(Request $request)
    {
        // Check if user has staff permissions
        if (!auth()->check() || auth()->user()->perms < 6) {
            return redirect()->back()->with('error', 'Unauthorized access.');
        }

        $seasonId = $this->getActiveSeasonId();
        if (!$seasonId) {
            return redirect()->back()->with('error', 'No active season found.');
        }

        $request->validate([
            'round' => 'required|integer',
            'slidebg_first' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'slidebg_second' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'slidebg_third' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'slidebg_normal' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'slidebg_elim' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        $roundNumber = $request->input('round');
        $updateData = [];

        // Get existing round data
        $round = DB::table('round')
            ->where('round_number', $roundNumber)
            ->where('season_id', $seasonId)
            ->first();

        if (!$round) {
            return redirect()->back()->with('error', 'Round not found in active season.');
        }

        // Process each background image
        $backgrounds = ['slidebg_first', 'slidebg_second', 'slidebg_third', 'slidebg_normal', 'slidebg_elim'];
        
        foreach ($backgrounds as $bgField) {
            if ($request->hasFile($bgField)) {
                // Delete old file if it exists
                if (!empty($round->$bgField)) {
                    Storage::disk('public')->delete($round->$bgField);
                }

                // Store new file
                $file = $request->file($bgField);
                $filename = 'slides/' . $roundNumber . '_' . $bgField . '_' . time() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('slides', basename($filename), 'public');
                
                $updateData[$bgField] = $path;
            }
        }

        // Update database
        if (!empty($updateData)) {
            DB::table('round')
                ->where('round_number', $roundNumber)
                ->where('season_id', $seasonId)
                ->update($updateData);
        }

        return redirect()->back()->with('success', 'Slide backgrounds updated successfully!');
    }
}