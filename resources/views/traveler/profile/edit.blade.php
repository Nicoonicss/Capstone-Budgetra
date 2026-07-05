@extends('layouts.app')

@section('title', 'My Profile')

@section('content')
<div class="page-header">
    <h1>My Profile</h1>
</div>

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if ($errors->any())
    <div class="alert alert-danger">
        <ul style="margin:0;padding-left:1.2em;">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card">
    <div class="card-body">
        @if ($user->profile_photo)
            <div style="margin-bottom:1rem;">
                <img src="{{ Storage::url($user->profile_photo) }}"
                     width="80" height="80"
                     style="border-radius:50%;object-fit:cover;"
                     alt="Profile photo">
            </div>
        @endif

        <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label for="full_name">Full Name <span style="color:red">*</span></label>
                <input type="text" id="full_name" name="full_name"
                       class="form-control @error('full_name') is-invalid @enderror"
                       value="{{ old('full_name', $user->full_name) }}" required>
                @error('full_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label for="phone">Phone</label>
                <input type="text" id="phone" name="phone"
                       class="form-control"
                       value="{{ old('phone', $user->phone) }}">
            </div>

            <div class="form-group">
                <label for="country">Country</label>
                <input type="text" id="country" name="country"
                       class="form-control"
                       value="{{ old('country', $user->country) }}">
            </div>

            <div class="form-group">
                <label for="currency_code">Currency Code</label>
                <input type="text" id="currency_code" name="currency_code"
                       class="form-control" maxlength="10"
                       value="{{ old('currency_code', $user->currency_code) }}">
            </div>

            <div class="form-group">
                <label for="currency_symbol">Currency Symbol</label>
                <input type="text" id="currency_symbol" name="currency_symbol"
                       class="form-control" maxlength="10"
                       value="{{ old('currency_symbol', $user->currency_symbol) }}">
            </div>

            <div class="form-group">
                <label for="profile_photo">Profile Photo</label>
                <input type="file" id="profile_photo" name="profile_photo"
                       class="form-control @error('profile_photo') is-invalid @enderror"
                       accept="image/jpeg,image/png,image/jpg,image/webp">
                @error('profile_photo') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <button type="submit" class="btn btn-primary">Save Changes</button>
        </form>
    </div>
</div>
@endsection
