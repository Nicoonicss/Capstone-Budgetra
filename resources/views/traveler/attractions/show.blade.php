@extends('layouts.app')
@section('title', $attraction->name)
@section('content')

<a href="{{ route('attractions.index') }}" class="btn btn-outline btn-sm mb-16">
    <i class="fa-solid fa-arrow-left"></i> All Attractions
</a>

{{-- Hero --}}
<div class="card mb-24" style="position:relative;overflow:hidden;min-height:180px;background:linear-gradient(135deg,#5C2D0E,#C9A84C);color:white;border:none;">
    <div style="padding:28px;">
        <span class="badge" style="background:rgba(255,255,255,0.2);color:white;margin-bottom:12px;">{{ $attraction->category }}</span>
        <h1 style="color:white;font-size:30px;margin-bottom:6px;">{{ $attraction->name }}</h1>
        <div style="font-size:14px;opacity:0.85;margin-bottom:12px;">
            <i class="fa-solid fa-location-dot"></i> {{ $attraction->destination }}
        </div>
        <div style="display:flex;align-items:center;gap:6px;">
            <span style="color:#F39C12;font-size:18px;letter-spacing:2px;">
                {{ str_repeat('★', round($avgRating)) }}{{ str_repeat('☆', 5 - round($avgRating)) }}
            </span>
            <span style="font-size:15px;font-weight:600;">{{ number_format($avgRating, 1) }}</span>
            <span style="font-size:13px;opacity:0.8;">({{ $reviews->count() }} reviews)</span>
        </div>
    </div>
</div>

{{-- Description --}}
@if ($attraction->description)
<div class="card mb-24">
    <h2 class="mb-12">About</h2>
    <p style="font-size:14px;line-height:1.7;color:var(--color-text-muted);">{{ $attraction->description }}</p>
</div>
@endif

<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;align-items:start;">
    {{-- Reviews list --}}
    <div>
        <h2 class="mb-16">Reviews</h2>
        @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @forelse ($reviews as $review)
        <div class="card mb-12">
            <div style="display:flex;align-items:center;justify-content:space-between;" class="mb-8">
                <div style="display:flex;align-items:center;gap:10px;">
                    <div class="avatar" style="width:36px;height:36px;font-size:13px;">
                        {{ strtoupper(substr($review->user->full_name ?? 'U', 0, 1)) }}
                    </div>
                    <div>
                        <div style="font-size:14px;font-weight:600;">{{ $review->user->full_name ?? 'Traveler' }}</div>
                        <div class="text-muted" style="font-size:11px;">{{ $review->created_at->format('M j, Y') }}</div>
                    </div>
                </div>
                <span style="color:#F39C12;font-size:14px;">
                    {{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', 5 - $review->rating) }}
                </span>
            </div>
            <p style="font-size:14px;color:var(--color-text-muted);line-height:1.6;">{{ $review->body }}</p>
        </div>
        @empty
        <div style="text-align:center;padding:28px 24px;">
            <div style="font-size:40px;margin-bottom:12px;">💬</div>
            <p class="text-muted">No reviews yet. Be the first!</p>
        </div>
        @endforelse
    </div>

    {{-- Add review form --}}
    <div>
        <h2 class="mb-16">Leave a Review</h2>
        @auth
        @if ($hasReviewed)
        <div class="alert alert-success">You've already reviewed this attraction.</div>
        @else
        <div class="card">
            @if ($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
            @endif
            <form method="POST" action="{{ route('reviews.store') }}">
                @csrf
                <input type="hidden" name="destination"   value="{{ $attraction->destination }}">
                <input type="hidden" name="attraction_id" value="{{ $attraction->id }}">

                <div class="form-group">
                    <label class="form-label">Your Rating</label>
                    <div style="display:flex;gap:4px;">
                        @for ($i = 5; $i >= 1; $i--)
                        <input type="radio" name="rating" id="star{{ $i }}" value="{{ $i }}"
                               {{ old('rating') == $i ? 'checked' : '' }}>
                        <label for="star{{ $i }}" title="{{ $i }} star">★</label>
                        @endfor
                    </div>
                    @error('rating')<div class="error">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Your Review</label>
                    <textarea name="body" class="form-control {{ $errors->has('body') ? 'is-invalid' : '' }}"
                              placeholder="Share your experience (min. 10 characters)..."
                              rows="4" required>{{ old('body') }}</textarea>
                    @error('body')<div class="error">{{ $message }}</div>@enderror
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fa-solid fa-paper-plane"></i> Submit Review
                </button>
            </form>
        </div>
        @endif
        @endauth
    </div>
</div>

@endsection
