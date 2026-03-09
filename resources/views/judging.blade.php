<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Judge Submissions - Group {{ request()->query('group', 0) == 0 ? 'Merge' : request()->query('group', 0) }} -
            Round {{ request()->query('round', -1) }}
        </h2>
    </x-slot>

    @vite(['resources/css/judging.css'])

    <div class="judge-container">
        @guest
            <div class="access-message">
                <p style="font-size: 1.5rem; margin-bottom: 1rem;">ACCESS REQUIRED</p>
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
                    ->select(DB::raw('COALESCE(users.global_name, users.username) as global_name'), 'users.id')
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
                        ->where('submissions.draft', false)
                        ->orderBy('judges.judge_number')
                        ->get() : collect();

                    // Skip contestants with no final submissions
                    if ($subs->isEmpty()) {
                        continue;
                    }

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
                        ->select('submissions.*', 'contestants.submission_date', DB::raw('COALESCE(users.global_name, users.username) as contestant_name'))
                        ->where('judge_id', $judge->id)
                        ->where('round', $round)
                        ->where('submissions.md_group', $group_raw)
                        ->where('submissions.season_id', $seasonId)
                        ->where('submissions.draft', false)
                        ->orderBy('submission_date', 'asc')
                        ->get() : collect();
                    $judgeSubmissions[$judge->id] = $submissions;
                }
            @endphp

            @if($group == -1 || $round == -1)
                <div class="access-message error">
                    <p style="font-size: 1.5rem; margin-bottom: 1rem;">INVALID SELECTION</p>
                    <p>The group or round parameters are invalid. Please check the URL and try again.</p>
                </div>
            @elseif(auth()->user()->perms == $group || auth()->user()->perms == 6 || auth()->user()->perms == 7)
                @if(count($judges) > 0)
                    <div class="tab-container">
                        <!-- Tab Buttons -->
                        <div class="tab-buttons">
                            <button class="tab-button active" onclick="switchTab(0, 0); loadSummary(); window.location.reload();">
                                [SUMMARY]
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
                            <h3>[SUMMARY] Overview</h3>
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
                                <h3>Submissions for {{ $judge->global_name }}</h3>

                                @if(count($judgeSubmissions[$judge->id]) > 0)
                                    <table class="submissions-table">
                                        @foreach($judgeSubmissions[$judge->id] as $submission)
                                            <tr class="{{ $submission->marked_for_resub ? 'submission-locked' : '' }}">
                                                <td>
                                                    @if($submission->marked_for_resub)
                                                        <span class="locked-badge">[MARKED FOR RESUBMISSION]</span>
                                                    @endif
                                                    <a href="{{ $submission->url }}" target="_blank" class="song-link">
                                                        > {{ $submission->artist }} - {{ $submission->title }}
                                                    </a>
                                                </td>
                                                <td class="score-cell">
                                                    <input type="number" min="0" max="10" 
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
                                                            [EDIT]
                                                        </button>
                                                    </td>
                                                @endif
                                            </tr>
                                        @endforeach
                                    </table>
                                    @php
                                        $judgeScores = $judgeSubmissions[$judge->id]->pluck('score')->filter(fn($s) => $s !== null);
                                        $judgeAvg = $judgeScores->count() > 0 ? round($judgeScores->avg(), 2) : null;
                                    @endphp
                                    <div class="judge-average" style="text-align: right; padding: 0.75rem 1rem; margin-top: 0.5rem; font-size: 1.1rem; color: #d4d4d4;">
                                        Judging Average: <strong>{{ $judgeAvg !== null ? $judgeAvg : 'N/A' }}</strong>
                                    </div>
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
                        <p style="font-size: 1.5rem; margin-bottom: 1rem;">NO JUDGES FOUND</p>
                        <p>No judges have been assigned to this group and round yet.</p>
                    </div>
                @endif
            @else
                <div class="access-message error">
                    <p style="font-size: 1.5rem; margin-bottom: 1rem;">ACCESS DENIED</p>
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
                input.style.borderColor = '#3e3e42';
                input.style.backgroundColor = '#252526';
                return;
            }

            value = parseFloat(value);

            if (isNaN(value) || value < 0 || value > 10) {
                input.style.borderColor = '#c85050';
                input.style.backgroundColor = '#5a1e1e';
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
            input.style.color = '#000';
        }

        function validateAndColorScoreSummary(input) {
            let value = input.textContent.trim();

            // Handle empty cells
            if (value === '' || value === null || value === undefined) {
                input.style.borderColor = '';
                input.style.backgroundColor = '';
                input.style.color = '#d4d4d4';
                return;
            }

            value = parseFloat(value);

            if (isNaN(value) || value < 0 || value > 10) {
                input.style.borderColor = '#c85050';
                input.style.backgroundColor = '#5a1e1e';
                input.style.color = '#d4d4d4';
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
            input.style.color = '#000';
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
                btn.style.background = 'linear-gradient(135deg, #0e4429, #1e5a3e)';
                btn.style.color = '#d4d4d4';
                btn.style.borderColor = '#4ec9b0';
            } else {
                btn.textContent = 'Mark for resub';
                btn.style.background = '#3e2d1e';
                btn.style.color = '#d7ba7d';
                btn.style.borderColor = '#d7ba7d';
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