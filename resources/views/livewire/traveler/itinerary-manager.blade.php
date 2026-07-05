<div>

{{-- ══════════════════════════════════════════════════
     STEP 1 — No trip selected yet
══════════════════════════════════════════════════ --}}
@if (!$selectedTripId)

@if (!$this->trips->isEmpty() && !$selectedTripId)
<div class="mb-24">
    <div style="font-size:11px;font-weight:700;letter-spacing:.1em;color:var(--primary);text-transform:uppercase;margin-bottom:4px;">ITINERARY</div>
</div>
@endif

@if ($this->trips->isEmpty())
{{-- Empty state --}}
<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:70vh;text-align:center;">
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
{{-- Trip picker --}}
<div style="max-width:640px;">

    <div style="display:flex;flex-direction:column;gap:12px;">
        @foreach ($this->trips as $trip)
        @php
            $flagMap = [
                'luxor'=>'eg','cairo'=>'eg','egypt'=>'eg',
                'boracay'=>'ph','manila'=>'ph','cebu'=>'ph','davao'=>'ph','palawan'=>'ph','bohol'=>'ph','siargao'=>'ph','philippines'=>'ph',
                'brisbane'=>'au','sydney'=>'au','melbourne'=>'au','australia'=>'au',
                'tokyo'=>'jp','osaka'=>'jp','kyoto'=>'jp','japan'=>'jp',
                'paris'=>'fr','france'=>'fr',
                'london'=>'gb','uk'=>'gb','england'=>'gb',
                'new york'=>'us','los angeles'=>'us','usa'=>'us','america'=>'us',
                'bali'=>'id','jakarta'=>'id','indonesia'=>'id',
                'singapore'=>'sg',
                'bangkok'=>'th','thailand'=>'th','phuket'=>'th',
                'kuala lumpur'=>'my','malaysia'=>'my',
                'dubai'=>'ae','abu dhabi'=>'ae','uae'=>'ae',
                'rome'=>'it','milan'=>'it','italy'=>'it',
                'barcelona'=>'es','madrid'=>'es','spain'=>'es',
                'amsterdam'=>'nl','netherlands'=>'nl',
                'seoul'=>'kr','korea'=>'kr',
                'hong kong'=>'hk',
                'new zealand'=>'nz','auckland'=>'nz',
                'vietnam'=>'vn','hanoi'=>'vn','ho chi minh'=>'vn',
                'maldives'=>'mv',
                'greece'=>'gr','athens'=>'gr','santorini'=>'gr',
                'turkey'=>'tr','istanbul'=>'tr',
                'china'=>'cn','beijing'=>'cn','shanghai'=>'cn',
                'india'=>'in','mumbai'=>'in','delhi'=>'in',
                'canada'=>'ca','toronto'=>'ca','vancouver'=>'ca',
            ];
            $destKey = strtolower(trim($trip->destination ?? ''));
            $countryCode = null;
            foreach ($flagMap as $key => $code) {
                if (str_contains($destKey, $key)) { $countryCode = $code; break; }
            }
        @endphp
        <button wire:click="selectTrip({{ $trip->id }})"
                style="display:flex;align-items:center;gap:16px;padding:16px 20px;background:#fff;border:1.5px solid var(--border);border-radius:14px;cursor:pointer;text-align:left;width:100%;transition:border-color .15s,box-shadow .15s;"
                onmouseover="this.style.borderColor='var(--primary)';this.style.boxShadow='0 4px 16px rgba(139,58,16,.1)'"
                onmouseout="this.style.borderColor='var(--border)';this.style.boxShadow='none'">
            <div style="width:48px;height:48px;border-radius:12px;overflow:hidden;flex-shrink:0;background:#E5E7EB;display:flex;align-items:center;justify-content:center;">
                @if ($countryCode)
                <img src="https://flagcdn.com/w160/{{ $countryCode }}.png"
                     srcset="https://flagcdn.com/w320/{{ $countryCode }}.png 2x"
                     alt="{{ $countryCode }}"
                     style="width:100%;height:100%;object-fit:cover;">
                @else
                <i class="fa-solid fa-earth-asia" style="color:#6B7280;font-size:20px;"></i>
                @endif
            </div>
            <div style="flex:1;min-width:0;">
                <div style="font-size:16px;font-weight:700;color:var(--dark);">{{ $trip->destination }}</div>
                <div style="font-size:13px;color:var(--muted);margin-top:2px;">
                    {{ ucfirst($trip->travel_type ?? 'Trip') }} &middot;
                    {{ $trip->start_date->format('M j') }}–{{ $trip->end_date->format('M j, Y') }} &middot;
                    @php $iTripDays = (int)$trip->start_date->diffInDays($trip->end_date) + 1; @endphp
                    {{ $iTripDays }} day{{ $iTripDays !== 1 ? 's' : '' }}
                </div>
            </div>
            <i class="fa-solid fa-chevron-right" style="color:var(--muted);font-size:13px;"></i>
        </button>
        @endforeach
    </div>
