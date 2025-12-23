<nav x-data="{ open: false }">
    <!-- Primary Navigation Menu -->
    @auth
    @php
        // Check if user has submitted a judge application
        $hasJudgeApp = DB::table('apps')->where('user_id', auth()->id())->exists();
        
        // Check if user is staff
        $isStaff = (auth()->user()->perms ?? 0) >= 6;
    @endphp
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            
            <div class="flex">
                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ml-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" class="nav-link">
                        {{ __('Mic Drop Season 18') }}
                    </x-nav-link>
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ml-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="user-profile-button">
                            <img class="user-avatar" 
                                 src="{{ Auth::user()->getAvatar(['extension' => 'webp', 'size' => 32]) }}" 
                                 alt="{{ Auth::user()->getTagAttribute() }}" />

                            <div class="user-info">
                                <span class="user-tag">{{ Auth::user()->global_name ?? Auth::user()->username }}</span>
                            </div>

                            <div class="ml-1">
                                <svg class="fill-current h-4 w-4" style="color: #4ec9b0;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <div class="dropdown-content">
                       
                            <x-dropdown-link :href="url('/submit-judge-app')" class="dropdown-link">
                                {{ $hasJudgeApp ? __('Edit Judging Application') : __('Submit Judging Application') }}
                            </x-dropdown-link>

                            @if($isStaff)
                                <!-- Divider -->
                                <div class="dropdown-divider"></div>
                                
                                <x-dropdown-link :href="url('/view-apps')" class="dropdown-link">
                                    {{ __('View Judging Applications') }}
                                </x-dropdown-link>
                                
                                <x-dropdown-link :href="url('/admin')" class="dropdown-link">
                                    {{ __('Admin Panel') }}
                                </x-dropdown-link>
                                
                                <!-- Divider -->
                                <div class="dropdown-divider"></div>
                            @endif

                            <!-- Authentication -->
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf

                                <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault(); this.closest('form').submit();"
                                    class="dropdown-link">
                                    {{ __('Log Out') }}
                                </x-dropdown-link>
                            </form>
                        </div>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-mr-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="hamburger-button">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden responsive-nav-menu">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" class="responsive-nav-link">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 responsive-user-info">
            <div class="px-4">
                <div class="responsive-user-name">{{ Auth::user()->name }}</div>
                <div class="responsive-user-email">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                

                <x-responsive-nav-link :href="url('/submit-judge-app')" class="responsive-nav-link">
                    {{ $hasJudgeApp ? __('Edit Judging Application') : __('Submit Judging Application') }}
                </x-responsive-nav-link>

                @if($isStaff)
                    <x-responsive-nav-link :href="url('/view-apps')" class="responsive-nav-link">
                        {{ __('View Judging Applications') }}
                    </x-responsive-nav-link>
                    
                    <x-responsive-nav-link :href="url('/admin')" class="responsive-nav-link">
                        {{ __('Admin Panel') }}
                    </x-responsive-nav-link>
                @endif

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                        onclick="event.preventDefault(); this.closest('form').submit();"
                        class="responsive-nav-link">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
    @endauth
    
    @guest
    <div class="login-button-container">
        <a href="{{ route('login') }}" class="login-button">
            <svg class="login-icon" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M3 3a1 1 0 011 1v12a1 1 0 11-2 0V4a1 1 0 011-1zm7.707 3.293a1 1 0 010 1.414L9.414 9H17a1 1 0 110 2H9.414l1.293 1.293a1 1 0 01-1.414 1.414l-3-3a1 1 0 010-1.414l3-3a1 1 0 011.414 0z" clip-rule="evenodd"/>
            </svg>
            Login with Discord
        </a>
    </div>
    @endguest
</nav>