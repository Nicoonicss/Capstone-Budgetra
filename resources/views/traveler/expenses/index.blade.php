@extends('layouts.app')
@section('title', 'Expenses')
@section('content')

@if (session('success'))
<div class="alert alert-success" style="margin-bottom:16px;">{{ session('success') }}</div>
@endif

@if ($trips->isEmpty())
{{-- No trips empty state --}}
<div class="empty-state-center">
    <div style="width:72px;height:72px;border-radius:20px;background:#F5EDE7;display:flex;align-items:center;justify-content:center;margin-bottom:20px;">
        <i class="fa-solid fa-receipt" style="font-size:32px;color:var(--primary);"></i>
    </div>
    <h2 style="font-weight:700;margin-bottom:8px;">No expenses yet</h2>
    <p class="text-muted" style="max-width:320px;margin-bottom:24px;">Plan your trip first before logging your expenses.</p>
    <a href="{{ route('trips.plan') }}" class="btn btn-primary btn-lg">
        <i class="fa-solid fa-paper-plane"></i> Plan a Trip
    </a>
</div>

@else
@php
    $selectedTripId = request('trip_id') ?? ($trips->count() === 1 ? $trips->first()->id : null);
    $selectedTrip   = $selectedTripId ? $trips->firstWhere('id', $selectedTripId) : null;
@endphp

