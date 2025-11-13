<nav x-data="{ open: false }" style="background: white; border-bottom: 3px solid #ffb3e6; box-shadow: 0 4px 15px rgba(255, 182, 223, 0.2);">
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
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" 
                        style="color: #ff69b4; font-weight: 700; border-bottom: 3px solid transparent; transition: all 0.3s;"
                        onmouseover="this.style.borderBottomColor='#ff69b4'; this.style.color='#ff85c1';"
                        onmouseout="this.style.borderBottomColor='transparent'; this.style.color='#ff69b4';">
                        {{ __('Mic Drop Season 18') }} ♡
                    </x-nav-link>
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ml-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button style="display: inline-flex; align-items: center; padding: 0.5rem 0.75rem; border: 2px solid #ffb3e6; border-radius: 50px; background: linear-gradient(135deg, #fff0f6, #ffe0f0); color: #d47fa8; font-weight: 700; transition: all 0.3s; box-shadow: 0 2px 8px rgba(255, 182, 223, 0.2);"
                            onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(255, 182, 223, 0.4)'; this.style.background='linear-gradient(135deg, #ffe0f0, #ffd5eb)';"
                            onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(255, 182, 223, 0.2)'; this.style.background='linear-gradient(135deg, #fff0f6, #ffe0f0)';">
                            <img class="h-8 w-8 rounded-full object-cover mr-2" style="border: 2px solid #ff69b4;" src="{{ Auth::user()->getAvatar(['extension' => 'webp', 'size' => 32]) }}" alt="{{ Auth::user()->getTagAttribute() }}" />

                            <div style="display: flex; flex-direction: column; align-items: flex-start;">
                                <span style="color: #ff69b4; font-weight: 800;">{{ Auth::user()->getTagAttribute() }}</span>
                                @if (Auth::user()->global_name)
                                    <small style="color: #d47fa8; font-weight: 600;">{{ Auth::user()->username }}</small>
                                @endif
                            </div>

                            <div class="ml-1">
                                <svg class="fill-current h-4 w-4" style="color: #ff69b4;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <div style="background: white; border-radius: 15px; border: 2px solid #ffb3e6; box-shadow: 0 8px 25px rgba(255, 182, 223, 0.3); overflow: hidden;">
                            <x-dropdown-link :href="route('profile.edit')" 
                                style="color: #d47fa8; font-weight: 700; padding: 0.75rem 1rem; transition: all 0.3s;"
                                onmouseover="this.style.background='linear-gradient(135deg, #fff0f6, #ffe0f0)'; this.style.color='#ff69b4'; this.style.paddingLeft='1.5rem';"
                                onmouseout="this.style.background='white'; this.style.color='#d47fa8'; this.style.paddingLeft='1rem';">
                                {{ __('Profile') }}
                            </x-dropdown-link>

                            <x-dropdown-link :href="url('/submit-judge-app')"
                                style="color: #d47fa8; font-weight: 700; padding: 0.75rem 1rem; transition: all 0.3s;"
                                onmouseover="this.style.background='linear-gradient(135deg, #fff0f6, #ffe0f0)'; this.style.color='#ff69b4'; this.style.paddingLeft='1.5rem';"
                                onmouseout="this.style.background='white'; this.style.color='#d47fa8'; this.style.paddingLeft='1rem';">
                                {{ $hasJudgeApp ? __('Edit Judging Application') : __('Submit Judging Application') }}
                            </x-dropdown-link>

                            @if($isStaff)
                                <!-- Divider -->
                                <div style="border-top: 2px dashed #ffe0f0; margin: 0.5rem 0;"></div>
                                
                                <x-dropdown-link :href="url('/view-apps')"
                                    style="color: #d47fa8; font-weight: 700; padding: 0.75rem 1rem; transition: all 0.3s;"
                                    onmouseover="this.style.background='linear-gradient(135deg, #fff0f6, #ffe0f0)'; this.style.color='#ff69b4'; this.style.paddingLeft='1.5rem';"
                                    onmouseout="this.style.background='white'; this.style.color='#d47fa8'; this.style.paddingLeft='1rem';">
                                    {{ __('View Judging Applications') }}
                                </x-dropdown-link>
                                
                                <x-dropdown-link :href="url('/admin')"
                                    style="color: #d47fa8; font-weight: 700; padding: 0.75rem 1rem; transition: all 0.3s;"
                                    onmouseover="this.style.background='linear-gradient(135deg, #fff0f6, #ffe0f0)'; this.style.color='#ff69b4'; this.style.paddingLeft='1.5rem';"
                                    onmouseout="this.style.background='white'; this.style.color='#d47fa8'; this.style.paddingLeft='1rem';">
                                    {{ __('Admin Panel') }}
                                </x-dropdown-link>
                                
                                <!-- Divider -->
                                <div style="border-top: 2px dashed #ffe0f0; margin: 0.5rem 0;"></div>
                            @endif

                            <!-- Authentication -->
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf

                                <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault(); this.closest('form').submit();"
                                    style="color: #d47fa8; font-weight: 700; padding: 0.75rem 1rem; transition: all 0.3s;"
                                    onmouseover="this.style.background='linear-gradient(135deg, #fff0f6, #ffe0f0)'; this.style.color='#ff69b4'; this.style.paddingLeft='1.5rem';"
                                    onmouseout="this.style.background='white'; this.style.color='#d47fa8'; this.style.paddingLeft='1rem';">
                                    {{ __('Log Out') }}
                                </x-dropdown-link>
                            </form>
                        </div>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-mr-2 flex items-center sm:hidden">
                <button @click="open = ! open" style="padding: 0.5rem; border-radius: 12px; color: #ff69b4; background: #fff0f6; border: 2px solid #ffb3e6; transition: all 0.3s;"
                    onmouseover="this.style.background='#ffe0f0'; this.style.transform='scale(1.05)';"
                    onmouseout="this.style.background='#fff0f6'; this.style.transform='scale(1)';">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden" style="background: linear-gradient(135deg, #fff0f6, #ffe0f0); border-top: 2px solid #ffb3e6;">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')"
                style="color: #ff69b4; font-weight: 700; padding: 0.75rem 1rem; margin: 0.25rem 0.5rem; border-radius: 12px; transition: all 0.3s;"
                onmouseover="this.style.background='white'; this.style.paddingLeft='1.5rem';"
                onmouseout="this.style.background='transparent'; this.style.paddingLeft='1rem';">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1" style="border-top: 2px dashed #ffe0f0;">
            <div class="px-4">
                <div style="font-weight: 800; color: #ff69b4;">{{ Auth::user()->name }}</div>
                <div style="font-weight: 600; color: #d47fa8; font-size: 0.875rem;">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')"
                    style="color: #ff69b4; font-weight: 700; padding: 0.75rem 1rem; margin: 0.25rem 0.5rem; border-radius: 12px; transition: all 0.3s;"
                    onmouseover="this.style.background='white'; this.style.paddingLeft='1.5rem';"
                    onmouseout="this.style.background='transparent'; this.style.paddingLeft='1rem';">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <x-responsive-nav-link :href="url('/submit-judge-app')"
                    style="color: #ff69b4; font-weight: 700; padding: 0.75rem 1rem; margin: 0.25rem 0.5rem; border-radius: 12px; transition: all 0.3s;"
                    onmouseover="this.style.background='white'; this.style.paddingLeft='1.5rem';"
                    onmouseout="this.style.background='transparent'; this.style.paddingLeft='1rem';">
                    {{ $hasJudgeApp ? __('Edit Judging Application') : __('Submit Judging Application') }}
                </x-responsive-nav-link>

                @if($isStaff)
                    <x-responsive-nav-link :href="url('/view-apps')"
                        style="color: #ff69b4; font-weight: 700; padding: 0.75rem 1rem; margin: 0.25rem 0.5rem; border-radius: 12px; transition: all 0.3s;"
                        onmouseover="this.style.background='white'; this.style.paddingLeft='1.5rem';"
                        onmouseout="this.style.background='transparent'; this.style.paddingLeft='1rem';">
                        {{ __('View Judging Applications') }}
                    </x-responsive-nav-link>
                    
                    <x-responsive-nav-link :href="url('/admin')"
                        style="color: #ff69b4; font-weight: 700; padding: 0.75rem 1rem; margin: 0.25rem 0.5rem; border-radius: 12px; transition: all 0.3s;"
                        onmouseover="this.style.background='white'; this.style.paddingLeft='1.5rem';"
                        onmouseout="this.style.background='transparent'; this.style.paddingLeft='1rem';">
                        {{ __('Admin Panel') }}
                    </x-responsive-nav-link>
                @endif

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                        onclick="event.preventDefault(); this.closest('form').submit();"
                        style="color: #ff69b4; font-weight: 700; padding: 0.75rem 1rem; margin: 0.25rem 0.5rem; border-radius: 12px; transition: all 0.3s;"
                        onmouseover="this.style.background='white'; this.style.paddingLeft='1.5rem';"
                        onmouseout="this.style.background='transparent'; this.style.paddingLeft='1rem';">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
    @endauth
    @guest
    <div style="display: flex; justify-content: center; align-items: center; padding: 1rem;">
        <a href="{{ route('login') }}" style="display: inline-flex; align-items: center; padding: 0.75rem 2rem; background: linear-gradient(135deg, #ff69b4, #ff85c1); color: white; font-weight: 800; border-radius: 50px; transition: all 0.3s; text-decoration: none; box-shadow: 0 4px 15px rgba(255, 105, 180, 0.4); border: 2px solid #ff69b4;"
            onmouseover="this.style.transform='translateY(-3px) scale(1.05)'; this.style.boxShadow='0 8px 25px rgba(255, 105, 180, 0.6)'; this.style.background='linear-gradient(135deg, #ff85c1, #ff9ed8)';"
            onmouseout="this.style.transform='translateY(0) scale(1)'; this.style.boxShadow='0 4px 15px rgba(255, 105, 180, 0.4)'; this.style.background='linear-gradient(135deg, #ff69b4, #ff85c1)';">
            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M3 3a1 1 0 011 1v12a1 1 0 11-2 0V4a1 1 0 011-1zm7.707 3.293a1 1 0 010 1.414L9.414 9H17a1 1 0 110 2H9.414l1.293 1.293a1 1 0 01-1.414 1.414l-3-3a1 1 0 010-1.414l3-3a1 1 0 011.414 0z" clip-rule="evenodd"/>
            </svg>
            Login with Discord ♡
        </a>
    </div>
    @endguest
</nav>