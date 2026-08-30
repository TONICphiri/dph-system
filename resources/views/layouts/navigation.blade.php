<nav x-data="{ open: false }" class="bg-white border-b border-sky-100 shadow-sm">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-sky-700 text-white shadow-sm ring-4 ring-sky-50">
                            <span class="text-2xl font-bold leading-none">+</span>
                        </span>
                        <span class="hidden lg:block">
                            <span class="block text-sm font-bold uppercase tracking-wide text-sky-900">DHP System</span>
                            <span class="block text-xs font-medium text-teal-700">Digital Health Passport</span>
                        </span>
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-6 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Dashboard') }}
                    </x-nav-link>
                    @can('view_patients')
                        <x-nav-link :href="route('patients.index')" :active="request()->routeIs('patients.*')">
                            {{ __('Patients') }}
                        </x-nav-link>
                    @endcan
                    @can('triage_patient')
                        <x-nav-link :href="route('patients.index')" :active="request()->routeIs('triage*')">
                            {{ __('Triage') }}
                        </x-nav-link>
                    @endcan
                    @can('consult_patient')
                        <x-nav-link :href="route('patients.index')" :active="request()->routeIs('consultation*')">
                            {{ __('Consultation') }}
                        </x-nav-link>
                    @endcan
                    @can('dispense_medication')
                        <x-nav-link :href="route('patients.index')" :active="request()->routeIs('pharmacy*')">
                            {{ __('Pharmacy') }}
                        </x-nav-link>
                    @endcan
                    @can('manage_inventory')
                        <x-nav-link :href="route('inventory.index')" :active="request()->routeIs('inventory.*')">
                            {{ __('Inventory') }}
                        </x-nav-link>
                    @endcan
                    @can('view_reports')
                        <x-nav-link :href="route('reports.index')" :active="request()->routeIs('reports.*')">
                            {{ __('Reports') }}
                        </x-nav-link>
                    @endcan
                    @canany(['manage_facility', 'manage_facility_users'])
                        <x-nav-link :href="route('facilities.index')" :active="request()->routeIs('facilities.*') || request()->routeIs('users.*')">
                            {{ __('Administration') }}
                        </x-nav-link>
                    @endcanany
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center gap-2 rounded-full border border-sky-100 bg-sky-50 px-3 py-2 text-sm leading-4 font-semibold text-sky-900 hover:bg-sky-100 focus:outline-none transition ease-in-out duration-150">
                            <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-teal-600 text-xs font-bold text-white">
                                {{ Str::of(Auth::user()->name)->substr(0, 1)->upper() }}
                            </span>
                            <div>{{ Auth::user()->name }}</div>

                            <div class="ms-1 text-sky-700">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-sky-700 hover:text-sky-900 hover:bg-sky-50 focus:outline-none focus:bg-sky-50 focus:text-sky-900 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="px-4 pb-2 pt-4">
            <p class="text-xs font-bold uppercase tracking-wide text-sky-800">Clinical Workflow</p>
            <p class="text-xs text-gray-500">Follow the patient journey from registration to discharge.</p>
        </div>
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>
            @can('view_patients')
                <x-responsive-nav-link :href="route('patients.index')" :active="request()->routeIs('patients.*')">
                    {{ __('Patients') }}
                </x-responsive-nav-link>
            @endcan
            @can('triage_patient')
                <x-responsive-nav-link :href="route('patients.index')" :active="request()->routeIs('triage*')">
                    {{ __('Triage') }}
                </x-responsive-nav-link>
            @endcan
            @can('consult_patient')
                <x-responsive-nav-link :href="route('patients.index')" :active="request()->routeIs('consultation*')">
                    {{ __('Consultation') }}
                </x-responsive-nav-link>
            @endcan
            @can('dispense_medication')
                <x-responsive-nav-link :href="route('patients.index')" :active="request()->routeIs('pharmacy*')">
                    {{ __('Pharmacy') }}
                </x-responsive-nav-link>
            @endcan
            @can('manage_inventory')
                <x-responsive-nav-link :href="route('inventory.index')" :active="request()->routeIs('inventory.*')">
                    {{ __('Inventory') }}
                </x-responsive-nav-link>
            @endcan
            @can('view_reports')
                <x-responsive-nav-link :href="route('reports.index')" :active="request()->routeIs('reports.*')">
                    {{ __('Reports') }}
                </x-responsive-nav-link>
            @endcan
            @canany(['manage_facility', 'manage_facility_users'])
                <x-responsive-nav-link :href="route('facilities.index')" :active="request()->routeIs('facilities.*') || request()->routeIs('users.*')">
                    {{ __('Administration') }}
                </x-responsive-nav-link>
            @endcanany
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-sky-100 bg-sky-50/60">
            <div class="px-4">
                <div class="font-medium text-base text-sky-900">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
