<x-app-layout>
        @php
                $group = request()->query('group', 0);
                $round = request()->query('round', -1);
                if ($group < 0 || $group > 3) {
                        $group = -1;
                }

                // Get the active season
                $activeSeason = DB::table('season')
                        ->where('active', true)
                        ->first();

                $seasonId = $activeSeason ? $activeSeason->season_id : null;

                if (!$seasonId) {
                        abort(404, 'No active season found');
                }

                $round_info = DB::table('round')
                        ->where('round_number', $round)
                        ->where('season_id', $seasonId)
                        ->first();

                // Check if round exists
                if (!$round_info) {
                        abort(404, 'Round not found');
                }
                // Check round status - must be active (status = 1)
                if ($round_info->status == 0) {
                        $statusError = 'not_started';
                } elseif ($round_info->status == 2) {
                        $statusError = 'completed';
                } else {
                        $statusError = null;
                }

                // Security checks - must be done early
                $accessDenied = null;
                $accessDeniedReason = '';
                $isStaffViewing = false;

                if (!$statusError && auth()->check()) {
                        $userPerms = auth()->user()->perms ?? 0;

                        // Check 1: Staff can view but not submit (perms >= 6)
                        if ($userPerms >= 6) {
                                $isStaffViewing = true;
                        }

                        // Check 2: Must be a contestant (perms == 1) to submit
                        elseif ($userPerms != 1) {
                                $accessDenied = true;
                                $accessDeniedReason = 'not_contestant';
                        }

                        // Check 3: For non-merge rounds, contestant must be in the correct group
                        elseif (!$accessDenied && $group != 0) {
                                $contestant = DB::table('contestants')
                                        ->where('id', auth()->id())
                                        ->where('season_id', $seasonId)
                                        ->first();

                                if (!$contestant) {
                                        $accessDenied = true;
                                        $accessDeniedReason = 'not_contestant';
                                } elseif ($contestant->md_group != $group) {
                                        $accessDenied = true;
                                        $accessDeniedReason = 'wrong_group';
                                }
                        }
                } elseif (!$statusError && !auth()->check()) {
                        $accessDenied = true;
                        $accessDeniedReason = 'not_logged_in';
                }

                // Get contestant info (including extension_hours)
                $contestant = null;
                $effectiveDeadline = null;
                $deadlinePassed = false;
                if (!$statusError && !$accessDenied && auth()->check() && !$isStaffViewing) {
                        $contestant = DB::table('contestants')
                                ->where('id', auth()->id())
                                ->where('season_id', $seasonId)
                                ->first();
                        if ($contestant) {
                                // Calculate effective deadline with extension hours
                                $baseDeadline = new DateTime($round_info->deadline);
                                $extensionHours = $contestant->extension_hours ?? 0;
                                $baseDeadline->modify("+{$extensionHours} hours");
                                $effectiveDeadline = $baseDeadline;
                                // Check if deadline has passed
                                $now = new DateTime();
                                if ($now > $effectiveDeadline) {
                                        $deadlinePassed = true;
                                }
                        }
                }
                // Only check for existing submissions if round is active and deadline hasn't passed
                $existing_submissions = false;
                $alreadySubmitted = false;
                if (!$statusError && !$accessDenied && !$isStaffViewing && !$deadlinePassed) {
                        // Check if user has already submitted for this round
                        $existing_submissions = DB::table('submissions')
                                ->where('contestant_id', auth()->id())
                                ->where('round', $round)
                                ->where('md_group', $group)
                                ->where('season_id', $seasonId)
                                ->exists();
                        if ($existing_submissions) {
                                $alreadySubmitted = true;
                        }
                }
                $judges = [];
                if (!$statusError && !$accessDenied && (!$deadlinePassed || $isStaffViewing) && (!$alreadySubmitted || $isStaffViewing)) {
                        $judges = DB::table('judges')
                                ->join('users', 'users.id', '=', 'judges.id')
                                ->join('apps', 'apps.user_id', '=', 'users.id')
                                ->select('judges.id as judge_id', 'users.global_name', 'apps.*')
                                ->where('judges.round', $round)
                                ->where('judges.md_group', $group)
                                ->where('judges.season_id', $seasonId)->orderBy('judge_number')
                                ->get();
                }
        @endphp
        @vite(['resources/css/submit.css'])
        <div class="submission-container">
                @if($statusError)
                        {{-- Access Denied Page for Invalid Round Status --}}
                        <div class="access-denied {{ $statusError == 'not_started' ? 'not-started' : 'completed' }}">
                                @if($statusError == 'not_started')
                                        <div class="access-denied-icon">PENDING</div>
                                        <h1>Round Not Started Yet</h1>
                                        <p>This round hasn't begun yet. Submissions are not currently being accepted.</p>
                                @else
                                        <div class="access-denied-icon">COMPLETE</div>
                                        <h1>Round Already Completed</h1>
                                        <p>This round has ended and is no longer accepting submissions.</p>
                                @endif
                                <div class="round-info">
                                        <h3>{{ $round_info->title }}</h3>
                                        <p><strong>Round:</strong> {{ $round }}</p>
                                        <p><strong>Group:</strong> {{ $group == 0 ? 'Merge' : 'Group ' . $group }}</p>
                                        <p><strong>Status:</strong>
                                                @if($statusError == 'not_started')
                                                        <span style="color: #d7ba7d; font-weight: 600;">Pending</span>
                                                @else
                                                        <span style="color: #858585; font-weight: 600;">Completed</span>
                                                @endif
                                        </p>
                                </div>
                                @if($statusError == 'not_started')
                                        <p style="font-size: 16px; color: #a0a0a0;">
                                                Please wait for the round to be activated by an administrator.
                                                You'll be notified when submissions open.
                                        </p>
                                @else
                                        <p style="font-size: 16px; color: #a0a0a0;">
                                                Thank you for participating! Check the results page to see the outcomes.
                                        </p>
                                @endif
                                <a href="/dashboard" class="back-button">Back to Dashboard</a>
                        </div>
                @elseif($accessDenied)
                        {{-- Access Denied for Permission Issues --}}
                        <div class="access-denied permission-denied">
                                <div class="access-denied-icon">ACCESS DENIED</div>
                                @if($accessDeniedReason == 'not_contestant')
                                        <h1>Access Denied</h1>
                                        <p>You must be a registered contestant to submit songs.</p>
                                        <p style="font-size: 16px; color: #a0a0a0;">
                                                If you believe this is an error, please contact an administrator.
                                        </p>
                                @elseif($accessDeniedReason == 'wrong_group')
                                        <h1>Wrong Group</h1>
                                        <p>You are not assigned to this group and cannot submit songs here.</p>
                                        <div class="round-info">
                                                <h3>{{ $round_info->title }}</h3>
                                                <p><strong>Round:</strong> {{ $round }}</p>
                                                <p><strong>Requested Group:</strong> Group {{ $group }}</p>
                                                @if($contestant)
                                                        <p><strong>Your Group:</strong> Group {{ $contestant->md_group }}</p>
                                                @endif
                                        </div>
                                        <p style="font-size: 16px; color: #a0a0a0;">
                                                Please submit songs for your assigned group only.
                                        </p>
                                @elseif($accessDeniedReason == 'not_logged_in')
                                        <h1>Login Required</h1>
                                        <p>You must be logged in to submit songs.</p>
                                        <p style="font-size: 16px; color: #a0a0a0;">
                                                Please log in and try again.
                                        </p>
                                @else
                                        <h1>Access Denied</h1>
                                        <p>You do not have permission to submit songs for this round.</p>
                                @endif
                                <a href="/dashboard" class="back-button">Back to Dashboard</a>
                        </div>
                @elseif(!$isStaffViewing && $deadlinePassed)
                        {{-- Deadline Passed Screen --}}
                        <div class="access-denied deadline-passed">
                                <div class="access-denied-icon">DEADLINE PASSED</div>
                                <h1>Deadline Has Passed</h1>
                                <p>The submission deadline for this round has expired.</p>
                                <div class="round-info">
                                        <h3>{{ $round_info->title }}</h3>
                                        <p><strong>Round:</strong> {{ $round }}</p>
                                        <p><strong>Group:</strong> {{ $group == 0 ? 'Merge' : 'Group ' . $group }}</p>
                                        <p><strong>Deadline was:</strong>
                                                <span class="deadline-display" data-deadline="{{ $baseDeadline->format('c') }}"
                                                        style="color: #f48771; font-weight: 600;">
                                                        {{ $effectiveDeadline->format('M j, Y g:i A') }} (Server Time)
                                                </span>
                                        </p>
                                        @if($contestant && $contestant->extension_hours > 0)
                                                <p style="font-size: 14px; color: #a0a0a0; margin-top: 10px;">
                                                        <em>This included your {{ $contestant->extension_hours }}-hour extension</em>
                                                </p>
                                        @endif
                                </div>
                                <p style="font-size: 16px; color: #a0a0a0;">
                                        Unfortunately, you can no longer submit songs for this round.
                                        If you believe this is an error, please contact an administrator.
                                </p>
                                <a href="/dashboard" class="back-button">Back to Dashboard</a>
                        </div>
                @elseif(!$isStaffViewing && $alreadySubmitted)
                        {{-- Already Submitted Screen --}}
                        <div class="access-denied already-submitted">
                                <div class="access-denied-icon">SUBMITTED</div>
                                <h1>Submission Already Received</h1>
                                <p>You have already submitted your songs for this round.</p>
                                <div class="round-info">
                                        <h3>{{ $round_info->title }}</h3>
                                        <p><strong>Round:</strong> {{ $round }}</p>
                                        <p><strong>Group:</strong> {{ $group == 0 ? 'Merge' : 'Group ' . $group }}</p>
                                        <p><strong>Status:</strong>
                                                <span style="color: #608b4e; font-weight: 600;">Submitted</span>
                                        </p>
                                </div>
                                <p style="font-size: 16px; color: #a0a0a0;">
                                        Your submission has been recorded successfully.
                                        Good luck with your songs!
                                </p>
                                <p style="font-size: 14px; color: #858585; margin-top: 20px;">
                                        Need to make changes? Contact an administrator for assistance.
                                </p>
                                <a href="/dashboard" class="back-button">Back to Dashboard</a>
                        </div>
                @else
                        {{-- Normal Submission Page / Staff Viewing --}}
                        <div class="page-header">
                                <h1>Mic Drop, Season 18, Round {{ $round }}{{ $group != 0 ? ", Group $group" : "" }} – {{ $round_info->title }}</h1>
                                <div class="header-description">
                                        <p>{{ $round_info->description }}</p>
                                </div>
                                @if($round_info->theme_details)
                                        <div class="theme-details">
                                                <div class="theme-details-label">Theme Details</div>
                                                <div class="theme-details-content">{{ $round_info->theme_details }}</div>
                                        </div>
                                @endif
                        </div>

                        @if($isStaffViewing)
                                <div class="staff-viewing-banner">
                                        [STAFF] View Mode - You are viewing this form as staff. Submission is disabled.
                                </div>
                        @endif

                        @if(session('success'))
                                <div class="success-message">
                                        <h3>{{ session('success') }}</h3>
                                        <p>Your songs have been submitted successfully. Good luck!</p>
                                </div>
                        @endif
                        @if($errors->any())
                                <div class="error-message">
                                        <ul>
                                                @foreach($errors->all() as $error)
                                                        <li>{{ $error }}</li>
                                                @endforeach
                                        </ul>
                                </div>
                        @endif
                        {{-- Show deadline info with extension if applicable --}}
                        @if(!$isStaffViewing && $contestant && $effectiveDeadline)
                                <div class="deadline-info">
                                        <p>[DEADLINE] Submission Deadline:
                                                <span class="deadline-display" data-deadline="{{ $baseDeadline->format('c') }}"
                                                        style="font-weight: 600;">
                                                        Calculating...
                                                </span>
                                        </p>
                                        @if($contestant->extension_hours > 0)
                                                <p class="extension-note">
                                                        You have been granted a {{ $contestant->extension_hours }}-hour extension
                                                </p>
                                        @endif
                                </div>
                        @endif
                        <form action="{{ route('submit.songs') }}" method="POST" id="submissionForm">
                                @csrf
                                <input type="hidden" name="group" value="{{ $group }}">
                                <input type="hidden" name="round" value="{{ $round }}">
                                @foreach($judges as $judge)
                                        <div class="judge-section">
                                                <h2 class="judge-name">
                                                        {{ ($loop->iteration == 1 ? "Head Judge" : "Guest Judge " . ($loop->iteration - 1)) . ": " . $judge->global_name }}
                                                </h2>
                                                <div class="judge-info">
                                                        <h3>1. Who are some of your favourite artists?</h3>
                                                        <p>{{ $judge->fav_artists }}</p>
                                                </div>
                                                <div class="judge-info">
                                                        <h3>2. Do you have any least favourite artists, or artists you know won't score well with you?</h3>
                                                        <p>{{ $judge->least_fav_artists }}</p>
                                                </div>
                                                <div class="judge-info">
                                                        <h3>3. What are some of your favourite genres?</h3>
                                                        <p>{{ $judge->fav_genres }}</p>
                                                </div>
                                                <div class="judge-info">
                                                        <h3>4. Do you have any least favourite genres?</h3>
                                                        <p>{{ $judge->least_fav_genres }}</p>
                                                </div>
                                                <div class="judge-info">
                                                        <h3>5. Is there anything else you would like to mention about your judging
                                                                style?</h3>
                                                        <p>{{ $judge->judging_style }}</p>
                                                </div>
                                                <div class="judge-info">
                                                        <h3>6. Describe what you would consider a safe pick.</h3>
                                                        <p>{{ $judge->safe_pick_criteria }}</p>
                                                </div>
                                                <div class="judge-info">
                                                        <h3>7. Aside from browser-based music platforms, is there a streaming service you'd be able to receive submissions on?</h3>
                                                        <p><b>{{ $judge->extra_streaming ?? 'Not specified' }}</b></p>
                                                </div>
                                                <div class="judge-info">
                                                        <h3>8. Will you give a 0.5 bonus to songs you haven't heard before?</h3>
                                                        <p><b>{{ $judge->bonus == 1 ? 'Yes' : 'No' }}</b></p>
                                                </div>
                                                <div class="judge-info">
                                                        <h3>9. Provide up to 6 artists you want to ban contestants from submitting.</h3>
                                                        <p><b>{{ $judge->banned_artists }}</b></p>
                                                </div>
                                                <div class="submission-block">
                                                        <h2>What will you submit to {{ $judge->global_name }}? (Judge ID:
                                                                {{ $judge->judge_id }})</h2>
                                                        <div class="input-group">
                                                                <input type="text" name="artist_{{ $judge->judge_id }}"
                                                                        placeholder="Artist Name" {{ $isStaffViewing ? 'disabled' : 'required' }}>
                                                                <input type="text" name="title_{{ $judge->judge_id }}"
                                                                        placeholder="Song Title" {{ $isStaffViewing ? 'disabled' : 'required' }}>
                                                                <input type="url" name="link_{{ $judge->judge_id }}"
                                                                        placeholder="YouTube Link" {{ $isStaffViewing ? 'disabled' : 'required' }}>
                                                        </div>
                                                </div>
                                        </div>
                                @endforeach
                                <button type="submit" class="submit-button" {{ $isStaffViewing ? 'disabled' : '' }}>
                                        {{ $isStaffViewing ? 'Submission Disabled (Staff View)' : 'Submit All Songs' }}
                                </button>
                        </form>
                @endif
        </div>
        <script>
                // Convert deadlines to user's local timezone
                document.addEventListener('DOMContentLoaded', function () {
                        const deadlineElements = document.querySelectorAll('.deadline-display');
                        deadlineElements.forEach(element => {
                                const serverTime = element.getAttribute('data-deadline');
                                const localDate = new Date(serverTime);
                                // Format the local date
                                const options = {
                                        month: 'short',
                                        day: 'numeric',
                                        year: 'numeric',
                                        hour: 'numeric',
                                        minute: '2-digit',
                                        hour12: true
                                };
                                const localTimeString = localDate.toLocaleString(undefined, options);
                                const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
                                element.textContent = `${localTimeString} (Your Local Time - ${timezone})`;
                        });
                });

                // Prevent form submission for staff
                @if($isStaffViewing)
                        document.getElementById('submissionForm').addEventListener('submit', function (e) {
                                e.preventDefault();
                                alert('Staff members cannot submit songs. This is a view-only mode.');
                                return false;
                        });
                @endif

                document.addEventListener('DOMContentLoaded', function () {
                        const judgeInfoElements = document.querySelectorAll('.judge-info p');
                        
                        judgeInfoElements.forEach(element => {
                                const text = element.innerHTML;
                                // Regular expression to find URLs starting with http:// or https://
                                const urlRegex = /(https?:\/\/[^\s<]+)/g;
                                
                                // Replace URLs with clickable links
                                const linkedText = text.replace(urlRegex, function(url) {
                                return '<a href="' + url + '" target="_blank" rel="noopener noreferrer" style="color: #569cd6; text-decoration: underline;">' + url + '</a>';
                                });
                                
                                element.innerHTML = linkedText;
                        });
                        });
        </script>
</x-app-layout>