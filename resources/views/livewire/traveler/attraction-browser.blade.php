<div>
    <div style="display:flex;align-items:center;justify-content:space-between;" class="mb-16">
        <div>
            <h1>Attractions</h1>
            <p class="text-muted">Explore popular spots at your destinations.</p>
        </div>
    </div>

    {{-- Filters --}}
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;max-width:560px;" class="mb-24">
        <div>
            <input type="text" wire:model.live.debounce.300ms="search"
                   class="form-control" placeholder="Search attractions...">
        </div>
        <div>
            <select wire:model.live="destination" class="form-control">
                <option value="">All destinations</option>
                @foreach ($this->destinations as $dest)
                <option value="{{ $dest }}">{{ $dest }}</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Card grid --}}
    @if ($this->attractions->isEmpty())
    <div style="text-align:center;padding:40px 24px;">
        <div style="font-size:40px;margin-bottom:12px;">🏔️</div>
        <h3 style="font-weight:700;margin-bottom:8px;">No attractions found</h3>
        <p class="text-muted" style="margin-bottom:20px;">Try a different search or destination filter.</p>
    </div>
    @else
    <div class="attraction-grid">
        @foreach ($this->attractions as $attraction)
        <a href="{{ route('attractions.show', $attraction) }}" style="text-decoration:none;color:inherit;">
            <div class="card">
                <div style="height:160px;background:#F3F4F6;border-radius:10px 10px 0 0;overflow:hidden;display:flex;align-items:center;justify-content:center;font-size:40px;">
                    @if ($attraction->image)
                    <img src="{{ asset('storage/' . $attraction->image) }}"
                         style="width:100%;height:100%;object-fit:cover;" alt="{{ $attraction->name }}">
                    @else
                    🗺️
                    @endif
                </div>
                <div class="card-body">
                    <div style="font-size:14px;font-weight:600;">{{ $attraction->name }}</div>
                    <span class="badge badge-primary mt-4">{{ $attraction->destination }}</span>
                    <div class="mt-4" style="display:flex;align-items:center;gap:4px;">
                        <span class="stars">{{ str_repeat('★', round($attraction->rating)) }}{{ str_repeat('☆', 5 - round($attraction->rating)) }}</span>
                        <span class="text-muted" style="font-size:12px;">{{ number_format($attraction->rating, 1) }}</span>
                    </div>
                    <div class="btn btn-primary btn-sm mt-8" style="font-size:11px;">View Details</div>
                </div>
            </div>
        </a>
        @endforeach
    </div>
    @endif
</div>
