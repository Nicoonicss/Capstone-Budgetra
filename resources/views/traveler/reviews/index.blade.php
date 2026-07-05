@extends('layouts.app')
@section('title', 'Traveler Reviews')
@section('content')
<div class="page-header" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;">
    <h1>Traveler Reviews</h1>
    <button onclick="document.getElementById('review-modal').style.display='flex'" class="btn btn-primary">+ Write Review</button>
</div>

<form method="GET" style="margin-bottom:1rem;">
    <select name="destination" class="form-control" style="width:auto;display:inline-block;" onchange="this.form.submit()">
        <option value="">All Destinations</option>
        @foreach($destinations as $d)
            <option value="{{ $d }}" {{ request('destination') === $d ? 'selected' : '' }}>{{ $d }}</option>
        @endforeach
    </select>
</form>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

@forelse($reviews as $review)
    <div class="card" style="margin-bottom:1rem;">
        <div class="card-body">
            <div style="display:flex;justify-content:space-between;">
                <div>
                    <strong>{{ $review->destination }}</strong>
                    <span style="color:#f5a623;margin-left:8px;">{{ str_repeat('★',$review->rating) }}{{ str_repeat('☆',5-$review->rating) }}</span>
                </div>
                <small style="color:#999;">{{ $review->user->full_name }} &bull; {{ $review->created_at->diffForHumans() }}</small>
            </div>
            <p style="margin-top:.5rem;">{{ $review->body }}</p>
        </div>
    </div>
@empty
    <p>No reviews yet. Be the first to share your experience!</p>
@endforelse
{{ $reviews->links() }}

{{-- Submit review modal --}}
<div id="review-modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,.5);justify-content:center;align-items:center;z-index:1000;">
    <div class="card" style="width:500px;max-width:90%;">
        <div class="card-body">
            <h3>Write a Review</h3>
            <form method="POST" action="{{ route('reviews.store') }}">
                @csrf
                <div class="form-group">
                    <label>Destination</label>
                    <select name="destination" class="form-control" required>
                        <option value="">Select...</option>
                        @foreach($destinations as $d)<option value="{{ $d }}">{{ $d }}</option>@endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Rating</label>
                    <select name="rating" class="form-control" required>
                        @for($i = 5; $i >= 1; $i--)
                            <option value="{{ $i }}">{{ $i }} Star{{ $i > 1 ? 's' : '' }}</option>
                        @endfor
                    </select>
                </div>
                <div class="form-group">
                    <label>Your Experience</label>
                    <textarea name="body" class="form-control" rows="4" minlength="10" required></textarea>
                </div>
                <div style="display:flex;gap:8px;">
                    <button type="submit" class="btn btn-primary">Submit</button>
                    <button type="button" onclick="document.getElementById('review-modal').style.display='none'" class="btn btn-secondary">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
