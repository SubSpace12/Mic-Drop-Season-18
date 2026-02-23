<x-app-layout>
@php

if (auth()->user()->perms < 6) {
    abort(403, 'You do not have permission to access this page.');
}

// Get the active season
$activeSeason = DB::table('season')
    ->where('active', true)
    ->first();

$seasonId = $activeSeason ? $activeSeason->season_id : null;

$user_apps = DB::table('apps')
    ->join('users', 'apps.user_id', '=', 'users.id')
    ->select('apps.*', 'users.global_name', 'users.perms')->OrderBy('apps.id', 'asc')
    ->get();

$current_user_id = auth()->id();

// Get all votes and comments for the current user
$user_entries = DB::table('judge_upvotes')
    ->where('staff_id', $current_user_id)
    ->get()
    ->keyBy('app_id');

// Get vote counts for each app
$vote_counts = DB::table('judge_upvotes')
    ->select('app_id', 
        DB::raw('SUM(CASE WHEN score = 2 THEN 1 ELSE 0 END) as strong_like'),
        DB::raw('SUM(CASE WHEN score = 1 THEN 1 ELSE 0 END) as thumbs_up'),
        DB::raw('SUM(CASE WHEN score = -1 THEN 1 ELSE 0 END) as thumbs_down'))
    ->groupBy('app_id')
    ->get()
    ->keyBy('app_id');

// Get all comments for all apps
$all_comments = DB::table('judge_upvotes')
    ->join('users', 'judge_upvotes.staff_id', '=', 'users.id')
    ->whereNotNull('judge_upvotes.comment')
    ->where('judge_upvotes.comment', '!=', '')
    ->select(
        'judge_upvotes.app_id',
        'judge_upvotes.comment',
        'judge_upvotes.score',
        'users.global_name',
        'judge_upvotes.staff_id'
    )
    ->orderBy('judge_upvotes.staff_id', 'asc')
    ->get()
    ->groupBy('app_id');

// Get judging history for each user in the active season
$judging_history = [];
if ($seasonId) {
    $judges = DB::table('judges')
        ->where('season_id', $seasonId)
        ->select('id', 'round')
        ->get();
    
    foreach ($judges as $judge) {
        if (!isset($judging_history[$judge->id])) {
            $judging_history[$judge->id] = [];
        }
        $judging_history[$judge->id][] = $judge->round;
    }
    
    // Sort rounds for each judge
    foreach ($judging_history as $userId => $rounds) {
        sort($judging_history[$userId]);
    }
}
@endphp

