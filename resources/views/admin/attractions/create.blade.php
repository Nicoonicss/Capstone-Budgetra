@extends('layouts.admin')
@section('content')
<a href="{{ route('admin.attractions.index') }}">&larr; Back</a>
<h1>Add Attraction</h1>
<form method="POST" action="{{ route('admin.attractions.store') }}" enctype="multipart/form-data">
    @csrf
    <div class="form-group"><label>Destination *</label><input type="text" name="destination" class="form-control" required></div>
    <div class="form-group"><label>Name *</label><input type="text" name="name" class="form-control" required></div>
    <div class="form-group"><label>Category</label><input type="text" name="category" class="form-control"></div>
    <div class="form-group"><label>Rating (0–5)</label><input type="number" step="0.1" min="0" max="5" name="rating" class="form-control"></div>
    <div class="form-group"><label>Description</label><textarea name="description" class="form-control" rows="3"></textarea></div>
    <div class="form-group"><label>Image</label><input type="file" name="image" class="form-control" accept="image/*"></div>
    <button class="btn btn-primary">Save</button>
</form>
@endsection
