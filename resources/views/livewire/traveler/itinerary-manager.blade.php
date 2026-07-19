<div style="display:flex;flex-direction:column;flex:1;min-height:0;">

@if ($this->trips->isEmpty())
{{-- Empty state --}}
<div class="empty-state-center">
    <div style="width:72px;height:72px;border-radius:20px;background:#F5EDE7;display:flex;align-items:center;justify-content:center;margin-bottom:20px;">
        <i class="fa-solid fa-calendar-days" style="font-size:32px;color:var(--primary);"></i>
    </div>
    <h2 style="font-weight:700;margin-bottom:8px;">No itineraries yet</h2>
    <p class="text-muted" style="max-width:320px;margin-bottom:24px;">Plan your first trip to generate an itinerary for your destination.</p>
    <a href="{{ route('trips.plan') }}" class="btn btn-primary btn-lg">
        <i class="fa-solid fa-paper-plane"></i> Plan a Trip
    </a>
</div>

@else
{{-- Destination selector + calendar --}}
<div style="flex:1;display:flex;align-items:center;justify-content:center;">
<div style="width:100%;max-width:720px;">

    {{-- Destination dropdown --}}
    <div style="margin-bottom:20px;">
        <div style="font-size:10px;font-weight:700;letter-spacing:.1em;color:var(--primary);text-transform:uppercase;margin-bottom:6px;">Destination</div>
        <div style="position:relative;">
            <i class="fa-solid fa-location-dot" style="position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#C8874A;font-size:13px;pointer-events:none;z-index:1;"></i>
            @if ($this->trips->count() === 1)
            {{-- Single trip: show as read-only display --}}
            @php $onlyTrip = $this->trips->first(); @endphp
            <div style="background:#fff;border:1.5px solid var(--border);border-radius:10px;padding:12px 40px 12px 38px;font-size:14px;font-weight:600;color:var(--dark);">
                {{ $onlyTrip->origin ?? 'Manila' }} to {{ $onlyTrip->destination }}
            </div>
            @else
            {{-- Multiple trips: dropdown --}}
            <select wire:model.live="selectedTripId"
                    style="width:100%;background:#fff;border:1.5px solid var(--border);border-radius:10px;padding:12px 40px 12px 38px;font-size:14px;font-weight:600;color:var(--dark);appearance:none;cursor:pointer;outline:none;">
                <option value="">— Select a trip —</option>
                @foreach($this->trips as $t)
                <option value="{{ $t->id }}">
                    {{ $t->origin ?? 'Manila' }} to {{ $t->destination }}
                    ({{ $t->start_date->format('M j') }}–{{ $t->end_date->format('M j, Y') }})
                </option>
                @endforeach
            </select>
            <i class="fa-solid fa-chevron-down" style="position:absolute;right:14px;top:50%;transform:translateY(-50%);color:#9B8EA0;font-size:12px;pointer-events:none;"></i>
            @endif
        </div>
    </div>

    @if ($selectedTripId && $this->selectedTrip)
    {{-- Calendar card --}}
    <div style="background:#fff;border:1.5px solid var(--border);border-radius:16px;padding:24px 28px;box-shadow:0 2px 10px rgba(0,0,0,.04);">
        <div id="calendar-wrapper"
             wire:ignore
             data-events="{{ json_encode($this->events) }}"
             data-start="{{ $this->selectedTrip->start_date->toDateString() }}"
             data-end="{{ $this->selectedTrip->end_date->clone()->addDay()->toDateString() }}"
             data-initial="{{ $this->selectedTrip->start_date->toDateString() }}">
            <div id="itinerary-calendar"></div>
        </div>
    </div>

    {{-- Legend --}}
    <div style="display:flex;gap:16px;flex-wrap:wrap;margin-top:14px;">
        <div style="display:flex;align-items:center;gap:6px;font-size:12px;color:#6B5E5B;">
            <span style="width:20px;height:20px;border-radius:6px;background:#EFF6FF;display:inline-flex;align-items:center;justify-content:center;">
                <i class="fa-solid fa-plane" style="font-size:9px;color:#1D4ED8;"></i>
            </span> Flight
        </div>
        <div style="display:flex;align-items:center;gap:6px;font-size:12px;color:#6B5E5B;">
            <span style="width:20px;height:20px;border-radius:6px;background:#F0FDF4;display:inline-flex;align-items:center;justify-content:center;">
                <i class="fa-solid fa-bed" style="font-size:9px;color:#16A34A;"></i>
            </span> Hotel
        </div>
        <div style="display:flex;align-items:center;gap:6px;font-size:12px;color:#6B5E5B;">
            <span style="width:20px;height:20px;border-radius:6px;background:#F5EDE7;display:inline-flex;align-items:center;justify-content:center;">
                <i class="fa-solid fa-camera" style="font-size:9px;color:#8B3A10;"></i>
            </span> Activity
        </div>
        <div style="display:flex;align-items:center;gap:6px;font-size:12px;color:#6B5E5B;">
            <span style="width:20px;height:20px;border-radius:6px;background:#FFF7ED;display:inline-flex;align-items:center;justify-content:center;">
                <i class="fa-solid fa-car" style="font-size:9px;color:#D97706;"></i>
            </span> Transport
        </div>
    </div>
    @endif

</div>
</div>
@endif

