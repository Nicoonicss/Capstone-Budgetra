@extends('layouts.app')
@section('title', 'Alerts')
@section('content')

@php
    $unreadCount  = $notifications->getCollection()->where('is_read', false)->count();
    $hasAny       = $notifications->getCollection()->isNotEmpty();
    $budgetNotifs = $notifications->getCollection()->filter(fn($n) => str_starts_with($n->type, 'budget'));
@endphp

@if (session('success'))
<div class="alert alert-success mb-16">{{ session('success') }}</div>
@endif

{{-- No trips at all --}}
@if ($trips->isEmpty())
<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:70vh;text-align:center;">
    <div style="width:72px;height:72px;border-radius:20px;background:#F5EDE7;display:flex;align-items:center;justify-content:center;margin-bottom:20px;">
        <i class="fa-solid fa-bell" style="font-size:32px;color:var(--primary);"></i>
    </div>
    <h2 style="font-weight:700;margin-bottom:8px;">No trips yet</h2>
    <p class="text-muted" style="max-width:320px;margin-bottom:24px;">Plan a trip first to start receiving budget alerts and notifications.</p>
    <a href="{{ route('trips.plan') }}" class="btn btn-primary btn-lg">
        <i class="fa-solid fa-paper-plane"></i> Plan a Trip
    </a>
</div>

@elseif (!$hasAny)
{{-- Has trips but no notifications --}}
<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:70vh;text-align:center;">
    <div style="width:72px;height:72px;border-radius:20px;background:#F5EDE7;display:flex;align-items:center;justify-content:center;margin-bottom:20px;">
        <i class="fa-solid fa-bell" style="font-size:32px;color:var(--primary);"></i>
    </div>
    <h2 style="font-weight:700;margin-bottom:8px;">All caught up!</h2>
    <p class="text-muted" style="max-width:320px;">
        @if ($activeTrip)
            No notifications for <strong>{{ $activeTrip->destination }}</strong> yet.
        @else
            No notifications yet. We'll alert you when your budget needs attention.
        @endif
    </p>
</div>

@else
{{-- Header --}}
<div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:24px;">
    <div>
        <div style="display:flex;align-items:center;gap:10px;">
            <i class="fa-solid fa-bell" style="font-size:22px;color:var(--primary);"></i>
            <h1>Alerts & Notifications</h1>
        </div>
        <p class="text-muted" style="margin-top:4px;">
            Budget alerts and trip updates for <strong>{{ $activeTrip?->destination }}</strong>
        </p>
    </div>
    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
        {{-- Trip filter pills --}}
        @if ($trips->count() > 1)
        <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            @foreach ($trips as $trip)
            <a href="{{ route('alerts.index', ['trip_id' => $trip->id]) }}"
               style="font-size:12px;font-weight:600;padding:5px 14px;border-radius:20px;text-decoration:none;
                      background:{{ $activeTrip?->id === $trip->id ? 'var(--primary)' : 'var(--border)' }};
                      color:{{ $activeTrip?->id === $trip->id ? '#fff' : 'var(--dark)' }};">
                {{ $trip->destination }}
            </a>
            @endforeach
        </div>
        @endif

        @if ($unreadCount > 0)
        <form method="POST" action="{{ route('alerts.read-all') }}">
            @csrf @method('PATCH')
            <button class="btn btn-outline btn-sm" type="submit">
                <i class="fa-solid fa-check-double"></i> Mark all as read
            </button>
        </form>
        @endif
    </div>
</div>

