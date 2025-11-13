<x-app-layout>
@php
$user_apps = DB::table('apps')
    ->join('users', 'apps.user_id', '=', 'users.id')->get();

$current_user_id = auth()->id();

// Get all upvotes for the current user
$user_votes = DB::table('judge_upvotes')
    ->where('staff_id', $current_user_id)
    ->pluck('score', 'app_id')
    ->toArray();
@endphp

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

.apps-container {
    display: flex;
    height: calc(100vh - 64px);
    overflow: hidden;
    background: #f5f5f5;
}

.apps-sidebar {
    width: 280px;
    background: #2c3e50;
    color: white;
    overflow-y: auto;
    box-shadow: 2px 0 10px rgba(0,0,0,0.1);
}

.apps-sidebar h2 {
    padding: 20px;
    background: #1a252f;
    font-size: 18px;
    border-bottom: 1px solid #34495e;
}

.app-nav {
    list-style: none;
}

.app-nav li {
    padding: 15px 20px;
    cursor: pointer;
    border-bottom: 1px solid #34495e;
    transition: background 0.2s;
}

.app-nav li:hover {
    background: #34495e;
}

.app-nav li.active {
    background: #3498db;
    border-left: 4px solid #2980b9;
}

.apps-main {
    flex: 1;
    overflow-y: auto;
    padding: 40px;
    display: flex;
    gap: 30px;
}

.app-content {
    flex: 1;
    max-width: 900px;
}

.app-sheet {
    display: none;
    background: white;
    border-radius: 12px;
    padding: 40px;
    box-shadow: 0 2px 20px rgba(0,0,0,0.1);
}

.app-sheet.active {
    display: block;
}

.app-sheet h1 {
    color: #2c3e50;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 3px solid #3498db;
    font-size: 32px;
}

.app-question {
    margin-bottom: 30px;
}

.app-question h3 {
    color: #34495e;
    margin-bottom: 10px;
    font-size: 16px;
    font-weight: 600;
}

.app-question p {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    border-left: 4px solid #3498db;
    color: #2c3e50;
    line-height: 1.6;
}

.vote-buttons {
    display: flex;
    flex-direction: column;
    gap: 20px;
    position: sticky;
    top: 40px;
}

.vote-btn {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

.vote-btn:hover {
    transform: scale(1.1);
    box-shadow: 0 6px 20px rgba(0,0,0,0.3);
}

.vote-btn:active {
    transform: scale(0.95);
}

.vote-btn.voted {
    box-shadow: 0 6px 25px rgba(0,0,0,0.4);
    transform: scale(1.05);
}

.thumbs-up {
    background: linear-gradient(135deg, #2ecc71, #27ae60);
    color: white;
}

.thumbs-up:hover {
    background: linear-gradient(135deg, #27ae60, #229954);
}

.thumbs-up.voted {
    background: linear-gradient(135deg, #27ae60, #1e8449);
    box-shadow: 0 0 20px rgba(46, 204, 113, 0.6);
}

.thumbs-down {
    background: linear-gradient(135deg, #e74c3c, #c0392b);
    color: white;
}

.thumbs-down:hover {
    background: linear-gradient(135deg, #c0392b, #a93226);
}

.thumbs-down.voted {
    background: linear-gradient(135deg, #c0392b, #922b21);
    box-shadow: 0 0 20px rgba(231, 76, 60, 0.6);
}

.no-apps {
    text-align: center;
    padding: 40px;
    color: #7f8c8d;
    font-size: 18px;
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
            <ul class="app-nav">
                @foreach($user_apps as $index => $app)
                    <li onclick="showApp({{ $index }})" data-app="{{ $index }}" class="{{ $index === 0 ? 'active' : '' }}">
                        {{ $app->global_name }}
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
                            <h3>Do you have any least favourite artists? This question is optional, write N/A if none.</h3>
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
                            <h3>Will you give a 0.5 bonus to songs you haven't heard before?</h3>
                            <p>{{ $app->bonus == 0 ? 'No' : 'Yes' }}</p>
                        </div>

                        <div class="app-question">
                            <h3>Provide up to 6 artists you want to ban contestants from submitting. Write N/A if you want to ban none.</h3>
                            <p>{{ $app->banned_artists }}</p>
                        </div>

                        <div class="app-question">
                            <h3>Would you prefer to judge more or less submissions in a round?</h3>
                            <p>{{ $app->longer == 0 ? 'Less' : 'More' }}</p>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="vote-buttons">
                <button class="vote-btn thumbs-up" onclick="handleVote(true)" id="thumbs-up-btn">👍</button>
                <button class="vote-btn thumbs-down" onclick="handleVote(false)" id="thumbs-down-btn">👎</button>
            </div>
        </div>
    </div>

    <script>
        const userVotes = @json($user_votes);
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        function updateVoteButtons() {
            const activeSheet = document.querySelector('.app-sheet.active');
            const appId = parseInt(activeSheet.getAttribute('data-app-id'));
            
            const thumbsUpBtn = document.getElementById('thumbs-up-btn');
            const thumbsDownBtn = document.getElementById('thumbs-down-btn');
            
            // Remove voted class from both
            thumbsUpBtn.classList.remove('voted');
            thumbsDownBtn.classList.remove('voted');
            
            // Add voted class based on user's vote
            if (userVotes[appId] === true) {
                thumbsUpBtn.classList.add('voted');
            } else if (userVotes[appId] === false) {
                thumbsDownBtn.classList.add('voted');
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
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        app_id: appId,
                        score: score
                    })
                });

                const data = await response.json();
                
                if (data.success) {
                    // Update local vote data
                    userVotes[appId] = score;
                    updateVoteButtons();
                } else {
                    alert('Error: ' + (data.message || 'Failed to record vote'));
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to record vote. Please try again.');
            }
        }

        // Initialize vote buttons on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateVoteButtons();
        });
    </script>
@endif
</x-app-layout>