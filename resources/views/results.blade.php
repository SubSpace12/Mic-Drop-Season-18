<x-app-layout>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Roboto+Mono:wght@300;400;500;600;700&display=swap');

        * {
            font-family: 'Consolas', 'Monaco', 'Roboto Mono', 'Courier New', monospace;
        }

        .results-container {
            display: flex;
            height: calc(100vh - 64px);
            overflow: hidden;
            background: linear-gradient(135deg, #1e1e1e 0%, #252526 100%);
        }

        .sidebar {
            width: 280px;
            background: #2d2d30;
            color: white;
            overflow-y: auto;
            box-shadow: 2px 0 10px rgba(0,0,0,0.5);
            border-right: 2px solid #3e3e42;
        }

        .sidebar h2 {
            padding: 20px;
            background: #1e1e1e;
            font-size: 18px;
            border-bottom: 2px solid #3e3e42;
            color: #4ec9b0;
            font-weight: 600;
        }

        .slide-nav {
            list-style: none;
        }

        .slide-nav li {
            padding: 15px 20px;
            cursor: pointer;
            border-bottom: 1px solid #3e3e42;
            transition: background 0.2s;
            color: #d4d4d4;
        }

        .slide-nav li:hover {
            background: #3e3e42;
        }

        .slide-nav li.active {
            background: #0e639c;
            border-left: 4px solid #4ec9b0;
            color: white;
        }

        .main-content {
            flex: 1;
            overflow-y: auto;
            padding: 40px;
            background: linear-gradient(135deg, #1e1e1e 0%, #252526 100%);
        }

        .result-slide {
            display: none;
            max-width: 1200px;
            margin: 0 auto;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.8);
            position: relative;
            border: 2px solid #3e3e42;
        }

        .result-slide.active {
            display: block;
        }

        .slide-header {
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 20px;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #3e3e42;
        }

        .rank-box, .score-box {
            background: #2d2d30;
            color: #d4d4d4;
            padding: 15px 25px;
            border-radius: 4px;
            font-size: 24px;
            font-weight: bold;
            text-align: center;
            min-width: 80px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.5);
            border: 2px solid #3e3e42;
        }

        .rank-box.gold {
            background: linear-gradient(135deg, #d4a574, #b8935f);
            color: #1e1e1e;
            border-color: #d4a574;
            box-shadow: 0 4px 15px rgba(212, 165, 116, 0.6);
        }

        .rank-box.silver {
            background: linear-gradient(135deg, #858585, #6e6e6e);
            color: #1e1e1e;
            border-color: #858585;
            box-shadow: 0 4px 15px rgba(133, 133, 133, 0.6);
        }

        .rank-box.bronze {
            background: linear-gradient(135deg, #8b5a3c, #6d4428);
            color: #d4d4d4;
            border-color: #8b5a3c;
            box-shadow: 0 4px 15px rgba(139, 90, 60, 0.6);
        }

        .rank-box.eliminated {
            background: linear-gradient(135deg, #7a2828, #5a1e1e);
            color: #d4d4d4;
            border-color: #c85050;
            box-shadow: 0 4px 15px rgba(200, 80, 80, 0.8);
            animation: terminal-blink 2s infinite;
        }

        @keyframes terminal-blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }

        .score-box {
            background: #0e4429;
            color: #4ec9b0;
            border-color: #4ec9b0;
            box-shadow: 0 4px 15px rgba(78, 201, 176, 0.4);
        }

        .contestant-name {
            font-size: 28px;
            font-weight: bold;
            text-align: center;
            color: #4ec9b0;
            background: #1e1e1e;
            padding: 10px 20px;
            border-radius: 4px;
            border: 2px solid #3e3e42;
        }

        .judge-block {
            margin-bottom: 20px;
            border: 2px solid #3e3e42;
            border-radius: 4px;
            overflow: hidden;
            background: #1e1e1e;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.5);
        }

        .judge-header {
            display: grid;
            grid-template-columns: 1fr 2fr 1fr;
            background: #2d2d30;
            color: #d4d4d4;
            padding: 12px 20px;
            font-size: 14px;
            align-items: center;
            border-bottom: 2px solid #3e3e42;
        }

        .judge-name {
            text-align: left;
            font-weight: 600;
            color: #569cd6;
        }

        .song-title {
            text-align: center;
            font-weight: 600;
            color: #d4d4d4;
        }

        .judge-score {
            text-align: right;
            font-weight: bold;
            font-size: 18px;
        }

        .judge-score.low {
            color: #f48771;
        }

        .judge-score.high {
            color: #6a9955;
        }

        .judge-score.perfect {
            color: #d7ba7d;
        }

        .review-body {
            padding: 20px;
            background: #252526;
            min-height: 80px;
            line-height: 1.6;
            color: #d4d4d4;
            text-align: center;
        }

        .no-results {
            text-align: center;
            padding: 40px;
            color: #a0a0a0;
            font-size: 18px;
            background: #252526;
            border-radius: 8px;
            margin: 40px auto;
            max-width: 600px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.8);
            border: 2px solid #3e3e42;
        }

        .announcement {
            text-align: center;
            padding: 60px 40px;
            background: #2d2d30;
            border-radius: 8px;
            margin: 40px auto;
            max-width: 800px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.8);
            border-left: 6px solid #f48771;
            border: 2px solid #3e3e42;
        }

        .announcement-icon {
            font-size: 64px;
            margin-bottom: 20px;
            color: #d7ba7d;
        }

        .announcement h2 {
            color: #f48771;
            margin-bottom: 20px;
            font-size: 28px;
            font-weight: 600;
        }

        .announcement p {
            color: #d4d4d4;
            font-size: 18px;
            line-height: 1.6;
            margin-bottom: 10px;
        }

        .bg-upload-section {
            background: #2d2d30;
            padding: 30px;
            border-radius: 8px;
            margin-bottom: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.8);
            border: 2px solid #3e3e42;
        }

        .bg-upload-section h3 {
            color: #4ec9b0;
            margin-bottom: 20px;
            font-size: 20px;
            font-weight: bold;
        }

        .bg-upload-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .bg-upload-item {
            border: 2px dashed #3e3e42;
            border-radius: 4px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s;
            background: #1e1e1e;
        }

        .bg-upload-item:hover {
            border-color: #569cd6;
            background: #252526;
        }

        .bg-upload-item label {
            display: block;
            font-weight: 600;
            margin-bottom: 10px;
            color: #d4d4d4;
        }

        .staff-tools-button {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            border-radius: 4px;
            background: linear-gradient(135deg, #0e639c, #1177bb);
            color: white;
            border: 2px solid #569cd6;
            font-size: 24px;
            cursor: pointer;
            box-shadow: 0 4px 20px rgba(14, 99, 156, 0.6);
            transition: all 0.3s;
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .staff-tools-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 30px rgba(14, 99, 156, 0.8);
            border-color: #4ec9b0;
        }

        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.9);
            z-index: 9998;
        }

        .modal-overlay.active {
            display: block;
        }

        .bg-upload-modal {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 90%;
            max-width: 900px;
            max-height: 90vh;
            overflow-y: auto;
            background: #2d2d30;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 10px 50px rgba(0, 0, 0, 0.9);
            border: 2px solid #3e3e42;
            z-index: 9999;
        }

        .bg-upload-modal.active {
            display: block;
            animation: modalSlideIn 0.3s ease-out;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translate(-50%, -45%);
            }
            to {
                opacity: 1;
                transform: translate(-50%, -50%);
            }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #3e3e42;
        }

        .modal-header h3 {
            color: #4ec9b0;
            font-size: 24px;
            margin: 0;
            font-weight: 600;
        }

        .modal-close {
            background: #7a2828;
            color: white;
            border: 2px solid #c85050;
            width: 35px;
            height: 35px;
            border-radius: 4px;
            font-size: 20px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-close:hover {
            background: #a03535;
            transform: scale(1.1);
        }

        .debug-info-box {
            background: #1e1e1e;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-family: monospace;
            font-size: 12px;
            color: #d4d4d4;
            border: 2px solid #3e3e42;
        }

        .debug-info-box strong {
            color: #569cd6;
        }

        .bg-upload-item input[type="file"] {
            display: block;
            width: 100%;
            padding: 8px;
            font-size: 12px;
            background: #252526;
            color: #d4d4d4;
            border: 2px solid #3e3e42;
            border-radius: 4px;
        }

        .bg-upload-item button {
            margin-top: 10px;
            padding: 8px 16px;
            background: #0e639c;
            color: white;
            border: 2px solid #569cd6;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
        }

        .bg-upload-item button:hover {
            background: #1177bb;
            border-color: #4ec9b0;
        }

        .bg-preview {
            margin-top: 10px;
            max-width: 100%;
            max-height: 100px;
            border-radius: 4px;
            border: 2px solid #3e3e42;
        }

        .access-denied {
            text-align: center;
            padding: 60px 40px;
            background: #2d2d30;
            border-radius: 8px;
            margin: 40px auto;
            max-width: 600px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.8);
            border-left: 6px solid #f48771;
            border: 2px solid #3e3e42;
        }

        .access-denied-icon {
            font-size: 64px;
            margin-bottom: 20px;
            color: #f48771;
        }

        .access-denied h2 {
            color: #f48771;
            margin-bottom: 20px;
            font-size: 28px;
            font-weight: 600;
        }

        .access-denied p {
            color: #d4d4d4;
            font-size: 18px;
        }
    </style>

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

    // Get round information
    $roundInfo = $seasonId ? DB::table('round')
        ->where('round_number', $round)
        ->where('season_id', $seasonId)
        ->first() : null;

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
        ->where('md_group', $group)
        ->where('round', $round)
        ->where('season_id', $seasonId)
        ->whereNull('score')
        ->exists() : false;

    $contestants = $seasonId ? DB::table('contestants')
        ->join('users', 'users.id', '=', 'contestants.id')
        ->where('contestants.md_group', $group)
        ->where('contestants.season_id', $seasonId)
        ->where('contestants.eliminated', false)
        ->get() : collect();

    // Count contestants with null submission_date and not eliminated
    $missedSubmissions = $seasonId ? DB::table('contestants')
        ->where('md_group', $group)
        ->where('season_id', $seasonId)
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
        ->join('judges', function ($join) use ($round, $group, $seasonId) {
            $join->on('judges.id', '=', 'submissions.judge_id')
                 ->where('judges.round', '=', $round)
                 ->where('judges.md_group', '=', $group)
                 ->where('judges.season_id', '=', $seasonId);
        })
        ->join('users', 'users.id', '=', 'judges.id')
        ->select('submissions.*', 'judges.judge_number', 'users.global_name', 'users.username')
        ->where('submissions.contestant_id', $contestant->id)
        ->where('submissions.round', $round)
        ->where('submissions.md_group', $group)
        ->where('submissions.season_id', $seasonId)
        ->orderBy('judges.judge_number')
        ->get() : collect();

        foreach ($subs as $sub) {
            $song = $sub->artist . ' - ' . $sub->title;
            $scores[$i] = $song;
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
            return "background: url('{$url}') center/cover no-repeat;";
        }
        if ($rank == 2 && !empty($roundInfo->slidebg_second)) {
            $url = $getUrl($roundInfo->slidebg_second);
            return "background: url('{$url}') center/cover no-repeat;";
        }
        if ($rank == 3 && !empty($roundInfo->slidebg_third)) {
            $url = $getUrl($roundInfo->slidebg_third);
            return "background: url('{$url}') center/cover no-repeat;";
        }
        
        // Check if eliminated (lower priority than podium)
        if ($rank > $total - $elimThreshold && !empty($roundInfo->slidebg_elim)) {
            $url = $getUrl($roundInfo->slidebg_elim);
            return "background: url('{$url}') center/cover no-repeat;";
        }
        
        // Normal background
        if (!empty($roundInfo->slidebg_normal)) {
            $url = $getUrl($roundInfo->slidebg_normal);
            return "background: url('{$url}') center/cover no-repeat;";
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
                                <div class="judge-name">{{ $table[2] }}</div>
                                <div class="song-title">{{ $table[1] }}</div>
                                <div class="judge-score @if($table[4] == 10) perfect @elseif($table[4] >= 9) high @elseif($table[4] < 4) low @endif">{{ $table[4] }}</div>
                            </div>
                            <div class="review-body">{{ $table[3] }}</div>
                        </div>

                        <div class="judge-block">
                            <div class="judge-header">
                                <div class="judge-name">{{ $table[6] }}</div>
                                <div class="song-title">{{ $table[5] }}</div>
                                <div class="judge-score @if($table[8] == 10) perfect @elseif($table[8] >= 9) high @elseif($table[8] < 4) low @endif">{{ $table[8] }}</div>
                            </div>
                            <div class="review-body">{{ $table[7] }}</div>
                        </div>

                        <div class="judge-block">
                            <div class="judge-header">
                                <div class="judge-name">{{ $table[10] }}</div>
                                <div class="song-title">{{ $table[9] }}</div>
                                <div class="judge-score @if($table[12] == 10) perfect @elseif($table[12] >= 9) high @elseif($table[12] < 4) low @endif">{{ $table[12] }}</div>
                            </div>
                            <div class="review-body">{{ $table[11] }}</div>
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