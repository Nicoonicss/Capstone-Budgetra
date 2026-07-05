@props(['active' => ''])
<aside class="sidebar" id="appSidebar">
    <button class="sidebar-toggle-btn" id="sidebarToggle" title="Toggle sidebar">
        <i class="fa-solid fa-bars" id="sidebarToggleIcon"></i>
    </button>
    <div class="sidebar-brand">
        <div class="sidebar-trip-badge"><span>Admin Panel</span></div>
        <div class="sidebar-trip-name"><span>Budgetra</span></div>
        <div class="sidebar-trip-status"><span>Management Dashboard</span></div>
    </div>
    <nav class="sidebar-nav" style="overflow-y:auto;max-height:calc(100vh - 220px);">
        @php
        $links = [
            ['href' => url('/admin'),              'icon' => 'fa-solid fa-gauge-high',       'label' => 'Overview',      'key' => 'dashboard'],
            ['href' => url('/admin/users'),         'icon' => 'fa-solid fa-users',            'label' => 'Users',         'key' => 'users'],
            ['href' => url('/admin/destinations'),  'icon' => 'fa-solid fa-map-pin',          'label' => 'Destinations',  'key' => 'destinations'],
            ['href' => url('/admin/attractions'),   'icon' => 'fa-solid fa-mountain-sun',     'label' => 'Attractions',   'key' => 'attractions'],
            ['href' => url('/admin/integrations'),  'icon' => 'fa-solid fa-plug-circle-bolt', 'label' => 'Integrations',  'key' => 'integrations'],
            ['href' => url('/admin/reports'),       'icon' => 'fa-solid fa-chart-column',     'label' => 'Reports',       'key' => 'reports'],
            ['href' => url('/admin/reviews'),       'icon' => 'fa-solid fa-star-half-stroke', 'label' => 'Reviews',       'key' => 'reviews'],
        ];
        @endphp
        @foreach ($links as $link)
        <a href="{{ $link['href'] }}"
           class="sidebar-link {{ $active === $link['key'] ? 'active' : '' }}"
           title="{{ $link['label'] }}">
            <i class="{{ $link['icon'] }}"></i>
            <span class="sidebar-link-label">{{ $link['label'] }}</span>
        </a>
        @endforeach
    </nav>
    <div class="sidebar-avatar-wrap">
        <div class="sidebar-avatar">A</div>
        <div class="sidebar-user-details">
            <div class="sidebar-user-name">Admin</div>
            <div class="sidebar-user-email">Administrator</div>
        </div>
    </div>
</aside>
<style>
.sidebar-nav { scrollbar-width: none; }
.sidebar-nav::-webkit-scrollbar { display: none; }
</style>
<script>
(function () {
    var wrap = document.querySelector('.dashboard-wrapper');
    var btn  = document.getElementById('sidebarToggle');
    var icon = document.getElementById('sidebarToggleIcon');
    if (!wrap || !btn) return;
    function applyState(c) {
        wrap.classList.toggle('sidebar-collapsed', c);
        icon.className = c ? 'fa-solid fa-chevron-right' : 'fa-solid fa-bars';
    }
    applyState(localStorage.getItem('sidebarCollapsed') === '1');
    btn.addEventListener('click', function () {
        var c = !wrap.classList.contains('sidebar-collapsed');
        localStorage.setItem('sidebarCollapsed', c ? '1' : '0');
        applyState(c);
    });
})();
</script>
