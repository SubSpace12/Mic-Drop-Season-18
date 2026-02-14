<x-app-layout>
    @vite(['resources/css/admin.css'])
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Admin Panel - Round Generation
        </h2>
    </x-slot>

    <meta name="csrf-token" content="{{ csrf_token() }}">
    

    <div class="admin-container">
        @guest
            <div class="access-message error">
                <p style="font-size: 1.5rem; margin-bottom: 1rem;">🔒 Not Logged In</p>
                <p>Please log in to access the admin panel.</p>
            </div>
        @endguest

        @auth
            @if(auth()->user()->perms < 6)
                <div class="access-message error">
                    <p style="font-size: 1.5rem; margin-bottom: 1rem;">🚫 Access Denied</p>
                    <p>You do not have permission to access the admin panel.</p>
                    <p style="margin-top: 0.5rem; font-size: 0.875rem;">Required permission level: 6 or higher</p>
                </div>
            @else
                @php
                    // Get the active season
                    $activeSeason = DB::table('season')
                        ->where('active', true)
                        ->first();

                    // If no active season, set default
                    $seasonId = $activeSeason ? $activeSeason->season_id : null;

                    // Get the active round (status = 1)
                    $activeRound = $seasonId ? DB::table('round')
                        ->where('status', 1)
                        ->where('season_id', $seasonId)
                        ->orderBy('round_number')
                        ->first() : null;

                    // Get the first round with status = 0, ordered by round_number
                    $nextRound = $seasonId ? DB::table('round')
                        ->where('status', 0)
                        ->where('season_id', $seasonId)
                        ->orderBy('round_number')
                        ->first() : null;

                    // Get all eligible judges (users in apps table)
                    $eligibleJudges = DB::table('users')
                        ->join('apps', 'users.id', '=', 'apps.user_id')
                        ->select('users.id', 'users.global_name')
                        ->distinct()
                        ->orderBy('users.global_name')
                        ->get();

                    // Check if judges are already assigned for the next round
                    $existingJudges = null;
                    $judgesAssigned = false;
                    if ($nextRound && $seasonId) {
                        $existingJudges = DB::table('judges')
                            ->join('users', 'judges.id', '=', 'users.id')
                            ->where('judges.season_id', $seasonId)
                            ->where('judges.round', $nextRound->round_number)
                            ->select('judges.*', 'users.global_name')
                            ->get();

                        $judgesAssigned = $existingJudges->count() > 0;
                    }

                    // Get active round judges if there's an active round
                    $activeJudges = null;
                    if ($activeRound && $seasonId) {
                        $activeJudges = DB::table('judges')
                            ->join('users', 'judges.id', '=', 'users.id')
                            ->where('judges.season_id', $seasonId)
                            ->where('judges.round', $activeRound->round_number)
                            ->select('judges.*', 'users.global_name')
                            ->get();
                    }

                    // Check elimination eligibility for active round
                    $canEliminate = false;
                    $eliminationData = [];

                    if ($activeRound && $seasonId) {
                        // Check if deadline has passed
                        $deadlinePassed = strtotime($activeRound->deadline) < time();

                        // Check if all scores are submitted (no null scores)
                        $nullScores = DB::table('submissions')
                            ->where('round', $activeRound->round_number)
                            ->where('season_id', $seasonId)
                            ->whereNull('score')
                            ->count();

                        $allScoresSubmitted = $nullScores === 0;

                        $canEliminate = $deadlinePassed && $allScoresSubmitted;

                        if ($canEliminate) {
                            // Calculate eliminations
                            if ($activeRound->is_merge) {
                                // Merge round - single list
                                $eliminationData['merge'] = [];

                                // Get all non-eliminated contestants
                                $contestants = DB::table('contestants')
                                    ->join('users', 'contestants.id', '=', 'users.id')
                                    ->where('contestants.season_id', $seasonId)
                                    ->where('contestants.eliminated', false)
                                    ->select('contestants.*', 'users.global_name')
                                    ->get();

                                $noSubmission = [];
                                $withScores = [];
                                $droppedOut = [];

                                foreach ($contestants as $contestant) {
                                    if ($contestant->special) {
                                        // Dropped out - always eliminate
                                        $droppedOut[] = [
                                            'contestant' => $contestant,
                                            'avg' => null,
                                            'reason' => 'dropped_out'
                                        ];
                                    } elseif (is_null($contestant->submission_date)) {
                                        $noSubmission[] = [
                                            'contestant' => $contestant,
                                            'avg' => null,
                                            'reason' => 'no_submission'
                                        ];
                                    } else {
                                        // Calculate average score
                                        $subs = DB::table('submissions')
                                            ->join('judges', function ($join) use ($activeRound, $seasonId) {
                                                $join->on('judges.id', '=', 'submissions.judge_id')
                                                    ->where('judges.round', '=', $activeRound->round_number)
                                                    ->where('judges.season_id', '=', $seasonId);
                                            })
                                            ->where('submissions.contestant_id', $contestant->id)
                                            ->where('submissions.round', $activeRound->round_number)
                                            ->where('submissions.season_id', $seasonId)
                                            ->whereNotNull('submissions.score')
                                            ->get();

                                        $avg = 0;
                                        $count = count($subs);

                                        if ($count > 0) {
                                            foreach ($subs as $sub) {
                                                $avg += $sub->score;
                                            }
                                            $avg /= $count;

                                            $withScores[] = [
                                                'contestant' => $contestant,
                                                'avg' => $avg,
                                                'reason' => 'low_score'
                                            ];
                                        }
                                    }
                                }

                                // Sort by average (lowest first)
                                usort($withScores, function ($a, $b) {
                                    return $a['avg'] <=> $b['avg'];
                                });

                                // Determine how many to show from withScores
                                // Dropped out + no submission count
                                $autoEliminateCount = count($droppedOut) + count($noSubmission);
                                $remainingEliminations = $activeRound->eliminate_number - $autoEliminateCount;

                                if ($remainingEliminations > 0) {
                                    $toEliminate = array_merge($droppedOut, $noSubmission, array_slice($withScores, 0, $remainingEliminations));
                                } else {
                                    $toEliminate = array_merge($droppedOut, $noSubmission);
                                }

                                $eliminationData['merge'] = $toEliminate;

                            } else {
                                // Group round - three lists
                                for ($group = 1; $group <= 3; $group++) {
                                    $eliminationData[$group] = [];

                                    $contestants = DB::table('contestants')
                                        ->join('users', 'contestants.id', '=', 'users.id')
                                        ->where('contestants.season_id', $seasonId)
                                        ->where('contestants.eliminated', false)
                                        ->where('contestants.md_group', $group)
                                        ->select('contestants.*', 'users.global_name')
                                        ->get();

                                    $noSubmission = [];
                                    $withScores = [];
                                    $droppedOut = [];

                                    foreach ($contestants as $contestant) {
                                        if ($contestant->special) {
                                            // Dropped out - always eliminate
                                            $droppedOut[] = [
                                                'contestant' => $contestant,
                                                'avg' => null,
                                                'reason' => 'dropped_out'
                                            ];
                                        } elseif (is_null($contestant->submission_date)) {
                                            $noSubmission[] = [
                                                'contestant' => $contestant,
                                                'avg' => null,
                                                'reason' => 'no_submission'
                                            ];
                                        } else {
                                            // Calculate average score for this group
                                            $subs = DB::table('submissions')
                                                ->join('judges', function ($join) use ($activeRound, $group, $seasonId) {
                                                    $join->on('judges.id', '=', 'submissions.judge_id')
                                                        ->where('judges.round', '=', $activeRound->round_number)
                                                        ->where('judges.md_group', '=', $group)
                                                        ->where('judges.season_id', '=', $seasonId);
                                                })
                                                ->where('submissions.contestant_id', $contestant->id)
                                                ->where('submissions.round', $activeRound->round_number)
                                                ->where('submissions.md_group', $group)
                                                ->where('submissions.season_id', $seasonId)
                                                ->whereNotNull('submissions.score')
                                                ->get();

                                            $avg = 0;
                                            $count = count($subs);

                                            if ($count > 0) {
                                                foreach ($subs as $sub) {
                                                    $avg += $sub->score;
                                                }
                                                $avg /= $count;

                                                $withScores[] = [
                                                    'contestant' => $contestant,
                                                    'avg' => $avg,
                                                    'reason' => 'low_score'
                                                ];
                                            }
                                        }
                                    }

                                    // Sort by average (lowest first)
                                    usort($withScores, function ($a, $b) {
                                        return $a['avg'] <=> $b['avg'];
                                    });

                                    // Determine how many to show from withScores
                                    // Dropped out + no submission count
                                    $autoEliminateCount = count($droppedOut) + count($noSubmission);
                                    $remainingEliminations = $activeRound->eliminate_number - $autoEliminateCount;

                                    if ($remainingEliminations > 0) {
                                        $toEliminate = array_merge($droppedOut, $noSubmission, array_slice($withScores, 0, $remainingEliminations));
                                    } else {
                                        $toEliminate = array_merge($droppedOut, $noSubmission);
                                    }

                                    $eliminationData[$group] = $toEliminate;
                                }
                            }
                        }
                    }
                @endphp

                @if(!$seasonId)
                    <div class="access-message error">
                        <p style="font-size: 1.5rem; margin-bottom: 1rem;">⚠️ No Active Season</p>
                        <p>There is no active season configured in the database.</p>
                        <p style="margin-top: 0.5rem; font-size: 0.875rem;">Please set a season's 'active' column to TRUE in the 'season' table.</p>
                    </div>
                @else
                    {{-- Active Round Section --}}
                    @if($activeRound)
                    <div class="round-info-card" style="border: 3px solid #17a2b8;">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                            <div>
                                <span class="status-badge active">🔄 ACTIVE ROUND</span>
                                <h3 style="margin-top: 1rem;">📋 Round {{ $activeRound->round_number }}: {{ $activeRound->title }}
                                </h3>
                                <p style="color: #666; font-size: 1.125rem;">{{ $activeRound->description }}</p>
                            </div>
                        </div>

                        <div class="info-grid">
                            <div class="info-item" style="border-left-color: #17a2b8;">
                                <label>Round Type</label>
                                <div class="value">
                                    {{ $activeRound->is_merge ? '🔀 Merge Round' : '👥 Group Round' }}
                                </div>
                            </div>
                            <div class="info-item" style="border-left-color: #17a2b8;">
                                <label>Eliminate</label>
                                <div class="value">{{ $activeRound->eliminate_number }} Contestants</div>
                            </div>
                            <div class="info-item" style="border-left-color: #17a2b8;">
                                <label>Deadline</label>
                                <div class="value">{{ date('M j, Y g:i A', strtotime($activeRound->deadline)) }}</div>
                            </div>
                        </div>

                        @if($activeJudges && $activeJudges->count() > 0)
                            <div class="judges-display" style="background: #d1ecf1; border-color: #17a2b8;">
                                <h4 style="color: #17a2b8;">👨‍⚖️ Assigned Judges</h4>
                                @if($activeRound->is_merge)
                                    <div class="judge-group" style="border-color: #17a2b8;">
                                        <div class="judge-group-title" style="color: #17a2b8;">Merge Round Judges</div>
                                        <ul class="judge-list">
                                            @foreach($activeJudges as $judge)
                                                <li>{{ $judge->global_name }} <span style="color: #666; font-size: 0.875rem;">(ID:
                                                        {{ $judge->id }})</span></li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @else
                                    <div class="judge-groups">
                                        @for($group = 1; $group <= 3; $group++)
                                            @php
                                                $groupJudges = $activeJudges->where('md_group', $group);
                                            @endphp
                                            @if($groupJudges->count() > 0)
                                                <div class="judge-group" style="border-color: #17a2b8;">
                                                    <div class="judge-group-title" style="color: #17a2b8;">Group {{ $group }}</div>
                                                    <ul class="judge-list">
                                                        @foreach($groupJudges as $judge)
                                                            <li>{{ $judge->global_name }} <span style="color: #666; font-size: 0.875rem;">(ID:
                                                                    {{ $judge->id }})</span></li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            @endif
                                        @endfor
                                    </div>
                                @endif
                            </div>
                        @endif

                        {{-- Contestant Management Section --}}
                        @php
                            // Get active contestants for this round
                            $activeContestants = DB::table('contestants')
                                ->join('users', 'contestants.id', '=', 'users.id')
                                ->where('contestants.season_id', $seasonId)
                                ->where('contestants.eliminated', false);

                            if (!$activeRound->is_merge) {
                                // For group rounds, we might want to show all or filter by group
                                // For now, showing all non-eliminated contestants
                            }

                            $activeContestants = $activeContestants
                                ->select('contestants.*', 'users.global_name')
                                ->orderBy('users.global_name')
                                ->get();
                        @endphp

                        <div class="contestant-management-section">
                            <h4>👤 Contestant Management</h4>

                            <div class="search-container">
                                <input type="text" class="search-input" placeholder="🔍 Search contestants by name or ID..."
                                    id="contestant-search" oninput="filterContestants()">
                            </div>

                            <div class="contestant-results">
                                <div class="contestant-grid" id="contestant-grid">
                                    @foreach($activeContestants as $contestant)
                                        <div class="contestant-management-card" data-contestant-id="{{ $contestant->id }}"
                                            data-contestant-name="{{ strtolower($contestant->global_name) }}">
                                            <div class="contestant-management-info">
                                                <div class="contestant-management-name">{{ $contestant->global_name }}</div>
                                                <div class="contestant-management-id">ID: {{ $contestant->id }}</div>
                                                @if($contestant->extension_hours > 0)
                                                    <div class="contestant-management-extension">
                                                        <span class="extension-badge">+{{ $contestant->extension_hours }}h extension</span>
                                                    </div>
                                                @endif
                                                @if($contestant->special)
                                                    <div class="contestant-management-status">
                                                        <span class="dropout-badge">🚫 Dropped Out</span>
                                                    </div>
                                                @endif
                                                @if(!is_null($contestant->submission_date))
                                                    <div class="contestant-management-status">
                                                        <span class="submitted-badge">✓ Submitted</span>
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="contestant-management-actions">
                                                @if($contestant->special)
                                                    <form method="POST" action="/admin/restore-contestant" style="display: inline;"
                                                        onsubmit="return confirm('Restore {{ $contestant->global_name }} from dropped out status?');">
                                                        @csrf
                                                        <input type="hidden" name="contestant_id" value="{{ $contestant->id }}">
                                                        <input type="hidden" name="round_number" value="{{ $activeRound->round_number }}">
                                                        <button type="submit" class="btn btn-sm btn-info">
                                                            ↩️ Restore
                                                        </button>
                                                    </form>
                                                @else
                                                    <button type="button" class="btn btn-sm btn-warning"
                                                        onclick="openExtensionModal('{{ $contestant->id }}', '{{ $contestant->global_name }}', {{ $contestant->extension_hours }})">
                                                        ⏰ Extension
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-danger"
                                                        onclick="confirmDropout('{{ $contestant->id }}', '{{ $contestant->global_name }}')">
                                                        🚫 Drop Out
                                                    </button>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        {{-- Elimination Preview Section --}}
                        @if($canEliminate)
                            <div class="elimination-section">
                                <h4>⚠️ Round Completion & Contestant Elimination</h4>

                                <div class="elimination-warning">
                                    ⚠️ <strong>Warning:</strong> Completing this round will eliminate the contestants shown below. This
                                    action is irreversible.
                                </div>

                                <div class="elimination-summary">
                                    <div class="elimination-summary-grid">
                                        <div class="elimination-summary-item">
                                            <label>To Eliminate</label>
                                            <div class="value">{{ $activeRound->eliminate_number }}</div>
                                        </div>
                                        <div class="elimination-summary-item">
                                            <label>Deadline Status</label>
                                            <div class="value" style="color: #28a745; font-size: 1.25rem;">✓ Passed</div>
                                        </div>
                                        <div class="elimination-summary-item">
                                            <label>Scores Status</label>
                                            <div class="value" style="color: #28a745; font-size: 1.25rem;">✓ Complete</div>
                                        </div>
                                    </div>
                                </div>

                                @if($activeRound->is_merge)
                                    {{-- Merge Round Eliminations --}}
                                    @if(count($eliminationData['merge']) > 0)
                                        <div class="elimination-group">
                                            <div class="elimination-group-title">Contestants to be Eliminated on Round Completion</div>
                                            <ul class="contestant-list">
                                                @foreach($eliminationData['merge'] as $item)
                                                    <li
                                                        class="contestant-item {{ $item['reason'] === 'no_submission' ? 'no-submission' : ($item['reason'] === 'dropped_out' ? 'dropped-out' : 'low-score') }}">
                                                        <div class="contestant-name">{{ $item['contestant']->global_name }}</div>
                                                        <div class="contestant-details">
                                                            <span class="contestant-detail-item">
                                                                <strong>ID:</strong> {{ $item['contestant']->id }}
                                                            </span>
                                                            @if($item['avg'] !== null)
                                                                <span class="contestant-detail-item">
                                                                    <strong>Avg Score:</strong>
                                                                    <span class="score-badge">{{ number_format($item['avg'], 2) }}</span>
                                                                </span>
                                                            @endif
                                                            <span class="contestant-detail-item">
                                                                <span
                                                                    class="reason-badge {{ $item['reason'] === 'no_submission' ? 'no-sub' : ($item['reason'] === 'dropped_out' ? 'dropout' : '') }}">
                                                                    {{ $item['reason'] === 'no_submission' ? 'No Submission' : ($item['reason'] === 'dropped_out' ? 'Dropped Out' : 'Low Score') }}
                                                                </span>
                                                            </span>
                                                        </div>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @else
                                        <div class="no-eliminations">
                                            No contestants to eliminate at this time.
                                        </div>
                                    @endif
                                @else
                                    {{-- Group Round Eliminations --}}
                                    <div class="elimination-groups">
                                        @for($group = 1; $group <= 3; $group++)
                                            @if(count($eliminationData[$group]) > 0)
                                                <div class="elimination-group">
                                                    <div class="elimination-group-title">Group {{ $group }} - Contestants to be Eliminated</div>
                                                    <ul class="contestant-list">
                                                        @foreach($eliminationData[$group] as $item)
                                                            <li
                                                                class="contestant-item {{ $item['reason'] === 'no_submission' ? 'no-submission' : ($item['reason'] === 'dropped_out' ? 'dropped-out' : 'low-score') }}">
                                                                <div class="contestant-name">{{ $item['contestant']->global_name }}</div>
                                                                <div class="contestant-details">
                                                                    <span class="contestant-detail-item">
                                                                        <strong>ID:</strong> {{ $item['contestant']->id }}
                                                                    </span>
                                                                    @if($item['avg'] !== null)
                                                                        <span class="contestant-detail-item">
                                                                            <strong>Avg:</strong>
                                                                            <span class="score-badge">{{ number_format($item['avg'], 2) }}</span>
                                                                        </span>
                                                                    @endif
                                                                    <span class="contestant-detail-item">
                                                                        <span
                                                                            class="reason-badge {{ $item['reason'] === 'no_submission' ? 'no-sub' : ($item['reason'] === 'dropped_out' ? 'dropout' : '') }}">
                                                                            {{ $item['reason'] === 'no_submission' ? 'No Sub' : ($item['reason'] === 'dropped_out' ? 'Dropped Out' : 'Low Score') }}
                                                                        </span>
                                                                    </span>
                                                                </div>
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            @endif
                                        @endfor
                                    </div>
                                @endif
                            </div>
                        @else
                            {{-- Show why complete button is disabled --}}
                            @php
                                $deadlinePassed = strtotime($activeRound->deadline) < time();
                                $nullScores = DB::table('submissions')
                                    ->where('round', $activeRound->round_number)
                                    ->where('season_id', $seasonId)
                                    ->whereNull('score')
                                    ->count();
                                $allScoresSubmitted = $nullScores === 0;
                            @endphp

                            @if(!$deadlinePassed || !$allScoresSubmitted)
                                <div class="alert alert-info" style="margin-top: 1.5rem;">
                                    <strong>ℹ️ Round Cannot Be Completed Yet</strong>
                                    <ul style="margin: 0.5rem 0 0 1.5rem;">
                                        @if(!$deadlinePassed)
                                            <li>⏰ Deadline has not passed yet ({{ date('M j, Y g:i A', strtotime($activeRound->deadline)) }})
                                            </li>
                                        @endif
                                        @if(!$allScoresSubmitted)
                                            <li>📝 {{ $nullScores }} score(s) still pending from judges</li>
                                        @endif
                                    </ul>
                                </div>
                            @endif
                        @endif

                        <div class="actions-bar" style="border-top-color: #17a2b8;">
                            <button type="button" class="btn btn-edit" data-round="{{ $activeRound->round_number }}"
                                data-title="{{ $activeRound->title }}" data-description="{{ $activeRound->description }}"
                                data-theme-details="{{ $activeRound->theme_details }}"
                                data-eliminate="{{ $activeRound->eliminate_number }}"
                                data-deadline="{{ date('Y-m-d\TH:i', strtotime($activeRound->deadline)) }}"
                                onclick="openEditModalFromButton(this)">
                                <span class="edit-icon">✏️ Edit Round Details</span>
                            </button>

                            @if($canEliminate)
                                <form method="POST" action="/admin/complete-round" style="display: inline;"
                                    onsubmit="return confirm('⚠️ Are you ABSOLUTELY SURE you want to complete this round?\n\nThis will:\n- Eliminate {{ $activeRound->eliminate_number }} contestant(s)\n- Mark the round as completed\n\nThis action CANNOT be undone!');">
                                    @csrf
                                    <input type="hidden" name="round_number" value="{{ $activeRound->round_number }}">
                                    @if($activeRound->is_merge)
                                        <input type="hidden" name="contestants"
                                            value="{{ json_encode(array_map(function ($item) {
                                            return $item['contestant']->id; }, $eliminationData['merge'])) }}">
                                    @else
                                        @php
                                            $allContestants = [];
                                            foreach ([1, 2, 3] as $g) {
                                                foreach ($eliminationData[$g] as $item) {
                                                    $allContestants[] = $item['contestant']->id;
                                                }
                                            }
                                        @endphp
                                        <input type="hidden" name="contestants" value="{{ json_encode($allContestants) }}">
                                    @endif
                                    <button type="submit" class="btn btn-success">
                                        ✅ Complete Round & Eliminate Contestants
                                    </button>
                                </form>
                            @else
                                <button type="button" class="btn btn-success" disabled style="opacity: 0.5; cursor: not-allowed;">
                                    ✅ Complete Round (Conditions Not Met)
                                </button>
                            @endif
                        </div>
                    </div>

                    <div class="section-divider"></div>
                @endif

                {{-- Next Round Section --}}
                @if(!$nextRound)
                    <div class="no-round">
                        <p style="font-size: 1.5rem; margin-bottom: 1rem;">✅ All Rounds Processed</p>
                        <p>There are no rounds pending judge assignment.</p>
                    </div>
                @else
                    <div class="round-info-card">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                            <div>
                                <span class="status-badge pending">⏳ PENDING</span>
                                <h3 style="margin-top: 1rem;">📋 Round {{ $nextRound->round_number }}: {{ $nextRound->title }}</h3>
                                <p style="color: #666; font-size: 1.125rem;">{{ $nextRound->description }}</p>
                            </div>
                        </div>

                        <div class="info-grid">
                            <div class="info-item">
                                <label>Round Type</label>
                                <div class="value">
                                    {{ $nextRound->is_merge ? '🔀 Merge Round' : '👥 Group Round' }}
                                </div>
                            </div>
                            <div class="info-item">
                                <label>Judges Required</label>
                                <div class="value">
                                    {{ $nextRound->is_merge ? '3 Judges' : '9 Judges (3 per group)' }}
                                </div>
                            </div>
                            <div class="info-item">
                                <label>Eliminate</label>
                                <div class="value">{{ $nextRound->eliminate_number }} Contestants</div>
                            </div>
                            <div class="info-item">
                                <label>Deadline</label>
                                <div class="value">{{ date('M j, Y g:i A', strtotime($nextRound->deadline)) }}</div>
                            </div>
                        </div>

                        @if($judgesAssigned)
                            {{-- Judges already assigned - show them with options to reset or start --}}
                            <div class="alert alert-info" style="margin-top: 1.5rem;">
                                <strong>✅ Judges Already Assigned</strong> - This round has judges assigned. You can start the round or
                                choose new judges.
                            </div>

                            <div class="judges-display">
                                <h4>👨‍⚖️ Currently Assigned Judges</h4>
                                @if($nextRound->is_merge)
                                    <div class="judge-group">
                                        <div class="judge-group-title">Merge Round Judges</div>
                                        <ul class="judge-list">
                                            @foreach($existingJudges as $judge)
                                                <li>{{ $judge->global_name }} <span style="color: #666; font-size: 0.875rem;">(ID:
                                                        {{ $judge->id }})</span></li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @else
                                    <div class="judge-groups">
                                        @for($group = 1; $group <= 3; $group++)
                                            @php
                                                $groupJudges = $existingJudges->where('md_group', $group);
                                            @endphp
                                            @if($groupJudges->count() > 0)
                                                <div class="judge-group">
                                                    <div class="judge-group-title">Group {{ $group }}</div>
                                                    <ul class="judge-list">
                                                        @foreach($groupJudges as $judge)
                                                            <li>{{ $judge->global_name }} <span style="color: #666; font-size: 0.875rem;">(ID:
                                                                    {{ $judge->id }})</span></li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            @endif
                                        @endfor
                                    </div>
                                @endif
                            </div>

                            <div class="actions-bar">
                                <button type="button" class="btn btn-edit" data-round="{{ $nextRound->round_number }}"
                                    data-title="{{ $nextRound->title }}" data-description="{{ $nextRound->description }}"
                                    data-theme-details="{{ $activeRound->theme_details }}"
                                    data-eliminate="{{ $nextRound->eliminate_number }}"
                                    data-deadline="{{ date('Y-m-d\TH:i', strtotime($nextRound->deadline)) }}"
                                    onclick="openEditModalFromButton(this)">
                                    <span class="edit-icon">✏️ Edit Round Details</span>
                                </button>
                                <form method="POST" action="/admin/reset-judges" style="display: inline;"
                                    onsubmit="return confirm('Are you sure you want to reset the judges for this round? This will delete all current judge assignments.');">
                                    @csrf
                                    <input type="hidden" name="round_number" value="{{ $nextRound->round_number }}">
                                    <button type="submit" class="btn btn-warning">
                                        🔄 Choose New Judges
                                    </button>
                                </form>
                                <form method="POST" action="/admin/start-round" style="display: inline;"
                                    onsubmit="return confirm('Are you sure you want to start this round? This will activate the round for judging.');">
                                    @csrf
                                    <input type="hidden" name="round_number" value="{{ $nextRound->round_number }}">
                                    <button type="submit" class="btn btn-success">
                                        🚀 Start Round
                                    </button>
                                </form>
                            </div>
                        @else
                            {{-- No judges assigned - show selection interface --}}
                            <form id="judgeAssignmentForm" method="POST" action="/admin/generate-round">
                                @csrf
                                <input type="hidden" name="round_number" value="{{ $nextRound->round_number }}">
                                <input type="hidden" name="is_merge" value="{{ $nextRound->is_merge ? '1' : '0' }}">

                                @if($nextRound->is_merge)
                                    <!-- Merge Round: Single tab with 3 judges -->
                                    <div class="tab-container">
                                        <div class="tab-content active">
                                            <h4 style="font-size: 1.5rem; font-weight: 600; margin-bottom: 1rem;">
                                                Select 3 Judges for Merge Round
                                            </h4>

                                            <div class="selection-counter" id="counter-merge">
                                                Selected: <span class="count">0</span> / 3 judges
                                            </div>

                                            <div class="selected-judges" id="selected-merge">
                                                <div class="selected-judges-title">Selected Judges</div>
                                                <div class="selected-judges-list" id="selected-list-merge">
                                                    <span style="color: #666; font-style: italic;">No judges selected yet</span>
                                                </div>
                                            </div>

                                            <div class="search-container">
                                                <input type="text" class="search-input" placeholder="🔍 Search judges by name or ID..."
                                                    id="search-merge" oninput="filterJudges('merge')">
                                            </div>

                                            <div class="judge-results">
                                                <div class="judge-grid" id="judge-grid-merge">
                                                    @foreach($eligibleJudges as $judge)
                                                        <div class="judge-card" data-judge-id="{{ $judge->id }}"
                                                            data-judge-name="{{ strtolower($judge->global_name) }}" data-group="merge">
                                                            <div class="judge-info">
                                                                <div class="judge-name">{{ $judge->global_name }}</div>
                                                                <div class="judge-id">ID: {{ $judge->id }}</div>
                                                            </div>
                                                            <button type="button" class="add-btn" onclick="toggleJudge(this, 'merge')">
                                                                + Add
                                                            </button>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>

                                            <input type="hidden" name="judges[merge]" id="judges-merge" value="">
                                        </div>
                                    </div>
                                @else
                                    <!-- Group Round: 3 tabs, 3 judges each -->
                                    <div class="tab-container">
                                        <div class="tab-buttons">
                                            <button type="button" class="tab-button active" onclick="switchGroupTab(1)">
                                                Group 1
                                            </button>
                                            <button type="button" class="tab-button" onclick="switchGroupTab(2)">
                                                Group 2
                                            </button>
                                            <button type="button" class="tab-button" onclick="switchGroupTab(3)">
                                                Group 3
                                            </button>
                                        </div>

                                        @for($group = 1; $group <= 3; $group++)
                                            <div class="tab-content {{ $group === 1 ? 'active' : '' }}" id="tab-group-{{ $group }}">
                                                <h4 style="font-size: 1.5rem; font-weight: 600; margin-bottom: 1rem;">
                                                    Select 3 Judges for Group {{ $group }}
                                                </h4>

                                                <div class="selection-counter" id="counter-group-{{ $group }}">
                                                    Selected: <span class="count">0</span> / 3 judges
                                                </div>

                                                <div class="selected-judges" id="selected-group-{{ $group }}">
                                                    <div class="selected-judges-title">Selected Judges</div>
                                                    <div class="selected-judges-list" id="selected-list-group-{{ $group }}">
                                                        <span style="color: #666; font-style: italic;">No judges selected yet</span>
                                                    </div>
                                                </div>

                                                <div class="search-container">
                                                    <input type="text" class="search-input" placeholder="🔍 Search judges by name or ID..."
                                                        id="search-group-{{ $group }}" oninput="filterJudges({{ $group }})">
                                                </div>

                                                <div class="judge-results">
                                                    <div class="judge-grid" id="judge-grid-group-{{ $group }}">
                                                        @foreach($eligibleJudges as $judge)
                                                            <div class="judge-card" data-judge-id="{{ $judge->id }}"
                                                                data-judge-name="{{ strtolower($judge->global_name) }}" data-group="{{ $group }}">
                                                                <div class="judge-info">
                                                                    <div class="judge-name">{{ $judge->global_name }}</div>
                                                                    <div class="judge-id">ID: {{ $judge->id }}</div>
                                                                </div>
                                                                <button type="button" class="add-btn" onclick="toggleJudge(this, {{ $group }})">
                                                                    + Add
                                                                </button>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>

                                                <input type="hidden" name="judges[{{ $group }}]" id="judges-group-{{ $group }}" value="">
                                            </div>
                                        @endfor
                                    </div>
                                @endif

                                <div class="actions-bar">
                                    <button type="button" class="btn btn-edit" data-round="{{ $nextRound->round_number }}"
                                        data-title="{{ $nextRound->title }}" data-description="{{ $nextRound->description }}"
                                        data-theme-details="{{ $activeRound->theme_details }}"
                                        data-eliminate="{{ $nextRound->eliminate_number }}"
                                        data-deadline="{{ date('Y-m-d\TH:i', strtotime($nextRound->deadline)) }}"
                                        onclick="openEditModalFromButton(this)">
                                        <span class="edit-icon">✏️ Edit Round Details</span>
                                    </button>
                                    <button type="button" class="btn btn-primary" onclick="resetSelections()">
                                        🔄 Reset All
                                    </button>
                                    <button type="submit" class="btn btn-success" id="submitBtn" disabled>
                                        ✅ Generate Round Assignments
                                    </button>
                                </div>
                            </form>
                        @endif
                    </div>
                @endif
                <script>
                    @if($nextRound && !$judgesAssigned)
                        const isMerge = {{ $nextRound->is_merge ? 'true' : 'false' }};
                        const selections = isMerge ? { merge: [] } : { 1: [], 2: [], 3: [] };
                        const judgeNames = {};

                        // Store judge names for display
                        document.querySelectorAll('.judge-card').forEach(card => {
                            const id = card.dataset.judgeId;
                            const name = card.querySelector('.judge-name').textContent;
                            judgeNames[id] = name;
                        });

                        function switchGroupTab(groupNum) {
                            // Update tab buttons
                            document.querySelectorAll('.tab-button').forEach((btn, index) => {
                                btn.classList.toggle('active', index + 1 === groupNum);
                            });

                            // Update tab content
                            document.querySelectorAll('.tab-content').forEach((content, index) => {
                                content.classList.toggle('active', index + 1 === groupNum);
                            });
                        }

                        function toggleJudge(button, group) {
                            const card = button.closest('.judge-card');
                            const judgeId = card.dataset.judgeId;
                            const groupKey = isMerge ? 'merge' : group;

                            if (card.classList.contains('selected')) {
                                // Deselect
                                card.classList.remove('selected');
                                button.textContent = '+ Add';
                                selections[groupKey] = selections[groupKey].filter(id => id !== judgeId);
                            } else {
                                // Check if we've reached the limit
                                if (selections[groupKey].length >= 3) {
                                    alert(`You can only select 3 judges for ${isMerge ? 'this round' : 'Group ' + group}`);
                                    return;
                                }

                                // Check if judge is already selected in another group
                                if (!isMerge) {
                                    for (let g of [1, 2, 3]) {
                                        if (g !== group && selections[g].includes(judgeId)) {
                                            alert(`This judge is already selected for Group ${g}`);
                                            return;
                                        }
                                    }
                                }

                                // Select
                                card.classList.add('selected');
                                button.textContent = '✓ Added';
                                selections[groupKey].push(judgeId);
                            }

                            updateSelectedDisplay(groupKey);
                            updateCounter(groupKey);
                            updateHiddenInput(groupKey);
                            checkFormValidity();
                        }

                        function updateSelectedDisplay(group) {
                            const listId = `selected-list-${isMerge ? 'merge' : 'group-' + group}`;
                            const list = document.getElementById(listId);

                            if (selections[group].length === 0) {
                                list.innerHTML = '<span style="color: #666; font-style: italic;">No judges selected yet</span>';
                            } else {
                                list.innerHTML = selections[group].map(judgeId => {
                                    return `<div class="selected-judge-badge">
                                    ${judgeNames[judgeId]}
                                    <span class="remove-btn" onclick="removeJudge('${judgeId}', '${group}')">✕</span>
                                </div>`;
                                }).join('');
                            }
                        }

                        function removeJudge(judgeId, group) {
                            const groupKey = isMerge ? 'merge' : parseInt(group);

                            // Find the card and deselect it
                            const gridId = `judge-grid-${isMerge ? 'merge' : 'group-' + group}`;
                            const grid = document.getElementById(gridId);
                            const card = grid.querySelector(`[data-judge-id="${judgeId}"]`);

                            if (card) {
                                card.classList.remove('selected');
                                card.querySelector('.add-btn').textContent = '+ Add';
                            }

                            selections[groupKey] = selections[groupKey].filter(id => id !== judgeId);

                            updateSelectedDisplay(groupKey);
                            updateCounter(groupKey);
                            updateHiddenInput(groupKey);
                            checkFormValidity();
                        }

                        function filterJudges(group) {
                            const searchId = `search-${isMerge ? 'merge' : 'group-' + group}`;
                            const searchInput = document.getElementById(searchId);
                            const searchTerm = searchInput.value.toLowerCase();

                            const gridId = `judge-grid-${isMerge ? 'merge' : 'group-' + group}`;
                            const grid = document.getElementById(gridId);
                            const cards = grid.querySelectorAll('.judge-card');

                            let visibleCount = 0;

                            cards.forEach(card => {
                                const name = card.dataset.judgeName;
                                const id = card.dataset.judgeId;

                                if (searchTerm === '' ||
                                    name.includes(searchTerm) ||
                                    id.includes(searchTerm)) {
                                    card.classList.remove('hidden');
                                    visibleCount++;
                                } else {
                                    card.classList.add('hidden');
                                }
                            });

                            // Show "no results" message if needed
                            const existingNoResults = grid.querySelector('.no-results');
                            if (visibleCount === 0 && !existingNoResults) {
                                const noResults = document.createElement('div');
                                noResults.className = 'no-results';
                                noResults.textContent = 'No judges found matching your search';
                                grid.appendChild(noResults);
                            } else if (visibleCount > 0 && existingNoResults) {
                                existingNoResults.remove();
                            }
                        }

                        function updateCounter(group) {
                            const counter = document.getElementById(`counter-${isMerge ? 'merge' : 'group-' + group}`);
                            const count = selections[group].length;
                            counter.querySelector('.count').textContent = count;

                            counter.classList.remove('complete', 'error');
                            if (count === 3) {
                                counter.classList.add('complete');
                            } else if (count > 3) {
                                counter.classList.add('error');
                            }
                        }

                        function updateHiddenInput(group) {
                            const input = document.getElementById(`judges-${isMerge ? 'merge' : 'group-' + group}`);
                            input.value = JSON.stringify(selections[group]);
                        }

                        function checkFormValidity() {
                            const submitBtn = document.getElementById('submitBtn');
                            let allValid = true;

                            if (isMerge) {
                                allValid = selections.merge.length === 3;
                            } else {
                                allValid = selections[1].length === 3 &&
                                    selections[2].length === 3 &&
                                    selections[3].length === 3;
                            }

                            submitBtn.disabled = !allValid;
                        }

                        function resetSelections() {
                            if (!confirm('Are you sure you want to reset all selections?')) {
                                return;
                            }

                            // Clear selections
                            if (isMerge) {
                                selections.merge = [];
                            } else {
                                selections[1] = [];
                                selections[2] = [];
                                selections[3] = [];
                            }

                            // Clear UI
                            document.querySelectorAll('.judge-card').forEach(card => {
                                card.classList.remove('selected');
                                card.querySelector('.add-btn').textContent = '+ Add';
                            });

                            // Clear search inputs
                            document.querySelectorAll('.search-input').forEach(input => {
                                input.value = '';
                            });

                            // Reset filters
                            document.querySelectorAll('.judge-card').forEach(card => {
                                card.classList.remove('hidden');
                            });

                            // Update everything
                            Object.keys(selections).forEach(group => {
                                updateSelectedDisplay(group);
                                updateCounter(group);
                                updateHiddenInput(group);
                            });

                            checkFormValidity();
                        }

                        // Form submission
                        document.getElementById('judgeAssignmentForm')?.addEventListener('submit', function (e) {
                            e.preventDefault();

                            if (!confirm('Are you sure you want to generate these judge assignments?')) {
                                return;
                            }

                            const formData = new FormData(this);

                            fetch(this.action, {
                                method: 'POST',
                                body: formData,
                                headers: {
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                }
                            })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        alert('Round assignments generated successfully!');
                                        window.location.reload();
                                    } else {
                                        alert('Error: ' + (data.message || 'Unknown error occurred'));
                                    }
                                })
                                .catch(error => {
                                    console.error('Error:', error);
                                    alert('An error occurred while generating assignments.');
                                });
                        });
                    @endif

                        // Edit Round Modal Functions
                    function openEditModalFromButton(button) {
                        const roundNumber = button.getAttribute('data-round');
                        const title = button.getAttribute('data-title');
                        const description = button.getAttribute('data-description');
                        const themeDetails = button.getAttribute('data-theme-details');
                        const eliminateNumber = button.getAttribute('data-eliminate');
                        const deadline = button.getAttribute('data-deadline');

                        openEditModal(roundNumber, title, description, themeDetails, eliminateNumber, deadline);
                    }

                    function openEditModal(roundNumber, title, description, themeDetails, eliminateNumber, deadline) {
                        document.getElementById('edit_round_number').value = roundNumber;
                        document.getElementById('edit_title').value = title;
                        document.getElementById('edit_description').value = description;
                        document.getElementById('edit_theme_details').value = themeDetails || '';
                        document.getElementById('edit_eliminate_number').value = eliminateNumber;
                        document.getElementById('edit_deadline').value = deadline;

                        document.getElementById('editModal').classList.add('show');
                    }

                    function closeEditModal() {
                        document.getElementById('editModal').classList.remove('show');
                    }

                    // Close modal when clicking outside
                    window.addEventListener('click', function (event) {
                        const modal = document.getElementById('editModal');
                        if (event.target === modal) {
                            closeEditModal();
                        }
                    });

                    // Close modal with Escape key
                    document.addEventListener('keydown', function (event) {
                        if (event.key === 'Escape') {
                            closeEditModal();
                        }
                    });

                    // Handle edit form submission
                    const editForm = document.getElementById('editRoundForm');
                    if (editForm) {
                        editForm.addEventListener('submit', function (e) {
                            if (!confirm('Are you sure you want to update this round\'s details?')) {
                                e.preventDefault();
                                return false;
                            }
                            return true;
                        });
                    }

                    // Contestant Management Functions
                    function filterContestants() {
                        const searchInput = document.getElementById('contestant-search');
                        const searchTerm = searchInput.value.toLowerCase();
                        const grid = document.getElementById('contestant-grid');
                        const cards = grid.querySelectorAll('.contestant-management-card');

                        let visibleCount = 0;

                        cards.forEach(card => {
                            const name = card.dataset.contestantName;
                            const id = card.dataset.contestantId;

                            if (searchTerm === '' ||
                                name.includes(searchTerm) ||
                                id.includes(searchTerm)) {
                                card.classList.remove('hidden');
                                visibleCount++;
                            } else {
                                card.classList.add('hidden');
                            }
                        });

                        // Show "no results" message if needed
                        const existingNoResults = grid.querySelector('.no-results');
                        if (visibleCount === 0 && !existingNoResults) {
                            const noResults = document.createElement('div');
                            noResults.className = 'no-results';
                            noResults.textContent = 'No contestants found matching your search';
                            grid.appendChild(noResults);
                        } else if (visibleCount > 0 && existingNoResults) {
                            existingNoResults.remove();
                        }
                    }

                    function openExtensionModal(contestantId, contestantName, currentExtension) {
                        document.getElementById('extension_contestant_id').value = contestantId;
                        document.getElementById('extension_contestant_name').textContent = contestantName;
                        document.getElementById('extension_hours').value = currentExtension || 0;
                        document.getElementById('extensionModal').classList.add('show');
                    }

                    function closeExtensionModal() {
                        document.getElementById('extensionModal').classList.remove('show');
                    }

                    function confirmDropout(contestantId, contestantName) {
                        if (confirm(`Are you sure you want to mark ${contestantName} as DROPPED OUT?\n\nThis will:\n- Mark them for elimination\n- Show them as "Dropped Out" in the elimination list\n\nThis can be reversed using the Restore button.`)) {
                            const form = document.createElement('form');
                            form.method = 'POST';
                            form.action = '/admin/dropout-contestant';

                            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                            form.innerHTML = `
                            <input type="hidden" name="_token" value="${csrfToken}">
                            <input type="hidden" name="contestant_id" value="${contestantId}">
                            <input type="hidden" name="round_number" value="${document.querySelector('input[name="round_number"]')?.value || ''}">
                        `;

                            document.body.appendChild(form);
                            form.submit();
                        }
                    }

                    // Close modals when clicking outside
                    window.addEventListener('click', function (event) {
                        const editModal = document.getElementById('editModal');
                        const extensionModal = document.getElementById('extensionModal');

                        if (event.target === editModal) {
                            closeEditModal();
                        }
                        if (event.target === extensionModal) {
                            closeExtensionModal();
                        }
                    });

                    // Close modals with Escape key
                    document.addEventListener('keydown', function (event) {
                        if (event.key === 'Escape') {
                            closeEditModal();
                            closeExtensionModal();
                        }
                    });
                </script>

                <!-- Edit Round Modal -->
                <div id="editModal" class="edit-modal">
                    <div class="edit-modal-content">
                        <h2 class="edit-modal-header">✏️ Edit Round Details</h2>

                        <form id="editRoundForm" method="POST" action="/admin/update-round">
                            @csrf
                            <input type="hidden" name="round_number" id="edit_round_number">

                            <div class="edit-form-group">
                                <label for="edit_title">Round Title</label>
                                <input type="text" id="edit_title" name="title" required maxlength="255">
                            </div>

                            <div class="edit-form-group">
                                <label for="edit_description">Description / Theme</label>
                                <textarea id="edit_description" name="description" required rows="4"></textarea>
                            </div>
                            
                            <div class="edit-form-group">
                                <label for="edit_theme_details">Theme Details</label>
                                <textarea id="edit_theme_details" name="theme_details" rows="4"></textarea>
                            </div>

                            <div class="edit-form-group">
                                <label for="edit_eliminate_number">Number of Contestants to Eliminate</label>
                                <input type="number" id="edit_eliminate_number" name="eliminate_number" required min="0"
                                    max="100">
                            </div>

                            <div class="edit-form-group">
                                <label for="edit_deadline">Deadline (Date & Time - <b style="color:red">UTC</b>)</label>
                                <input type="datetime-local" id="edit_deadline" name="deadline" required>
                            </div>

                            <div class="edit-modal-actions">
                                <button type="button" class="btn btn-cancel" onclick="closeEditModal()">
                                    Cancel
                                </button>
                                <button type="submit" class="btn btn-success">
                                    ✅ Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Extension Modal -->
                <div id="extensionModal" class="extension-modal">
                    <div class="extension-modal-content">
                        <h2 class="extension-modal-header">⏰ Grant Extension</h2>

                        <form method="POST" action="/admin/grant-extension">
                            @csrf
                            <input type="hidden" name="contestant_id" id="extension_contestant_id">
                            <input type="hidden" name="round_number" value="{{ $activeRound->round_number ?? '' }}">

                            <div class="extension-form-group">
                                <label>Contestant</label>
                                <div style="font-size: 1.125rem; font-weight: 600; color: #333; padding: 0.5rem 0;">
                                    <span id="extension_contestant_name"></span>
                                </div>
                            </div>

                            <div class="extension-form-group">
                                <label for="extension_hours">Extension Hours</label>
                                <input type="number" id="extension_hours" name="extension_hours" required min="0" max="168"
                                    step="1" placeholder="Enter hours (e.g., 24)">
                                <small style="color: #666; display: block; margin-top: 0.5rem;">
                                    Set to 0 to remove extension. Maximum: 168 hours (1 week)
                                </small>
                            </div>

                            <div class="extension-modal-actions">
                                <button type="button" class="btn btn-cancel" onclick="closeExtensionModal()">
                                    Cancel
                                </button>
                                <button type="submit" class="btn btn-success">
                                    ✅ Grant Extension
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                @endif
            @endif
        @endauth
    </div>


</x-app-layout>