<div style="max-width:720px;margin:0 auto;width:100%;">

    {{-- Destination selector --}}
    <div style="margin-bottom:20px;">
        <div style="font-size:10px;font-weight:700;letter-spacing:.1em;color:var(--primary);text-transform:uppercase;margin-bottom:6px;">Destination</div>
        <div style="display:flex;gap:10px;align-items:center;">
            <div style="position:relative;flex:1;">
                <i class="fa-solid fa-location-dot" style="position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#C8874A;font-size:13px;pointer-events:none;z-index:1;"></i>
                @if ($trips->count() === 1)
                {{-- Single trip: read-only --}}
                @php $t = $trips->first(); @endphp
                <div style="background:#fff;border:1.5px solid var(--border);border-radius:10px;padding:12px 16px 12px 38px;font-size:14px;font-weight:600;color:var(--dark);">
                    {{ $t->origin ?? 'Manila' }} to {{ $t->destination }}
                </div>
                <input type="hidden" name="trip_id" value="{{ $t->id }}">
                @else
                {{-- Multiple trips: dropdown --}}
                <form method="GET" action="{{ route('expenses.index') }}" id="trip-filter-form" style="margin:0;">
                    <select name="trip_id" id="trip-selector"
                            onchange="document.getElementById('trip-filter-form').submit()"
                            style="width:100%;background:#fff;border:1.5px solid var(--border);border-radius:10px;padding:12px 40px 12px 38px;font-size:14px;font-weight:600;color:var(--dark);appearance:none;cursor:pointer;outline:none;">
                        <option value="">— Select a trip —</option>
                        @foreach($trips as $t)
                        <option value="{{ $t->id }}" {{ $selectedTripId == $t->id ? 'selected' : '' }}>
                            {{ $t->origin ?? 'Manila' }} to {{ $t->destination }}
                        </option>
                        @endforeach
                    </select>
                    <i class="fa-solid fa-chevron-down" style="position:absolute;right:14px;top:50%;transform:translateY(-50%);color:#9B8EA0;font-size:12px;pointer-events:none;"></i>
                </form>
                @endif
            </div>
            {{-- Add Expense button --}}
            <a href="{{ route('expenses.create') }}{{ $selectedTripId ? '?trip_id='.$selectedTripId : '' }}"
               style="flex-shrink:0;display:inline-flex;align-items:center;gap:7px;background:#7B3F00;color:#fff;border:none;border-radius:10px;padding:12px 18px;font-size:13px;font-weight:600;cursor:pointer;text-decoration:none;white-space:nowrap;">
                <i class="fa-solid fa-circle-plus" style="font-size:14px;"></i> Add Expense
            </a>
        </div>
    </div>

    {{-- Content card --}}
    <div style="background:#fff;border:1.5px solid var(--border);border-radius:16px;overflow:hidden;box-shadow:0 2px 10px rgba(0,0,0,.04);">

        @if (!$selectedTrip || $expenses->isEmpty())
        {{-- Empty state inside card --}}
        <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;padding:64px 24px;text-align:center;">
            <div style="width:56px;height:56px;border-radius:16px;background:#F5EDE7;display:flex;align-items:center;justify-content:center;margin-bottom:16px;">
                <i class="fa-solid fa-receipt" style="font-size:24px;color:#C8874A;"></i>
            </div>
            <div style="font-size:15px;font-weight:600;color:#1A0A00;margin-bottom:6px;">No expenses recorded yet</div>
            <div style="font-size:13px;color:#9B8EA0;max-width:280px;line-height:1.5;">
                Start tracking your trip costs by adding your first expense. Keep your budget on track with real-time ledger updates.
            </div>
        </div>

        @else
        {{-- Expenses table --}}
        <table style="width:100%;border-collapse:collapse;">
            <thead>
                <tr style="border-bottom:1.5px solid #F0E8DF;">
                    <th style="padding:12px 16px;text-align:left;font-size:11px;font-weight:700;color:#9B8EA0;text-transform:uppercase;letter-spacing:.05em;">Date</th>
                    <th style="padding:12px 16px;text-align:left;font-size:11px;font-weight:700;color:#9B8EA0;text-transform:uppercase;letter-spacing:.05em;">Category</th>
                    <th style="padding:12px 16px;text-align:left;font-size:11px;font-weight:700;color:#9B8EA0;text-transform:uppercase;letter-spacing:.05em;">Description</th>
                    <th style="padding:12px 16px;text-align:right;font-size:11px;font-weight:700;color:#9B8EA0;text-transform:uppercase;letter-spacing:.05em;">Amount</th>
                    <th style="padding:12px 16px;text-align:center;font-size:11px;font-weight:700;color:#9B8EA0;text-transform:uppercase;letter-spacing:.05em;"></th>
                </tr>
            </thead>
            <tbody>
            @foreach($expenses as $expense)
            <tr style="border-bottom:1px solid #FAF5F0;">
                <td style="padding:13px 16px;font-size:13px;color:#6B5E5B;">{{ $expense->expense_date->format('M j, Y') }}</td>
                <td style="padding:13px 16px;">
                    <span style="display:inline-block;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600;background:#F5EDE7;color:#7B3F00;">{{ $expense->category }}</span>
                </td>
                <td style="padding:13px 16px;font-size:13px;color:#1A0A00;">{{ $expense->description ?? '—' }}</td>
                <td style="padding:13px 16px;font-size:14px;font-weight:700;color:#C8874A;text-align:right;">₱{{ number_format($expense->amount, 0) }}</td>
                <td style="padding:13px 16px;text-align:center;">
                    <div style="display:flex;gap:6px;justify-content:center;">
                        <a href="{{ route('expenses.edit', $expense) }}"
                           style="background:transparent;border:1.5px solid var(--border);border-radius:8px;padding:5px 12px;font-size:12px;font-weight:600;color:#6B5E5B;text-decoration:none;">Edit</a>
                        <form method="POST" action="{{ route('expenses.destroy', $expense) }}" style="display:inline;">
                            @csrf @method('DELETE')
                            <button onclick="return confirm('Delete this expense?')"
                                    style="background:transparent;border:1.5px solid #FEE2E2;border-radius:8px;padding:5px 12px;font-size:12px;font-weight:600;color:#DC2626;cursor:pointer;">Delete</button>
                        </form>
                    </div>
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
        @if($expenses->hasPages())
        <div style="padding:12px 16px;border-top:1px solid #F0E8DF;">{{ $expenses->links() }}</div>
        @endif
        @endif

    </div>

</div>
@endif

@endsection
