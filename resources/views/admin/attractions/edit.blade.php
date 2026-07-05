@extends('layouts.admin')
@section('content')
<a href="{{ route('admin.attractions.index') }}">&larr; Back</a>
<h1>Edit: {{ $attraction->name }}</h1>
<form method="POST" action="{{ route('admin.attractions.update', $attraction) }}" enctype="multipart/form-data">
    @csrf @method('PUT')
    <div class="form-group"><label>Destination *</label><input type="text" name="destination" value="{{ old('destination', $attraction->destination) }}" class="form-control" required></div>
    <div class="form-group"><label>Name *</label><input type="text" name="name" value="{{ old('name', $attraction->name) }}" class="form-control" required></div>
    <div class="form-group"><label>Category</label><input type="text" name="category" value="{{ old('category', $attraction->category) }}" class="form-control"></div>
    <div class="form-group"><label>Rating (0–5)</label><input type="number" step="0.1" min="0" max="5" name="rating" value="{{ old('rating', $attraction->rating) }}" class="form-control"></div>
    <div class="form-group"><label>Description</label><textarea name="description" class="form-control" rows="3">{{ old('description', $attraction->description) }}</textarea></div>
    <div class="form-group">
        <label>Image (leave blank to keep existing)</label>
        @if($attraction->image)
            <img src="{{ asset('storage/'.$attraction->image) }}" style="height:80px;display:block;margin-bottom:8px;" alt="{{ $attraction->name }}">
        @endif
        <input type="file" name="image" class="form-control" accept="image/*">
    </div>
    <button class="btn btn-primary">Update</button>
</form>
@endsection
