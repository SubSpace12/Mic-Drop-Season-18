<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Judge Submissions - Group {{ request()->query('group', 0) == 0 ? 'Merge' : request()->query('group', 0) }} -
            Round {{ request()->query('round', -1) }}
        </h2>
    </x-slot>

    <style>
        .judge-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        .access-message {
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            margin: 2rem auto;
            max-width: 600px;
        }

        .access-message.error {
            background: #fee;
            border: 2px solid #fcc;
            color: #c33;
        }

        .access-message.success {
            background: #efe;
            border: 2px solid #cfc;
            color: #3c3;
        }

        .info-section {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .info-section p {
            margin: 0.5rem 0;
            color: #555;
            font-size: 1rem;
        }

        .info-section p:first-child {
            font-weight: bold;
            color: #333;
            font-size: 1.1rem;
        }

        .tab-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .tab-buttons {
            display: flex;
            gap: 0;
            background: #f8f9fa;
            border-bottom: 2px solid #e0e0e0;
            overflow-x: auto;
            padding: 0;
        }

        .tab-button {
            padding: 1rem 1.5rem;
            background-color: transparent;
            border: none;
            border-bottom: 3px solid transparent;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
            color: #666;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .tab-button:hover {
            background-color: #e9ecef;
            color: #333;
        }

        .tab-button.active {
            background-color: white;
            color: #007bff;
            border-bottom-color: #007bff;
        }

        .tab-content {
            display: none;
            padding: 2rem;
        }

        .tab-content.active {
            display: block;
        }

        .tab-content h3 {
            margin-bottom: 1.5rem;
            color: #333;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .submissions-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 0.75rem;
        }

        .submissions-table tr {
            background: #f8f9fa;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .submissions-table tr:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .submissions-table td {
            vertical-align: top;
            padding: 1rem;
            border: none;
        }

        .submissions-table td:first-child {
            border-radius: 8px 0 0 8px;
            width: 35%;
        }

        .submissions-table td:last-child {
            border-radius: 0 8px 8px 0;
        }

        .song-link {
            text-decoration: none;
            color: #007bff;
            font-weight: 600;
            font-size: 1.05rem;
            transition: color 0.2s ease;
        }

        .song-link:hover {
            color: #0056b3;
            text-decoration: underline;
        }

        .score-cell {
            width: 10%;
        }

        .score-input {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #ccc;
            border-radius: 8px;
            text-align: center;
            font-weight: bold;
            font-size: 1.25rem;
            transition: all 0.3s ease;
            box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .score-input:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
        }

        .review-textarea {
            width: 100%;
            min-height: 80px;
            padding: 0.75rem;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 0.95rem;
            line-height: 1.5;
            resize: none;
            transition: border-color 0.3s ease;
        }

        .review-textarea:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .summary-table th {
            background: #343a40;
            color: white;
            padding: 1rem;
            text-align: center;
            font-weight: 600;
            border-bottom: 2px solid #23272b;
        }

        .summary-table td {
            padding: 1rem;
            text-align: center;
            border-bottom: 1px solid #e9ecef;
            font-weight: 600;
            height: 60px;
            font-size: 1.1rem;
        }

        .summary-table tr:last-child td {
            border-bottom: none;
        }

        .summary-table tr:hover {
            background: #f8f9fa;
        }

        .score-input-sum {
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .no-submissions {
            text-align: center;
            color: #999;
            font-style: italic;
            padding: 3rem;
            background: #f8f9fa;
            border-radius: 8px;
            border: 2px dashed #ddd;
        }

        .auth-buttons {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: #007bff;
            color: white;
        }

        .btn-primary:hover {
            background: #0056b3;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 123, 255, 0.3);
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(220, 53, 69, 0.3);
        }

        .btn-edit {
            padding: 0.5rem 1rem;
            background: #6c757d;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-edit:hover {
            background: #5a6268;
            transform: translateY(-1px);
        }

        .edit-actions-cell {
            width: 8%;
            text-align: center;
        }

        .valid-checkbox-cell {
            width: 5%;
            text-align: center;
        }

        .valid-checkbox {
            width: 24px;
            height: 24px;
            cursor: pointer;
            accent-color: #28a745;
        }

        /* Modal Styles */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.6);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e9ecef;
        }

        .modal-header h3 {
            margin: 0;
            color: #333;
            font-size: 1.5rem;
        }

        .modal-body {
            margin-bottom: 1.5rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #555;
            font-weight: 600;
        }

        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
        }

        .form-group input:disabled {
            background-color: #f8f9fa;
            cursor: not-allowed;
        }

        .modal-footer {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            padding-top: 1rem;
            border-top: 2px solid #e9ecef;
        }

        .btn-cancel {
            background: #6c757d;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .btn-cancel:hover {
            background: #5a6268;
        }

        .btn-save {
            background: #28a745;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .btn-save:hover {
            background: #218838;
        }

        .btn-mark-resub {
            background: #ffc107;
            color: #333;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .btn-mark-resub:hover {
            background: #e0a800;
        }

        .submission-locked {
            opacity: 0.6;
            position: relative;
        }

        .submission-locked::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 193, 7, 0.15);
            backdrop-filter: blur(2px);
            pointer-events: auto;
            cursor: not-allowed;
            z-index: 10;
            border-radius: 8px;
        }

        .submission-locked *:not(.btn-edit):not(.edit-actions-cell):not(.valid-checkbox):not(.valid-checkbox-cell) {
            pointer-events: none;
        }

        .submission-locked .edit-actions-cell,
        .submission-locked .valid-checkbox-cell {
            position: relative;
            z-index: 12;
        }

        .submission-locked .btn-edit,
        .submission-locked .valid-checkbox {
            pointer-events: auto;
            z-index: 12;
            position: relative;
        }

        .locked-badge {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: #ffc107;
            color: #333;
            padding: 0.5rem 1.5rem;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: bold;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
            z-index: 11;
            pointer-events: none;
            white-space: nowrap;
        }

        .contestant-name-display {
            color: #6c757d;
            font-size: 0.9rem;
            font-style: italic;
            margin-top: 0.5rem;
        }
    </style>

    <div class="judge-container">
        @guest
            <div class="access-message">
                <p style="font-size: 1.5rem; margin-bottom: 1rem;">🔒 Not Logged In</p>
                <p>Please log in to access the judge submissions.</p>
            </div>
        @endguest

        @auth
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
                $group_raw = $group;
                switch ($group) {
                    case 0:
                        $group = 2;
                        break;
                    case 1:
                        $group = 3;
                        break;
                    case 2:
                        $group = 4;
                        break;
                    case 3:
                        $group = 5;
                        break;
                    default:
                        break;
                }
                $judges = $seasonId ? DB::table('judges')->join('users', 'users.id', '=', 'judges.id')
                    ->select('users.global_name', 'users.id')
                    ->where('md_group', $group_raw)->where('round', $round)
                    ->where('judges.season_id', $seasonId)
                    ->orderBy('judge_number')->get() : collect();
                $contestants = $seasonId ? DB::table('contestants')
                    ->where('md_group', $group_raw)
                    ->where('season_id', $seasonId)
                    ->where('eliminated', false)->orderBy('submission_date', 'asc')
                    ->get() : collect();
                $subsTable = [];
                foreach ($contestants as $contestant) {
                    $scores = [];
                    $validScores = [];
                    $avg = 0.0;

                    $subs = $seasonId ? DB::table('submissions')
                        ->join('judges', function ($join) use ($round, $group_raw, $seasonId) {
                            $join->on('judges.id', '=', 'submissions.judge_id')
                                ->where('judges.round', '=', $round)
                                ->where('judges.md_group', '=', $group_raw)
                                ->where('judges.season_id', '=', $seasonId);
                        })
                        ->where('submissions.contestant_id', $contestant->id)
                        ->where('submissions.round', $round)
                        ->where('submissions.md_group', $group_raw)
                        ->where('submissions.season_id', $seasonId)
                        ->orderBy('judges.judge_number')
                        ->get() : collect();
                    // Collect scores from each judge
                    foreach ($subs as $sub) {
                        if ($sub->score !== null) {
                            $scores[] = $sub->score;  // Add to main scores array
                            $validScores[] = $sub->score;  // Also track for calculations
                            $avg += $sub->score;
                        } else {
                            $scores[] = null;
                        }
                    }

                    // Calculate average
                    $validCount = count($validScores);
                    if ($validCount > 0) {
                        $avg = $avg / $validCount;
                    } else {
                        $avg = null;
                    }

                    // Calculate standard deviation
                    $stddev = null;
                    if ($validCount > 1) {
                        $variance = 0;
                        foreach ($validScores as $score) {
                            $variance += ($score - $avg) ** 2;
                        }
                        $stddev = sqrt($variance / ($validCount - 1));
                    }

                    // Add average and std dev to the end
                    $scores[] = $avg !== null ? round($avg, 2) : null;
                    $scores[] = $stddev !== null ? round($stddev, 3) : null;

                    $subsTable[$contestant->id] = $scores;
                }

                // Get submissions for each judge
                $judgeSubmissions = [];
                foreach ($judges as $judge) {
                    $submissions = $seasonId ? DB::table('submissions')
                        ->join('contestants', 'contestants.id', '=', 'submissions.contestant_id')
                        ->join('users', 'users.id', '=', 'contestants.id')
                        ->select('submissions.*', 'contestants.submission_date', 'users.global_name as contestant_name')
                        ->where('judge_id', $judge->id)
                        ->where('round', $round)
                        ->where('submissions.md_group', $group_raw)
                        ->where('submissions.season_id', $seasonId)
                        ->orderBy('submission_date', 'asc')
                        ->get() : collect();
                    $judgeSubmissions[$judge->id] = $submissions;
                }
            @endphp

            @if($group == -1 || $round == -1)
                <div class="access-message error">
                    <p style="font-size: 1.5rem; margin-bottom: 1rem;">⚠️ Invalid Selection</p>
                    <p>The group or round parameters are invalid. Please check the URL and try again.</p>
                </div>
            @elseif(auth()->user()->perms == $group || auth()->user()->perms == 6 || auth()->user()->perms == 7)
                @if(count($judges) > 0)
                    <div class="tab-container">
                        <!-- Tab Buttons -->
                        <div class="tab-buttons">
                            <button class="tab-button active" onclick="switchTab(0, 0); loadSummary(); window.location.reload();">
                                📊 Summary
                            </button>
                            @foreach($judges as $index => $judge)
                                @php
                                    $index = $index + 1; 
                                @endphp
                                <button class="tab-button" onclick="switchTab({{ $index }}, '{{ $judge->id }}')">
                                    {{ $judge->global_name }}
                                </button>
                            @endforeach
                        </div>

                        <!-- Summary Tab -->
                        <div class="tab-content active" id="tab-0">
                            <h3>📊 Summary Overview</h3>
                            <table class="summary-table">
                                <thead>
                                    <tr>
                                        @foreach($judges as $judge)
                                            <th>{{ $judge->global_name }}</th>
                                        @endforeach
                                        <th>Average</th>
                                        <th>Std. Dev</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($subsTable as $sub)
                                        <tr>
                                            @foreach($sub as $data)
                                                <td class="score-input-sum">
                                                    @if($data !== null)
                                                        {{ $data }}
                                                    @endif
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Judge Tabs -->
                        @foreach($judges as $index => $judge)
                            <div class="tab-content" id="tab-{{ $judge->id }}">
                                <h3>🎵 Submissions for {{ $judge->global_name }}</h3>

                                @if(count($judgeSubmissions[$judge->id]) > 0)
                                    <table class="submissions-table">
                                        @foreach($judgeSubmissions[$judge->id] as $submission)
                                            <tr class="{{ $submission->marked_for_resub ? 'submission-locked' : '' }}">
                                                <td>
                                                    @if($submission->marked_for_resub)
                                                        <span class="locked-badge">⚠️ Marked for resubmission</span>
                                                    @endif
                                                    <a href="{{ $submission->url }}" target="_blank" class="song-link">
                                                        🎵 {{ $submission->artist }} - {{ $submission->title }}
                                                    </a>
                                                </td>
                                                <td class="score-cell">
                                                    <input type="number" min="0" max="10" step="0.25"
                                                        value="{{ $submission->score !== null ? $submission->score : '' }}" autocomplete="off"
                                                        class="score-input" data-id="{{ $submission->submission_id }}"
                                                        onblur="validateAndColorScore(this); updateScore(this);"
                                                        oninput="validateAndColorScore(this); updateScore(this);">
                                                </td>
                                                <td>
                                                    <textarea class="review-textarea" data-id="{{ $submission->submission_id }}"
                                                        oninput="autoExpand(this); updateReview(this);"
                                                        placeholder="Write your review here...">{{ $submission->review }}</textarea>
                                                </td>
                                                @if(auth()->user()->perms >= 6)
                                                    <td class="valid-checkbox-cell">
                                                        <input type="checkbox" 
                                                            class="valid-checkbox" 
                                                            data-id="{{ $submission->submission_id }}"
                                                            {{ ($submission->is_valid ?? false) ? 'checked' : '' }}
                                                            onchange="updateValidStatus(this)"
                                                            title="Mark as valid submission">
                                                    </td>
                                                    <td class="edit-actions-cell">
                                                        <button class="btn-edit"
                                                            onclick="openEditModal({{ $submission->submission_id }}, '{{ addslashes($submission->artist) }}', '{{ addslashes($submission->title) }}', '{{ $submission->url }}', {{ $submission->marked_for_resub ? 'true' : 'false' }}, '{{ addslashes($submission->contestant_name) }}')">
                                                            ✏️ Edit
                                                        </button>
                                                    </td>
                                                @endif
                                            </tr>
                                        @endforeach
                                    </table>
                                @else
                                    <div class="no-submissions">
                                        No submissions found for this judge.
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="access-message">
                        <p style="font-size: 1.5rem; margin-bottom: 1rem;">👨‍⚖️ No Judges Found</p>
                        <p>No judges have been assigned to this group and round yet.</p>
                    </div>
                @endif
            @else
                <div class="access-message error">
                    <p style="font-size: 1.5rem; margin-bottom: 1rem;">🚫 Access Denied</p>
                    <p>You do not have permission to view submissions for this group.</p>
                </div>
            @endif
        @endauth
    </div>

    <!-- Edit Modal -->
    <div class="modal-overlay" id="editModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Submission</h3>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="edit-contestant">Contestant</label>
                    <input type="text" id="edit-contestant" disabled>
                </div>
                <div class="form-group">
                    <label for="edit-artist">Artist</label>
                    <input type="text" id="edit-artist" placeholder="Enter artist name">
                </div>
                <div class="form-group">
                    <label for="edit-title">Title</label>
                    <input type="text" id="edit-title" placeholder="Enter song title">
                </div>
                <div class="form-group">
                    <label for="edit-url">URL</label>
                    <input type="text" id="edit-url" placeholder="Enter song URL">
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-cancel" onclick="closeEditModal()">Cancel</button>
                <button class="btn-mark-resub" id="markResubBtn" onclick="toggleMarkForResub()">Mark for resub</button>
                <button class="btn-save" onclick="saveSubmissionEdit()">Save Changes</button>
            </div>
        </div>
    </div>

    <script>
        let currentEditingSubmissionId = null;
        let currentMarkedForResub = false;

        function autoExpand(textarea) {
            textarea.style.height = 'auto';
            textarea.style.height = (textarea.scrollHeight) + 'px';
        }

        function switchTab(tabIndex, judgeId) {
            const tabContents = document.querySelectorAll('.tab-content');
            tabContents.forEach(content => content.classList.remove('active'));

            const tabButtons = document.querySelectorAll('.tab-button');
            tabButtons.forEach(button => button.classList.remove('active'));

            document.getElementById('tab-' + judgeId).classList.add('active');
            tabButtons[tabIndex].classList.add('active');

            document.querySelectorAll('textarea').forEach(function (textarea) {
                autoExpand(textarea);
            });
        }

        function validateAndColorScore(input) {
            let value = input.value.trim();

            // Handle empty values
            if (value === '' || value === null || value === undefined) {
                input.value = '';
                input.style.borderColor = '#ccc';
                input.style.backgroundColor = 'white';
                return;
            }

            value = parseFloat(value);

            if (isNaN(value) || value < 0 || value > 10) {
                input.style.borderColor = '#dc3545';
                input.style.backgroundColor = '#ffebee';
                return;
            }

            let roundedValue = Math.round(value * 4) / 4;
            input.value = roundedValue;

            let ratio = roundedValue / 10;
            let red, green, blue;

            if (ratio <= 0.5) {
                red = 255;
                green = Math.round(255 * (ratio * 2));
                blue = 0;
            } else {
                red = Math.round(255 * (2 - ratio * 2));
                green = 255;
                blue = 0;
            }

            input.style.backgroundColor = `rgb(${red}, ${green}, ${blue})`;
            input.style.borderColor = `rgb(${Math.max(red - 50, 0)}, ${Math.max(green - 50, 0)}, ${blue})`;
        }

        function validateAndColorScoreSummary(input) {
            let value = input.textContent.trim();

            // Handle empty cells
            if (value === '' || value === null || value === undefined) {
                input.style.borderColor = '';
                input.style.backgroundColor = '';
                return;
            }

            value = parseFloat(value);

            if (isNaN(value) || value < 0 || value > 10) {
                input.style.borderColor = '#dc3545';
                input.style.backgroundColor = '#ffebee';
                return;
            }

            let ratio = value / 10;
            let red, green, blue;

            if (ratio <= 0.5) {
                red = 255;
                green = Math.round(255 * (ratio * 2));
                blue = 0;
            } else {
                red = Math.round(255 * (2 - ratio * 2));
                green = 255;
                blue = 0;
            }

            input.style.backgroundColor = `rgb(${red}, ${green}, ${blue})`;
            input.style.borderColor = `rgb(${Math.max(red - 50, 0)}, ${Math.max(green - 50, 0)}, ${blue})`;
        }

        function updateScore(el) {
            let id = el.dataset.id;
            let score = el.value;

            // Convert empty string to null for database
            if (score === '' || score === null || score === undefined) {
                score = null;
            }

            fetch('/update-submission', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ submission_id: id, score: score })
            });
        }

        function updateReview(el) {
            let id = el.dataset.id;
            let review = el.value;

            fetch('/update-submission', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ submission_id: id, review: review })
            });
        }

        function updateValidStatus(checkbox) {
            let id = checkbox.dataset.id;
            let isValid = checkbox.checked;

            fetch('/update-submission', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ submission_id: id, is_valid: isValid })
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    console.error('Failed to update valid status');
                    // Revert checkbox on failure
                    checkbox.checked = !isValid;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                // Revert checkbox on error
                checkbox.checked = !isValid;
            });
        }

        function openEditModal(submissionId, artist, title, url, markedForResub, contestantName) {
            currentEditingSubmissionId = submissionId;
            currentMarkedForResub = markedForResub;

            document.getElementById('edit-contestant').value = contestantName;
            document.getElementById('edit-artist').value = artist;
            document.getElementById('edit-title').value = title;
            document.getElementById('edit-url').value = url;

            updateMarkResubButton();

            document.getElementById('editModal').classList.add('active');
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.remove('active');
            currentEditingSubmissionId = null;
        }

        function updateMarkResubButton() {
            const btn = document.getElementById('markResubBtn');
            if (currentMarkedForResub) {
                btn.textContent = 'Unmark for resub';
                btn.style.background = '#28a745';
                btn.style.color = 'white';
            } else {
                btn.textContent = 'Mark for resub';
                btn.style.background = '#ffc107';
                btn.style.color = '#333';
            }
        }

        function toggleMarkForResub() {
            currentMarkedForResub = !currentMarkedForResub;
            updateMarkResubButton();
        }

        function saveSubmissionEdit() {
            if (!currentEditingSubmissionId) return;

            const artist = document.getElementById('edit-artist').value;
            const title = document.getElementById('edit-title').value;
            const url = document.getElementById('edit-url').value;

            fetch('/update-submission-details', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    submission_id: currentEditingSubmissionId,
                    artist: artist,
                    title: title,
                    url: url,
                    marked_for_resub: currentMarkedForResub
                })
            })
                .then(response => {
                    if (!response.ok) {
                        return response.text().then(text => {
                            console.error('Server response:', text);
                            throw new Error(`Server error: ${response.status}`);
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        closeEditModal();
                        location.reload();
                    } else {
                        alert('Error updating submission: ' + (data.message || 'Unknown error'));
                        console.error('Update failed:', data);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error updating submission: ' + error.message + '\n\nCheck the browser console for details.');
                });
        }

        // Close modal when clicking outside
        document.getElementById('editModal').addEventListener('click', function (e) {
            if (e.target === this) {
                closeEditModal();
            }
        });

        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.score-input').forEach(function (input) {
                validateAndColorScore(input);
            });
            document.querySelectorAll('.score-input-sum').forEach(function (input) {
                validateAndColorScoreSummary(input);
            });
        });

        window.addEventListener('load', function () {
            document.querySelectorAll('textarea').forEach(function (textarea) {
                autoExpand(textarea);
            });
        });

        function loadSummary() {
            document.querySelectorAll('.score-input-sum').forEach(function (input) {
                validateAndColorScoreSummary(input);
            });
        }
    </script>
</x-app-layout>