@extends('layouts.admin')
@section('title', 'Review Moderation')
@section('content')
<h1>Review Moderation</h1>
<form method="GET" style="display:flex;gap:8px;margin-bottom:1rem;">
    <select name="status" class="form-control" style="width:auto;" onchange="this.form.submit()">
        <option value="">All Statuses</option>
        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
        <option value="hidden" {{ request('status') === 'hidden' ? 'selected' : '' }}>Hidden</option>
    </select>
</form>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@forelse($reviews as $review)
    <div class="card" style="margin-bottom:.75rem;{{ $review->status === 'hidden' ? 'opacity:.6;' : '' }}">
        <div class="card-body">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;">
                <div>
                    <strong>{{ $review->destination }}</strong>
                    <span style="color:#f5a623;">{{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', 5-$review->rating) }}</span>
                    <span style="margin-left:8px;padding:2px 6px;border-radius:4px;font-size:.75rem;background:{{ $review->status === 'active' ? '#d4edda' : '#f8d7da' }};color:{{ $review->status === 'active' ? '#155724' : '#721c24' }};">{{ $review->status }}</span>
                    <p style="margin:.5rem 0;">{{ $review->body }}</p>
                    <small style="color:#999;">{{ $review->user->full_name }} &bull; {{ $review->created_at->format('M j, Y') }}</small>
                </div>
                <div style="display:flex;gap:4px;flex-shrink:0;margin-left:1rem;">
                    @if($review->status === 'active')
                        <form method="POST" action="{{ route('admin.reviews.hide', $review) }}">
                            @csrf @method('PATCH')
                            <button class="btn btn-sm btn-warning">Hide</button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('admin.reviews.show', $review) }}">
                            @csrf @method('PATCH')
                            <button class="btn btn-sm btn-success">Show</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
@empty
    <p>No reviews found.</p>
@endforelse
{{ $reviews->links() }}
@endsection
