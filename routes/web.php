<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\SeasonController;
use App\Http\Controllers\AppVoteController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SlideBGController;
use App\Http\Controllers\JudgeAppController;
use App\Http\Controllers\SubmissionController;
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

Route::post('/join-season', [SeasonController::class, 'joinSeason'])
    ->middleware('auth')
    ->name('join.season');

Route::post('/submit-songs', [SubmissionController::class, 'submitSongs'])
    ->name('submit.songs')
    ->middleware('auth');

Route::post('/submit/draft', [SubmissionController::class, 'saveDraft'])
    ->name('submit.draft')
    ->middleware('auth');


Route::get('/judge-vote-counts/{appId}', [AppVoteController::class, 'getVoteCounts'])->middleware('auth');

Route::post('/apps', [JudgeAppController::class, 'store'])->name('apps.store');

Route::post('/update-submission', [SubmissionController::class, 'update']);

Route::post('/update-submission-details', [SubmissionController::class, 'updateSubmissionDetails'])->middleware('auth');
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


Route::get('/stats', function () {
    return view('stats');
});

Route::get('/test-with-layout', function () {
    return view('test-with-layout');
});

Route::get('/judge-comments/{appId}', [AppVoteController::class, 'getComments'])
    ->middleware(['auth'])
    ->name('judge.comments');


Route::get('/error/guild-access', function () {
    return view('errors.guild-access-denied');
})->name('error.guild-access');

Route::post('/apps/draft', [JudgeAppController::class, 'saveDraft'])
    ->name('apps.draft')
    ->middleware('auth');