</div>
@endif

@else
{{-- ══════════════════════════════════════════════════
     STEP 2 — Calendar view
══════════════════════════════════════════════════ --}}
<div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;flex-wrap:wrap;">
    <button wire:click="goBack" class="btn btn-back">← Back</button>
    <div>
        <h1 style="margin:0;font-size:22px;">{{ $this->selectedTrip?->destination }}</h1>
        <p class="text-muted" style="font-size:13px;margin:0;">
            {{ $this->selectedTrip?->start_date->format('M j') }} – {{ $this->selectedTrip?->end_date->format('M j, Y') }}
            &middot; Click a date to generate or view its itinerary
        </p>
    </div>
</div>

<div class="card card-body" style="padding:20px;">
    <div id="calendar-wrapper"
         wire:ignore
         data-events="{{ json_encode($this->events) }}"
         data-start="{{ $this->selectedTrip?->start_date->toDateString() }}"
         data-end="{{ $this->selectedTrip?->end_date->clone()->addDay()->toDateString() }}"
         data-initial="{{ $this->selectedTrip?->start_date->toDateString() }}">
        <div id="itinerary-calendar"></div>
    </div>
</div>

{{-- ── Generate Itinerary modal ──────────────────────── --}}
@if ($showGenerateModal && $selectedDate)
<div style="position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1000;display:flex;align-items:center;justify-content:center;padding:16px;" wire:click.self="closeModals">
    <div style="background:#fff;border-radius:16px;width:100%;max-width:380px;box-shadow:0 24px 64px rgba(0,0,0,.18);padding:24px;">
        <h2 style="font-size:16px;font-weight:700;color:var(--dark);margin:0 0 8px;">No activities planned for this day yet.</h2>
        <p style="font-size:14px;color:var(--muted);margin:0 0 24px;line-height:1.55;">
            Auto-generate a full day schedule for <strong style="color:var(--dark);">{{ $this->selectedTrip?->destination }}</strong> based on top-rated attractions?
        </p>
        <div style="display:flex;gap:10px;">
            <button wire:click="closeModals" class="btn btn-outline" style="flex:1;">Cancel</button>
            <button wire:click="generateItinerary" class="btn btn-primary" style="flex:1;"
                    wire:loading.attr="disabled" wire:target="generateItinerary">
                <span wire:loading.remove wire:target="generateItinerary">
                    <i class="fa-solid fa-wand-magic-sparkles"></i> Generate
                </span>
                <span wire:loading wire:target="generateItinerary">
                    <i class="fa-solid fa-spinner fa-spin"></i> Generating…
                </span>
            </button>
        </div>
    </div>
</div>
@endif

