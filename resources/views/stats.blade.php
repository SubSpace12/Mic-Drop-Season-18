<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Stats Sheet') }}
        </h2>
    </x-slot>

    @php
        // Get active season
        $season = DB::table('season')->where('active', true)->first();
        $seasonId = $season ? $season->season_id : null;

        // Get all finished rounds (status = 2)
        $finishedRounds = $seasonId ? DB::table('round')
            ->where('season_id', $seasonId)
            ->where('status', 2)
            ->orderBy('round_number')
            ->get() : collect();

        $roundNumbers = $finishedRounds->pluck('round_number')->toArray();

        // Get all contestants for this season
        $allContestants = $seasonId ? DB::table('contestants')
            ->join('users', 'users.id', '=', 'contestants.id')
            ->where('contestants.season_id', $seasonId)
            ->select('contestants.*', 'users.global_name', 'users.username')
            ->get() : collect();

        // Build stats for each contestant
        $aliveStats = [];
        $eliminatedStats = [];

        foreach ($allContestants as $contestant) {
            $roundAverages = [];

            $elimRound = $contestant->eliminated ? ($contestant->round_eliminated ?? null) : null;

            // Determine which rounds this contestant participated in
            $participatedRounds = $roundNumbers;
            if ($contestant->eliminated && $elimRound) {
                $participatedRounds = array_filter($roundNumbers, function($rn) use ($elimRound) {
                    return $rn <= $elimRound;
                });
            }

            foreach ($participatedRounds as $rn) {
                $round = $finishedRounds->firstWhere('round_number', $rn);
                if (!$round) continue;

                // Determine group for this round
                // For merge rounds (is_merge = true), group = 0
                // For group rounds, use the contestant's md_group
                // If group_legacy exists and round context differs, use group_legacy
                $group = $round->is_merge ? 0 : $contestant->md_group;

                $subs = DB::table('submissions')
                    ->join('judges', function ($join) use ($rn, $group, $seasonId) {
                        $join->on('judges.id', '=', 'submissions.judge_id')
                             ->where('judges.round', '=', $rn)
                             ->where('judges.md_group', '=', $group)
                             ->where('judges.season_id', '=', $seasonId);
                    })
                    ->where('submissions.contestant_id', $contestant->id)
                    ->where('submissions.round', $rn)
                    ->where('submissions.md_group', $group)
                    ->where('submissions.season_id', $seasonId)
                    ->whereNotNull('submissions.score')
                    ->pluck('submissions.score');

                if ($subs->count() > 0) {
                    $avg = $subs->avg();
                    $roundAverages[$rn] = round($avg, 2);
                }
            }

            // Calculate season stats
            $seasonAvg = count($roundAverages) > 0 ? array_sum($roundAverages) / count($roundAverages) : 0;
            $seasonMedian = 0;
            $seasonStdDev = 0;

            if (count($roundAverages) > 0) {
                $values = array_values($roundAverages);
                sort($values);
                $count = count($values);
                if ($count % 2 === 0) {
                    $seasonMedian = ($values[$count / 2 - 1] + $values[$count / 2]) / 2;
                } else {
                    $seasonMedian = $values[intdiv($count, 2)];
                }

                $variance = 0;
                foreach ($values as $v) {
                    $variance += pow($v - $seasonAvg, 2);
                }
                $seasonStdDev = sqrt($variance / $count);
            }

            $entry = [
                'id' => $contestant->id,
                'name' => $contestant->global_name ?? $contestant->username,
                'eliminated' => $contestant->eliminated,
                'round_eliminated' => $elimRound,
                'round_averages' => $roundAverages,
                'season_avg' => round($seasonAvg, 2),
                'season_median' => round($seasonMedian, 3),
                'season_stddev' => round($seasonStdDev, 3),
            ];

            if ($contestant->eliminated) {
                // For eliminated contestants, the sort key is their average from the elimination round
                $entry['elim_round_avg'] = ($elimRound && isset($roundAverages[$elimRound]))
                    ? $roundAverages[$elimRound] : 0;
                $eliminatedStats[] = $entry;
            } else {
                $aliveStats[] = $entry;
            }
        }

        // Sort alive contestants by season average descending
        usort($aliveStats, function($a, $b) {
            $cmp = $b['season_avg'] <=> $a['season_avg'];
            if ($cmp === 0) {
                // Tiebreaker: lower std dev wins
                return $a['season_stddev'] <=> $b['season_stddev'];
            }
            return $cmp;
        });

        // Sort eliminated contestants by elimination round (later = higher rank), then by their elim round avg descending
        usort($eliminatedStats, function($a, $b) {
            $cmp = ($b['round_eliminated'] ?? 0) <=> ($a['round_eliminated'] ?? 0);
            if ($cmp === 0) {
                return $b['elim_round_avg'] <=> $a['elim_round_avg'];
            }
            return $cmp;
        });

        // Merge: alive first, then eliminated
        $allStats = array_merge($aliveStats, $eliminatedStats);

        // Build per-round rankings: for each round, rank contestants by their average in that round
        $roundRankings = []; // roundNumber => [ contestantId => rank ]
        $roundEliminated = []; // roundNumber => [ contestantId => true ] contestants eliminated IN that round
        foreach ($roundNumbers as $rn) {
            $scoresInRound = [];
            foreach ($allStats as $entry) {
                if (isset($entry['round_averages'][$rn])) {
                    $scoresInRound[] = ['id' => $entry['id'], 'avg' => $entry['round_averages'][$rn]];
                }
            }
            // Sort descending by average
            usort($scoresInRound, function($a, $b) {
                return $b['avg'] <=> $a['avg'];
            });
            $rankings = [];
            foreach ($scoresInRound as $i => $s) {
                $rankings[$s['id']] = $i + 1; // 1-indexed rank
            }
            $roundRankings[$rn] = $rankings;

            // Track who was eliminated in this round
            $roundEliminated[$rn] = [];
            foreach ($allStats as $entry) {
                if ($entry['eliminated'] && $entry['round_eliminated'] == $rn) {
                    $roundEliminated[$rn][$entry['id']] = true;
                }
            }
        }

        // Helper: heatmap color for a score (used for avg, median summary columns)
        if (!function_exists('getHeatColor')) {
            function getHeatColor($score) {
                if ($score === null) return 'transparent';
                $score = max(0, min(10, $score));
                if ($score >= 9.0) return '#2d8a4e';
                if ($score >= 8.5) return '#4caf50';
                if ($score >= 8.0) return '#8bc34a';
                if ($score >= 7.5) return '#cddc39';
                if ($score >= 7.0) return '#ffeb3b';
                if ($score >= 6.5) return '#ffc107';
                if ($score >= 6.0) return '#ff9800';
                if ($score >= 5.5) return '#ff5722';
                if ($score >= 5.0) return '#f44336';
                return '#b71c1c';
            }
        }

        if (!function_exists('getHeatTextColor')) {
            function getHeatTextColor($score) {
                if ($score === null) return '#888';
                if ($score >= 7.5) return '#111';
                return '#fff';
            }
        }

        // Helper: std dev color (lower = better = green, higher = worse = red)
        if (!function_exists('getStdDevColor')) {
            function getStdDevColor($stddev) {
                if ($stddev === null) return 'transparent';
                if ($stddev <= 0.3) return '#2d8a4e';
                if ($stddev <= 0.5) return '#4caf50';
                if ($stddev <= 0.7) return '#8bc34a';
                if ($stddev <= 0.9) return '#cddc39';
                if ($stddev <= 1.1) return '#ffeb3b';
                if ($stddev <= 1.3) return '#ffc107';
                if ($stddev <= 1.6) return '#ff9800';
                if ($stddev <= 2.0) return '#ff5722';
                if ($stddev <= 2.5) return '#f44336';
                return '#b71c1c';
            }
        }

        if (!function_exists('getStdDevTextColor')) {
            function getStdDevTextColor($stddev) {
                if ($stddev === null) return '#888';
                if ($stddev <= 0.9) return '#111';
                return '#fff';
            }
        }

        $totalFinished = count($roundNumbers);
    @endphp

    <style>
        @import url('https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@300;400;500;600;700&family=Source+Code+Pro:wght@300;400;500;600&display=swap');

        .stats-page {
            font-family: 'JetBrains Mono', 'Source Code Pro', 'Consolas', 'Monaco', monospace;
            background: #1e1e1e;
            color: #d4d4d4;
            min-height: 100vh;
            padding: 24px;
        }

        .stats-header {
            margin-bottom: 24px;
            display: flex;
            align-items: baseline;
            gap: 16px;
            flex-wrap: wrap;
        }

        .stats-header h1 {
            font-size: 22px;
            font-weight: 700;
            color: #4ec9b0;
            letter-spacing: 1px;
        }

        .stats-header .subtitle {
            font-size: 13px;
            color: #888;
            font-weight: 400;
        }

        .stats-header .subtitle span {
            color: #569cd6;
        }

        .table-wrapper {
            overflow-x: auto;
            border: 1px solid #333;
            border-radius: 6px;
            background: #252526;
            scrollbar-width: thin;
            scrollbar-color: #4ec9b0 #1e1e1e;
        }

        .table-wrapper::-webkit-scrollbar {
            height: 8px;
        }

        .table-wrapper::-webkit-scrollbar-track {
            background: #1e1e1e;
        }

        .table-wrapper::-webkit-scrollbar-thumb {
            background: #4ec9b0;
            border-radius: 4px;
        }

        .stats-table {
            border-collapse: collapse;
            width: 100%;
            min-width: max-content;
            font-size: 12.5px;
        }

        .stats-table th,
        .stats-table td {
            padding: 6px 10px;
            text-align: center;
            white-space: nowrap;
            border-bottom: 1px solid #333;
            border-right: 1px solid #2d2d30;
        }

        .stats-table th {
            background: #2d2d30;
            color: #569cd6;
            font-weight: 600;
            font-size: 11.5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            position: sticky;
            top: 0;
            z-index: 10;
            border-bottom: 2px solid #4ec9b0;
        }

        /* Sticky columns: #, Contestant */
        .stats-table th:nth-child(1),
        .stats-table td:nth-child(1) {
            position: sticky;
            left: 0;
            z-index: 5;
            background: #2d2d30;
            min-width: 36px;
            border-right: 1px solid #444;
        }

        .stats-table td:nth-child(1) {
            background: #252526;
            font-weight: 600;
        }

        .stats-table th:nth-child(2),
        .stats-table td:nth-child(2) {
            position: sticky;
            left: 36px;
            z-index: 5;
            background: #2d2d30;
            text-align: left;
            min-width: 140px;
            max-width: 180px;
            overflow: hidden;
            text-overflow: ellipsis;
            border-right: 2px solid #4ec9b0;
        }

        .stats-table td:nth-child(2) {
            background: #252526;
            color: #e0e0e0;
            font-weight: 500;
        }

        .stats-table th:nth-child(1) {
            z-index: 15;
        }

        .stats-table th:nth-child(2) {
            z-index: 15;
        }

        /* Round score cells */
        .score-cell {
            font-weight: 500;
            font-size: 12px;
            min-width: 52px;
            transition: transform 0.1s ease;
        }

        .score-cell:hover {
            transform: scale(1.05);
            outline: 1px solid #4ec9b0;
            outline-offset: -1px;
        }

        .score-cell.empty {
            background: #1a1a1a !important;
            color: #444 !important;
        }

        .score-cell.no-data {
            background: #2a2a2a !important;
            color: #555 !important;
            font-style: italic;
            font-size: 10px;
        }

        /* Summary stat columns */
        .stat-col {
            font-weight: 600;
            min-width: 64px;
        }

        /* Row types */
        .row-alive {
            /* default */
        }

        .row-alive:hover td {
            background-color: rgba(78, 201, 176, 0.06) !important;
        }

        .row-eliminated {
            opacity: 0.7;
        }

        .row-eliminated:hover {
            opacity: 1;
        }

        .row-eliminated td:nth-child(1),
        .row-eliminated td:nth-child(2) {
            background: #1e1e1e !important;
        }

        .row-eliminated td:nth-child(2) {
            color: #888;
        }

        /* Rank coloring */
        .rank-gold {
            color: #ffd700 !important;
            text-shadow: 0 0 6px rgba(255, 215, 0, 0.3);
        }

        .rank-silver {
            color: #c0c0c0 !important;
            text-shadow: 0 0 6px rgba(192, 192, 192, 0.3);
        }

        .rank-bronze {
            color: #cd7f32 !important;
            text-shadow: 0 0 6px rgba(205, 127, 50, 0.3);
        }

        .rank-eliminated {
            color: #f44336 !important;
        }

        /* Divider between alive and eliminated */
        .divider-row td {
            background: #1e1e1e !important;
            border-bottom: 2px solid #f44336;
            padding: 2px 10px;
            height: 8px;
        }

        .divider-row td:nth-child(1),
        .divider-row td:nth-child(2) {
            background: #1e1e1e !important;
        }

        .divider-label {
            color: #f44336;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            text-align: left !important;
        }

        /* Eliminated round indicator */
        .elim-badge {
            display: inline-block;
            background: #5c2020;
            color: #f48771;
            font-size: 9px;
            padding: 1px 5px;
            border-radius: 3px;
            margin-left: 6px;
            font-weight: 600;
            letter-spacing: 0.5px;
            vertical-align: middle;
        }

        /* No data state */
        .no-data-message {
            text-align: center;
            padding: 60px 20px;
            color: #888;
            font-size: 14px;
        }

        .no-data-message .icon {
            font-size: 36px;
            margin-bottom: 12px;
            color: #4ec9b0;
        }

        /* Legend */
        .stats-legend {
            display: flex;
            gap: 16px;
            margin-top: 16px;
            flex-wrap: wrap;
            font-size: 11px;
            color: #888;
            align-items: center;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .legend-swatch {
            width: 14px;
            height: 14px;
            border-radius: 2px;
            border: 1px solid #444;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .stats-page {
                padding: 12px 8px;
            }

            .stats-header h1 {
                font-size: 18px;
            }

            .stats-table {
                font-size: 11px;
            }

            .stats-table th,
            .stats-table td {
                padding: 4px 6px;
            }

            .score-cell {
                min-width: 42px;
            }
        }
    </style>

    <div class="stats-page">
        <div class="stats-header">
            <h1>> STATS_SHEET</h1>
            <div class="subtitle">
                {{ $season->name ?? 'No Season' }}
                //
                <span>{{ $totalFinished }}</span> rounds completed
                //
                <span>{{ count($aliveStats) }}</span> alive
                //
                <span>{{ count($eliminatedStats) }}</span> eliminated
            </div>
        </div>

        @if(count($allStats) === 0 || $totalFinished === 0)
            <div class="no-data-message">
                <div class="icon">{ }</div>
                <p>No completed rounds or contestants found.</p>
                <p style="font-size: 12px; margin-top: 8px;">Stats will appear once at least one round is finished.</p>
            </div>
        @else
            <div class="table-wrapper">
                <table class="stats-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Contestant</th>
                            @foreach($roundNumbers as $rn)
                                <th>R{{ $rn }}</th>
                            @endforeach
                            <th>Average</th>
                            <th>Median</th>
                            <th>S. Dev</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $rank = 1; $showedDivider = false; @endphp
                        @foreach($allStats as $idx => $entry)
                            @if($entry['eliminated'] && !$showedDivider)
                                @php $showedDivider = true; @endphp
                                <tr class="divider-row">
                                    <td></td>
                                    <td class="divider-label">// ELIMINATED</td>
                                    @foreach($roundNumbers as $rn)
                                        <td></td>
                                    @endforeach
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                </tr>
                            @endif

                            @php
                                $isAlive = !$entry['eliminated'];
                                $rankClass = '';
                                if ($isAlive) {
                                    if ($rank === 1) $rankClass = 'rank-gold';
                                    elseif ($rank === 2) $rankClass = 'rank-silver';
                                    elseif ($rank === 3) $rankClass = 'rank-bronze';
                                } else {
                                    $rankClass = 'rank-eliminated';
                                }
                            @endphp

                            <tr class="{{ $isAlive ? 'row-alive' : 'row-eliminated' }}">
                                <td class="{{ $rankClass }}">{{ $rank }}</td>
                                <td>
                                    {{ $entry['name'] }}
                                    @if($entry['eliminated'] && $entry['round_eliminated'])
                                        <span class="elim-badge">R{{ $entry['round_eliminated'] }}</span>
                                    @endif
                                </td>
                                @foreach($roundNumbers as $rn)
                                    @php
                                        $hasScore = isset($entry['round_averages'][$rn]);
                                        $isAfterElim = $entry['eliminated']
                                            && $entry['round_eliminated']
                                            && $rn > $entry['round_eliminated'];
                                        $score = $hasScore ? $entry['round_averages'][$rn] : null;

                                        // Determine cell color for this round
                                        $cellBg = 'transparent';
                                        $cellColor = '#d4d4d4';

                                        if ($hasScore && !$isAfterElim) {
                                            $contestantRank = $roundRankings[$rn][$entry['id']] ?? null;
                                            $wasEliminatedHere = isset($roundEliminated[$rn][$entry['id']]);

                                            if ($wasEliminatedHere) {
                                                // Eliminated in this round -> red
                                                $cellBg = '#c62828';
                                                $cellColor = '#fff';
                                            } elseif ($contestantRank === 1) {
                                                $cellBg = '#b8860b';
                                                $cellColor = '#fff';
                                            } elseif ($contestantRank === 2) {
                                                $cellBg = '#808080';
                                                $cellColor = '#fff';
                                            } elseif ($contestantRank === 3) {
                                                $cellBg = '#8B5E3C';
                                                $cellColor = '#fff';
                                            } elseif ($score >= 9.0) {
                                                $cellBg = '#2e7d32';
                                                $cellColor = '#fff';
                                            }
                                        }
                                    @endphp

                                    @if($isAfterElim)
                                        <td class="score-cell empty"></td>
                                    @elseif($hasScore)
                                        <td class="score-cell"
                                            style="background: {{ $cellBg }}; color: {{ $cellColor }};">
                                            {{ number_format($score, 2) }}
                                        </td>
                                    @else
                                        <td class="score-cell no-data">--</td>
                                    @endif
                                @endforeach
                                <td class="stat-col avg"
                                    style="background: {{ getHeatColor($entry['season_avg']) }}; color: {{ getHeatTextColor($entry['season_avg']) }};">
                                    {{ number_format($entry['season_avg'], 2) }}
                                </td>
                                <td class="stat-col median"
                                    style="background: {{ getHeatColor($entry['season_median']) }}; color: {{ getHeatTextColor($entry['season_median']) }};">
                                    {{ number_format($entry['season_median'], 2) }}
                                </td>
                                <td class="stat-col stddev"
                                    style="background: {{ getStdDevColor($entry['season_stddev']) }}; color: {{ getStdDevTextColor($entry['season_stddev']) }};">
                                    {{ number_format($entry['season_stddev'], 3) }}
                                </td>
                            </tr>
                            @php $rank++; @endphp
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="stats-legend">
                <span style="color: #569cd6;">// ROUNDS</span>
                <div class="legend-item">
                    <div class="legend-swatch" style="background: #b8860b;"></div> 1st
                </div>
                <div class="legend-item">
                    <div class="legend-swatch" style="background: #808080;"></div> 2nd
                </div>
                <div class="legend-item">
                    <div class="legend-swatch" style="background: #8B5E3C;"></div> 3rd
                </div>
                <div class="legend-item">
                    <div class="legend-swatch" style="background: #2e7d32;"></div> 9.0+
                </div>
                <div class="legend-item">
                    <div class="legend-swatch" style="background: #c62828;"></div> eliminated
                </div>
                <span style="margin-left: 12px; color: #569cd6;">// SUMMARY</span>
                <div class="legend-item">
                    <div class="legend-swatch" style="background: #2d8a4e;"></div> best
                </div>
                <div class="legend-item">
                    <div class="legend-swatch" style="background: #ffeb3b;"></div> mid
                </div>
                <div class="legend-item">
                    <div class="legend-swatch" style="background: #b71c1c;"></div> worst
                </div>
                <span style="margin-left: 12px; color: #569cd6;">|</span>
                <div class="legend-item">
                    <div class="legend-swatch" style="background: #1a1a1a;"></div> post-elim
                </div>
            </div>
        @endif
    </div>
</x-app-layout>