{{-- ── Generate Itinerary modal ──────────────────────── --}}
@if ($showGenerateModal && $selectedDate)
<div style="position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1000;display:flex;align-items:center;justify-content:center;padding:16px;" wire:click.self="closeModals">
    <div style="background:#fff;border-radius:16px;width:100%;max-width:380px;box-shadow:0 24px 64px rgba(0,0,0,.18);padding:24px;">
        <h2 style="font-size:16px;font-weight:700;color:var(--dark);margin:0 0 8px;">No activities planned for this day yet.</h2>
        <p style="font-size:14px;color:var(--muted);margin:0 0 24px;line-height:1.55;">
            Auto-generate a full day schedule for <strong style="color:var(--dark);">{{ $this->selectedTrip?->destination }}</strong>?
        </p>
        <div style="display:flex;gap:10px;">
            <button wire:click="closeModals" class="btn btn-outline" style="flex:1;">Cancel</button>
            <button wire:click="generateItinerary" class="btn btn-primary" style="flex:1;"
                    wire:loading.attr="disabled" wire:target="generateItinerary">
                <span wire:loading.remove wire:target="generateItinerary"><i class="fa-solid fa-wand-magic-sparkles"></i> Generate</span>
                <span wire:loading wire:target="generateItinerary"><i class="fa-solid fa-spinner fa-spin"></i> Generating…</span>
            </button>
        </div>
    </div>
</div>
@endif

{{-- ── Day itinerary modal ────────────────────────────── --}}
@if ($showDayModal && $selectedDate)
<div style="position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1000;display:flex;align-items:center;justify-content:center;padding:16px;" wire:click.self="closeModals">
    <div style="background:#fff;border-radius:20px;width:100%;max-width:480px;max-height:80vh;display:flex;flex-direction:column;box-shadow:0 24px 64px rgba(0,0,0,.18);overflow:hidden;">
        <div style="background:var(--primary);padding:20px 24px;flex-shrink:0;">
            <div style="display:flex;align-items:center;justify-content:space-between;">
                <div>
                    <p style="color:rgba(255,255,255,.75);font-size:12px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;margin:0 0 4px;">{{ $this->selectedTrip?->destination }}</p>
                    <h2 style="color:#fff;margin:0;font-size:18px;">{{ \Carbon\Carbon::parse($selectedDate)->format('l, F j') }}</h2>
                </div>
                <button wire:click="closeModals" style="background:rgba(255,255,255,.2);border:none;border-radius:8px;width:32px;height:32px;display:flex;align-items:center;justify-content:center;cursor:pointer;color:#fff;font-size:16px;">&times;</button>
            </div>
        </div>
        <div style="display:flex;padding:12px 24px 8px;border-bottom:1px solid var(--border);flex-shrink:0;">
            <div style="width:72px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);">TIME</div>
            <div style="flex:1;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);">ACTIVITY</div>
        </div>
        <div style="overflow-y:auto;flex:1;padding:8px 24px 20px;">
            @forelse ($this->dayItems as $item)
            @php
                $typeColors = [
                    'Flight'         => ['bg'=>'#EFF6FF','text'=>'#1D4ED8','icon'=>'fa-plane'],
                    'Hotel'          => ['bg'=>'#F0FDF4','text'=>'#16A34A','icon'=>'fa-bed'],
                    'Activity'       => ['bg'=>'#F5EDE7','text'=>'var(--primary)','icon'=>'fa-map-pin'],
                    'Transportation' => ['bg'=>'#FFF7ED','text'=>'#D97706','icon'=>'fa-car'],
                ];
                $tc = $typeColors[$item->type] ?? $typeColors['Activity'];
            @endphp
            <div style="display:flex;align-items:flex-start;padding:12px 0;border-bottom:1px solid #F3F4F6;">
                <div style="width:72px;flex-shrink:0;padding-top:2px;">
                    <span style="font-size:12px;font-weight:700;color:var(--dark);">{{ $item->start_datetime->format('g:i A') }}</span>
                </div>
                <div style="flex:1;display:flex;align-items:flex-start;gap:10px;">
                    <div style="width:28px;height:28px;border-radius:8px;background:{{ $tc['bg'] }};display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="fa-solid {{ $tc['icon'] }}" style="font-size:11px;color:{{ $tc['text'] }};"></i>
                    </div>
                    <div style="flex:1;">
                        <div style="font-size:14px;font-weight:600;color:var(--dark);">{{ $item->title }}</div>
                        @if ($item->notes)<div style="font-size:12px;color:var(--muted);margin-top:2px;">{{ $item->notes }}</div>@endif
                        <span style="display:inline-block;margin-top:4px;padding:2px 8px;border-radius:20px;font-size:11px;font-weight:600;background:{{ $tc['bg'] }};color:{{ $tc['text'] }};">{{ $item->type }}</span>
                    </div>
                    <button wire:click="deleteItem({{ $item->id }})" style="background:none;border:none;cursor:pointer;color:#9CA3AF;padding:4px;flex-shrink:0;">
                        <i class="fa-solid fa-xmark" style="font-size:12px;"></i>
                    </button>
                </div>
            </div>
            @empty
            <p class="text-muted" style="text-align:center;padding:24px 0;font-size:13px;">No items for this day.</p>
            @endforelse
        </div>
        <div style="padding:16px 24px;border-top:1px solid var(--border);display:flex;gap:10px;flex-shrink:0;">
            <button wire:click="closeModals" class="btn btn-outline" style="flex:1;">Close</button>
            <button wire:click="generateItinerary" class="btn btn-primary" style="flex:1;"
                    wire:loading.attr="disabled" wire:target="generateItinerary">
                <span wire:loading.remove wire:target="generateItinerary"><i class="fa-solid fa-arrows-rotate"></i> Regenerate</span>
                <span wire:loading wire:target="generateItinerary"><i class="fa-solid fa-spinner fa-spin"></i> Generating…</span>
            </button>
        </div>
    </div>
</div>
@endif

</div>
