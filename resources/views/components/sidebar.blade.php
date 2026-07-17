@props(['active' => ''])
@php
    $user   = auth()->user();
    $userInit = strtoupper(substr($user->full_name ?? 'U', 0, 1));
    $trip   = $user ? $user->trips()->latest('start_date')->first() : null;
    $unread = $user ? $user->notifications()->where('is_read', false)->count() : 0;

    $links = [
        ['href' => url('/dashboard'),  'icon' => 'fa-solid fa-house',            'label' => 'Dashboard',    'key' => 'dashboard'],
        ['href' => url('/trips'),      'icon' => 'fa-solid fa-map-location-dot', 'label' => 'Planner',      'key' => 'trips'],
        ['href' => url('/trips'),      'icon' => 'fa-solid fa-suitcase-rolling', 'label' => 'Saved Trips',  'key' => 'saved-trips'],
        ['href' => url('/savings'),    'icon' => 'fa-solid fa-piggy-bank',       'label' => 'Saving Goals', 'key' => 'savings'],
        ['href' => url('/itinerary'),  'icon' => 'fa-regular fa-calendar-days',  'label' => 'Itinerary',    'key' => 'itinerary'],
        ['href' => url('/expenses'),   'icon' => 'fa-solid fa-receipt',          'label' => 'Expenses',     'key' => 'expenses'],
        ['href' => url('/alerts'),     'icon' => 'fa-regular fa-bell',           'label' => 'Notifications','key' => 'alerts', 'badge' => $unread],
        ['href' => url('/trips'),      'icon' => 'fa-solid fa-layer-group',      'label' => 'Multi Trips',  'key' => 'multi-trips'],
        ['href' => url('/itinerary'),  'icon' => 'fa-regular fa-images',         'label' => 'Moments',      'key' => 'moments'],
    ];

    $bottomLinks = [
        ['href' => url('/profile'),  'icon' => 'fa-regular fa-user-circle', 'label' => 'Profile',  'key' => 'profile'],
        ['href' => url('/dashboard'),'icon' => 'fa-solid fa-gear',          'label' => 'Settings', 'key' => 'settings'],
    ];
@endphp

<aside class="sidebar" id="appSidebar">

    <button class="sidebar-toggle-btn" id="sidebarToggle" title="Toggle sidebar">
        <i class="fa-solid fa-bars" id="sidebarToggleIcon"></i>
    </button>

    <div class="sidebar-brand">
        <div class="sidebar-trip-badge"><span>Active Workspace</span></div>
        <div class="sidebar-trip-name">
            <span>{{ $trip->destination ?? 'My Trips' }}</span>
        </div>
        <div class="sidebar-trip-status">
            @if ($trip)
                <span>{{ $trip->start_date->format('M d') }} &ndash; {{ $trip->end_date->format('M d, Y') }}</span>
            @else
                <span>No active trip</span>
            @endif
        </div>
    </div>

    <nav class="sidebar-nav">
        @foreach ($links as $link)
        <a href="{{ $link['href'] }}"
           class="sidebar-link {{ $active === $link['key'] ? 'active' : '' }}"
           title="{{ $link['label'] }}">
            <i class="{{ $link['icon'] }}"></i>
            <span class="sidebar-link-label">{{ $link['label'] }}</span>
            @if (!empty($link['badge']) && $link['badge'] > 0)
                <span class="sidebar-badge sidebar-link-label">{{ $link['badge'] > 99 ? '99+' : $link['badge'] }}</span>
            @endif
        </a>
        @endforeach
    </nav>

    {{-- Logout button --}}
    <form method="POST" action="{{ route('logout') }}" style="padding:0 12px;margin:8px 0;">
        @csrf
        <button type="submit" class="sidebar-new-trip-btn" title="Logout">
            <i class="fa-solid fa-right-from-bracket"></i>
            <span class="sidebar-link-label" style="margin-left:8px;">Logout</span>
        </button>
    </form>

    {{-- Bottom links: Profile, Settings --}}
    <div class="sidebar-bottom-links">
        @foreach ($bottomLinks as $link)
        <a href="{{ $link['href'] }}"
           class="sidebar-link {{ $active === $link['key'] ? 'active' : '' }}"
           title="{{ $link['label'] }}">
            <i class="{{ $link['icon'] }}"></i>
            <span class="sidebar-link-label">{{ $link['label'] }}</span>
        </a>
        @endforeach
    </div>

</aside>

<script>
(function () {
    var wrap = document.getElementById('dashWrapper');
    var btn  = document.getElementById('sidebarToggle');
    var icon = document.getElementById('sidebarToggleIcon');
    if (!wrap || !btn) return;

    function applyState(collapsed) {
        wrap.classList.toggle('sidebar-collapsed', collapsed);
        icon.className = collapsed ? 'fa-solid fa-chevron-right' : 'fa-solid fa-bars';
    }

    applyState(localStorage.getItem('sidebarCollapsed') === '1');

    btn.addEventListener('click', function () {
        var c = !wrap.classList.contains('sidebar-collapsed');
        localStorage.setItem('sidebarCollapsed', c ? '1' : '0');
        applyState(c);
    });
})();
</script>
