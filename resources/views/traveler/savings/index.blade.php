@extends('layouts.app')
@section('title', 'Savings Goals')
@section('content')

<div class="mb-24">
    @if ($goals->isNotEmpty())
    <div style="font-size:11px;font-weight:700;letter-spacing:.1em;color:var(--primary);text-transform:uppercase;margin-bottom:4px;">SAVINGS GOAL</div>
    @endif
</div>

@if (session('success'))
<div class="alert alert-success mb-16">{{ session('success') }}</div>
@endif

@if ($goals->isEmpty())
<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:70vh;text-align:center;">
    <div style="width:72px;height:72px;border-radius:20px;background:#F5EDE7;display:flex;align-items:center;justify-content:center;margin-bottom:20px;">
        <i class="fa-solid fa-piggy-bank" style="font-size:32px;color:var(--primary);"></i>
    </div>
    <h2 style="font-weight:700;margin-bottom:8px;">No savings goals yet</h2>
    <p class="text-muted" style="max-width:320px;margin-bottom:24px;">Plan your trip first before adding your savings.</p>
    <a href="{{ route('trips.plan') }}" class="btn btn-primary btn-lg">
        <i class="fa-solid fa-paper-plane"></i> Plan a Trip
    </a>
</div>
@else
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px;align-items:stretch;">
    @foreach ($goals as $goal)
    @livewire('traveler.savings-goal-manager', ['goal' => $goal], key($goal->id))
    @endforeach
</div>
@endif

@endsection