@vite(['resources/css/view-apps.css'])
<style>
    /* Permission-based color coding */
    .app-nav li.perms-0 .app-nav-name {
        color: #4ade80; /* Green for regular users */
    }
    .app-nav li.perms-1 .app-nav-name {
        color: #f87171; /* Red for contestants */
    }
    .app-nav li.perms-staff .app-nav-name {
        color: #c084fc; /* Purple for staff */
    }
    
    /* Active state overrides */
    .app-nav li.active.perms-0 .app-nav-name {
        color: #22c55e;
    }
    .app-nav li.active.perms-1 .app-nav-name {
        color: #ef4444;
    }
    .app-nav li.active.perms-staff .app-nav-name {
        color: #a855f7;
    }

    /* Filter controls */
    .filter-controls {
        padding: 12px 15px;
        border-bottom: 1px solid #3c3c3c;
        background: #252526;
    }
    .filter-controls label {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
        color: #cccccc;
        cursor: pointer;
    }
    .filter-controls input[type="checkbox"] {
        width: 16px;
        height: 16px;
        cursor: pointer;
        accent-color: #569cd6;
    }
    .filter-legend {
        display: flex;
        gap: 12px;
        margin-top: 10px;
        font-size: 11px;
    }
    .filter-legend span {
        display: flex;
        align-items: center;
        gap: 4px;
    }
    .legend-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        display: inline-block;
    }
    .legend-dot.green { background-color: #4ade80; }
    .legend-dot.red { background-color: #f87171; }
    .legend-dot.purple { background-color: #c084fc; }

    /* Hidden state for filtered items */
    .app-nav li.hidden-by-filter {
        display: none;
    }
</style>

@if (count($user_apps) == 0)
    <div class="apps-container">
        <div class="no-apps">
            <p>No judge applications found.</p>
        </div>
    </div>
@else
    <div class="apps-container">
        <div class="apps-sidebar">
            <h2>Judge Applications</h2>
            
            <!-- Filter Controls -->
            <div class="filter-controls">
                <label>
                    <input type="checkbox" id="hide-contestants" onchange="toggleContestantVisibility()">
                    Hide contestants (red)
                </label>
                <div class="filter-legend">
                    <span><span class="legend-dot green"></span> User</span>
                    <span><span class="legend-dot red"></span> Contestant</span>
                    <span><span class="legend-dot purple"></span> Staff</span>
                </div>
            </div>

            <ul class="app-nav">
                @foreach($user_apps as $index => $app)
                    @php
                        $permsClass = 'perms-0';
                        if ($app->perms == 1) {
                            $permsClass = 'perms-1';
                        } elseif ($app->perms >= 6) {
                            $permsClass = 'perms-staff';
                        }
                    @endphp
                    <li onclick="showApp({{ $index }})" data-app="{{ $index }}" data-perms="{{ $app->perms }}" class="{{ $index === 0 ? 'active' : '' }} {{ $permsClass }}">
                        <div class="app-nav-name">{{ $app->global_name }}</div>
                        <div class="app-nav-votes">
                            <span class="vote-count vote-strong">
                                💖 {{ $vote_counts[$app->id]->strong_like ?? 0 }}
                            </span>
                            <span class="vote-count vote-up">
                                👍 {{ $vote_counts[$app->id]->thumbs_up ?? 0 }}
                            </span>
                            <span class="vote-count vote-down">
                                👎 {{ $vote_counts[$app->id]->thumbs_down ?? 0 }}
                            </span>
                        </div>
                        @if(isset($judging_history[$app->user_id]) && count($judging_history[$app->user_id]) > 0)
                            <div class="app-nav-judging">
                                ⚖️ Judged: R{{ implode(', R', $judging_history[$app->user_id]) }}
                            </div>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="apps-main">
            <div class="app-content">
                @foreach($user_apps as $index => $app)
                    <div class="app-sheet {{ $index === 0 ? 'active' : '' }}" data-app="{{ $index }}" data-app-id="{{ $app->id }}">
                        <h1>{{ $app->global_name }}</h1>

                        <div class="app-question">
                            <h3>Who are some of your favourite artists? Provide at least 5.</h3>
                            <p>{{ $app->fav_artists }}</p>
                        </div>

                        <div class="app-question">
                            <h3>Do you have any least favourite artists, and/or artists that won't score well with you? Try to include at least three.</h3>
                            <p>{{ $app->least_fav_artists }}</p>
                        </div>

                        <div class="app-question">
                            <h3>What are some of your favourite genres? Be as specific as possible.</h3>
                            <p>{{ $app->fav_genres }}</p>
                        </div>

                        <div class="app-question">
                            <h3>Do you have any least favourite genres? If not, explain why.</h3>
                            <p>{{ $app->least_fav_genres }}</p>
                        </div>

                        <div class="app-question">
                            <h3>Is there anything else you would like to mention about your judging style? An in-depth explanation about what you look for in songs, the vibe you usually enjoy, etc. will be appreciated.</h3>
                            <p>{{ $app->judging_style }}</p>
                        </div>

                        <div class="app-question">
                            <h3>Describe what you would consider a safe pick. You may include any helpful links here.</h3>
                            <p>{{ $app->safe_pick_criteria }}</p>
                        </div>

                        <div class="app-question">
                            <h3>Aside from browser-based music platforms, is there a streaming service you'd be able to receive submissions on?</h3>
                            <p>{{ $app->extra_streaming ?? 'Not specified' }}</p>
                        </div>

                        <div class="app-question">
                            <h3>Will you give a 0.5 bonus to songs you haven't heard before?</h3>
                            <p>{{ $app->bonus == 0 ? 'No' : 'Yes' }}</p>
                        </div>

                        <div class="app-question">
                            <h3>Provide up to 6 artists you want to ban contestants from submitting. Write N/A if you want to ban none.</h3>
                            <p>{{ $app->banned_artists }}</p>
                        </div>

                        <div class="app-question">
                            <h3>Would you prefer to judge more or less submissions in a round?</h3>
                            <p>{{ is_null($app->longer) ? 'No preference' : ($app->longer == 0 ? 'Less' : 'More') }}</p>
                        </div>

                        <!-- Comments Section -->
                        <div class="comments-section">
                            <h2>Comments</h2>
                            
                            <!-- Comment Form -->
                            <div class="comment-form">
                                <textarea 
                                    id="comment-textarea-{{ $app->id }}" 
                                    class="comment-textarea" 
                                    placeholder="Leave a comment about this application (optional)..."
                                    maxlength="5000"
                                >{{ $user_entries[$app->id]->comment ?? '' }}</textarea>
                                <div class="comment-form-actions">
                                    <span class="char-counter">
                                        <span id="char-count-{{ $app->id }}">{{ isset($user_entries[$app->id]) ? strlen($user_entries[$app->id]->comment ?? '') : 0 }}</span>/5000
                                    </span>
                                    <button 
                                        class="submit-comment-btn" 
                                        onclick="submitComment({{ $app->id }})"
                                    >
                                        Save Comment
                                    </button>
                                </div>
                            </div>

                            <!-- Existing Comments -->
                            <div class="comments-list" id="comments-list-{{ $app->id }}">
                                @if(isset($all_comments[$app->id]) && count($all_comments[$app->id]) > 0)
                                    @foreach($all_comments[$app->id] as $comment)
                                        <div class="comment-item {{ $comment->staff_id == $current_user_id ? 'own-comment' : '' }}">
                                            <div class="comment-header">
                                                <span class="comment-author">{{ $comment->global_name }}</span>
                                                @if($comment->score !== null)
                                                    <span class="comment-score score-{{ $comment->score }}">
                                                        @if($comment->score == 2)
                                                            💖 Strong Like
                                                        @elseif($comment->score == 1)
                                                            👍 Thumbs Up
                                                        @else
                                                            👎 Thumbs Down
                                                        @endif
                                                    </span>
                                                @endif
                                            </div>
                                            <div class="comment-text">{{ $comment->comment }}</div>
                                        </div>
                                    @endforeach
                                @else
                                    <p class="no-comments">No comments yet.</p>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="vote-buttons">
                <button class="vote-btn strong-like" onclick="handleVote(2)" id="strong-like-btn" title="Strong Like">💖</button>
                <button class="vote-btn thumbs-up" onclick="handleVote(1)" id="thumbs-up-btn" title="Thumbs Up">👍</button>
                <button class="vote-btn thumbs-down" onclick="handleVote(-1)" id="thumbs-down-btn" title="Thumbs Down">👎</button>
            </div>
        </div>
    </div>

    <script>
        const userEntries = @json($user_entries);
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        // Toggle contestant visibility
        function toggleContestantVisibility() {
            const checkbox = document.getElementById('hide-contestants');
            const contestantItems = document.querySelectorAll('.app-nav li[data-perms="1"]');
            
            contestantItems.forEach(item => {
                if (checkbox.checked) {
                    item.classList.add('hidden-by-filter');
                } else {
                    item.classList.remove('hidden-by-filter');
                }
            });

            // If the currently active item is now hidden, switch to the first visible one
            const activeItem = document.querySelector('.app-nav li.active');
            if (activeItem && activeItem.classList.contains('hidden-by-filter')) {
                const firstVisible = document.querySelector('.app-nav li:not(.hidden-by-filter)');
                if (firstVisible) {
                    const index = parseInt(firstVisible.getAttribute('data-app'));
                    showApp(index);
                }
            }
        }

        // Character counter for comment textareas
        document.addEventListener('DOMContentLoaded', function() {
            const textareas = document.querySelectorAll('.comment-textarea');
            textareas.forEach(textarea => {
                textarea.addEventListener('input', function() {
                    const appId = this.id.replace('comment-textarea-', '');
                    const counter = document.getElementById(`char-count-${appId}`);
                    counter.textContent = this.value.length;
                });
            });
            
            updateVoteButtons();
        });

        function updateVoteButtons() {
            const activeSheet = document.querySelector('.app-sheet.active');
            const appId = parseInt(activeSheet.getAttribute('data-app-id'));
            
            const strongLikeBtn = document.getElementById('strong-like-btn');
            const thumbsUpBtn = document.getElementById('thumbs-up-btn');
            const thumbsDownBtn = document.getElementById('thumbs-down-btn');
            
            // Remove voted class from all
            strongLikeBtn.classList.remove('voted');
            thumbsUpBtn.classList.remove('voted');
            thumbsDownBtn.classList.remove('voted');
            
            // Add voted class based on user's vote
            if (userEntries[appId]) {
                const score = userEntries[appId].score;
                if (score === 2) {
                    strongLikeBtn.classList.add('voted');
                } else if (score === 1) {
                    thumbsUpBtn.classList.add('voted');
                } else if (score === -1) {
                    thumbsDownBtn.classList.add('voted');
                }
            }
        }

        function showApp(index) {
            // Hide all app sheets
            document.querySelectorAll('.app-sheet').forEach(sheet => {
                sheet.classList.remove('active');
            });
            
            // Remove active from all nav items
            document.querySelectorAll('.app-nav li').forEach(item => {
                item.classList.remove('active');
            });
            
            // Show selected app sheet
            document.querySelector(`.app-sheet[data-app="${index}"]`).classList.add('active');
            document.querySelector(`.app-nav li[data-app="${index}"]`).classList.add('active');
            
            // Update vote buttons
            updateVoteButtons();
        }

        async function handleVote(score) {
            const activeSheet = document.querySelector('.app-sheet.active');
            const appId = parseInt(activeSheet.getAttribute('data-app-id'));
            
            try {
                const response = await fetch('/judge-vote', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        app_id: appId,
                        score: score
                    })
                });

                if (!response.ok) {
                    const text = await response.text();
                    console.error('Server response:', text);
                    alert('Server error: ' + response.status);
                    return;
                }

                const data = await response.json();
                
                if (data.success) {
                    // Update local vote data
                    if (!userEntries[appId]) {
                        userEntries[appId] = {};
                    }
                    userEntries[appId].score = score;
                    updateVoteButtons();
                    
                    // Update vote counts in sidebar
                    await updateVoteCounts(appId);
                } else {
                    alert('Error: ' + (data.message || 'Failed to record vote'));
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to record vote. Please try again.');
            }
        }

        async function submitComment(appId) {
            const textarea = document.getElementById(`comment-textarea-${appId}`);
            const comment = textarea.value.trim();
            
            try {
                const response = await fetch('/judge-vote', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        app_id: appId,
                        comment: comment
                    })
                });

                if (!response.ok) {
                    const text = await response.text();
                    console.error('Server response:', text);
                    alert('Server error: ' + response.status);
                    return;
                }

                const data = await response.json();
                
                if (data.success) {
                    // Update local comment data
                    if (!userEntries[appId]) {
                        userEntries[appId] = {};
                    }
                    userEntries[appId].comment = comment;
                    
                    // Reload comments
                    await loadComments(appId);
                    
                    alert('Comment saved successfully!');
                } else {
                    alert('Error: ' + (data.message || 'Failed to save comment'));
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to save comment. Please try again.');
            }
        }

        async function loadComments(appId) {
            try {
                const response = await fetch(`/judge-comments/${appId}`, {
                    headers: {
                        'Accept': 'application/json'
                    }
                });

                if (!response.ok) {
                    console.error('Failed to fetch comments');
                    return;
                }

                const data = await response.json();
                const commentsList = document.getElementById(`comments-list-${appId}`);
                const currentUserId = {{ $current_user_id }};
                
                if (data.comments.length === 0) {
                    commentsList.innerHTML = '<p class="no-comments">No comments yet.</p>';
                } else {
                    commentsList.innerHTML = data.comments.map(comment => {
                        const isOwnComment = comment.staff_id === currentUserId;
                        let scoreHtml = '';
                        
                        if (comment.score !== null) {
                            let scoreText = '';
                            if (comment.score === 2) {
                                scoreText = '💖 Strong Like';
                            } else if (comment.score === 1) {
                                scoreText = '👍 Thumbs Up';
                            } else {
                                scoreText = '👎 Thumbs Down';
                            }
                            scoreHtml = `<span class="comment-score score-${comment.score}">${scoreText}</span>`;
                        }
                        
                        return `
                            <div class="comment-item ${isOwnComment ? 'own-comment' : ''}">
                                <div class="comment-header">
                                    <span class="comment-author">${comment.global_name}</span>
                                    ${scoreHtml}
                                </div>
                                <div class="comment-text">${comment.comment}</div>
                            </div>
                        `;
                    }).join('');
                }
            } catch (error) {
                console.error('Error loading comments:', error);
            }
        }

        async function updateVoteCounts(appId) {
            try {
                const response = await fetch(`/judge-vote-counts/${appId}`, {
                    headers: {
                        'Accept': 'application/json'
                    }
                });

                if (!response.ok) {
                    console.error('Failed to fetch vote counts');
                    return;
                }

                const data = await response.json();
                
                // Find the sidebar item for this app
                const navItems = document.querySelectorAll('.app-nav li');
                navItems.forEach(item => {
                    const sheet = document.querySelector(`.app-sheet[data-app="${item.getAttribute('data-app')}"]`);
                    if (sheet && parseInt(sheet.getAttribute('data-app-id')) === appId) {
                        const voteCountSpans = item.querySelectorAll('.vote-count');
                        if (voteCountSpans.length >= 3) {
                            voteCountSpans[0].textContent = `💖 ${data.strong_like}`;
                            voteCountSpans[1].textContent = `👍 ${data.thumbs_up}`;
                            voteCountSpans[2].textContent = `👎 ${data.thumbs_down}`;
                        }
                    }
                });
            } catch (error) {
                console.error('Error updating vote counts:', error);
            }
        }

        const appQuestionElements = document.querySelectorAll('.app-question p');

        appQuestionElements.forEach(element => {
            const text = element.innerHTML;
            // Regular expression to find URLs starting with http:// or https://
            const urlRegex = /(https?:\/\/[^\s<]+)/g;
            
            // Replace URLs with clickable links
            const linkedText = text.replace(urlRegex, function(url) {
                return '<a href="' + url + '" target="_blank" rel="noopener noreferrer" style="color: #569cd6; text-decoration: underline;">' + url + '</a>';
            });
            
            element.innerHTML = linkedText;
        });
    </script>
@endif
</x-app-layout>