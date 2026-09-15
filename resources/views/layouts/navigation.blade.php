<nav x-data="{ open: false }" class="sticky top-0 z-40 border-b border-[#DCE8E8] bg-white/95 shadow-sm backdrop-blur" aria-label="Primary">
    <div class="dhp-container">
        <div class="flex h-16 items-center justify-between gap-4">
            <div class="flex min-w-0 items-center gap-6">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 rounded-xl p-1" aria-label="Digital Health Passport home">
                    @if(Auth::user()->facility?->logo_path)
                        <img src="{{ asset('storage/'.Auth::user()->facility->logo_path) }}" alt="{{ Auth::user()->facility->name }} logo" class="h-10 w-10 rounded-xl object-cover shadow-card ring-2 ring-dhp-100">
                    @else
                        <span class="inline-flex h-10 w-10 items-center justify-center overflow-hidden rounded-xl bg-white shadow-card ring-2 ring-dhp-100" aria-hidden="true">
                            @include('partials.logo-img', ['class' => 'h-full w-full object-contain p-1'])
                        </span>
                    @endif
                    <span class="hidden lg:block">
                        <span class="block text-sm font-extrabold uppercase tracking-wide text-dhp-900">Digital Health Passport</span>
                        <span class="block text-xs font-medium text-slate-500">{{ Auth::user()->facility?->name ?? 'National Registry' }}</span>
                    </span>
                </a>

                <div class="hidden items-center gap-1 md:flex" role="menubar">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">Dashboard</x-nav-link>
                    @canany(['enroll_patient', 'approve_enrollment', 'create_patient'])
                        <x-nav-link :href="route('enroll.create')" :active="request()->routeIs('enroll.*') || request()->routeIs('facility.*')">Enroll</x-nav-link>
                    @endcanany
                    @can('view_patients')
                        <x-nav-link :href="route('patients.index')" :active="request()->routeIs('patients.*') || request()->routeIs('triage*') || request()->routeIs('consultation*') || request()->routeIs('pharmacy*')">Patients</x-nav-link>
                    @endcan
                    @can('manage_credentials')
                        <x-nav-link :href="route('patient.credential')" :active="request()->routeIs('patient.credential')">My Passport</x-nav-link>
                    @endcan
                    @role('patient')
                        <x-nav-link :href="route('patient.records')" :active="request()->routeIs('patient.records')">My Records</x-nav-link>
                    @endrole
                    @can('verify_credential')
                        <x-nav-link :href="route('verify.scan')" :active="request()->routeIs('verify.*')">Verify</x-nav-link>
                    @endcan
                    @can('manage_inventory')
                        <x-nav-link :href="route('inventory.index')" :active="request()->routeIs('inventory.*')">Pharmacy Stock</x-nav-link>
                    @endcan
                    @can('view_reports')
                        <x-nav-link :href="route('reports.index')" :active="request()->routeIs('reports.*') || request()->routeIs('lab.orders.*')">Reports & Labs</x-nav-link>
                    @endcan
                    @can('view_audit_logs')
                        <x-nav-link :href="route('audit-logs.index')" :active="request()->routeIs('audit-logs.*')">Audit</x-nav-link>
                    @endcan
                    @canany(['manage_facility', 'manage_facility_users'])
                        <x-nav-link :href="route('facilities.index')" :active="request()->routeIs('facilities.*') || request()->routeIs('users.*')">Admin</x-nav-link>
                    @endcanany
                </div>
            </div>

            <div class="flex items-center gap-2">
                @can('create_patient')
                    <a href="{{ route('patients.create') }}" class="btn-primary hidden !min-h-[40px] !px-3.5 !py-1.5 sm:inline-flex">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                        Register
                    </a>
                @endcan

                <div class="relative" id="profile-menu">
                    <button type="button" id="profile-menu-button" aria-haspopup="true" aria-expanded="false" aria-controls="profile-menu-list" class="inline-flex min-h-[44px] items-center gap-2 rounded-full border border-[#DCE8E8] bg-dhp-50 py-1.5 pl-1.5 pr-3 text-sm font-semibold text-dhp-900 hover:bg-dhp-100">
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-dhp-600 text-sm font-bold text-white" aria-hidden="true">
                            {{ Str::of(Auth::user()->name)->substr(0, 1)->upper() }}
                        </span>
                        <span class="hidden max-w-[140px] truncate xl:block">{{ Auth::user()->name }}</span>
                        <svg class="h-4 w-4 text-dhp-700" fill="none" viewBox="0 0 20 20" aria-hidden="true"><path fill="currentColor" fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                    </button>

                    <div id="profile-menu-list" class="absolute end-0 z-50 mt-2 hidden w-56 rounded-2xl border border-[#DCE8E8] bg-white p-1.5 shadow-pop">
                        <p class="px-3 pb-1 pt-2 text-xs font-semibold uppercase tracking-wider text-slate-400">{{ Auth::user()->masked_nin }} · {{ Auth::user()->email }}</p>
                        <a href="{{ route('profile.edit') }}" class="block rounded-xl px-3 py-2.5 text-sm font-medium text-slate-700 hover:bg-dhp-50">Manage account</a>
                        <a href="{{ route('settings.2fa') }}" class="block rounded-xl px-3 py-2.5 text-sm font-medium text-slate-700 hover:bg-dhp-50">Two-factor authentication</a>
                        @can('manage_own_facility_settings')
                            <a href="{{ route('settings.facility.edit') }}" class="block rounded-xl px-3 py-2.5 text-sm font-medium text-slate-700 hover:bg-dhp-50">Facility settings</a>
                        @endcan
                        @can('manage_global_settings')
                            <a href="{{ route('settings.global.edit') }}" class="block rounded-xl px-3 py-2.5 text-sm font-medium text-slate-700 hover:bg-dhp-50">National configuration</a>
                        @endcan
                        <button type="button" data-open-logout class="block w-full rounded-xl px-3 py-2.5 text-left text-sm font-semibold text-rose-700 hover:bg-rose-50">Log out</button>
                    </div>
                </div>

                <button id="nav-hamburger" type="button" aria-controls="nav-mobile-menu" aria-expanded="false" aria-label="Open menu" class="touch-target inline-flex items-center justify-center rounded-xl text-dhp-800 hover:bg-dhp-50 md:hidden">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div id="nav-mobile-menu" class="hidden border-t border-[#E7F0F0] bg-white md:hidden">
        <div class="dhp-container py-3">
            <p class="dhp-eyebrow">Clinical workflow</p>
            <p class="text-xs text-slate-500">Registration → Triage → Consultation → Pharmacy → Ward</p>
        </div>
        <div class="dhp-container space-y-1 pb-3">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">Dashboard</x-responsive-nav-link>
            @canany(['enroll_patient', 'approve_enrollment', 'create_patient'])
                <x-responsive-nav-link :href="route('enroll.create')" :active="request()->routeIs('enroll.create')">Enroll user</x-responsive-nav-link>
            @endcanany
            @can('approve_enrollment')
                <x-responsive-nav-link :href="route('facility.approvals')" :active="request()->routeIs('facility.*')">Approvals</x-responsive-nav-link>
            @endcan
            @can('view_patients')
                <x-responsive-nav-link :href="route('patients.index')" :active="request()->routeIs('patients.*')">Patients</x-responsive-nav-link>
            @endcan
            @can('create_patient')
                <x-responsive-nav-link :href="route('patients.create')" :active="request()->routeIs('patients.create')">Register patient</x-responsive-nav-link>
            @endcan
            @can('manage_inventory')
                <x-responsive-nav-link :href="route('inventory.index')" :active="request()->routeIs('inventory.*')">Pharmacy stock</x-responsive-nav-link>
            @endcan
            @can('view_reports')
                <x-responsive-nav-link :href="route('reports.index')" :active="request()->routeIs('reports.*')">Reports</x-responsive-nav-link>
            @endcan
            @can('view_audit_logs')
                <x-responsive-nav-link :href="route('audit-logs.index')" :active="request()->routeIs('audit-logs.*')">Audit logs</x-responsive-nav-link>
            @endcan
            @canany(['manage_facility', 'manage_facility_users'])
                <x-responsive-nav-link :href="route('facilities.index')" :active="request()->routeIs('facilities.*') || request()->routeIs('users.*')">Administration</x-responsive-nav-link>
            @endcanany
        </div>
        <div class="border-t border-[#E7F0F0] bg-dhp-50/60">
            <div class="dhp-container py-3">
                <p class="text-sm font-bold text-dhp-900">{{ Auth::user()->display_name }}</p>
                <p class="text-xs text-slate-500">{{ Auth::user()->masked_nin }}</p>
                <div class="mt-2 flex gap-2">
                    <a href="{{ route('profile.edit') }}" class="btn-secondary flex-1">Manage account</a>
                    <button type="button" data-open-logout class="btn-danger flex-1">Log out</button>
                </div>
            </div>
        </div>
    </div>

    <div id="logout-modal" class="fixed inset-0 z-50 hidden items-center justify-center px-4" role="dialog" aria-modal="true" aria-labelledby="logout-modal-title">
        <div class="absolute inset-0 bg-dhp-950/60" data-close-logout></div>
        <div class="dhp-card relative w-full p-6 sm:max-w-md">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <h2 id="logout-modal-title" class="text-lg font-bold text-dhp-900">Log out of the Health Passport?</h2>
                <p class="mt-2 text-sm text-slate-600">You will need your NIN and password to sign back in. Unsaved work on this page will be lost.</p>
                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" data-close-logout class="btn-secondary">Stay signed in</button>
                    <button type="submit" class="btn-danger">Yes, log out</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    (function () {
        function $(id) { return document.getElementById(id); }
        var menuBtn = $('profile-menu-button'), menu = $('profile-menu-list'), menuWrap = $('profile-menu');
        function closeMenu() {
            if (menu) menu.classList.add('hidden');
            if (menuBtn) menuBtn.setAttribute('aria-expanded', 'false');
        }
        if (menuBtn && menu) {
            menuBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                var willShow = menu.classList.contains('hidden');
                closeMenu();
                if (willShow) { menu.classList.remove('hidden'); menuBtn.setAttribute('aria-expanded', 'true'); }
            });
            document.addEventListener('click', function (e) { if (menuWrap && !menuWrap.contains(e.target)) closeMenu(); });
        }
        var burger = $('nav-hamburger'), mobile = $('nav-mobile-menu');
        if (burger && mobile) {
            burger.addEventListener('click', function () {
                var willShow = mobile.classList.contains('hidden');
                mobile.classList.toggle('hidden', !willShow);
                burger.setAttribute('aria-expanded', willShow ? 'true' : 'false');
                burger.setAttribute('aria-label', willShow ? 'Close menu' : 'Open menu');
            });
        }
        var modal = $('logout-modal');
        function openLogout() {
            closeMenu();
            if (!modal) return;
            modal.classList.remove('hidden'); modal.classList.add('flex');
            var cancel = modal.querySelector('[data-close-logout]'); if (cancel) cancel.focus();
        }
        function closeLogout() { if (!modal) return; modal.classList.add('hidden'); modal.classList.remove('flex'); }
        document.querySelectorAll('[data-open-logout]').forEach(function (el) { el.addEventListener('click', function (e) { e.preventDefault(); openLogout(); }); });
        if (modal) { modal.querySelectorAll('[data-close-logout]').forEach(function (el) { el.addEventListener('click', closeLogout); }); }
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { closeMenu(); closeLogout(); } });
    })();
    </script>
</nav>
