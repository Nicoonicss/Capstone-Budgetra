@extends('layouts.admin')
@section('content')
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
    <h1 style="font-size:26px;font-weight:800;margin:0;">Attractions</h1>
    <a href="{{ route('admin.attractions.create') }}" class="btn btn-primary">+ Add Attraction</a>
</div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<table class="table">
    <thead><tr><th>Destination</th><th>Name</th><th>Category</th><th>Rating</th><th>Actions</th></tr></thead>
    <tbody>
    @foreach($attractions as $attr)
        <tr>
            <td>{{ $attr->destination }}</td>
            <td>{{ $attr->name }}</td>
            <td>{{ $attr->category ?? '—' }}</td>
            <td>{{ $attr->rating }}</td>
            <td>
                <a href="{{ route('admin.attractions.edit', $attr) }}" class="btn btn-sm btn-secondary">Edit</a>
                <form method="POST" action="{{ route('admin.attractions.destroy', $attr) }}" style="display:inline;" onsubmit="return confirm('Delete?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm btn-danger">Delete</button>
                </form>
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
{{ $attractions->links() }}
@endsection
