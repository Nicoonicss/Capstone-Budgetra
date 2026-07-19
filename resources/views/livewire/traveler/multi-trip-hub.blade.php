<div>
    {{-- Header --}}
    <div style="display:flex;align-items:center;justify-content:space-between;" class="mb-24">
        <div>
            <h1>Multi-Trip Hub</h1>
            <p class="text-muted">{{ $this->totals['count'] }} trip{{ $this->totals['count'] !== 1 ? 's' : '' }} total</p>
        </div>
        <a href="{{ route('trips.plan') }}" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> Plan New Adventure
        </a>
    </div>

    {{-- Aggregate stat tiles --}}
    <div class="stats-row mb-24">
        <div class="stat-card">
            <div class="stat-card-accent" style="background:var(--primary);"></div>
            <div class="stat-label"><i class="fa-solid fa-suitcase-rolling"></i> Total Trips</div>
            <div class="stat-value">{{ $this->totals['count'] }}</div>
            <div class="stat-sub" style="color:var(--primary);">All time</div>
        </div>
        <div class="stat-card">
            <div class="stat-card-accent" style="background:var(--secondary);"></div>
            <div class="stat-label"><i class="fa-solid fa-coins"></i> Total Budget</div>
            <div class="stat-value" style="color:var(--secondary);">₱{{ number_format($this->totals['budget'], 0) }}</div>
            <div class="stat-sub">Across all trips</div>
        </div>
        <div class="stat-card">
            <div class="stat-card-accent" style="background:var(--tertiary);"></div>
            <div class="stat-label"><i class="fa-regular fa-credit-card"></i> Total Spent</div>
            <div class="stat-value" style="color:var(--tertiary);">₱{{ number_format($this->totals['spent'], 0) }}</div>
            <div class="stat-sub">Across all trips</div>
        </div>
    </div>

    {{-- Search --}}
    <div style="display:flex;align-items:center;justify-content:space-between;" class="mb-16">
        <div style="max-width:300px;width:100%;">
            <input type="text" wire:model.live.debounce.300ms="search"
                   class="form-control" placeholder="Search by destination...">
        </div>
        @if (count($compareIds) > 0)
        <div style="display:flex;align-items:center;gap:8px;">
            <span class="text-muted" style="font-size:13px;">{{ count($compareIds) }}/2 selected</span>
            @if (count($compareIds) === 2)
            <button class="btn btn-primary btn-sm" wire:click="openComparison">Compare Now</button>
            @endif
            <button class="btn btn-outline btn-sm" wire:click="clearCompare">Clear</button>
        </div>
        @endif
    </div>

    {{-- Active Trips --}}
    @if ($this->activeTrips->isNotEmpty())
    <h2 class="mb-16">Active Trips</h2>
    <div class="planner-trips-grid mb-32">
        @foreach ($this->activeTrips as $trip)
        <div class="planner-trip-card" style="background:linear-gradient(160deg, #2E7D32, #66BB6A);">
            <div class="trip-card-overlay"></div>
            <div class="trip-card-compare">
                <input type="checkbox"
                       wire:click="toggleCompare({{ $trip->id }})"
                       {{ in_array($trip->id, $compareIds) ? 'checked' : '' }}
                       title="Select for comparison">
            </div>
            <span class="trip-card-status {{ $trip->status }}">{{ ucfirst($trip->status) }}</span>
            <div class="trip-card-body">
                <div class="trip-card-dest">{{ $trip->destination }}</div>
                <div class="trip-card-dates">
                    {{ $trip->start_date->format('M j') }} – {{ $trip->end_date->format('M j, Y') }}
                    · {{ $trip->days }} days
                </div>
                <div class="trip-card-budget">
                    Spent ₱{{ number_format($trip->total_spent, 0) }} / ₱{{ number_format($trip->budget_limit, 0) }}
                </div>
                <div class="progress mt-8" style="background:rgba(255,255,255,0.25);">
                    <div class="progress-bar"
                         style="background:rgba(255,255,255,0.85);width:{{ min(100,$trip->pct_used) }}%;"></div>
                </div>
                <div class="trip-card-actions mt-8">
                    <a href="{{ route('trips.dashboard', $trip) }}">Dashboard</a>
                    <a href="{{ route('expenses.index') }}?trip_id={{ $trip->id }}">Expenses</a>
                </div>
            </div>
        </div>
        @endforeach

        {{-- New trip card --}}
        <a href="{{ route('trips.plan') }}" class="planner-trip-card" style="border:2px dashed var(--border);background:transparent;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:8px;color:var(--muted);text-decoration:none;">
            <i class="fa-solid fa-plus" style="font-size:26px;"></i>
            <span style="font-size:13px;font-weight:600;">Plan New Adventure</span>
        </a>
    </div>
    @endif

    {{-- Past Trips --}}
    @if ($this->pastTrips->isNotEmpty())
    <h2 class="mb-16">Past Trips</h2>
    @foreach ($this->pastTrips as $trip)
    <div class="chart-card" style="display:flex;align-items:center;gap:16px;margin-bottom:12px;">
        <div style="flex:1;">
            <div style="font-size:14px;font-weight:600;">{{ $trip->destination }}</div>
            <div class="text-muted" style="font-size:12px;">{{ $trip->start_date->format('M j') }} – {{ $trip->end_date->format('M j, Y') }}</div>
        </div>
        <div style="text-align:center;">
            <div style="font-size:13px;font-weight:600;">₱{{ number_format($trip->budget_limit, 0) }}</div>
            <div class="text-muted" style="font-size:11px;">Budget</div>
        </div>
        <div style="text-align:center;">
            <div style="font-size:13px;font-weight:600;">₱{{ number_format($trip->total_spent, 0) }}</div>
            <div class="text-muted" style="font-size:11px;">Spent</div>
        </div>
        <span class="badge" style="opacity:0.6;background:#F3F4F6;color:#6B7280;">PAST</span>
        <div style="display:flex;align-items:center;gap:6px;">
            <input type="checkbox"
                   wire:click="toggleCompare({{ $trip->id }})"
                   {{ in_array($trip->id, $compareIds) ? 'checked' : '' }}
                   title="Compare">
            <a href="{{ route('trips.dashboard', $trip) }}" class="btn btn-outline btn-sm">View</a>
        </div>
    </div>
    @endforeach
    @endif

    {{-- Empty state --}}
    @if ($search && $this->trips->isEmpty())
    <div style="text-align:center;padding:40px 24px;">
        <div style="font-size:40px;margin-bottom:12px;">🔍</div>
        <h3 style="font-weight:700;margin-bottom:8px;">No trips match "{{ $search }}"</h3>
        <p class="text-muted" style="margin-bottom:20px;">Try a different search term.</p>
    </div>
    @elseif ($this->trips->isEmpty())
    <div class="empty-state-center">
        <div style="width:72px;height:72px;border-radius:20px;background:#F5EDE7;display:flex;align-items:center;justify-content:center;margin-bottom:20px;">
            <i class="fa-solid fa-layer-group" style="font-size:32px;color:var(--primary);"></i>
        </div>
        <h2 style="font-weight:700;margin-bottom:8px;">No trips yet</h2>
        <p class="text-muted" style="max-width:320px;margin-bottom:24px;">Start planning your first adventure and track every expense.</p>
        <a href="{{ route('trips.plan') }}" class="btn btn-primary btn-lg">
            <i class="fa-solid fa-paper-plane"></i> Plan Your First Trip
        </a>
    </div>
    @endif

    {{-- Comparison Modal --}}
    @if ($showComparison && count($this->compareTrips) === 2)
    @php [$t1, $t2] = $this->compareTrips; @endphp
    <div style="position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.5);z-index:1000;display:flex;align-items:center;justify-content:center;" wire:click.self="closeComparison">
        <div class="compare-modal" style="background:#fff;border-radius:16px;padding:28px;max-width:640px;width:90%;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
                <h2>Trip Comparison</h2>
                <button style="background:none;border:none;font-size:22px;cursor:pointer;color:#6B7280;line-height:1;" wire:click="closeComparison">×</button>
            </div>
            <div class="compare-grid">
                @foreach ([$t1, $t2] as $t)
                <div class="compare-col">
                    <h3>{{ $t['destination'] }}</h3>
                    <div class="compare-metric">
                        <span class="compare-label">Budget</span>
                        <span class="compare-val">₱{{ number_format($t['budget_limit'], 0) }}</span>
                    </div>
                    <div class="compare-metric">
                        <span class="compare-label">Spent</span>
                        <span class="compare-val">₱{{ number_format($t['total_spent'], 0) }}</span>
                    </div>
                    <div class="compare-metric">
                        <span class="compare-label">Budget Used</span>
                        <span class="compare-val">{{ $t['pct_used'] }}%</span>
                    </div>
                    <div class="compare-metric">
                        <span class="compare-label">Daily Avg</span>
                        <span class="compare-val">₱{{ number_format($t['daily_avg'], 0) }}</span>
                    </div>
                    <div class="compare-metric">
                        <span class="compare-label">Duration</span>
                        <span class="compare-val">{{ $t['days'] }} days</span>
                    </div>
                </div>
                @endforeach
            </div>
            <button class="btn btn-outline btn-block mt-16" wire:click="closeComparison">Close</button>
        </div>
    </div>
    @endif
</div>
