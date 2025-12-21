<x-app-layout>
    @vite(['resources/css/results.css'])

    @php
    // Get the active season
    $activeSeason = DB::table('season')
        ->where('active', true)
        ->first();

    $seasonId = $activeSeason ? $activeSeason->season_id : null;

    $group = request()->query('group', 0);
    $round = request()->query('round', -1);
    if ($group < 0 || $group > 3) {
        $group = -1;
    }

    // Get round information for the round we're viewing
    $roundInfo = $seasonId ? DB::table('round')
        ->where('round_number', $round)
        ->where('season_id', $seasonId)
        ->first() : null;

    // Get the currently active round
    $activeRound = $seasonId ? DB::table('round')
        ->where('status', 1)
        ->where('season_id', $seasonId)
        ->orderBy('round_number')
        ->first() : null;

    // Determine which group column to use
    $groupColumn = 'md_group';
    if ($activeRound && $roundInfo) {
        // If merge status doesn't match between active and viewed round, use group_legacy
        if ($activeRound->is_merge != $roundInfo->is_merge) {
            $groupColumn = 'group_legacy';
        }
    }

    $effectiveGroup = $group;

    // Check permissions
    $userPerms = auth()->user()->perms ?? 0;
    $isStaff = $userPerms >= 6;
    $canViewResults = $isStaff || ($roundInfo && $roundInfo->status == 2);

    if (!$canViewResults) {
        // Show access denied message
        echo '<div class="main-content" style="flex: 1; padding: 40px;">
            <div class="access-denied">
                <div class="access-denied-icon">ACCESS DENIED</div>
                <h2>Access Denied</h2>
                <p>Results for this round are not yet available.</p>
                <p>Please wait until the results are officially released.</p>
            </div>
        </div>';
        exit;
    }

    // Check if there are any submissions with NULL scores for this group and round
    $hasNullScores = $seasonId ? DB::table('submissions')
        ->where('md_group', $effectiveGroup)
        ->where('round', $round)
        ->where('season_id', $seasonId)
        ->whereNull('score')
        ->exists() : false;

    $contestants = $seasonId ? DB::table('contestants')
        ->join('users', 'users.id', '=', 'contestants.id')
        ->where('contestants.season_id', $seasonId)
        ->where("contestants.$groupColumn", $effectiveGroup)
        ->get() : collect();

    // Count contestants with null submission_date and not eliminated
    $missedSubmissions = $seasonId ? DB::table('contestants')
        ->where('season_id', $seasonId)
        ->where($groupColumn, $effectiveGroup)
        ->where('eliminated', false)
        ->whereNull('submission_date')
        ->count() : 0;

    $eliminateNumber = $roundInfo ? $roundInfo->eliminate_number : 0;
    $eliminationThreshold = max(1, $eliminateNumber - $missedSubmissions);

    $subsTable = [];
    $j = 0;

    foreach ($contestants as $contestant) {
        $i = 1;
        $scores = [];
        $scores[0] = $contestant->global_name;
        $avg = 0.0;

        $subs = $seasonId ? DB::table('submissions')
        ->join('judges', function ($join) use ($round, $effectiveGroup, $seasonId) {
            $join->on('judges.id', '=', 'submissions.judge_id')
                 ->where('judges.round', '=', $round)
                 ->where('judges.md_group', '=', $effectiveGroup)
                 ->where('judges.season_id', '=', $seasonId);
        })
        ->join('users', 'users.id', '=', 'judges.id')
        ->select('submissions.*', 'judges.judge_number', 'users.global_name', 'users.username')
        ->where('submissions.contestant_id', $contestant->id)
        ->where('submissions.round', $round)
        ->where('submissions.md_group', $effectiveGroup)
        ->where('submissions.season_id', $seasonId)
        ->orderBy('judges.judge_number')
        ->get() : collect();

        foreach ($subs as $sub) {
            $song = $sub->artist . ' - ' . $sub->title;
            $scores[$i] = $song;
            $i++;
            $scores[$i] = $sub->url; // Store the URL
            $i++;
            $scores[$i] = $sub->global_name;
            $i++;
            $scores[$i] = $sub->review;
            $i++;
            $avg += $sub->score;
            $scores[$i] = $sub->score;
            $i++;
        }

        $stddev = 0.0;
        $subCount = count($subs);
        if ($subCount != 0) {
            $avg /= $subCount;
            $variance = 0.0;
            foreach ($subs as $sub) {
                $variance += pow($sub->score - $avg, 2);
            }
            $stddev = sqrt($variance / $subCount);
        }

        $scores[$i] = round($avg, 2);
        $i++;
        $scores[$i] = round($stddev, 3);
        
        // Only add to subsTable if there are submissions
        if ($subCount > 0) {
            $subsTable[$j] = $scores;
            $j++;
        }
    }

    usort($subsTable, function($a, $b) {
        $cmp = $b[count($b)-2] <=> $a[count($a)-2];
        if ($cmp === 0) {
            $cmp = $a[count($a)-1] <=> $b[count($b)-1];
        }
        return $cmp;
    });

    // Determine rank colors
    $totalContestants = count($subsTable);
    function getRankClass($rank, $total, $elimThreshold) {
        if ($rank == 1) return 'gold';
        if ($rank == 2 && $total >= 2) return 'silver';
        if ($rank == 3 && $total >= 3) return 'bronze';
        if ($rank > $total - $elimThreshold) return 'eliminated';
        return '';
    }

    // Get slide backgrounds from database
    function getSlideBackground($roundInfo, $rank, $total, $elimThreshold) {
        if (!$roundInfo) return '';
        
        // Helper function to generate proper storage URL
        $getUrl = function($path) {
            if (empty($path)) return '';
            // If path already starts with 'slides/', use it as-is
            // Otherwise, assume it's the full path
            return asset('storage/' . $path);
        };
        
        // Check podium positions FIRST (highest priority)
        if ($rank == 1 && !empty($roundInfo->slidebg_first)) {
            $url = $getUrl($roundInfo->slidebg_first);
            return "background: url('{$url}') center/100% 100% no-repeat;";
        }
        if ($rank == 2 && !empty($roundInfo->slidebg_second)) {
            $url = $getUrl($roundInfo->slidebg_second);
            return "background: url('{$url}') center/100% 100% no-repeat;";
        }
        if ($rank == 3 && !empty($roundInfo->slidebg_third)) {
            $url = $getUrl($roundInfo->slidebg_third);
            return "background: url('{$url}') center/100% 100% no-repeat;";
        }
        
        // Check if eliminated (lower priority than podium)
        if ($rank > $total - $elimThreshold && !empty($roundInfo->slidebg_elim)) {
            $url = $getUrl($roundInfo->slidebg_elim);
            return "background: url('{$url}') center/100% 100% no-repeat;";
        }
        
        // Normal background
        if (!empty($roundInfo->slidebg_normal)) {
            $url = $getUrl($roundInfo->slidebg_normal);
            return "background: url('{$url}') center/100% 100% no-repeat;";
        }
        
        return '';
    }
    @endphp

    @if ($isStaff)
        <!-- Floating Staff Tools Button -->
        <button class="staff-tools-button" onclick="toggleStaffModal()" title="Staff Tools">
            [ADMIN]
        </button>

        <!-- Modal Overlay -->
        <div class="modal-overlay" id="modalOverlay" onclick="toggleStaffModal()"></div>

        <!-- Background Upload Modal -->
        <div class="bg-upload-modal" id="staffModal">
            <div class="modal-header">
                <h3>Slide Background Manager</h3>
                <button class="modal-close" onclick="toggleStaffModal()">X</button>
            </div>

            <p style="color: #d4d4d4; margin-bottom: 20px;">Upload background images for different placement slides. Images should be at least 1200x800px for best results.</p>
            
            @if($roundInfo)
                <div class="debug-info-box">
                    <strong>[DEBUG INFO]</strong><br>
                    @if($roundInfo->slidebg_first)
                        1st: {{ asset('storage/' . $roundInfo->slidebg_first) }}<br>
                    @endif
                    @if($roundInfo->slidebg_second)
                        2nd: {{ asset('storage/' . $roundInfo->slidebg_second) }}<br>
                    @endif
                    @if($roundInfo->slidebg_third)
                        3rd: {{ asset('storage/' . $roundInfo->slidebg_third) }}<br>
                    @endif
                    @if($roundInfo->slidebg_normal)
                        Normal: {{ asset('storage/' . $roundInfo->slidebg_normal) }}<br>
                    @endif
                    @if($roundInfo->slidebg_elim)
                        Elim: {{ asset('storage/' . $roundInfo->slidebg_elim) }}<br>
                    @endif
                    @if(!$roundInfo->slidebg_first && !$roundInfo->slidebg_second && !$roundInfo->slidebg_third && !$roundInfo->slidebg_normal && !$roundInfo->slidebg_elim)
                        <em>No backgrounds uploaded yet</em>
                    @endif
                </div>
            @endif
            
            <form action="{{ route('update.slide.backgrounds') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="round" value="{{ $round }}">
                
                <div class="bg-upload-grid">
                    <div class="bg-upload-item">
                        <label>[1ST PLACE]</label>
                        <input type="file" name="slidebg_first" accept="image/*">
                        @if($roundInfo && $roundInfo->slidebg_first)
                            <img src="{{ asset('storage/' . $roundInfo->slidebg_first) }}" class="bg-preview" alt="1st place background" onerror="this.style.border='2px solid red'; this.alt='Image not found';">
                            <div style="font-size: 10px; color: #858585; margin-top: 5px;">{{ $roundInfo->slidebg_first }}</div>
                        @endif
                    </div>

                    <div class="bg-upload-item">
                        <label>[2ND PLACE]</label>
                        <input type="file" name="slidebg_second" accept="image/*">
                        @if($roundInfo && $roundInfo->slidebg_second)
                            <img src="{{ asset('storage/' . $roundInfo->slidebg_second) }}" class="bg-preview" alt="2nd place background" onerror="this.style.border='2px solid red'; this.alt='Image not found';">
                            <div style="font-size: 10px; color: #858585; margin-top: 5px;">{{ $roundInfo->slidebg_second }}</div>
                        @endif
                    </div>

                    <div class="bg-upload-item">
                        <label>[3RD PLACE]</label>
                        <input type="file" name="slidebg_third" accept="image/*">
                        @if($roundInfo && $roundInfo->slidebg_third)
                            <img src="{{ asset('storage/' . $roundInfo->slidebg_third) }}" class="bg-preview" alt="3rd place background" onerror="this.style.border='2px solid red'; this.alt='Image not found';">
                            <div style="font-size: 10px; color: #858585; margin-top: 5px;">{{ $roundInfo->slidebg_third }}</div>
                        @endif
                    </div>

                    <div class="bg-upload-item">
                        <label>[NORMAL]</label>
                        <input type="file" name="slidebg_normal" accept="image/*">
                        @if($roundInfo && $roundInfo->slidebg_normal)
                            <img src="{{ asset('storage/' . $roundInfo->slidebg_normal) }}" class="bg-preview" alt="Normal background" onerror="this.style.border='2px solid red'; this.alt='Image not found';">
                            <div style="font-size: 10px; color: #858585; margin-top: 5px;">{{ $roundInfo->slidebg_normal }}</div>
                        @endif
                    </div>

                    <div class="bg-upload-item">
                        <label>[ELIMINATED]</label>
                        <input type="file" name="slidebg_elim" accept="image/*">
                        @if($roundInfo && $roundInfo->slidebg_elim)
                            <img src="{{ asset('storage/' . $roundInfo->slidebg_elim) }}" class="bg-preview" alt="Eliminated background" onerror="this.style.border='2px solid red'; this.alt='Image not found';">
                            <div style="font-size: 10px; color: #858585; margin-top: 5px;">{{ $roundInfo->slidebg_elim }}</div>
                        @endif
                    </div>
                </div>

                <button type="submit" style="margin-top: 20px; width: 100%; padding: 12px 30px; background: #0e4429; color: #4ec9b0; border: 2px solid #4ec9b0; border-radius: 4px; font-size: 16px; font-weight: bold; cursor: pointer; transition: all 0.3s;">
                    Save Backgrounds
                </button>
            </form>
        </div>

        <script>
            function toggleStaffModal() {
                const modal = document.getElementById('staffModal');
                const overlay = document.getElementById('modalOverlay');
                
                if (modal.classList.contains('active')) {
                    modal.classList.remove('active');
                    overlay.classList.remove('active');
                } else {
                    modal.classList.add('active');
                    overlay.classList.add('active');
                }
            }

            // Close modal with Escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    const modal = document.getElementById('staffModal');
                    const overlay = document.getElementById('modalOverlay');
                    if (modal.classList.contains('active')) {
                        modal.classList.remove('active');
                        overlay.classList.remove('active');
                    }
                }
            });
        </script>
    @endif

    @if ($hasNullScores)
        <div class="main-content" style="flex: 1; padding: 40px;">
            <div class="announcement">
                <div class="announcement-icon">PENDING</div>
                <h2>Judging Still in Progress</h2>
                <p>Not every song has been judged yet for Group {{ $group == 0 ? 'Merge' : $group }} - Round {{ $round }}.</p>
                <p>Please wait until all submissions have been scored before viewing the results.</p>
                <p><em>Some judges still need to complete their evaluations.</em></p>
            </div>
        </div>
    @elseif (count($subsTable) == 0)
        <div class="main-content" style="flex: 1; padding: 40px;">
            <div class="no-results">
                <p>No contestants found for the specified group and round.</p>
            </div>
        </div>
    @else
        <div class="results-container">
            <div class="sidebar">
                <h2>Results Navigation</h2>
                <ul class="slide-nav">
                    @foreach ($subsTable as $index => $table)
                        <li onclick="showSlide({{ $index }})" data-slide="{{ $index }}" class="{{ $index === 0 ? 'active' : '' }}">
                            #{{ $index + 1 }}: {{ $table[0] }}
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="main-content">
                @foreach ($subsTable as $index => $table)
                    @php
                        $rank = $index + 1;
                        $rankClass = getRankClass($rank, $totalContestants, $eliminationThreshold);
                        $slideStyle = getSlideBackground($roundInfo, $rank, $totalContestants, $eliminationThreshold);
                    @endphp
                    <div class="result-slide {{ $index === 0 ? 'active' : '' }}" data-slide="{{ $index }}" style="{{ $slideStyle }}">
                        <div class="slide-header">
                            <div class="rank-box {{ $rankClass }}">#{{ $rank }}</div>
                            <div class="contestant-name">{{ $table[0] }}</div>
                            <div class="score-box">{{ $table[count($table) - 2] }}</div>
                        </div>

                        <div class="judge-block">
                            <div class="judge-header">
                                <div class="judge-name">{{ $table[3] }}</div>
                                <div class="song-title"><a href="{{ $table[2] }}" target="_blank">{{ $table[1] }}</a></div>
                                <div class="judge-score @if($table[5] == 10) perfect @elseif($table[5] >= 9) high @elseif($table[5] < 4) low @endif">{{ $table[5] }}</div>
                            </div>
                            <div class="review-body">{{ $table[4] }}</div>
                        </div>

                        <div class="judge-block">
                            <div class="judge-header">
                                <div class="judge-name">{{ $table[8] }}</div>
                                <div class="song-title"><a href="{{ $table[7] }}" target="_blank">{{ $table[6] }}</a></div>
                                <div class="judge-score @if($table[10] == 10) perfect @elseif($table[10] >= 9) high @elseif($table[10] < 4) low @endif">{{ $table[10] }}</div>
                            </div>
                            <div class="review-body">{{ $table[9] }}</div>
                        </div>

                        <div class="judge-block">
                            <div class="judge-header">
                                <div class="judge-name">{{ $table[13] }}</div>
                                <div class="song-title"><a href="{{ $table[12] }}" target="_blank">{{ $table[11] }}</a></div>
                                <div class="judge-score @if($table[15] == 10) perfect @elseif($table[15] >= 9) high @elseif($table[15] < 4) low @endif">{{ $table[15] }}</div>
                            </div>
                            <div class="review-body">{{ $table[14] }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <script>
            function showSlide(index) {
                // Hide all slides
                document.querySelectorAll('.result-slide').forEach(slide => {
                    slide.classList.remove('active');
                });
                
                // Remove active from all nav items
                document.querySelectorAll('.slide-nav li').forEach(item => {
                    item.classList.remove('active');
                });
                
                // Show selected slide
                document.querySelector(`.result-slide[data-slide="${index}"]`).classList.add('active');
                document.querySelector(`.slide-nav li[data-slide="${index}"]`).classList.add('active');
            }
        </script>
    @endif
</x-app-layout>