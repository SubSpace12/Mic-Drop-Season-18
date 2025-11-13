<x-app-layout>

    <style>
        .results-container {
            display: flex;
            height: calc(100vh - 64px); /* Subtract header height */
            overflow: hidden;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
        }

        .sidebar {
            width: 280px;
            background: rgba(44, 62, 80, 0.9);
            backdrop-filter: blur(10px);
            color: white;
            overflow-y: auto;
            box-shadow: 2px 0 10px rgba(0,0,0,0.3);
        }

        .sidebar h2 {
            padding: 20px;
            background: rgba(26, 37, 47, 0.9);
            font-size: 18px;
            border-bottom: 1px solid rgba(52, 73, 94, 0.5);
        }

        .slide-nav {
            list-style: none;
        }

        .slide-nav li {
            padding: 15px 20px;
            cursor: pointer;
            border-bottom: 1px solid rgba(52, 73, 94, 0.5);
            transition: background 0.2s;
        }

        .slide-nav li:hover {
            background: rgba(52, 73, 94, 0.7);
        }

        .slide-nav li.active {
            background: rgba(52, 152, 219, 0.8);
            border-left: 4px solid #2980b9;
        }

        .main-content {
            flex: 1;
            overflow-y: auto;
            padding: 40px;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
        }

        .result-slide {
            display: none;
            max-width: 1200px;
            margin: 0 auto;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 20px rgba(0,0,0,0.3);
            position: relative;
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
            border-bottom: 2px solid rgba(255, 255, 255, 0.3);
        }

        .rank-box, .score-box {
            background: rgba(52, 73, 94, 0.85);
            backdrop-filter: blur(10px);
            color: white;
            padding: 15px 25px;
            border-radius: 12px;
            font-size: 24px;
            font-weight: bold;
            text-align: center;
            min-width: 80px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
        }

        .rank-box.gold {
            background: linear-gradient(135deg, rgba(255, 215, 0, 0.9), rgba(255, 165, 0, 0.9));
            backdrop-filter: blur(10px);
            color: #000;
            text-shadow: 1px 1px 2px rgba(255,255,255,0.5);
            box-shadow: 0 4px 15px rgba(255, 215, 0, 0.6);
        }

        .rank-box.silver {
            background: linear-gradient(135deg, rgba(192, 192, 192, 0.9), rgba(168, 168, 168, 0.9));
            backdrop-filter: blur(10px);
            color: #000;
            text-shadow: 1px 1px 2px rgba(255,255,255,0.5);
            box-shadow: 0 4px 15px rgba(192, 192, 192, 0.6);
        }

        .rank-box.bronze {
            background: linear-gradient(135deg, rgba(205, 127, 50, 0.9), rgba(139, 69, 19, 0.9));
            backdrop-filter: blur(10px);
            color: #fff;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
            box-shadow: 0 4px 15px rgba(205, 127, 50, 0.6);
        }

        .rank-box.eliminated {
            background: linear-gradient(135deg, rgba(231, 76, 60, 0.9), rgba(192, 57, 43, 0.9));
            backdrop-filter: blur(10px);
            color: #fff;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
            box-shadow: 0 4px 15px rgba(231, 76, 60, 0.8);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .score-box {
            background: rgba(46, 204, 113, 0.85);
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 15px rgba(46, 204, 113, 0.4);
        }

        .contestant-name {
            font-size: 28px;
            font-weight: bold;
            text-align: center;
            color: white;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.8);
            background: rgba(0, 0, 0, 0.4);
            padding: 10px 20px;
            border-radius: 8px;
            backdrop-filter: blur(5px);
        }

        .judge-block {
            margin-bottom: 20px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            overflow: hidden;
            background: rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
        }

        .judge-header {
            display: grid;
            grid-template-columns: 1fr 2fr 1fr;
            background: rgba(52, 73, 94, 0.85);
            backdrop-filter: blur(10px);
            color: white;
            padding: 12px 20px;
            font-size: 14px;
            align-items: center;
        }

        .judge-name {
            text-align: left;
            font-weight: 600;
        }

        .song-title {
            text-align: center;
            font-weight: 600;
        }

        .judge-score {
            text-align: right;
            font-weight: bold;
            font-size: 18px;
        }

        .judge-score.low {
            color: #e74c3c;
        }

        .judge-score.high {
            color: #2ecc71;
        }

        .judge-score.perfect {
            color: #f39c12;
            text-shadow: 0 0 10px rgba(243, 156, 18, 0.8);
        }

        .review-body {
            padding: 20px;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
            min-height: 80px;
            line-height: 1.6;
            color: white;
            text-align: center;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.8);
        }

        .no-results {
            text-align: center;
            padding: 40px;
            color: white;
            font-size: 18px;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            margin: 40px auto;
            max-width: 600px;
            box-shadow: 0 2px 20px rgba(0,0,0,0.5);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .announcement {
            text-align: center;
            padding: 60px 40px;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            margin: 40px auto;
            max-width: 800px;
            box-shadow: 0 2px 20px rgba(0,0,0,0.5);
            border-left: 6px solid #e74c3c;
        }

        .announcement-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }

        .announcement h2 {
            color: #e74c3c;
            margin-bottom: 20px;
            font-size: 28px;
        }

        .announcement p {
            color: #ecf0f1;
            font-size: 18px;
            line-height: 1.6;
            margin-bottom: 10px;
        }

        .bg-upload-section {
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(10px);
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 2px 20px rgba(0,0,0,0.5);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .bg-upload-section h3 {
            color: white;
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
            border: 2px dashed rgba(255, 255, 255, 0.3);
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s;
            background: rgba(0, 0, 0, 0.3);
        }

        .bg-upload-item:hover {
            border-color: #3498db;
            background: rgba(52, 152, 219, 0.2);
        }

        .bg-upload-item label {
            display: block;
            font-weight: 600;
            margin-bottom: 10px;
            color: white;
        }

        /* Floating Staff Button */
        .staff-tools-button {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(52, 152, 219, 0.9), rgba(41, 128, 185, 0.9));
            backdrop-filter: blur(10px);
            color: white;
            border: none;
            font-size: 24px;
            cursor: pointer;
            box-shadow: 0 4px 20px rgba(52, 152, 219, 0.6);
            transition: all 0.3s;
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .staff-tools-button:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 30px rgba(52, 152, 219, 0.8);
        }

        /* Modal Overlay */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(5px);
            z-index: 9998;
        }

        .modal-overlay.active {
            display: block;
        }

        /* Modal Container */
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
            background: rgba(26, 37, 47, 0.95);
            backdrop-filter: blur(15px);
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 10px 50px rgba(0, 0, 0, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.2);
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
            border-bottom: 2px solid rgba(255, 255, 255, 0.2);
        }

        .modal-header h3 {
            color: white;
            font-size: 24px;
            margin: 0;
        }

        .modal-close {
            background: rgba(231, 76, 60, 0.8);
            color: white;
            border: none;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            font-size: 20px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-close:hover {
            background: rgba(231, 76, 60, 1);
            transform: rotate(90deg);
        }

        .debug-info-box {
            background: rgba(0, 0, 0, 0.4);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-family: monospace;
            font-size: 12px;
            color: #ecf0f1;
        }

        .debug-info-box strong {
            color: #3498db;
        }

        .bg-upload-item input[type="file"] {
            display: block;
            width: 100%;
            padding: 8px;
            font-size: 12px;
        }

        .bg-upload-item button {
            margin-top: 10px;
            padding: 8px 16px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
        }

        .bg-upload-item button:hover {
            background: #2980b9;
        }

        .bg-preview {
            margin-top: 10px;
            max-width: 100%;
            max-height: 100px;
            border-radius: 4px;
        }

        .access-denied {
            text-align: center;
            padding: 60px 40px;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            margin: 40px auto;
            max-width: 600px;
            box-shadow: 0 2px 20px rgba(0,0,0,0.5);
            border-left: 6px solid #e74c3c;
        }

        .access-denied-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }

        .access-denied h2 {
            color: #e74c3c;
            margin-bottom: 20px;
            font-size: 28px;
        }

        .access-denied p {
            color: #ecf0f1;
            font-size: 18px;
        }
    </style>

    @php
    $group = request()->query('group', 0);
    $round = request()->query('round', -1);
    if ($group < 0 || $group > 3) {
        $group = -1;
    }

    // Get round information
    $roundInfo = DB::table('round')
        ->where('round_number', $round)
        ->first();

    // Check permissions
    $userPerms = auth()->user()->perms ?? 0;
    $isStaff = $userPerms >= 6;
    $canViewResults = $isStaff || ($roundInfo && $roundInfo->status == 2);

    if (!$canViewResults) {
        // Show access denied message
        echo '<div class="main-content" style="flex: 1; padding: 40px;">
            <div class="access-denied">
                <div class="access-denied-icon">🔒</div>
                <h2>Access Denied</h2>
                <p>Results for this round are not yet available.</p>
                <p>Please wait until the results are officially released.</p>
            </div>
        </div>';
        exit;
    }

    // Check if there are any submissions with NULL scores for this group and round
    $hasNullScores = DB::table('submissions')
        ->where('md_group', $group)
        ->where('round', $round)
        ->whereNull('score')
        ->exists();

    $contestants = DB::table('contestants')
        ->join('users', 'users.id', '=', 'contestants.id')
        ->where('contestants.md_group', $group)
        ->where('contestants.eliminated', false)
        ->get();

    // Count contestants with null submission_date and not eliminated
    $missedSubmissions = DB::table('contestants')
        ->where('md_group', $group)
        ->where('eliminated', false)
        ->whereNull('submission_date')
        ->count();

    $eliminateNumber = $roundInfo ? $roundInfo->eliminate_number : 0;
    $eliminationThreshold = max(1, $eliminateNumber - $missedSubmissions);

    $subsTable = [];
    $j = 0;

    foreach ($contestants as $contestant) {
        $i = 1;
        $scores = [];
        $scores[0] = $contestant->global_name;
        $avg = 0.0;

        $subs = DB::table('submissions')
        ->join('judges', function ($join) use ($round, $group) {
            $join->on('judges.id', '=', 'submissions.judge_id')
                 ->where('judges.round', '=', $round)
                 ->where('judges.md_group', '=', $group);
        })
        ->join('users', 'users.id', '=', 'judges.id')
        ->select('submissions.*', 'judges.judge_number', 'users.global_name', 'users.username')
        ->where('submissions.contestant_id', $contestant->id)
        ->where('submissions.round', $round)
        ->where('submissions.md_group', $group)
        ->orderBy('judges.judge_number')
        ->get();

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
            ⚙️
        </button>

        <!-- Modal Overlay -->
        <div class="modal-overlay" id="modalOverlay" onclick="toggleStaffModal()"></div>

        <!-- Background Upload Modal -->
        <div class="bg-upload-modal" id="staffModal">
            <div class="modal-header">
                <h3>🎨 Slide Background Manager</h3>
                <button class="modal-close" onclick="toggleStaffModal()">×</button>
            </div>

            <p style="color: #ecf0f1; margin-bottom: 20px;">Upload background images for different placement slides. Images should be at least 1200x800px for best results.</p>
            
            @if($roundInfo)
                <div class="debug-info-box">
                    <strong>🔍 Debug Info:</strong><br>
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
                        <label>🥇 1st Place</label>
                        <input type="file" name="slidebg_first" accept="image/*">
                        @if($roundInfo && $roundInfo->slidebg_first)
                            <img src="{{ asset('storage/' . $roundInfo->slidebg_first) }}" class="bg-preview" alt="1st place background" onerror="this.style.border='2px solid red'; this.alt='⚠️ Image not found';">
                            <div style="font-size: 10px; color: #7f8c8d; margin-top: 5px;">{{ $roundInfo->slidebg_first }}</div>
                        @endif
                    </div>

                    <div class="bg-upload-item">
                        <label>🥈 2nd Place</label>
                        <input type="file" name="slidebg_second" accept="image/*">
                        @if($roundInfo && $roundInfo->slidebg_second)
                            <img src="{{ asset('storage/' . $roundInfo->slidebg_second) }}" class="bg-preview" alt="2nd place background" onerror="this.style.border='2px solid red'; this.alt='⚠️ Image not found';">
                            <div style="font-size: 10px; color: #7f8c8d; margin-top: 5px;">{{ $roundInfo->slidebg_second }}</div>
                        @endif
                    </div>

                    <div class="bg-upload-item">
                        <label>🥉 3rd Place</label>
                        <input type="file" name="slidebg_third" accept="image/*">
                        @if($roundInfo && $roundInfo->slidebg_third)
                            <img src="{{ asset('storage/' . $roundInfo->slidebg_third) }}" class="bg-preview" alt="3rd place background" onerror="this.style.border='2px solid red'; this.alt='⚠️ Image not found';">
                            <div style="font-size: 10px; color: #7f8c8d; margin-top: 5px;">{{ $roundInfo->slidebg_third }}</div>
                        @endif
                    </div>

                    <div class="bg-upload-item">
                        <label>📄 Normal Slides</label>
                        <input type="file" name="slidebg_normal" accept="image/*">
                        @if($roundInfo && $roundInfo->slidebg_normal)
                            <img src="{{ asset('storage/' . $roundInfo->slidebg_normal) }}" class="bg-preview" alt="Normal background" onerror="this.style.border='2px solid red'; this.alt='⚠️ Image not found';">
                            <div style="font-size: 10px; color: #7f8c8d; margin-top: 5px;">{{ $roundInfo->slidebg_normal }}</div>
                        @endif
                    </div>

                    <div class="bg-upload-item">
                        <label>❌ Eliminated</label>
                        <input type="file" name="slidebg_elim" accept="image/*">
                        @if($roundInfo && $roundInfo->slidebg_elim)
                            <img src="{{ asset('storage/' . $roundInfo->slidebg_elim) }}" class="bg-preview" alt="Eliminated background" onerror="this.style.border='2px solid red'; this.alt='⚠️ Image not found';">
                            <div style="font-size: 10px; color: #7f8c8d; margin-top: 5px;">{{ $roundInfo->slidebg_elim }}</div>
                        @endif
                    </div>
                </div>

                <button type="submit" style="margin-top: 20px; width: 100%; padding: 12px 30px; background: #2ecc71; color: white; border: none; border-radius: 6px; font-size: 16px; font-weight: bold; cursor: pointer; transition: all 0.3s;">
                    💾 Save Backgrounds
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
                <div class="announcement-icon">⏳</div>
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