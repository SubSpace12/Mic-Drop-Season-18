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
        @import url('https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700;800&display=swap');

        * {
            font-family: 'Nunito', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #fff0f6 0%, #ffe0f0 50%, #ffd5eb 100%);
        }

        .rounds-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        .welcome-message {
            background: linear-gradient(135deg, #ff9ed8 0%, #ffb3e6 50%, #ffc9f0 100%);
            color: white;
            padding: 2.5rem;
            border-radius: 25px;
            margin-bottom: 2rem;
            box-shadow: 0 8px 30px rgba(255, 158, 216, 0.4);
            border: 3px solid #ffd5eb;
            position: relative;
            overflow: hidden;
        }

        .welcome-message::before {
            content: '✧･ﾟ: *✧･ﾟ:*';
            position: absolute;
            top: 10px;
            right: 20px;
            font-size: 1.5rem;
            opacity: 0.6;
        }

        .welcome-message::after {
            content: '♡';
            position: absolute;
            bottom: 10px;
            left: 20px;
            font-size: 2rem;
            opacity: 0.4;
        }

        .welcome-message h1 {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            text-shadow: 2px 2px 4px rgba(255, 105, 180, 0.3);
        }

        .welcome-message p {
            font-size: 1.125rem;
            opacity: 0.95;
            font-weight: 600;
        }

        .rounds-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .round-card {
            background: white;
            border-radius: 25px;
            padding: 2rem;
            box-shadow: 0 5px 25px rgba(255, 182, 223, 0.3);
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            border: 3px solid #ffb3e6;
        }

        .round-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 8px;
            background: linear-gradient(90deg, #ff9ed8, #ffb3e6, #ffc9f0, #ffe0f0);
        }

        .round-card::after {
            content: '♡';
            position: absolute;
            bottom: 15px;
            right: 15px;
            font-size: 3rem;
            opacity: 0.08;
            color: #ff9ed8;
        }

        /* Status 0 - Coming Soon */
        .round-card.status-pending {
            background: #fff5fa;
            cursor: not-allowed;
            opacity: 0.7;
            border-color: #ffd5eb;
        }

        .round-card.status-pending::before {
            background: linear-gradient(90deg, #e8b4d4, #f0c9e0);
        }

        .round-card.status-pending:hover {
            transform: none;
        }

        /* Status 1 - Active */
        .round-card.status-active {
            border: 3px solid #ff69b4;
            background: linear-gradient(135deg, #fff 0%, #fff5fa 100%);
            animation: gentle-pulse 2s ease-in-out infinite;
        }

        @keyframes gentle-pulse {
            0%, 100% {
                box-shadow: 0 5px 25px rgba(255, 105, 180, 0.4);
            }
            50% {
                box-shadow: 0 8px 35px rgba(255, 105, 180, 0.6);
            }
        }

        .round-card.status-active::before {
            background: linear-gradient(90deg, #ff69b4, #ff85c1, #ff9ed8);
        }

        .round-card.status-active:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 12px 40px rgba(255, 105, 180, 0.5);
        }

        /* Status 1 - Active but not clickable for spectators */
        .round-card.status-active.not-clickable {
            cursor: not-allowed;
            opacity: 0.7;
            animation: none;
        }

        .round-card.status-active.not-clickable:hover {
            transform: none;
            box-shadow: 0 5px 25px rgba(255, 182, 223, 0.3);
        }

        /* Status 2 - Completed */
        .round-card.status-completed {
            border: 3px solid #c77dff;
            background: linear-gradient(135deg, #fff 0%, #f8f0ff 100%);
        }

        .round-card.status-completed::before {
            background: linear-gradient(90deg, #c77dff, #d4a5ff, #e0ccff);
        }

        .round-card.status-completed:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 12px 40px rgba(199, 125, 255, 0.4);
        }

        .round-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
        }

        .round-number {
            font-size: 3rem;
            font-weight: 900;
            line-height: 1;
            color: #ff9ed8;
            text-shadow: 3px 3px 0px #ffb3e6, 6px 6px 0px #ffc9f0;
        }

        .status-pending .round-number {
            color: #e8b4d4;
            text-shadow: 2px 2px 0px #f0c9e0;
        }

        .status-active .round-number {
            color: #ff69b4;
            text-shadow: 3px 3px 0px #ff85c1, 6px 6px 0px #ff9ed8;
        }

        .status-completed .round-number {
            color: #c77dff;
            text-shadow: 3px 3px 0px #d4a5ff, 6px 6px 0px #e0ccff;
        }

        .round-badge {
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            border: 2px solid;
        }

        .badge-pending {
            background: linear-gradient(135deg, #f0c9e0, #f5d9e8);
            color: #d47fa8;
            border-color: #e8b4d4;
        }

        .badge-active {
            background: linear-gradient(135deg, #ff9ed8, #ffb3e6);
            color: white;
            border-color: #ff69b4;
            animation: badge-shimmer 2s ease-in-out infinite;
        }

        @keyframes badge-shimmer {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
        }

        .badge-completed {
            background: linear-gradient(135deg, #c77dff, #d4a5ff);
            color: white;
            border-color: #b565f0;
        }

        .badge-merge {
            background: linear-gradient(135deg, #ffc9f0, #ffe0f0);
            color: #d47fa8;
            border-color: #ffb3e6;
            margin-left: 0.5rem;
        }

        .round-title {
            font-size: 1.5rem;
            font-weight: 800;
            color: #d47fa8;
            margin-bottom: 0.5rem;
            text-shadow: 1px 1px 2px rgba(255, 182, 223, 0.3);
        }

        .status-pending .round-title {
            color: #e8b4d4;
        }

        .status-active .round-title {
            color: #ff69b4;
        }

        .status-completed .round-title {
            color: #b565f0;
        }

        .round-description {
            color: #d47fa8;
            font-size: 0.95rem;
            line-height: 1.6;
            margin-bottom: 1rem;
            font-weight: 600;
        }

        .status-pending .round-description {
            color: #e8b4d4;
        }

        .round-info {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 2px dashed #ffe0f0;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            color: #d47fa8;
            font-weight: 700;
        }

        .info-icon {
            font-size: 1.25rem;
        }

        .action-text {
            text-align: center;
            margin-top: 1.5rem;
            font-weight: 800;
            font-size: 1.1rem;
            padding: 0.75rem;
            border-radius: 50px;
            border: 2px solid;
        }

        .status-pending .action-text {
            color: #d47fa8;
            background: #fff5fa;
            border-color: #ffe0f0;
        }

        .status-active .action-text {
            color: white;
            background: linear-gradient(135deg, #ff69b4, #ff85c1);
            border-color: #ff69b4;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.2);
        }

        .status-completed .action-text {
            color: white;
            background: linear-gradient(135deg, #c77dff, #d4a5ff);
            border-color: #b565f0;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.2);
        }

        .status-active.not-clickable .action-text {
            color: #d47fa8;
            background: #fff5fa;
            border-color: #ffe0f0;
        }

        .no-rounds {
            text-align: center;
            padding: 4rem;
            color: #d47fa8;
            font-size: 1.25rem;
            font-weight: 700;
            background: white;
            border-radius: 25px;
            border: 3px solid #ffe0f0;
            box-shadow: 0 5px 25px rgba(255, 182, 223, 0.3);
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
            background-color: rgba(255, 182, 223, 0.7);
            backdrop-filter: blur(8px);
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
            background: white;
            border-radius: 30px;
            padding: 2.5rem;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 15px 50px rgba(255, 105, 180, 0.4);
            border: 4px solid #ffb3e6;
            animation: slideUp 0.3s;
            position: relative;
        }

        .modal-content::before {
            content: '♡';
            position: absolute;
            top: -15px;
            right: 30px;
            font-size: 2.5rem;
            color: #ff69b4;
            text-shadow: 2px 2px 4px rgba(255, 105, 180, 0.3);
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
            font-weight: 900;
            color: #ff69b4;
            margin-bottom: 1rem;
            text-align: center;
            text-shadow: 2px 2px 4px rgba(255, 182, 223, 0.3);
        }

        .modal-description {
            color: #d47fa8;
            text-align: center;
            margin-bottom: 2rem;
            font-weight: 600;
            font-size: 1.1rem;
        }

        .group-buttons {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .group-button {
            padding: 1.75rem 1rem;
            background: linear-gradient(135deg, #ff9ed8, #ffb3e6);
            color: white;
            border: 3px solid #ff69b4;
            border-radius: 20px;
            font-weight: 800;
            font-size: 1.25rem;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 6px 20px rgba(255, 105, 180, 0.4);
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.2);
        }

        .group-button:hover {
            transform: translateY(-5px) scale(1.05);
            box-shadow: 0 10px 30px rgba(255, 105, 180, 0.6);
            background: linear-gradient(135deg, #ff69b4, #ff85c1);
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
            background: linear-gradient(135deg, #c77dff, #d4a5ff);
            color: white;
            border: 3px solid #b565f0;
            border-radius: 20px;
            font-weight: 800;
            font-size: 1.25rem;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 6px 20px rgba(199, 125, 255, 0.4);
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.2);
        }

        .action-button:hover {
            transform: translateY(-5px) scale(1.05);
            box-shadow: 0 10px 30px rgba(199, 125, 255, 0.6);
            background: linear-gradient(135deg, #b565f0, #c77dff);
        }

        .cancel-button {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #fff0f6, #ffe0f0);
            border: 3px solid #ffb3e6;
            border-radius: 50px;
            font-weight: 800;
            cursor: pointer;
            transition: all 0.3s;
            color: #d47fa8;
            font-size: 1.1rem;
        }

        .cancel-button:hover {
            background: linear-gradient(135deg, #ffe0f0, #ffd5eb);
            transform: scale(1.02);
        }

        .guest-message {
            background: white;
            padding: 3rem;
            border-radius: 30px;
            text-align: center;
            box-shadow: 0 8px 35px rgba(255, 182, 223, 0.4);
            border: 4px solid #ffb3e6;
            position: relative;
        }

        .guest-message::before {
            content: '✧';
            position: absolute;
            top: 20px;
            left: 30px;
            font-size: 2rem;
            color: #ff9ed8;
            opacity: 0.5;
        }

        .guest-message::after {
            content: '✧';
            position: absolute;
            bottom: 20px;
            right: 30px;
            font-size: 2rem;
            color: #ffb3e6;
            opacity: 0.5;
        }

        .guest-message h3 {
            font-size: 1.75rem;
            color: #ff69b4;
            margin-bottom: 1rem;
            font-weight: 900;
            text-shadow: 2px 2px 4px rgba(255, 182, 223, 0.3);
        }

        .guest-message p {
            color: #d47fa8;
            margin-bottom: 1.5rem;
            font-weight: 700;
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

        /* Cute sparkle animation */
        @keyframes sparkle {
            0%, 100% {
                opacity: 0;
                transform: scale(0);
            }
            50% {
                opacity: 1;
                transform: scale(1);
            }
        }
    </style>

    <div class="rounds-container">
        @guest
            <div class="guest-message">
                <h3>💖 Welcome to Mic Drop Season 18! 💖</h3>
                <p>Please log in to participate in rounds and view results~ ✨</p>
            </div>
        @endguest

        @auth
            @if($rounds->isEmpty())
                <div class="no-rounds">
                    No rounds have been created yet~ Check back soon! (◕‿◕)♡
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
                                        <span class="round-badge badge-merge">🔀 Merge</span>
                                    @endif
                                </div>
                            </div>

                            @if($round->status == 0)
                                {{-- Coming soon - hide details --}}
                                <h3 class="round-title">Round {{ $round->round_number }}</h3>
                                <p class="round-description">Details will be revealed when the round starts~ ✨</p>
                            @else
                                {{-- Active or completed - show details --}}
                                <h3 class="round-title">{{ $round->title }}</h3>
                                <p class="round-description">{{ $round->description }}</p>
                            @endif

                            @if($round->status != 0)
                                <div class="round-info">
                                    <div class="info-item">
                                        <span class="info-icon">📅</span>
                                        <span>{{ date('M j, Y', strtotime($round->deadline)) }}</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-icon">👥</span>
                                        <span>{{ $round->is_merge ? 'All Groups' : '3 Groups' }}</span>
                                    </div>
                                </div>
                            @endif

                            @if($round->status == 0)
                                <div class="action-text">⏳ Coming Soon~</div>
                            @elseif($round->status == 1)
                                <div class="action-text">
                                    @if($userRole == 'spectator')
                                        👁️ Spectating
                                    @elseif($userRole == 'contestant')
                                        🎤 Submit Songs ♡
                                    @elseif($userRole == 'judge')
                                        ⚖️ Judge Now!
                                    @elseif($userRole == 'staff')
                                        🛠️ Staff Options
                                    @else
                                        ℹ️ Active Round
                                    @endif
                                </div>
                            @elseif($round->status == 2)
                                <div class="action-text">📊 View Results ✨</div>
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
            <h2 class="modal-header">Select a Group~ ♡</h2>
            <p class="modal-description">Which group's results would you like to view? ✨</p>

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
            <h2 class="modal-header">Choose Action~ ♡</h2>
            <p class="modal-description">What would you like to do? ✨</p>

            <div class="action-buttons">
                <button class="action-button" onclick="staffAction('submit')">
                    🎤 View submission form
                </button>
                <button class="action-button" onclick="staffAction('judge')">
                    ⚖️ View judging sheets
                </button>
            </div>

            <button class="cancel-button" onclick="closeModal()">Cancel</button>
        </div>
    </div>

    <!-- Staff Group Selection Modal -->
    <div id="staffGroupModal" class="modal">
        <div class="modal-content">
            <h2 class="modal-header">Select a Group~ ♡</h2>
            <p class="modal-description" id="staffGroupDescription">Which group would you like to work with? ✨</p>

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
                    document.getElementById('staffGroupDescription').textContent = 'Which group would you like to submit for? ✨';
                } else if (action === 'judge') {
                    document.getElementById('staffGroupDescription').textContent = 'Which group would you like to judge? ✨';
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