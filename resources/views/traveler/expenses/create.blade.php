@extends('layouts.app')
@section('title', 'Scan Expense')
@section('content')

@if ($trips->isEmpty())
<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:70vh;text-align:center;">
    <div style="width:72px;height:72px;border-radius:20px;background:#F5EDE7;display:flex;align-items:center;justify-content:center;margin-bottom:20px;">
        <i class="fa-solid fa-receipt" style="font-size:32px;color:var(--primary);"></i>
    </div>
    <h2 style="font-weight:700;margin-bottom:8px;">No trips planned yet</h2>
    <p class="text-muted" style="max-width:320px;margin-bottom:24px;">Plan a trip first before logging your expenses.</p>
    <a href="{{ route('trips.plan') }}" class="btn btn-primary btn-lg">
        <i class="fa-solid fa-paper-plane"></i> Plan a Trip
    </a>
</div>
@else

<div style="display:flex;align-items:center;justify-content:space-between;" class="mb-24">
    <div>
        <h1>Scan Your Receipt</h1>
        <p class="text-muted">Upload a photo of your receipt to automatically extract and track expenses.</p>
    </div>
    <a href="{{ route('expenses.index') }}" class="btn btn-outline">← Back</a>
</div>

<div style="display:grid;grid-template-columns:1fr 320px;gap:24px;align-items:start;">
    {{-- Left: drop zone + form --}}
    <div>
        {{-- Drop zone --}}
        <div class="dropzone" id="dropZone">
            <div class="drop-icon"><i class="fa-solid fa-camera"></i></div>
            <h3>Drag & Drop Receipt</h3>
            <p>or click to browse from your computer</p>
            <button type="button" class="btn btn-primary" onclick="document.getElementById('receiptFile').click()">
                <i class="fa-solid fa-upload"></i> Select File
            </button>
            <input type="file" id="receiptFile" accept="image/*,application/pdf" style="display:none;">
        </div>

        <div id="ocrStatus" style="display:none;" class="alert alert-warning mt-8">
            <i class="fa-solid fa-spinner fa-spin"></i> Scanning receipt...
        </div>

        {{-- Expense Details form --}}
        <div class="card mt-16">
            <h3 class="mb-4">Expense Details</h3>
            <p class="text-muted mb-16" style="font-size:13px;">Fill in details below or let OCR auto-fill after scanning.</p>

            @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if ($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('expenses.store') }}" enctype="multipart/form-data">
                @csrf

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                    <div class="form-group">
                        <label class="form-label" for="trip_id">Trip</label>
                        <select name="trip_id" id="trip_id"
                                class="form-control {{ $errors->has('trip_id') ? 'is-invalid' : '' }}" required>
                            <option value="">Select trip</option>
                            @foreach ($trips as $trip)
                            <option value="{{ $trip->id }}"
                                {{ (old('trip_id', request('trip_id')) == $trip->id) ? 'selected' : '' }}>
                                {{ $trip->destination }} ({{ $trip->start_date->format('M Y') }})
                            </option>
                            @endforeach
                        </select>
                        @error('trip_id')<div class="error">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="amount">Amount (₱)</label>
                        <input type="number" id="amount" name="amount" step="0.01" min="0.01"
                               value="{{ old('amount') }}"
                               class="form-control {{ $errors->has('amount') ? 'is-invalid' : '' }}"
                               placeholder="0.00" required>
                        @error('amount')<div class="error">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                    <div class="form-group">
                        <label class="form-label" for="category">Category</label>
                        <select name="category" id="category"
                                class="form-control {{ $errors->has('category') ? 'is-invalid' : '' }}" required>
                            <option value="">Select category</option>
                            @foreach ($categories as $cat)
                            <option value="{{ $cat }}" {{ old('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                            @endforeach
                        </select>
                        @error('category')<div class="error">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="expense_date">Date</label>
                        <input type="date" id="expense_date" name="expense_date"
                               value="{{ old('expense_date', date('Y-m-d')) }}"
                               class="form-control {{ $errors->has('expense_date') ? 'is-invalid' : '' }}" required>
                        @error('expense_date')<div class="error">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="description">Description / Merchant</label>
                    <input type="text" id="description" name="description"
                           value="{{ old('description') }}"
                           class="form-control" placeholder="e.g. L'Osteria Pizza">
                </div>

                {{-- Hidden real file input for form submission --}}
                <input type="file" name="receipt" id="receiptSubmit" style="display:none;" accept="image/*,application/pdf">

                <button type="submit" class="btn btn-primary btn-lg btn-block mt-8">
                    <i class="fa-solid fa-floppy-disk"></i> Save Expense
                </button>
            </form>
        </div>
    </div>

    {{-- Right sidebar --}}
    <div>
        {{-- Camera option --}}
        <div class="card mb-16" style="background:var(--color-blue);color:white;border-color:var(--color-blue);">
            <div style="display:flex;align-items:center;justify-content:space-between;">
                <div>
                    <div style="font-weight:600;font-size:14px;">On the go?</div>
                    <div style="font-size:12px;opacity:0.85;">Use mobile camera</div>
                </div>
                <button type="button"
                        onclick="document.getElementById('mobileCamera').click()"
                        style="background:rgba(255,255,255,0.2);border:none;border-radius:50%;width:40px;height:40px;cursor:pointer;color:white;font-size:18px;">
                    <i class="fa-solid fa-camera"></i>
                </button>
            </div>
            <input type="file" id="mobileCamera" accept="image/*" capture="environment" style="display:none;">
        </div>

        {{-- Recent Scans --}}
        <div class="card mb-16">
            <div style="display:flex;align-items:center;justify-content:space-between;" class="mb-12">
                <h3 style="font-size:15px;">Recent Scans</h3>
                <a href="{{ route('expenses.index') }}" style="font-size:12px;color:var(--color-primary);">View All</a>
            </div>
            @php $recentExpenses = auth()->user()->expenses()->with('trip')->latest('expense_date')->limit(3)->get(); @endphp
            @forelse ($recentExpenses as $exp)
            <div style="padding:8px 0;border-bottom:1px solid #F5F0EB;font-size:13px;">
                <div style="font-weight:500;">{{ $exp->description ?: $exp->category }}</div>
                <div class="text-muted" style="font-size:11px;">₱{{ number_format($exp->amount,2) }} · {{ $exp->expense_date->format('M j') }}</div>
            </div>
            @empty
            <p class="text-muted" style="font-size:13px;">No expenses yet. Add one above.</p>
            <div style="margin-top:8px;">
                <span class="badge badge-primary">#Travel</span>
                <span class="badge" style="background:#C9A84C;color:#fff;">#Budget</span>
                <span class="badge badge-blue">#Expenses</span>
            </div>
            @endforelse
        </div>

        {{-- Active trip card --}}
        @php $latestTrip = auth()->user()->trips()->latest()->first(); @endphp
        @if ($latestTrip)
        <div class="card" style="background:var(--color-gold);border-color:var(--color-gold);color:white;">
            <div style="font-size:20px;margin-bottom:8px;">🎒</div>
            <div style="font-weight:700;font-size:15px;">{{ $latestTrip->destination }}</div>
            @php $saved = $latestTrip->savingsGoals()->sum('current_savings'); $target = $latestTrip->budget_limit; @endphp
            <div class="progress mt-8" style="background:rgba(255,255,255,0.3);">
                <div class="progress-bar" style="background:white;width:{{ $target > 0 ? min(100, round($saved/$target*100)) : 0 }}%;"></div>
            </div>
            <div style="font-size:12px;opacity:0.9;margin-top:6px;">₱{{ number_format($saved,2) }} of ₱{{ number_format($target,2) }} saved</div>
        </div>
        @endif
    </div>
</div>

@endif
@endsection

@push('scripts')
<script>
(function () {
    var dropZone    = document.getElementById('dropZone');
    var fileInput   = document.getElementById('receiptFile');
    var mobileInput = document.getElementById('mobileCamera');
    var submitInput = document.getElementById('receiptSubmit');
    var statusDiv   = document.getElementById('ocrStatus');
    var amountInput = document.getElementById('amount');
    var dateInput   = document.getElementById('expense_date');
    var descInput   = document.getElementById('description');

    function handleFile(file) {
        if (!file) return;
        // Copy file to the real submit input
        var dt = new DataTransfer();
        dt.items.add(file);
        submitInput.files = dt.files;

        // Show scanning status
        statusDiv.style.display = '';

        var reader = new FileReader();
        reader.onload = function (e) {
            var formData = new FormData();
            formData.append('receipt', file);
            formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);

            fetch('/expenses/ocr', { method: 'POST', body: formData })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    statusDiv.style.display = 'none';
                    if (data.amount)      amountInput.value = data.amount;
                    if (data.date)        dateInput.value   = data.date;
                    if (data.description) descInput.value   = data.description;
                })
                .catch(function () { statusDiv.style.display = 'none'; });
        };
        reader.readAsDataURL(file);
    }

    // Click to browse
    fileInput.addEventListener('change', function () { handleFile(this.files[0]); });
    mobileInput.addEventListener('change', function () { handleFile(this.files[0]); });

    // Drag and drop
    dropZone.addEventListener('dragover', function (e) {
        e.preventDefault(); this.classList.add('dragover');
    });
    dropZone.addEventListener('dragleave', function () { this.classList.remove('dragover'); });
    dropZone.addEventListener('drop', function (e) {
        e.preventDefault(); this.classList.remove('dragover');
        handleFile(e.dataTransfer.files[0]);
    });
})();
</script>
@endpush
