@extends('layouts.admin')
@section('title', 'System Reports')
@section('content')
<h1>System Reports</h1>

<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:1rem;margin-bottom:2rem;">
    <div class="card text-center">
        <div class="card-body">
            <h2 style="margin:0;font-size:2rem;">{{ $stats['total_users'] }}</h2>
            <p style="margin:0;color:#666;">Total Users</p>
        </div>
    </div>
    <div class="card text-center">
        <div class="card-body">
            <h2 style="margin:0;font-size:2rem;">{{ $stats['total_trips'] }}</h2>
            <p style="margin:0;color:#666;">Total Trips</p>
        </div>
    </div>
    <div class="card text-center">
        <div class="card-body">
            <h2 style="margin:0;font-size:2rem;">₱{{ number_format($stats['total_expenses'], 2) }}</h2>
            <p style="margin:0;color:#666;">Total Expenses</p>
        </div>
    </div>
    <div class="card text-center">
        <div class="card-body">
            <h2 style="margin:0;font-size:2rem;">{{ $stats['total_reviews'] }}</h2>
            <p style="margin:0;color:#666;">Total Reviews</p>
        </div>
    </div>
    <div class="card text-center">
        <div class="card-body">
            <h2 style="margin:0;font-size:2rem;">{{ $stats['ocr_success'] }}</h2>
            <p style="margin:0;color:#666;">OCR Scans (OK)</p>
        </div>
    </div>
</div>

<h3>Top Destinations</h3>
@forelse($topDestinations as $row)
    <div style="display:flex;justify-content:space-between;align-items:center;padding:.5rem 0;border-bottom:1px solid #eee;">
        <span>{{ $row->destination }}</span>
        <span style="font-weight:600;">{{ $row->trip_count }} trips</span>
    </div>
@empty
    <p>No trip data yet.</p>
@endforelse
@endsection