{{-- Budget Alerts section --}}
@if ($budgetNotifs->isNotEmpty())
<div style="background:#fff;border:1.5px solid var(--border);border-radius:16px;margin-bottom:16px;overflow:hidden;">
    <div style="padding:16px 20px;border-bottom:1px solid var(--border);">
        <h3 style="font-weight:700;">Budget Alerts</h3>
    </div>
    <div>
        @foreach ($budgetNotifs as $notif)
        @php
            $isWarning = $notif->type === 'budget_warning';
            $isDanger  = $notif->type === 'budget_alert';
            $isSuccess = !$isWarning && !$isDanger;
            $iconBg    = $isSuccess ? '#F0FDF4' : ($isWarning ? '#FFFBEB' : '#FEF2F2');
            $iconClr   = $isSuccess ? '#16A34A' : ($isWarning ? '#D97706' : '#DC2626');
            $icon      = $isSuccess ? 'fa-circle-check' : ($isWarning ? 'fa-triangle-exclamation' : 'fa-circle-exclamation');
            $title     = $isDanger ? 'Budget Alert (80%+)' : ($isWarning ? 'Budget Warning (50%)' : 'Great progress on ' . ($notif->trip?->destination ?? 'your trip'));
            if (preg_match('/on (\w[\w\s]+) budget/i', $notif->message, $m)) {
                $title = 'Great progress on ' . trim($m[1]);
            } elseif (preg_match('/(\w[\w\s]+) budget.*?warning/i', $notif->message, $m)) {
                $title = trim($m[1]) . ' Budget Warning';
            }
        @endphp
        <div style="display:flex;align-items:flex-start;gap:14px;padding:16px 20px;{{ !$loop->last ? 'border-bottom:1px solid var(--border);' : '' }}">
            <div style="width:36px;height:36px;border-radius:50%;background:{{ $iconBg }};display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fa-solid {{ $icon }}" style="color:{{ $iconClr }};font-size:16px;"></i>
            </div>
            <div style="flex:1;min-width:0;">
                <div style="font-size:14px;font-weight:600;color:var(--dark);margin-bottom:4px;">{{ $title }}</div>
                <div class="text-muted" style="font-size:13px;">{{ $notif->message }}</div>
                @if (!$activeTrip && $notif->trip)
                <div style="font-size:12px;color:var(--muted);margin-top:3px;">
                    <i class="fa-solid fa-location-dot" style="font-size:10px;"></i> {{ $notif->trip->destination }}
                </div>
                @endif
            </div>
            <span class="text-muted" style="font-size:12px;white-space:nowrap;flex-shrink:0;">
                {{ $notif->created_at->isToday() ? 'Today' : $notif->created_at->format('M j') }}
            </span>
        </div>
        @endforeach
    </div>
</div>
@endif

{{-- General Notifications section --}}
<div style="background:#fff;border:1.5px solid var(--border);border-radius:16px;overflow:hidden;">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid var(--border);">
        <h3 style="font-weight:700;">Notifications</h3>
        @if ($unreadCount > 0)
        <span style="padding:2px 10px;border-radius:99px;background:var(--primary);color:#fff;font-size:11px;font-weight:700;">{{ $unreadCount }} unread</span>
        @endif
    </div>
    <div>
        @foreach ($notifications as $notif)
        @php
            $isDanger  = $notif->type === 'budget_alert';
            $isWarning = $notif->type === 'budget_warning';
            $iconBg    = $isDanger ? '#FEF2F2' : ($isWarning ? '#FFFBEB' : '#F0FDF4');
            $iconClr   = $isDanger ? '#DC2626' : ($isWarning ? '#D97706' : '#16A34A');
            $icon      = $isDanger ? 'fa-circle-exclamation' : ($isWarning ? 'fa-triangle-exclamation' : 'fa-circle-check');
        @endphp
        <div style="display:flex;align-items:flex-start;gap:14px;padding:14px 20px;{{ !$loop->last ? 'border-bottom:1px solid var(--border);' : '' }}{{ !$notif->is_read ? 'background:#FAFAF8;' : '' }}">
            <div style="width:32px;height:32px;border-radius:50%;background:{{ $iconBg }};display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fa-solid {{ $icon }}" style="color:{{ $iconClr }};font-size:14px;"></i>
            </div>
            <div style="flex:1;min-width:0;">
                <div style="font-size:13px;font-weight:600;color:var(--dark);margin-bottom:3px;">{{ $notif->message }}</div>
                @if (!$activeTrip && $notif->trip)
                <div style="font-size:12px;color:var(--muted);">
                    <i class="fa-solid fa-location-dot" style="font-size:10px;"></i> {{ $notif->trip->destination }}
                </div>
                @endif
            </div>
            <div style="display:flex;flex-direction:column;align-items:flex-end;gap:6px;flex-shrink:0;">
                <span class="text-muted" style="font-size:11px;white-space:nowrap;">
                    {{ $notif->created_at->format('M d, g:i A') }}
                </span>
                @if (!$notif->is_read)
                <div style="display:flex;align-items:center;gap:6px;">
                    <span style="width:8px;height:8px;border-radius:50%;background:var(--primary);display:inline-block;"></span>
                    <form method="POST" action="{{ route('alerts.read', $notif) }}">
                        @csrf @method('PATCH')
                        <button class="btn btn-outline" style="font-size:11px;padding:2px 8px;border-radius:6px;" type="submit">Mark read</button>
                    </form>
                </div>
                @endif
            </div>
        </div>
        @endforeach
    </div>
</div>

@if ($notifications->hasPages())
<div class="mt-24">{{ $notifications->links() }}</div>
@endif

@endif

@endsection
