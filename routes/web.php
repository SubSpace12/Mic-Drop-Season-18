<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SubmissionController;
use App\Http\Controllers\JudgeAppController;
use App\Http\Controllers\AppVoteController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\SlideBGController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('dashboard');
});


Route::get('/view-apps', function () {
    return view('view-apps');
});

Route::get('/judging', function () {
    return view('judging');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth'])->name('dashboard');

Route::post('/judge-vote', [AppVoteController::class, 'handleJudgeVote'])->middleware('auth');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    
});

Route::get('/results', function () {
    return view('results');
});

Route::get('/submit-judge-app', function () {
    return view('submit-judge-app');
});

Route::get('/submit', function() {
    return view('submit');
});

Route::get('/admin', function() {
    return view('admin');
});


Route::post('/submit-songs', [SubmissionController::class, 'submitSongs'])
    ->name('submit.songs')
    ->middleware('auth');


Route::get('/judge-vote-counts/{appId}', [AppVoteController::class, 'getVoteCounts'])->middleware('auth');

Route::post('/apps', [JudgeAppController::class, 'store'])->name('apps.store');

Route::post('/update-submission', [SubmissionController::class, 'update']);

Route::post('/update-submission-details', function() {
    try {
        // Check permissions first
        if (auth()->user()->perms < 6) {
            return response()->json([
                'success' => false, 
                'message' => 'Unauthorized - insufficient permissions'
            ], 403);
        }
        
        $validated = request()->validate([
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
})->middleware('auth');

// ============================================================
// ADMIN ROUND MANAGEMENT ROUTES
// ============================================================

Route::post('/admin/complete-round', [AdminController::class, 'completeRound'])
    ->middleware(['auth'])
    ->name('admin.complete-round');

// Start Round
Route::post('/admin/start-round', [AdminController::class, 'startRound'])
    ->middleware(['auth'])
    ->name('admin.start-round');

// Reset Judges
Route::post('/admin/reset-judges', [AdminController::class, 'resetJudges'])
    ->middleware(['auth'])
    ->name('admin.reset-judges');

// Generate Round (assign judges)
Route::post('/admin/generate-round', [AdminController::class, 'generateRound'])
    ->middleware(['auth'])
    ->name('admin.generate-round');

// Update Round Details
Route::post('/admin/update-round', [AdminController::class, 'updateRound'])
    ->middleware(['auth'])
    ->name('admin.update-round');

// Contestant Management
Route::post('/admin/dropout-contestant', [AdminController::class, 'dropoutContestant'])
    ->middleware(['auth'])
    ->name('admin.dropout-contestant');

Route::post('/admin/restore-contestant', [AdminController::class, 'restoreContestant'])
    ->middleware(['auth'])
    ->name('admin.restore-contestant');

Route::post('/admin/grant-extension', [AdminController::class, 'grantExtension'])
    ->middleware(['auth'])
    ->name('admin.grant-extension');


Route::post('/update-slide-backgrounds', [SlideBGController::class, 'update'])
    ->name('update.slide.backgrounds')
    ->middleware(['auth']);


// Add this test route
Route::get('/test-simple', function () {
    return view('test-simple');
});

Route::get('/test-with-layout', function () {
    return view('test-with-layout');
});