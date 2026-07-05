@extends('layouts.admin')
@section('content')
<a href="{{ route('admin.destinations.index') }}">&larr; Back</a>
<h1>Edit: {{ $destination->destination }}</h1>
<form method="POST" action="{{ route('admin.destinations.update', $destination) }}">
    @csrf @method('PUT')
    <div class="form-group">
        <label>Destination Name *</label>
        <input type="text" name="destination" value="{{ old('destination', $destination->destination) }}" class="form-control" required>
        @error('destination')<div class="text-danger">{{ $message }}</div>@enderror
    </div>
    <div class="form-group">
        <label>Cost Level *</label>
        <select name="cost_level" class="form-control" required>
            @foreach($costLevels as $level)
                <option value="{{ $level }}" {{ old('cost_level', $destination->cost_level) === $level ? 'selected' : '' }}>{{ $level }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group">
        <label>Multiplier *</label>
        <input type="number" step="0.001" name="multiplier" value="{{ old('multiplier', $destination->multiplier) }}" class="form-control" required>
    </div>
    <div class="form-group">
        <label>Category</label>
        <input type="text" name="category" value="{{ old('category', $destination->category) }}" class="form-control">
    </div>
    <div class="form-group">
        <label>Image URL</label>
        <input type="url" name="image_url" value="{{ old('image_url', $destination->image_url) }}" class="form-control">
    </div>
    <div class="form-group">
        <label>Description</label>
        <textarea name="description" class="form-control" rows="3">{{ old('description', $destination->description) }}</textarea>
    </div>
    <button class="btn btn-primary">Update</button>
</form>
@endsection
