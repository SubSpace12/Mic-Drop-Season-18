<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Stats Sheet') }}
        </h2>
    </x-slot>

    @vite(['resources/css/stats.css'])

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
            $roundStdDevs = [];

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

                    $variance = 0;
                    foreach ($subs as $s) {
                        $variance += pow($s - $avg, 2);
                    }
                    $roundStdDevs[$rn] = round(sqrt($variance / $subs->count()), 3);
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
                'md_group' => $contestant->md_group,
                'round_averages' => $roundAverages,
                'round_stddevs' => $roundStdDevs,
                'season_avg' => round($seasonAvg, 2),
                'season_median' => round($seasonMedian, 3),
                'season_stddev' => round($seasonStdDev, 3),
            ];

            if ($contestant->eliminated) {
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

        // Build per-round rankings PER GROUP
        $roundRankings = [];
        $roundEliminated = [];
        foreach ($roundNumbers as $rn) {
            $round = $finishedRounds->firstWhere('round_number', $rn);
            if (!$round) continue;

            $groupScores = [];
            foreach ($allStats as $entry) {
                if (!isset($entry['round_averages'][$rn])) continue;
                $group = $round->is_merge ? 0 : $entry['md_group'];
                $groupScores[$group][] = [
                    'id' => $entry['id'],
                    'avg' => $entry['round_averages'][$rn],
                    'stddev' => $entry['round_stddevs'][$rn] ?? 0,
                ];
            }

            $rankings = [];
            foreach ($groupScores as $group => $scores) {
                usort($scores, function($a, $b) {
                    $cmp = $b['avg'] <=> $a['avg'];
                    if ($cmp === 0) {
                        return $a['stddev'] <=> $b['stddev'];
                    }
                    return $cmp;
                });
                $currentRank = 1;
                foreach ($scores as $i => $s) {
                    if ($i > 0
                        && $s['avg'] == $scores[$i - 1]['avg']
                        && $s['stddev'] == $scores[$i - 1]['stddev']) {
                        $rankings[$s['id']] = $rankings[$scores[$i - 1]['id']];
                    } else {
                        $rankings[$s['id']] = $currentRank;
                    }
                    $currentRank++;
                }
            }
            $roundRankings[$rn] = $rankings;

            $roundEliminated[$rn] = [];
            foreach ($allStats as $entry) {
                if ($entry['eliminated'] && $entry['round_eliminated'] == $rn) {
                    $roundEliminated[$rn][$entry['id']] = true;
                }
            }
        }

        // Heatmap helpers (these produce inline style colors for data cells — theme-independent)
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

    <div class="stats-page">
        <div class="stats-header">
            <h1>Stats Sheet</h1>
            <div class="subtitle">
                {{ $season->name ?? 'No Season' }}
                &mdash;
                <span>{{ $totalFinished }}</span> rounds completed
                &mdash;
                <span>{{ count($aliveStats) }}</span> alive
                &mdash;
                <span>{{ count($eliminatedStats) }}</span> eliminated
            </div>
        </div>

        @if(count($allStats) === 0 || $totalFinished === 0)
            <div class="no-data-message">
                <div class="icon">._.</div>
                <p>No completed rounds or contestants found.</p>
                <p style="font-size: 12px; margin-top: 8px; color: var(--text-dim);">Stats will appear once at least one round is finished.</p>
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
                                    <td class="divider-label">Eliminated</td>
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

                                        $cellBg = 'transparent';
                                        $cellColor = 'var(--stats-cell-text)';

                                        if ($hasScore && !$isAfterElim) {
                                            $contestantRank = $roundRankings[$rn][$entry['id']] ?? null;
                                            $wasEliminatedHere = isset($roundEliminated[$rn][$entry['id']]);

                                            if ($wasEliminatedHere) {
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
        @endif
    </div>
</x-app-layout>