{{-- ── Day itinerary modal ────────────────────────────── --}}
@if ($showDayModal && $selectedDate)
<div style="position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1000;display:flex;align-items:center;justify-content:center;padding:16px;" wire:click.self="closeModals">
    <div style="background:#fff;border-radius:20px;width:100%;max-width:480px;max-height:80vh;display:flex;flex-direction:column;box-shadow:0 24px 64px rgba(0,0,0,.18);overflow:hidden;">

        {{-- Modal header --}}
        <div style="background:var(--primary);padding:20px 24px;flex-shrink:0;">
            <div style="display:flex;align-items:center;justify-content:space-between;">
                <div>
                    <p style="color:rgba(255,255,255,.75);font-size:12px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;margin:0 0 4px;">
                        {{ $this->selectedTrip?->destination }}
                    </p>
                    <h2 style="color:#fff;margin:0;font-size:18px;">
                        {{ \Carbon\Carbon::parse($selectedDate)->format('l, F j') }}
                    </h2>
                </div>
                <button wire:click="closeModals"
                        style="background:rgba(255,255,255,.2);border:none;border-radius:8px;width:32px;height:32px;display:flex;align-items:center;justify-content:center;cursor:pointer;color:#fff;font-size:16px;">
                    &times;
                </button>
            </div>
        </div>

        {{-- Header row --}}
        <div style="display:flex;padding:12px 24px 8px;border-bottom:1px solid var(--border);flex-shrink:0;">
            <div style="width:72px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);">TIME</div>
            <div style="flex:1;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);">ACTIVITY</div>
        </div>

        {{-- Scrollable items --}}
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
            <div style="display:flex;align-items:flex-start;gap:0;padding:12px 0;border-bottom:1px solid #F3F4F6;">
                <div style="width:72px;flex-shrink:0;padding-top:2px;">
                    <span style="font-size:12px;font-weight:700;color:var(--dark);">{{ $item->start_datetime->format('g:i A') }}</span>
                </div>
                <div style="flex:1;display:flex;align-items:flex-start;gap:10px;">
                    <div style="width:28px;height:28px;border-radius:8px;background:{{ $tc['bg'] }};display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="fa-solid {{ $tc['icon'] }}" style="font-size:11px;color:{{ $tc['text'] }};"></i>
                    </div>
                    <div style="flex:1;">
                        <div style="font-size:14px;font-weight:600;color:var(--dark);">{{ $item->title }}</div>
                        @if ($item->notes)
                        <div style="font-size:12px;color:var(--muted);margin-top:2px;">{{ $item->notes }}</div>
                        @endif
                        <span style="display:inline-block;margin-top:4px;padding:2px 8px;border-radius:20px;font-size:11px;font-weight:600;background:{{ $tc['bg'] }};color:{{ $tc['text'] }};">{{ $item->type }}</span>
                    </div>
                    <button wire:click="deleteItem({{ $item->id }})"
                            style="background:none;border:none;cursor:pointer;color:#9CA3AF;padding:4px;flex-shrink:0;" title="Remove">
                        <i class="fa-solid fa-xmark" style="font-size:12px;"></i>
                    </button>
                </div>
            </div>
            @empty
            <p class="text-muted" style="text-align:center;padding:24px 0;font-size:13px;">No items for this day.</p>
            @endforelse
        </div>

        {{-- Footer --}}
        <div style="padding:16px 24px;border-top:1px solid var(--border);display:flex;gap:10px;flex-shrink:0;">
            <button wire:click="closeModals" class="btn btn-outline" style="flex:1;">Close</button>
            <button wire:click="generateItinerary" class="btn btn-primary" style="flex:1;"
                    wire:loading.attr="disabled" wire:target="generateItinerary"
                    onclick="return confirm('This will replace all items for this day. Continue?')">
                <span wire:loading.remove wire:target="generateItinerary">
                    <i class="fa-solid fa-arrows-rotate"></i> Regenerate
                </span>
                <span wire:loading wire:target="generateItinerary">
                    <i class="fa-solid fa-spinner fa-spin"></i> Generating…
                </span>
            </button>
        </div>

    </div>
</div>
@endif

@endif {{-- selectedTripId --}}

</div>
