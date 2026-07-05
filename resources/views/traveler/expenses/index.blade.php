@extends('layouts.app')
@section('title', 'Expenses')
@section('content')

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if ($trips->isEmpty())
{{-- No trips empty state --}}
<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:70vh;text-align:center;">
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

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;">
    <h1>Expenses</h1>
    <a href="{{ route('expenses.create') }}" class="btn btn-primary">+ Log Expense</a>
</div>

<form method="GET" action="{{ route('expenses.index') }}" style="margin-bottom:1rem;display:flex;gap:8px;flex-wrap:wrap;">
    <select name="trip_id" class="form-control" style="width:auto;">
        <option value="">All Trips</option>
        @foreach($trips as $trip)
            <option value="{{ $trip->id }}" {{ request('trip_id') == $trip->id ? 'selected' : '' }}>{{ $trip->destination }}</option>
        @endforeach
    </select>
    <select name="category" class="form-control" style="width:auto;">
        <option value="">All Categories</option>
        @foreach($categories as $cat)
            <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
        @endforeach
    </select>
    <input type="date" name="date_from" class="form-control" style="width:auto;" value="{{ request('date_from') }}">
    <input type="date" name="date_to" class="form-control" style="width:auto;" value="{{ request('date_to') }}">
    <button type="submit" class="btn btn-secondary">Filter</button>
    <a href="{{ route('expenses.index') }}" class="btn btn-secondary">Clear</a>
</form>

<div class="card">
    <table style="width:100%;border-collapse:collapse;">
        <thead><tr>
            <th style="padding:8px;text-align:left;border-bottom:2px solid #eee;">Date</th>
            <th style="padding:8px;text-align:left;border-bottom:2px solid #eee;">Trip</th>
            <th style="padding:8px;text-align:left;border-bottom:2px solid #eee;">Category</th>
            <th style="padding:8px;text-align:left;border-bottom:2px solid #eee;">Description</th>
            <th style="padding:8px;text-align:right;border-bottom:2px solid #eee;">Amount</th>
            <th style="padding:8px;text-align:center;border-bottom:2px solid #eee;">Actions</th>
        </tr></thead>
        <tbody>
        @forelse($expenses as $expense)
            <tr>
                <td style="padding:8px;border-bottom:1px solid #f0f0f0;">{{ $expense->expense_date->format('Y-m-d') }}</td>
                <td style="padding:8px;border-bottom:1px solid #f0f0f0;">{{ $expense->trip->destination ?? '—' }}</td>
                <td style="padding:8px;border-bottom:1px solid #f0f0f0;">{{ $expense->category }}</td>
                <td style="padding:8px;border-bottom:1px solid #f0f0f0;">{{ $expense->description ?? '—' }}</td>
                <td style="padding:8px;border-bottom:1px solid #f0f0f0;text-align:right;">{{ number_format($expense->amount, 2) }}</td>
                <td style="padding:8px;border-bottom:1px solid #f0f0f0;text-align:center;">
                    <a href="{{ route('expenses.edit', $expense) }}" class="btn btn-sm btn-secondary">Edit</a>
                    <form method="POST" action="{{ route('expenses.destroy', $expense) }}" style="display:inline;">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-danger" onclick="return confirm('Delete?')">Del</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="6" style="padding:1rem;text-align:center;">No expenses yet.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $expenses->links() }}

@endif
@endsection
