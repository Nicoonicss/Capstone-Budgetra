@extends('layouts.app')
@section('title', 'Manage Users')
@section('content')
<h1>Manage Users</h1>
<form method="GET" style="display:flex;gap:8px;margin-bottom:1rem;">
    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name or email..." class="form-control" style="width:250px;">
    <select name="role" class="form-control" style="width:auto;">
        <option value="">All roles</option>
        <option value="traveler" {{ request('role') === 'traveler' ? 'selected' : '' }}>Traveler</option>
        <option value="admin"    {{ request('role') === 'admin'    ? 'selected' : '' }}>Admin</option>
        <option value="banned"   {{ request('role') === 'banned'   ? 'selected' : '' }}>Banned</option>
    </select>
    <button class="btn btn-secondary">Filter</button>
</form>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<table class="table">
    <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Registered</th><th>Actions</th></tr></thead>
    <tbody>
    @foreach($users as $user)
        <tr>
            <td><a href="{{ route('admin.users.show', $user) }}">{{ $user->full_name }}</a></td>
            <td>{{ $user->email }}</td>
            <td><span>{{ $user->role }}</span></td>
            <td>{{ $user->created_at->format('M j, Y') }}</td>
            <td style="display:flex;gap:4px;">
                @if($user->id !== auth()->id())
                    <form method="POST" action="{{ route('admin.users.ban', $user) }}">
                        @csrf @method('PATCH')
                        <button class="btn btn-sm {{ $user->role === 'banned' ? 'btn-success' : 'btn-warning' }}">
                            {{ $user->role === 'banned' ? 'Unban' : 'Ban' }}
                        </button>
                    </form>
                    <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Delete this user?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-danger">Delete</button>
                    </form>
                @endif
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
{{ $users->links() }}
@endsection
