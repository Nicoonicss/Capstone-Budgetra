@extends('layouts.app')
@section('title', 'Edit Expense')
@section('content')
<h1>Edit Expense</h1>
@if($errors->any())
    <div class="alert alert-danger">
        <ul style="margin:0;padding-left:1.2em;">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif
<div class="card"><div class="card-body">
<form method="POST" action="{{ route('expenses.update', $expense) }}">
    @csrf @method('PUT')
    <div class="form-group">
        <label>Trip</label>
        <select name="trip_id" class="form-control" required>
            @foreach($trips as $trip)
                <option value="{{ $trip->id }}" {{ old('trip_id', $expense->trip_id) == $trip->id ? 'selected' : '' }}>{{ $trip->destination }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group">
        <label>Amount</label>
        <input type="number" step="0.01" name="amount" class="form-control" value="{{ old('amount', $expense->amount) }}" required>
    </div>
    <div class="form-group">
        <label>Category</label>
        <select name="category" class="form-control" required>
            @foreach($categories as $cat)
                <option value="{{ $cat }}" {{ old('category', $expense->category) === $cat ? 'selected' : '' }}>{{ $cat }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group">
        <label>Description</label>
        <input type="text" name="description" class="form-control" value="{{ old('description', $expense->description) }}">
    </div>
    <div class="form-group">
        <label>Date</label>
        <input type="date" name="expense_date" class="form-control" value="{{ old('expense_date', $expense->expense_date->format('Y-m-d')) }}" required>
    </div>
    <button type="submit" class="btn btn-primary">Update</button>
    <a href="{{ route('expenses.index') }}" class="btn btn-secondary">Cancel</a>
</form>
</div></div>
@endsection
