\<x-app-layout>
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
        ->select('contestants.*', DB::raw('COALESCE(users.global_name, users.username) as global_name'))
        ->where('contestants.season_id', $seasonId)
        ->where("contestants.$groupColumn", $effectiveGroup)
        ->get() : collect();

    $subsTable = [];
    $j = 0;

    foreach ($contestants as $contestant) {
        $i = 2;  // Start at 2 to leave room for avatar URL and name
        $scores = [];
        
        // Get user for avatar
        $user = \App\Models\User::find($contestant->id);
        $scores[0] = $user ? $user->getAvatar(['extension' => 'webp', 'size' => 64]) : '';
        $scores[1] = $contestant->global_name;
        
        $avg = 0.0;

        $subs = $seasonId ? DB::table('submissions')
            ->join('judges', function ($join) use ($round, $effectiveGroup, $seasonId) {
                $join->on('judges.id', '=', 'submissions.judge_id')
                    ->where('judges.round', '=', $round)
                    ->where('judges.md_group', '=', $effectiveGroup)
                    ->where('judges.season_id', '=', $seasonId);
            })
            ->join('users', 'users.id', '=', 'judges.id')
            ->select('submissions.*', 'judges.judge_number', DB::raw('COALESCE(users.global_name, users.username) as global_name'), 'users.username')
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
        $i++;
        $scores[$i] = $contestant->round_eliminated;
        
        // Only add to subsTable if there are submissions
        if ($subCount > 0) {
            $subsTable[$j] = $scores;
            $j++;
        }
    }

    usort($subsTable, function($a, $b) {
        $cmp = $b[count($b)-3] <=> $a[count($a)-3];
        if ($cmp === 0) {
            $cmp = $a[count($a)-2] <=> $b[count($b)-2];
        }
        return $cmp;
    });

    // Determine rank colors
    $totalContestants = count($subsTable);
    function getRankClass($rank, $total, $isEliminated) {
        if ($rank == 1) return 'gold';
        if ($rank == 2 && $total >= 2) return 'silver';
        if ($rank == 3 && $total >= 3) return 'bronze';
        if ($isEliminated) return 'eliminated';
        return '';
    }

    // Get slide backgrounds from database
    function getSlideBackground($roundInfo, $rank, $total, $isEliminated) {
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
        if ($isEliminated && !empty($roundInfo->slidebg_elim)) {
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
                            #{{ $index + 1 }}: {{ $table[1] }}
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="main-content">
                @foreach ($subsTable as $index => $table)
                    @php
                        $rank = $index + 1;
                        $isEliminated = $table[count($table) - 1] == $round;
                        $rankClass = getRankClass($rank, $totalContestants, $isEliminated);
                        $slideStyle = getSlideBackground($roundInfo, $rank, $totalContestants, $isEliminated);
                    @endphp
                    <div class="result-slide {{ $index === 0 ? 'active' : '' }}" data-slide="{{ $index }}" style="{{ $slideStyle }}">
                        <div class="slide-header">
                            <div class="rank-box {{ $rankClass }}">#{{ $rank }}</div>
                            <div class="contestant-name">
                                @if($table[0])
                                    <img class="contestant-avatar" src="{{ $table[0] }}" alt="">
                                @endif
                                <span>{{ $table[1] }}</span>
                            </div>
                            <div class="score-box">{{ $table[count($table) - 3] }}</div>
                        </div>

                        <div class="judge-block">
                            <div class="judge-header">
                                <div class="judge-name">{{ $table[4] }}</div>
                                <div class="song-title"><a href="{{ $table[3] }}" target="_blank">{{ $table[2] }}</a></div>
                                <div class="judge-score @if($table[6] == 10) perfect @elseif($table[6] >= 9) high @elseif($table[6] < 4) low @endif">{{ $table[6] }}</div>
                            </div>
                            <div class="review-body"><span class="review-text">{{ $table[5] }}</span></div>
                        </div>

                        <div class="judge-block">
                            <div class="judge-header">
                                <div class="judge-name">{{ $table[9] }}</div>
                                <div class="song-title"><a href="{{ $table[8] }}" target="_blank">{{ $table[7] }}</a></div>
                                <div class="judge-score @if($table[11] == 10) perfect @elseif($table[11] >= 9) high @elseif($table[11] < 4) low @endif">{{ $table[11] }}</div>
                            </div>
                            <div class="review-body"><span class="review-text">{{ $table[10] }}</span></div>
                        </div>

                        <div class="judge-block">
                            <div class="judge-header">
                                <div class="judge-name">{{ $table[14] }}</div>
                                <div class="song-title"><a href="{{ $table[13] }}" target="_blank">{{ $table[12] }}</a></div>
                                <div class="judge-score @if($table[16] == 10) perfect @elseif($table[16] >= 9) high @elseif($table[16] < 4) low @endif">{{ $table[16] }}</div>
                            </div>
                            <div class="review-body"><span class="review-text">{{ $table[15] }}</span></div>
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

                // Re-scale text for the newly visible slide
                setTimeout(scaleReviewText, 10);
            }

            // Auto-scale review text to fit fixed-height boxes
            function scaleReviewText() {
                document.querySelectorAll('.result-slide.active .review-body').forEach(function(box) {
                    var textEl = box.querySelector('.review-text');
                    if (!textEl) return;

                    // Reset to base size first
                    textEl.style.fontSize = '16px';

                    // Shrink until it fits or we hit minimum 9px
                    var fontSize = 16;
                    while (textEl.scrollHeight > box.clientHeight && fontSize > 9) {
                        fontSize -= 0.5;
                        textEl.style.fontSize = fontSize + 'px';
                    }
                });
            }

            // Run on page load
            document.addEventListener('DOMContentLoaded', scaleReviewText);
            window.addEventListener('load', scaleReviewText);
        </script>
    @endif
</x-app-layout>