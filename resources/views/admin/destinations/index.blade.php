@extends('layouts.admin')
@section('content')
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
    <h1 style="font-size:26px;font-weight:800;margin:0;">Destinations</h1>
    <a href="{{ route('admin.destinations.create') }}" class="btn btn-primary">+ Add Destination</a>
</div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<table class="table">
    <thead><tr><th>Destination</th><th>Cost Level</th><th>Multiplier</th><th>Category</th><th>Actions</th></tr></thead>
    <tbody>
    @foreach($destinations as $dest)
        <tr>
            <td>{{ $dest->destination }}</td>
            <td>{{ $dest->cost_level }}</td>
            <td>{{ $dest->multiplier }}×</td>
            <td>{{ $dest->category ?? '—' }}</td>
            <td>
                <a href="{{ route('admin.destinations.edit', $dest) }}" class="btn btn-sm btn-secondary">Edit</a>
                <form method="POST" action="{{ route('admin.destinations.destroy', $dest) }}" style="display:inline;" onsubmit="return confirm('Delete?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm btn-danger">Delete</button>
                </form>
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
{{ $destinations->links() }}
@endsection
