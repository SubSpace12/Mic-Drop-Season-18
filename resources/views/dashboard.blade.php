<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Home Page - view rounds') }}
        </h2>
    </x-slot>

    @php
        // Get all rounds for season 1, ordered by round number
        $rounds = DB::table('round')
            ->where('season_id', 1)
            ->orderBy('round_number')
            ->get();

        // Get user permission level and role-specific info
        $userPerms = 0;
        $userGroup = null;
        $userRole = 'guest';
        $contestant = null;

        if (auth()->check()) {
            $userPerms = auth()->user()->perms ?? 0;

            // Determine role and group based on permission level
            if ($userPerms == 0) {
                $userRole = 'spectator';
            } elseif ($userPerms == 1) {
                $userRole = 'contestant';
                // Get contestant info including their group
                $contestant = DB::table('contestants')
                    ->where('id', auth()->id())
                    ->where('season_id', 1)
                    ->first();
                if ($contestant) {
                    $userGroup = $contestant->md_group;
                }
            } elseif ($userPerms >= 2 && $userPerms <= 5) {
                $userRole = 'judge';
                // Get judge info for active round
                $activeRound = DB::table('round')
                    ->where('season_id', 1)
                    ->where('status', 1)
                    ->first();

                if ($activeRound) {
                    $judgeInfo = DB::table('judges')
                        ->where('id', auth()->id())
                        ->where('season_id', 1)
                        ->where('round', $activeRound->round_number)
                        ->first();

                    if ($judgeInfo) {
                        $userGroup = $userPerms - 2;
                    }
                }
            } elseif ($userPerms >= 6) {
                $userRole = 'staff';
            }
        }
    @endphp

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Roboto+Mono:wght@300;400;500;600;700&display=swap');

        * {
            font-family: 'Consolas', 'Monaco', 'Roboto Mono', 'Courier New', monospace;
        }

        body {
            background: linear-gradient(135deg, #1e1e1e 0%, #252526 50%, #2d2d30 100%);
        }

        .rounds-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        .welcome-message {
            background: linear-gradient(135deg, #0e639c 0%, #1177bb 50%, #1c88d1 100%);
            color: #d4d4d4;
            padding: 2.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(14, 99, 156, 0.4);
            border: 2px solid #1c88d1;
            position: relative;
            overflow: hidden;
        }

        .welcome-message::before {
            content: '>';
            position: absolute;
            top: 10px;
            right: 20px;
            font-size: 1.5rem;
            opacity: 0.3;
            color: #4ec9b0;
        }

        .welcome-message::after {
            content: '//';
            position: absolute;
            bottom: 10px;
            left: 20px;
            font-size: 1.5rem;
            opacity: 0.3;
            color: #608b4e;
        }

        .welcome-message h1 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: #4ec9b0;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .welcome-message p {
            font-size: 1.125rem;
            opacity: 0.95;
            font-weight: 500;
        }

        .rounds-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .round-card {
            background: #252526;
            border-radius: 8px;
            padding: 2rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.5);
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            border: 2px solid #3e3e42;
        }

        .round-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #569cd6, #4ec9b0, #608b4e);
        }

        .round-card::after {
            content: '{ }';
            position: absolute;
            bottom: 15px;
            right: 15px;
            font-size: 2rem;
            opacity: 0.05;
            color: #4ec9b0;
        }

        /* Status 0 - Coming Soon */
        .round-card.status-pending {
            background: #1e1e1e;
            cursor: not-allowed;
            opacity: 0.6;
            border-color: #2d2d30;
        }

        .round-card.status-pending::before {
            background: linear-gradient(90deg, #3e3e42, #4e4e52);
        }

        .round-card.status-pending:hover {
            transform: none;
        }

        /* Status 1 - Active */
        .round-card.status-active {
            border: 2px solid #4ec9b0;
            background: linear-gradient(135deg, #252526 0%, #2d2d30 100%);
            animation: terminal-pulse 2s ease-in-out infinite;
        }

        @keyframes terminal-pulse {
            0%, 100% {
                box-shadow: 0 4px 15px rgba(78, 201, 176, 0.4);
            }
            50% {
                box-shadow: 0 6px 25px rgba(78, 201, 176, 0.6);
            }
        }

        .round-card.status-active::before {
            background: linear-gradient(90deg, #4ec9b0, #4ec9b0, #569cd6);
        }

        .round-card.status-active:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 30px rgba(78, 201, 176, 0.5);
        }

        /* Status 1 - Active but not clickable for spectators */
        .round-card.status-active.not-clickable {
            cursor: not-allowed;
            opacity: 0.6;
            animation: none;
        }

        .round-card.status-active.not-clickable:hover {
            transform: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.5);
        }

        /* Status 2 - Completed */
        .round-card.status-completed {
            border: 2px solid #608b4e;
            background: linear-gradient(135deg, #252526 0%, #2d2d30 100%);
        }

        .round-card.status-completed::before {
            background: linear-gradient(90deg, #608b4e, #6a9955, #608b4e);
        }

        .round-card.status-completed:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 30px rgba(96, 139, 78, 0.4);
        }

        .round-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
        }

        .round-number {
            font-size: 3rem;
            font-weight: 700;
            line-height: 1;
            color: #569cd6;
            text-shadow: 2px 2px 0px #3e3e42;
        }

        .status-pending .round-number {
            color: #6e6e6e;
            text-shadow: 2px 2px 0px #3e3e42;
        }

        .status-active .round-number {
            color: #4ec9b0;
            text-shadow: 2px 2px 0px #3e3e42;
        }

        .status-completed .round-number {
            color: #608b4e;
            text-shadow: 2px 2px 0px #3e3e42;
        }

        .round-badge {
            padding: 0.5rem 1rem;
            border-radius: 4px;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            border: 1px solid;
        }

        .badge-pending {
            background: #3e3e42;
            color: #858585;
            border-color: #4e4e52;
        }

        .badge-active {
            background: #0e4429;
            color: #4ec9b0;
            border-color: #4ec9b0;
            animation: badge-blink 2s ease-in-out infinite;
        }

        @keyframes badge-blink {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.7;
            }
        }

        .badge-completed {
            background: #1e3a1e;
            color: #6a9955;
            border-color: #608b4e;
        }

        .badge-merge {
            background: #1a1a2e;
            color: #569cd6;
            border-color: #569cd6;
            margin-left: 0.5rem;
        }

        .round-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #d4d4d4;
            margin-bottom: 0.5rem;
        }

        .status-pending .round-title {
            color: #858585;
        }

        .status-active .round-title {
            color: #4ec9b0;
        }

        .status-completed .round-title {
            color: #6a9955;
        }

        .round-description {
            color: #a0a0a0;
            font-size: 0.95rem;
            line-height: 1.6;
            margin-bottom: 1rem;
            font-weight: 400;
        }

        .status-pending .round-description {
            color: #6e6e6e;
        }

        .round-info {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #3e3e42;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            color: #a0a0a0;
            font-weight: 500;
        }

        .info-icon {
            font-size: 1rem;
            color: #569cd6;
        }

        .action-text {
            text-align: center;
            margin-top: 1.5rem;
            font-weight: 600;
            font-size: 1rem;
            padding: 0.75rem;
            border-radius: 4px;
            border: 1px solid;
        }

        .status-pending .action-text {
            color: #858585;
            background: #1e1e1e;
            border-color: #3e3e42;
        }

        .status-active .action-text {
            color: #4ec9b0;
            background: linear-gradient(135deg, #0e4429, #1e5a3e);
            border-color: #4ec9b0;
        }

        .status-completed .action-text {
            color: #6a9955;
            background: linear-gradient(135deg, #1e3a1e, #2e4a2e);
            border-color: #608b4e;
        }

        .status-active.not-clickable .action-text {
            color: #858585;
            background: #1e1e1e;
            border-color: #3e3e42;
        }

        .no-rounds {
            text-align: center;
            padding: 4rem;
            color: #a0a0a0;
            font-size: 1.25rem;
            font-weight: 500;
            background: #252526;
            border-radius: 8px;
            border: 2px solid #3e3e42;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.5);
        }

        /* Group Selection Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(4px);
            animation: fadeIn 0.3s;
        }

        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        .modal-content {
            background: #2d2d30;
            border-radius: 8px;
            padding: 2.5rem;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.8);
            border: 2px solid #3e3e42;
            animation: slideUp 0.3s;
            position: relative;
        }

        .modal-content::before {
            content: '>';
            position: absolute;
            top: 15px;
            right: 25px;
            font-size: 2rem;
            color: #4ec9b0;
            opacity: 0.3;
        }

        @keyframes slideUp {
            from {
                transform: translateY(50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            font-size: 1.75rem;
            font-weight: 600;
            color: #4ec9b0;
            margin-bottom: 1rem;
            text-align: center;
        }

        .modal-description {
            color: #a0a0a0;
            text-align: center;
            margin-bottom: 2rem;
            font-weight: 400;
            font-size: 1rem;
        }

        .group-buttons {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .group-button {
            padding: 1.75rem 1rem;
            background: linear-gradient(135deg, #0e639c, #1177bb);
            color: #d4d4d4;
            border: 2px solid #569cd6;
            border-radius: 4px;
            font-weight: 600;
            font-size: 1.25rem;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(14, 99, 156, 0.4);
        }

        .group-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(14, 99, 156, 0.6);
            background: linear-gradient(135deg, #1177bb, #1c88d1);
            border-color: #4ec9b0;
        }

        /* Staff Action Modal */
        .action-buttons {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .action-button {
            padding: 1.75rem 1rem;
            background: linear-gradient(135deg, #0e4429, #1e5a3e);
            color: #d4d4d4;
            border: 2px solid #4ec9b0;
            border-radius: 4px;
            font-weight: 600;
            font-size: 1.25rem;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(14, 68, 41, 0.4);
        }

        .action-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(14, 68, 41, 0.6);
            background: linear-gradient(135deg, #1e5a3e, #2e6a4e);
            border-color: #6a9955;
        }

        .cancel-button {
            width: 100%;
            padding: 1rem;
            background: #1e1e1e;
            border: 2px solid #3e3e42;
            border-radius: 4px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            color: #a0a0a0;
            font-size: 1rem;
        }

        .cancel-button:hover {
            background: #252526;
            border-color: #569cd6;
            color: #d4d4d4;
        }

        .guest-message {
            background: #252526;
            padding: 3rem;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
            border: 2px solid #3e3e42;
            position: relative;
        }

        .guest-message::before {
            content: '//';
            position: absolute;
            top: 20px;
            left: 30px;
            font-size: 2rem;
            color: #608b4e;
            opacity: 0.3;
        }

        .guest-message::after {
            content: '>';
            position: absolute;
            bottom: 20px;
            right: 30px;
            font-size: 2rem;
            color: #4ec9b0;
            opacity: 0.3;
        }

        .guest-message h3 {
            font-size: 1.75rem;
            color: #4ec9b0;
            margin-bottom: 1rem;
            font-weight: 600;
        }

        .guest-message p {
            color: #a0a0a0;
            margin-bottom: 1.5rem;
            font-weight: 400;
            font-size: 1.1rem;
        }

        @media (max-width: 768px) {
            .rounds-grid {
                grid-template-columns: 1fr;
            }

            .group-buttons,
            .action-buttons {
                grid-template-columns: 1fr;
            }

            .round-number {
                font-size: 2.5rem;
            }
        }
    </style>

    <div class="rounds-container">
        @guest
            <div class="guest-message">
                <h3>Welcome to Mic Drop Season 18</h3>
                <p>Please log in to participate in rounds and view results</p>
            </div>
        @endguest

        @auth
            @if($rounds->isEmpty())
                <div class="no-rounds">
                    No rounds have been created yet. Check back soon.
                </div>
            @else
                <div class="rounds-grid">
                    @foreach($rounds as $round)
                        @php
                            $statusClass = match ($round->status) {
                                0 => 'status-pending',
                                1 => 'status-active',
                                2 => 'status-completed',
                                default => 'status-pending'
                            };

                            // Spectators can't click active rounds
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
                                {{-- Coming soon - hide details --}}
                                <h3 class="round-title">Round {{ $round->round_number }}</h3>
                                <p class="round-description">Details will be revealed when the round starts</p>
                            @else
                                {{-- Active or completed - show details --}}
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
                <button class="group-button" onclick="selectGroup(1)">
                    Group 1
                </button>
                <button class="group-button" onclick="selectGroup(2)">
                    Group 2
                </button>
                <button class="group-button" onclick="selectGroup(3)">
                    Group 3
                </button>
            </div>

            <button class="cancel-button" onclick="closeModal()">Cancel</button>
        </div>
    </div>

    <!-- Staff Action Modal (for staff choosing action) -->
    <div id="staffModal" class="modal">
        <div class="modal-content">
            <h2 class="modal-header">Choose Action</h2>
            <p class="modal-description">What would you like to do?</p>

            <div class="action-buttons">
                <button class="action-button" onclick="staffAction('submit')">
                    View submission form
                </button>
                <button class="action-button" onclick="staffAction('judge')">
                    View judging sheets
                </button>
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
                <button class="group-button" onclick="selectStaffGroup(1)">
                    Group 1
                </button>
                <button class="group-button" onclick="selectStaffGroup(2)">
                    Group 2
                </button>
                <button class="group-button" onclick="selectStaffGroup(3)">
                    Group 3
                </button>
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
            // Status 0 - Do nothing (coming soon)
            if (status === 0) {
                return;
            }

            // Spectators can't interact with active rounds
            if (status === 1 && userRole === 'spectator') {
                return;
            }

            currentRound = roundNumber;
            currentIsMerge = isMerge;

            // Status 1 - Active round
            if (status === 1) {
                if (userRole === 'contestant') {
                    // Contestant - direct to submission form
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
                    // Judge - direct to judging page
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
                    // Staff - show action selection modal
                    openStaffModal();
                }
            }

            // Status 2 - Completed round (results)
            if (status === 2) {
                if (isMerge) {
                    // Merge round - direct to results
                    window.location.href = `/results?round=${roundNumber}`;
                } else {
                    // Group round - show modal to select group
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
            // Close only the staff modal, don't reset variables
            document.getElementById('staffModal').classList.remove('show');

            if (currentIsMerge) {
                // Merge round - direct to page
                if (action === 'submit') {
                    window.location.href = `/submit?round=${currentRound}`;
                } else if (action === 'judge') {
                    window.location.href = `/judging?round=${currentRound}`;
                }
            } else {
                // Group round - ask for group
                if (action === 'submit') {
                    document.getElementById('staffGroupDescription').textContent = 'Which group would you like to submit for?';
                } else if (action === 'judge') {
                    document.getElementById('staffGroupDescription').textContent = 'Which group would you like to judge?';
                }
                openStaffGroupModal();
            }
        }

        function selectStaffGroup(group) {
            console.log(staffChosenAction);
            if (staffChosenAction === 'submit') {

                window.location.href = `/submit?round=${currentRound}&group=${group}`;
            } else if (staffChosenAction === 'judge') {
                window.location.href = `/judging?round=${currentRound}&group=${group}`;
            }
            closeModal();
        }

        // Close modal when clicking outside
        window.onclick = function (event) {
            const groupModal = document.getElementById('groupModal');
            const staffModal = document.getElementById('staffModal');
            const staffGroupModal = document.getElementById('staffGroupModal');

            if (event.target === groupModal || event.target === staffModal || event.target === staffGroupModal) {
                closeModal();
            }
        }

        // Close modal with Escape key
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeModal();
            }
        });
    </script>
</x-app-layout>