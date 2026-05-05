<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl leading-tight">
            {{ $user->global_name ?? $user->username }}'s Profile
        </h2>
    </x-slot>

    @vite(['resources/css/profile.css'])


    <div class="profile-container">

        {{-- ── Profile Card ─────────────────────────────────────────── --}}
        <div class="profile-card">
            <div class="profile-avatar-wrapper">
                <img
                    src="{{ $avatarUrl }}"
                    alt="{{ $user->global_name ?? $user->username }}"
                    class="profile-avatar"
                    onerror="this.src='https://cdn.discordapp.com/embed/avatars/0.png'"
                >
            </div>

            <div class="profile-info">
                <div class="profile-name">{{ $user->global_name ?? $user->username }}</div>
                @if($user->global_name && $user->username)
                    <div class="profile-username">{{ $user->username }}</div>
                @endif
                <span class="profile-status-badge {{ $statusInfo['class'] }}">
                    <span class="status-dot"></span>
                    {{ $statusInfo['label'] }}
                </span>
            </div>
        </div>

        {{-- ── Contestant: Received Scores ─────────────────────────── --}}
        @if($contestantScores !== null)
            <div class="profile-section">
                <div class="profile-section-header">
                    <div>
                        <div class="profile-section-title">Received Scores</div>
                        <div class="profile-section-subtitle">As a contestant this season</div>
                    </div>
                </div>

                @if($contestantScores->isEmpty())
                    <div class="profile-no-data">
                        No scored submissions yet this season.
                    </div>
                @else
                    <div class="score-halves">
                        {{-- Highest 3 --}}
                        <div>
                            <div class="score-half-label highest">▲ Top 3 Scores</div>
                            @foreach($contestantHighest as $score)
                                <div class="score-card">
                                    <div class="score-card-top">
                                        <div class="score-card-song">
                                            <div class="score-card-artist">{{ $score->artist }}</div>
                                            <div class="score-card-title">{{ $score->title }}</div>
                                        </div>
                                        <div class="score-card-badge high">{{ $score->score }}</div>
                                    </div>
                                    <div class="score-card-meta">
                                        Round {{ $score->round }} · by
                                        <span class="meta-name">{{ $score->judge_name ?? $score->judge_username }}</span>
                                    </div>
                                    @if($score->review)
                                        <div class="score-card-review">{{ $score->review }}</div>
                                    @else
                                        <div class="score-card-review score-card-no-review">No review left.</div>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        {{-- Lowest 3 --}}
                        <div>
                            <div class="score-half-label lowest">▼ Bottom 3 Scores</div>
                            @foreach($contestantLowest as $score)
                                <div class="score-card">
                                    <div class="score-card-top">
                                        <div class="score-card-song">
                                            <div class="score-card-artist">{{ $score->artist }}</div>
                                            <div class="score-card-title">{{ $score->title }}</div>
                                        </div>
                                        <div class="score-card-badge low">{{ $score->score }}</div>
                                    </div>
                                    <div class="score-card-meta">
                                        Round {{ $score->round }} · by
                                        <span class="meta-name">{{ $score->judge_name ?? $score->judge_username }}</span>
                                    </div>
                                    @if($score->review)
                                        <div class="score-card-review">{{ $score->review }}</div>
                                    @else
                                        <div class="score-card-review score-card-no-review">No review left.</div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @endif

        {{-- ── Judge: Given Scores ──────────────────────────────────── --}}
        @if($judgeScores !== null)
            <div class="profile-section">
                <div class="profile-section-header">
                    <div>
                        <div class="profile-section-title">Given Scores</div>
                        <div class="profile-section-subtitle">As a judge this season</div>
                    </div>
                </div>

                @if($judgeScores->isEmpty())
                    <div class="profile-no-data">
                        <div class="profile-no-data-icon">📋</div>
                        No scores given yet this season.
                    </div>
                @else
                    <div class="score-halves">
                        {{-- Highest given --}}
                        <div>
                            <div class="score-half-label highest">▲ Top 3 Given</div>
                            @foreach($judgeHighest as $score)
                                <div class="score-card">
                                    <div class="score-card-top">
                                        <div class="score-card-song">
                                            <div class="score-card-artist">{{ $score->artist }}</div>
                                            <div class="score-card-title">{{ $score->title }}</div>
                                        </div>
                                        <div class="score-card-badge high">{{ $score->score }}</div>
                                    </div>
                                    <div class="score-card-meta">
                                        Round {{ $score->round }} · for
                                        <span class="meta-name">{{ $score->contestant_name ?? $score->contestant_username }}</span>
                                    </div>
                                    @if($score->review)
                                        <div class="score-card-review">{{ $score->review }}</div>
                                    @else
                                        <div class="score-card-review score-card-no-review">No review left.</div>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        {{-- Lowest given --}}
                        <div>
                            <div class="score-half-label lowest">▼ Bottom 3 Given</div>
                            @foreach($judgeLowest as $score)
                                <div class="score-card">
                                    <div class="score-card-top">
                                        <div class="score-card-song">
                                            <div class="score-card-artist">{{ $score->artist }}</div>
                                            <div class="score-card-title">{{ $score->title }}</div>
                                        </div>
                                        <div class="score-card-badge low">{{ $score->score }}</div>
                                    </div>
                                    <div class="score-card-meta">
                                        Round {{ $score->round }} · for
                                        <span class="meta-name">{{ $score->contestant_name ?? $score->contestant_username }}</span>
                                    </div>
                                    @if($score->review)
                                        <div class="score-card-review">{{ $score->review }}</div>
                                    @else
                                        <div class="score-card-review score-card-no-review">No review left.</div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @endif

        {{-- ── No active season / spectator fallback ───────────────── --}}
        @if(!$activeSeason)
            <div class="profile-section">
                <div class="profile-no-data">
                    <div class="profile-no-data-icon">📭</div>
                    No active season right now — check back later!
                </div>
            </div>
        @elseif($contestantScores === null && $judgeScores === null)
            <div class="profile-section">
                <div class="profile-no-data">
                    <div class="profile-no-data-icon">👀</div>
                    This user has not participated in the current season as a contestant or judge.
                </div>
            </div>
        @endif

    </div>
</x-app-layout>