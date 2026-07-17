@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')

{{-- Header --}}
<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;" class="mb-24">
    <div>
        <h1>Dashboard</h1>
        <p class="text-muted">Welcome back, {{ auth()->user()?->full_name ?? '' }}!</p>
    </div>
</div>

{{-- Aggregate stat cards --}}
<div class="stats-row mb-24">
    <div class="stat-card">
        <div class="stat-card-accent" style="background:var(--primary);"></div>
        <div class="stat-label"><i class="fa-solid fa-suitcase-rolling"></i> Total Trips</div>
        <div class="stat-value">{{ $trips->count() }}</div>
        <div class="stat-sub" style="color:var(--primary);">All time</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-accent" style="background:var(--secondary);"></div>
        <div class="stat-label"><i class="fa-solid fa-coins"></i> Total Budget</div>
        <div class="stat-value" style="color:var(--secondary);">₱{{ number_format($totalBudget, 0) }}</div>
        <div class="stat-sub">Across all trips</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-accent" style="background:var(--tertiary);"></div>
        <div class="stat-label"><i class="fa-regular fa-credit-card"></i> Total Spent</div>
        <div class="stat-value" style="color:var(--tertiary);">₱{{ number_format($totalSpent, 0) }}</div>
        <div class="stat-sub">Across all trips</div>
    </div>
</div>

{{-- Active Trips --}}
@php $activeTrips = $trips->whereIn('status', ['upcoming', 'active']); @endphp
@if ($activeTrips->isNotEmpty())
<h2 class="mb-16">Active Trips</h2>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px;margin-bottom:32px;">
    @foreach ($activeTrips as $trip)
    @php
        $spent = $trip->total_spent ?? 0;
        $pct   = $trip->budget_limit > 0 ? min(100, round($spent / $trip->budget_limit * 100)) : 0;
        $isOver = $spent > $trip->budget_limit && $trip->budget_limit > 0;
    @endphp
    <div style="background:#fff;border:1.5px solid var(--border);border-radius:16px;padding:20px;display:flex;flex-direction:column;gap:12px;">
        <div style="display:flex;align-items:center;justify-content:space-between;">
            <span style="font-size:11px;font-weight:700;padding:4px 12px;border-radius:20px;
                  background:{{ $trip->status === 'active' ? '#F0FDF4' : '#EFF6FF' }};
                  color:{{ $trip->status === 'active' ? '#16A34A' : '#1D4ED8' }};">
                {{ ucfirst($trip->status) }}
            </span>
        </div>

        <div>
            <div style="font-size:17px;font-weight:700;color:var(--dark);margin-bottom:4px;">{{ $trip->destination }}</div>
            <div style="font-size:13px;color:var(--muted);">
                {{ $trip->start_date->format('M j') }} – {{ $trip->end_date->format('M j, Y') }}
            </div>
            <div style="font-size:13px;color:var(--muted);margin-top:2px;">
                Spent ₱{{ number_format($spent, 0) }} / ₱{{ number_format($trip->budget_limit, 0) }}
            </div>
        </div>

        <div style="height:5px;background:#F3F4F6;border-radius:99px;overflow:hidden;">
            <div style="height:100%;width:{{ $pct }}%;background:{{ $isOver ? 'var(--danger)' : 'var(--primary)' }};border-radius:99px;"></div>
        </div>

        <div style="display:flex;gap:16px;font-size:13px;font-weight:600;">
            <a href="{{ route('trips.dashboard', $trip) }}" style="color:var(--primary);text-decoration:none;">Dashboard</a>
            <a href="{{ route('expenses.index') }}?trip_id={{ $trip->id }}" style="color:var(--primary);text-decoration:none;">Expenses</a>
        </div>
    </div>
    @endforeach

    {{-- Plan New Adventure card --}}
    <a href="{{ route('trips.plan') }}"
       style="background:transparent;border:2px dashed #D1D5DB;border-radius:16px;padding:20px;
              display:flex;flex-direction:column;align-items:center;justify-content:center;gap:10px;
              min-height:160px;text-decoration:none;transition:border-color 0.15s;"
       onmouseover="this.style.borderColor='var(--primary)'"
       onmouseout="this.style.borderColor='#D1D5DB'">
        <div style="width:44px;height:44px;border-radius:50%;background:#F3F4F6;display:flex;align-items:center;justify-content:center;">
            <i class="fa-solid fa-plus" style="font-size:20px;color:#9CA3AF;"></i>
        </div>
        <div style="font-size:14px;font-weight:600;color:#6B7280;">Plan New Adventure</div>
        <div style="font-size:12px;color:#9CA3AF;text-align:center;">Start tracking your next destination today.</div>
    </a>
</div>
@endif

@endsection
