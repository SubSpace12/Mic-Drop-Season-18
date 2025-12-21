<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Home Page: view rounds') }}
        </h2>
    </x-slot>
    @vite(['resources/css/dashboard.css'])
    @php
        // Get the active season
        $activeSeason = DB::table('season')
            ->where('active', true)
            ->first();

        // If no active season, set defaults
        $seasonId = $activeSeason ? $activeSeason->season_id : null;

        // Get all rounds for the active season, ordered by round number
        $rounds = $seasonId ? DB::table('round')
            ->where('season_id', $seasonId)
            ->orderBy('round_number')
            ->get() : collect();

        // Check if user needs to join the season
        $needsToJoin = false;
        $canJoinSeason = false;
        $firstRound = null;
        
        if (auth()->check() && $seasonId) {
            // Check if user is in contestants table for this season
            $isContestant = DB::table('contestants')
                ->where('id', auth()->id())
                ->where('season_id', $seasonId)
                ->exists();
            
            if (!$isContestant) {
                // Check if first round deadline has passed
                $firstRound = DB::table('round')
                    ->where('season_id', $seasonId)
                    ->where('round_number', 1)
                    ->first();
                
                if ($firstRound) {
                    $firstRoundDeadline = new DateTime($firstRound->deadline);
                    $now = new DateTime();
                    
                    // Can join if deadline hasn't passed
                    if ($now <= $firstRoundDeadline) {
                        $needsToJoin = true;
                        $canJoinSeason = true;
                    }
                } else {
                    // No first round yet, can join
                    $needsToJoin = true;
                    $canJoinSeason = true;
                }
            }
        }

        // Get user permission level and role-specific info
        $userPerms = 0;
        $userGroup = null;
        $userRole = 'guest';
        $contestant = null;

        if (auth()->check() && $seasonId) {
            $userPerms = auth()->user()->perms ?? 0;

            // Determine role and group based on permission level
            if ($userPerms == 0) {
                $userRole = 'spectator';
            } elseif ($userPerms == 1) {
                $userRole = 'contestant';
                // Get contestant info including their group
                $contestant = DB::table('contestants')
                    ->where('id', auth()->id())
                    ->where('season_id', $seasonId)
                    ->first();
                if ($contestant) {
                    $userGroup = $contestant->md_group;
                }
            } elseif ($userPerms >= 2 && $userPerms <= 5) {
                $userRole = 'judge';
                // Get judge info for active round
                $activeRound = DB::table('round')
                    ->where('season_id', $seasonId)
                    ->where('status', 1)
                    ->first();

                if ($activeRound) {
                    $judgeInfo = DB::table('judges')
                        ->where('id', auth()->id())
                        ->where('season_id', $seasonId)
                        ->where('round', $activeRound->round_number)
                        ->first();

                    if ($judgeInfo) {
                        $userGroup = $judgeInfo->md_group;
                    }
                }
            } elseif ($userPerms >= 6) {
                $userRole = 'staff';
            }
        }
    @endphp

    {{-- Link to external CSS file --}}
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">

    <div class="rounds-container">
        @guest
            <div class="guest-message">
                <h3>Welcome to Mic Drop Season 18</h3>
                <p>Please log in to participate in rounds and view results</p>
            </div>
        @endguest

        @auth
            @if($needsToJoin && $canJoinSeason && auth()->user()->perms < 6)
                {{-- Join Season Screen --}}
                <div class="join-season-container">
                    <div class="join-season-icon">🎵</div>
                    <h1 class="join-season-title">Join Season {{ $activeSeason->season_id }}</h1>
                    <p class="join-season-description">
                        You're not currently registered for this season. Click below to join and start competing!
                    </p>

                    @if($firstRound)
                        <div class="season-info-box">
                            <div class="season-info-item">
                                <span class="season-info-label">First Round Deadline:</span>
                                <span class="season-info-value">{{ date('M j, Y g:i A', strtotime($firstRound->deadline)) }}</span>
                            </div>
                            <div class="season-info-item">
                                <span class="season-info-label">Status:</span>
                                <span class="season-info-value">Registration Open</span>
                            </div>
                        </div>
                    @endif

                    <form action="{{ route('join.season') }}" method="POST">
                        @csrf
                        <button type="submit" class="join-button">
                            Join Season Now
                        </button>
                    </form>
                </div>
            @elseif($rounds->isEmpty())
                <div class="no-rounds">
                    No rounds have been created yet. Check back soon.
                </div>
            @else
                {{-- Normal Round Display --}}
                <div class="rounds-grid">
                    @foreach($rounds as $round)
                        @php
                            $statusClass = match ($round->status) {
                                0 => 'status-pending',
                                1 => 'status-active',
                                2 => 'status-completed',
                                default => 'status-pending'
                            };

                            if ($round->status == 1 && $userRole == 'spectator') {
                                $statusClass .= ' not-clickable';
                            }

                            $statusBadge = match ($round->status) {
                                0 => 'Coming Soon',
                                1 => 'Active Now',
                                2 => 'Completed',
                                default => 'Unknown'
                            };

                            $badgeClass = match ($round->status) {
                                0 => 'badge-pending',
                                1 => 'badge-active',
                                2 => 'badge-completed',
                                default => 'badge-pending'
                            };
                        @endphp

                        <div class="round-card {{ $statusClass }}"
                            onclick="handleRoundClick({{ $round->round_number }}, {{ $round->status }}, {{ $round->is_merge ? 'true' : 'false' }}, '{{ $userRole }}', {{ $userGroup ?? 'null' }})">

                            <div class="round-header">
                                <div class="round-number">{{ $round->round_number }}</div>
                                <div>
                                    <span class="round-badge {{ $badgeClass }}">{{ $statusBadge }}</span>
                                    @if($round->is_merge && $round->status != 0)
                                        <span class="round-badge badge-merge">MERGE</span>
                                    @endif
                                </div>
                            </div>

                            @if($round->status == 0)
                                <h3 class="round-title">Round {{ $round->round_number }}</h3>
                                <p class="round-description">Details will be revealed when the round starts</p>
                            @else
                                <h3 class="round-title">{{ $round->title }}</h3>
                                <p class="round-description">{{ $round->description }}</p>
                            @endif

                            @if($round->status != 0)
                                <div class="round-info">
                                    <div class="info-item">
                                        <span class="info-icon">[DATE]</span>
                                        <span>{{ date('M j, Y', strtotime($round->deadline)) }}</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-icon">[GROUPS]</span>
                                        <span>{{ $round->is_merge ? 'All Groups' : '3 Groups' }}</span>
                                    </div>
                                </div>
                            @endif

                            @if($round->status == 0)
                                <div class="action-text">COMING SOON</div>
                            @elseif($round->status == 1)
                                <div class="action-text">
                                    @if($userRole == 'spectator')
                                        SPECTATING
                                    @elseif($userRole == 'contestant')
                                        SUBMIT SONGS
                                    @elseif($userRole == 'judge')
                                        JUDGE NOW
                                    @elseif($userRole == 'staff')
                                        STAFF OPTIONS
                                    @else
                                        ACTIVE ROUND
                                    @endif
                                </div>
                            @elseif($round->status == 2)
                                <div class="action-text">VIEW RESULTS</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        @endauth
    </div>

    <!-- Group Selection Modal (for viewing results) -->
    <div id="groupModal" class="modal">
        <div class="modal-content">
            <h2 class="modal-header">Select a Group</h2>
            <p class="modal-description">Which group's results would you like to view?</p>

            <div class="group-buttons">
                <button class="group-button" onclick="selectGroup(1)">Group 1</button>
                <button class="group-button" onclick="selectGroup(2)">Group 2</button>
                <button class="group-button" onclick="selectGroup(3)">Group 3</button>
            </div>

            <button class="cancel-button" onclick="closeModal()">Cancel</button>
        </div>
    </div>

    <!-- Staff Action Modal -->
    <div id="staffModal" class="modal">
        <div class="modal-content">
            <h2 class="modal-header">Choose Action</h2>
            <p class="modal-description">What would you like to do?</p>

            <div class="action-buttons">
                <button class="action-button" onclick="staffAction('submit')">View submission form</button>
                <button class="action-button" onclick="staffAction('judge')">View judging sheets</button>
            </div>

            <button class="cancel-button" onclick="closeModal()">Cancel</button>
        </div>
    </div>

    <!-- Staff Group Selection Modal -->
    <div id="staffGroupModal" class="modal">
        <div class="modal-content">
            <h2 class="modal-header">Select a Group</h2>
            <p class="modal-description" id="staffGroupDescription">Which group would you like to work with?</p>

            <div class="group-buttons">
                <button class="group-button" onclick="selectStaffGroup(1)">Group 1</button>
                <button class="group-button" onclick="selectStaffGroup(2)">Group 2</button>
                <button class="group-button" onclick="selectStaffGroup(3)">Group 3</button>
            </div>

            <button class="cancel-button" onclick="closeModal()">Cancel</button>
        </div>
    </div>

    <script>
        let currentRound = null;
        let currentAction = null;
        let currentIsMerge = false;
        let staffChosenAction = null;

        function handleRoundClick(roundNumber, status, isMerge, userRole, userGroup) {
            if (status === 0) return;
            if (status === 1 && userRole === 'spectator') return;

            currentRound = roundNumber;
            currentIsMerge = isMerge;

            if (status === 1) {
                if (userRole === 'contestant') {
                    if (isMerge) {
                        window.location.href = `/submit?round=${roundNumber}`;
                    } else {
                        if (userGroup === null) {
                            alert('Error: Could not determine your group. Please contact an administrator.');
                            return;
                        }
                        window.location.href = `/submit?round=${roundNumber}&group=${userGroup}`;
                    }
                } else if (userRole === 'judge') {
                    if (isMerge) {
                        window.location.href = `/judging?round=${roundNumber}`;
                    } else {
                        if (userGroup === null) {
                            alert('Error: Could not determine your judging group. Please contact an administrator.');
                            return;
                        }
                        window.location.href = `/judging?round=${roundNumber}&group=${userGroup}`;
                    }
                } else if (userRole === 'staff') {
                    openStaffModal();
                }
            }

            if (status === 2) {
                if (isMerge) {
                    window.location.href = `/results?round=${roundNumber}`;
                } else {
                    currentAction = 'results';
                    openGroupModal();
                }
            }
        }

        function openGroupModal() {
            document.getElementById('groupModal').classList.add('show');
        }

        function openStaffModal() {
            document.getElementById('staffModal').classList.add('show');
        }

        function openStaffGroupModal() {
            document.getElementById('staffGroupModal').classList.add('show');
        }

        function closeModal() {
            document.getElementById('groupModal').classList.remove('show');
            document.getElementById('staffModal').classList.remove('show');
            document.getElementById('staffGroupModal').classList.remove('show');
            currentRound = null;
            currentAction = null;
            currentIsMerge = false;
            staffChosenAction = null;
        }

        function selectGroup(group) {
            if (currentAction === 'results' && currentRound !== null) {
                window.location.href = `/results?round=${currentRound}&group=${group}`;
            }
            closeModal();
        }

        function staffAction(action) {
            staffChosenAction = action;
            document.getElementById('staffModal').classList.remove('show');

            if (currentIsMerge) {
                if (action === 'submit') {
                    window.location.href = `/submit?round=${currentRound}`;
                } else if (action === 'judge') {
                    window.location.href = `/judging?round=${currentRound}`;
                }
            } else {
                if (action === 'submit') {
                    document.getElementById('staffGroupDescription').textContent = 'Which group would you like to submit for?';
                } else if (action === 'judge') {
                    document.getElementById('staffGroupDescription').textContent = 'Which group would you like to judge?';
                }
                openStaffGroupModal();
            }
        }

        function selectStaffGroup(group) {
            if (staffChosenAction === 'submit') {
                window.location.href = `/submit?round=${currentRound}&group=${group}`;
            } else if (staffChosenAction === 'judge') {
                window.location.href = `/judging?round=${currentRound}&group=${group}`;
            }
            closeModal();
        }

        window.onclick = function (event) {
            const groupModal = document.getElementById('groupModal');
            const staffModal = document.getElementById('staffModal');
            const staffGroupModal = document.getElementById('staffGroupModal');

            if (event.target === groupModal || event.target === staffModal || event.target === staffGroupModal) {
                closeModal();
            }
        }

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeModal();
            }
        });
    </script>
</x-app-layout>