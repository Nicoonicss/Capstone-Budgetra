<div>

{{-- ═══════════════════════════════════════════════════════════════
     MY PLANNED TRIPS — shown when user already has trips
═══════════════════════════════════════════════════════════════ --}}
@if ($showList)
<div style="margin-bottom:28px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
    <div>
        <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;color:var(--primary);margin-bottom:6px;">Trip Planner</div>
    </div>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:16px;">
    @forelse ($this->myTrips as $trip)
    @php
        $spent  = $trip->total_spent ?? 0;
        $budget = $trip->budget_limit ?: ($trip->total_estimated ?? 0);
        $pct    = $budget > 0 ? min(100, round($spent / $budget * 100)) : 0;
        $isOver = $spent > $budget && $budget > 0;
        $days   = (int) $trip->start_date->diffInDays($trip->end_date) + 1;
        $scope  = ucfirst($trip->travel_type ?? 'International');

        // Destination → ISO country code for flagcdn.com
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
    <div style="background:#fff;border:1.5px solid var(--border);border-radius:16px;padding:20px;display:flex;flex-direction:column;min-height:200px;">
        {{-- Header --}}
        @php
            $now = now()->startOfDay();
            if ($now->lt($trip->start_date->startOfDay())) {
                $tripStatus = 'upcoming';
            } elseif ($now->lte($trip->end_date->startOfDay())) {
                $tripStatus = 'ongoing';
            } else {
                $tripStatus = 'completed';
            }
        @endphp
        <div style="display:flex;align-items:flex-start;gap:12px;margin-bottom:14px;">
            <div style="width:44px;height:44px;border-radius:12px;overflow:hidden;flex-shrink:0;background:#E5E7EB;display:flex;align-items:center;justify-content:center;">
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
                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px;">
                    <span style="font-size:16px;font-weight:700;color:var(--dark);">{{ $trip->destination }}</span>
                    <span style="font-size:11px;font-weight:700;padding:4px 10px;border-radius:20px;white-space:nowrap;flex-shrink:0;
                          background:{{ $tripStatus === 'ongoing' ? '#E3F3FA' : ($tripStatus === 'upcoming' ? '#FDF3E0' : '#F0F1F2') }};
                          color:{{ $tripStatus === 'ongoing' ? '#1B729D' : ($tripStatus === 'upcoming' ? '#E69A28' : '#88929A') }};">
                        {{ ucfirst($tripStatus) }}
                    </span>
                </div>
                <div style="font-size:12px;color:var(--muted);margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                    {{ $scope }} &middot; {{ $trip->start_date->format('M j') }} – {{ $trip->end_date->format('M j, Y') }} &middot; {{ $days }} day{{ $days !== 1 ? 's' : '' }}
                </div>
            </div>
        </div>

        {{-- Budget bar --}}
        <div style="margin-bottom:14px;border-top:1px solid var(--border);padding-top:10px;">
            <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--muted);margin-bottom:6px;">
                <span>Spent: <strong style="color:{{ $isOver ? 'var(--danger)' : 'var(--dark)' }};">₱{{ number_format($spent, 2) }}</strong></span>
                <span>Budget: <strong style="color:var(--dark);">₱{{ number_format($budget, 2) }}</strong></span>
            </div>
            <div style="height:5px;background:#F3F4F6;border-radius:99px;overflow:hidden;">
                <div style="height:100%;width:{{ $pct }}%;background:{{ $isOver ? 'var(--danger)' : 'var(--primary)' }};border-radius:99px;transition:width 0.3s;"></div>
            </div>
        </div>

        {{-- Actions pushed to bottom --}}
        <div style="display:flex;gap:10px;margin-top:auto;">
            <a href="{{ route('trips.dashboard', $trip) }}"
               style="flex:1;text-align:center;padding:9px 0;background:var(--primary);color:#fff;border-radius:10px;font-size:13px;font-weight:600;text-decoration:none;display:flex;align-items:center;justify-content:center;gap:6px;">
                <i class="fa-regular fa-circle-question"></i> View Dashboard
            </a>
            <button wire:click="confirmDelete({{ $trip->id }})"
                    style="padding:9px 14px;background:#fff;border:1.5px solid #FECACA;color:#DC2626;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:5px;">
                <i class="fa-solid fa-trash"></i> Delete
            </button>
        </div>
    </div>
    @empty
    <div style="grid-column:1/-1;text-align:center;padding:40px 24px;">
        <div style="font-size:40px;margin-bottom:12px;">✈️</div>
        <p class="text-muted">No trips yet — let's plan your first adventure!</p>
    </div>
    @endforelse

    {{-- Plan New Trip card --}}
    <a href="{{ route('trips.plan') }}"
       style="background:transparent;border:2px dashed #D1D5DB;border-radius:16px;padding:20px;
              display:flex;flex-direction:column;align-items:center;justify-content:center;gap:10px;
              min-height:160px;text-decoration:none;transition:border-color 0.15s;"
       onmouseover="this.style.borderColor='var(--primary)'"
       onmouseout="this.style.borderColor='#D1D5DB'">
        <div style="width:44px;height:44px;border-radius:50%;background:#F3F4F6;display:flex;align-items:center;justify-content:center;">
            <i class="fa-solid fa-plus" style="font-size:20px;color:#9CA3AF;"></i>
        </div>
        <div style="font-size:14px;font-weight:600;color:#6B7280;">Plan New Trip</div>
        <div style="font-size:12px;color:#9CA3AF;text-align:center;">Start tracking your next destination today.</div>
    </a>
</div>

{{-- ── Delete confirmation modal ─────────────────────────────── --}}
@if ($tripToDelete)
@php $deletingTrip = $this->myTrips->find($tripToDelete); @endphp
<div style="position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1000;display:flex;align-items:center;justify-content:center;padding:16px;">
    <div style="background:#fff;border-radius:16px;width:100%;max-width:380px;padding:24px;box-shadow:0 20px 60px rgba(0,0,0,.15);">
        <p style="font-size:15px;font-weight:600;color:var(--dark);margin-bottom:6px;">Are you sure you want to delete this trip?</p>
        <p style="font-size:13px;color:var(--muted);margin-bottom:24px;">This will permanently remove the trip, all its expenses, savings goals, and itinerary. <strong style="color:#DC2626;">This cannot be undone.</strong></p>
        <div style="display:flex;gap:10px;">
            <button wire:click="cancelDelete" class="btn btn-outline" style="flex:1;">Cancel</button>
            <button wire:click="deleteTrip" style="flex:1;padding:10px;background:#DC2626;color:#fff;border:none;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;">
                <i class="fa-solid fa-trash"></i> Delete Trip
            </button>
        </div>
    </div>
</div>
@endif

@endif

@if (!$showList)
{{-- ═══════════════════════════════════════════════════════════════
     STEP 1 — Scope selection: Local vs International
═══════════════════════════════════════════════════════════════ --}}
@if ($step === 1)
<div style="text-align:center;padding:20px 0 32px;">
    <h1 style="font-size:clamp(28px,4vw,44px);font-weight:800;color:var(--dark);margin-bottom:12px;">Where are you headed?</h1>
    <p class="text-muted" style="max-width:480px;margin:0 auto;font-size:15px;line-height:1.6;">
        Choose your travel type to get personalized destination suggestions and accurate cost estimates.
    </p>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;max-width:800px;margin:0 auto;">

    {{-- LOCAL card --}}
    <div wire:click="selectScope('local')" id="card-local"
         style="border-radius:20px;cursor:pointer;position:relative;overflow:hidden;min-height:290px;
                display:flex;flex-direction:column;justify-content:space-between;
                transition:transform 0.15s,box-shadow 0.15s;"
         onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 12px 40px rgba(0,0,0,0.4)'"
         onmouseout="this.style.transform='';this.style.boxShadow=''">
        {{-- Slideshow layers --}}
        <div id="local-slides" style="position:absolute;inset:0;">
            <img src="/stockimages/international 1.jpg" class="slide-img active" loading="eager">
            <img src="/stockimages/international 2.jpg" class="slide-img" loading="eager">
            <img src="/stockimages/international 3.jpg" class="slide-img" loading="eager">
            <img src="/stockimages/international 4.jpg" class="slide-img" loading="eager">
            <img src="/stockimages/international 5.jpg" class="slide-img" loading="eager">
            <img src="/stockimages/international 6.jpg" class="slide-img" loading="eager">
            <img src="/stockimages/international 7.jpg" class="slide-img" loading="eager">
            <img src="/stockimages/international 8.jpg" class="slide-img" loading="eager">
            <img src="/stockimages/international 9.jpg" class="slide-img" loading="eager">
            <img src="/stockimages/international 10.jpg" class="slide-img" loading="eager">
        </div>
        {{-- Dark overlay for readability --}}
        <div style="position:absolute;inset:0;background:linear-gradient(to top,rgba(0,0,0,.75) 0%,rgba(0,0,0,.3) 60%,rgba(0,0,0,.15) 100%);pointer-events:none;z-index:1;"></div>
        {{-- Content --}}
        <div style="position:relative;z-index:2;padding:32px 32px 0;">
            <div style="width:52px;height:52px;background:rgba(255,255,255,0.22);backdrop-filter:blur(4px);border-radius:14px;display:flex;align-items:center;justify-content:center;margin-bottom:28px;">
                <i class="fa-solid fa-house" style="color:#fff;font-size:22px;"></i>
            </div>
            <div style="font-size:11px;font-weight:700;letter-spacing:0.8px;color:rgba(255,255,255,0.75);text-transform:uppercase;margin-bottom:8px;">Local Travel</div>
            <h2 style="color:#fff;font-size:26px;font-weight:800;margin-bottom:12px;line-height:1.2;text-shadow:0 2px 8px rgba(0,0,0,.4);">Explore Philippines</h2>
            <p style="color:rgba(255,255,255,0.9);font-size:14px;line-height:1.65;text-shadow:0 1px 4px rgba(0,0,0,.4);">
                Discover hidden gems and popular spots within your home country.
            </p>
        </div>
        <div style="position:relative;z-index:2;padding:0 32px 32px;color:rgba(255,255,255,0.95);font-size:14px;font-weight:700;margin-top:28px;text-shadow:0 1px 4px rgba(0,0,0,.4);">Plan Local Trip →</div>
    </div>

    {{-- INTERNATIONAL card --}}
    <div wire:click="selectScope('international')" id="card-international"
         style="border-radius:20px;cursor:pointer;position:relative;overflow:hidden;min-height:290px;
                display:flex;flex-direction:column;justify-content:space-between;
                transition:transform 0.15s,box-shadow 0.15s;"
         onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 12px 40px rgba(0,0,0,0.4)'"
         onmouseout="this.style.transform='';this.style.boxShadow=''">
        <div id="intl-slides" style="position:absolute;inset:0;">
            <img src="/stockimages/international 2.jpg" class="slide-img active" loading="eager">
            <img src="/stockimages/international 3.jpg" class="slide-img" loading="eager">
            <img src="/stockimages/international 4.jpg" class="slide-img" loading="eager">
            <img src="/stockimages/international 5.jpg" class="slide-img" loading="eager">
            <img src="/stockimages/international 6.jpg" class="slide-img" loading="eager">
            <img src="/stockimages/international 7.jpg" class="slide-img" loading="eager">
            <img src="/stockimages/international 8.jpg" class="slide-img" loading="eager">
            <img src="/stockimages/international 9.jpg" class="slide-img" loading="eager">
            <img src="/stockimages/international 10.jpg" class="slide-img" loading="eager">
            <img src="/stockimages/international 1.jpg" class="slide-img" loading="eager">
        </div>
        <div style="position:absolute;inset:0;background:linear-gradient(to top,rgba(0,0,0,.75) 0%,rgba(0,0,0,.3) 60%,rgba(0,0,0,.15) 100%);pointer-events:none;z-index:1;"></div>
        <div style="position:relative;z-index:2;padding:32px 32px 0;">
            <div style="width:52px;height:52px;background:rgba(255,255,255,0.22);backdrop-filter:blur(4px);border-radius:14px;display:flex;align-items:center;justify-content:center;margin-bottom:28px;">
                <i class="fa-solid fa-earth-asia" style="color:#fff;font-size:22px;"></i>
            </div>
            <div style="font-size:11px;font-weight:700;letter-spacing:0.8px;color:rgba(255,255,255,0.75);text-transform:uppercase;margin-bottom:8px;">International Travel</div>
            <h2 style="color:#fff;font-size:26px;font-weight:800;margin-bottom:12px;line-height:1.2;text-shadow:0 2px 8px rgba(0,0,0,.4);">Travel the World</h2>
            <p style="color:rgba(255,255,255,0.9);font-size:14px;line-height:1.65;text-shadow:0 1px 4px rgba(0,0,0,.4);">
                Explore iconic destinations across Asia, Europe, the Americas, Africa, and Oceania.
            </p>
        </div>
        <div style="position:relative;z-index:2;padding:0 32px 32px;color:rgba(255,255,255,0.95);font-size:14px;font-weight:700;margin-top:28px;text-shadow:0 1px 4px rgba(0,0,0,.4);">Plan International Trip →</div>
    </div>

<style>
.slide-img {
    position:absolute;inset:0;width:100%;height:100%;object-fit:cover;
    opacity:0;transition:opacity 1s ease;
}
.slide-img.active { opacity:1; }
</style>

<script>
(function(){
    function initSlideshow(containerId) {
        const container = document.getElementById(containerId);
        if (!container) return;
        let current = 0;
        setInterval(() => {
            const imgs = container.querySelectorAll('.slide-img');
            if (!imgs.length) return;
            imgs[current].classList.remove('active');
            let next;
            do { next = Math.floor(Math.random() * imgs.length); } while (next === current && imgs.length > 1);
            imgs[next].classList.add('active');
            current = next;
        }, 5000);
    }

    function boot() {
        initSlideshow('local-slides');
        initSlideshow('intl-slides');
    }

    document.addEventListener('DOMContentLoaded', boot);
    document.addEventListener('livewire:navigated', boot);
})();
</script>

</div>
@endif

{{-- ═══════════════════════════════════════════════════════════════
     STEP 2 — Destination Cards
═══════════════════════════════════════════════════════════════ --}}
@if ($step === 2)
<div style="text-align:center;padding:12px 0 28px;">
    <h1 style="font-size:clamp(24px,3.5vw,38px);font-weight:800;color:var(--dark);margin-bottom:10px;">
        {{ $tripScope === 'local' ? 'Local Destinations' : 'International Destinations' }}
    </h1>
    <p class="text-muted" style="font-size:15px;">
        {{ $tripScope === 'local' ? 'Curated spots in the Philippines just for you.' : 'Explore the world — pick your dream destination.' }}
    </p>
</div>

<div>
    <div style="position:relative;margin-bottom:24px;max-width:440px;">
        <i class="fa-solid fa-magnifying-glass" style="position:absolute;left:14px;top:50%;transform:translateY(-50%);color:var(--muted);font-size:14px;pointer-events:none;"></i>
        <input type="text" wire:model.live.debounce.300ms="destSearch"
               style="width:100%;padding:12px 14px 12px 40px;border:1.5px solid var(--border);border-radius:12px;font-size:14px;outline:none;box-sizing:border-box;"
               placeholder="Search destinations">
    </div>

    @php
        $grouped = $this->destinations->groupBy('country');
        $isLocal = $tripScope === 'local';
    @endphp

    @if ($grouped->isEmpty())
    <div style="text-align:center;padding:56px 24px;">
        <div style="font-size:48px;margin-bottom:14px;">🌍</div>
        <h3 style="font-weight:700;margin-bottom:6px;color:var(--dark);">No destinations found</h3>
        <p class="text-muted">{{ $destSearch ? 'Try a different search term.' : 'No destinations available.' }}</p>
    </div>
    @else
        @foreach ($grouped as $country => $dests)
        {{-- Country heading for international view --}}
        @if (!$isLocal)
        <div style="font-size:13px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:0.6px;margin:20px 0 10px;padding-bottom:6px;border-bottom:1.5px solid var(--border);">
            {{ $country }}
        </div>
        @endif

        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:14px;{{ !$isLocal ? 'margin-bottom:8px;' : '' }}">
            @foreach ($dests as $dest)
            @php $sel = $destinationId === $dest->id; @endphp
            <div wire:click="selectDestination({{ $dest->id }})"
                 style="cursor:pointer;border-radius:14px;overflow:hidden;background:#fff;
                        border:2.5px solid {{ $sel ? 'var(--primary)' : 'var(--border)' }};
                        transition:all 0.15s;position:relative;"
                 onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 6px 20px rgba(0,0,0,.10)'"
                 onmouseout="this.style.transform='';this.style.boxShadow=''">
                @if ($dest->image)
                <img src="{{ asset('storage/' . $dest->image) }}" alt="{{ $dest->name }}"
                     style="width:100%;height:110px;object-fit:cover;display:block;">
                @else
                <div style="height:110px;background:linear-gradient(135deg,{{ $isLocal ? 'var(--primary-light),#FDEBD0' : '#E8F0FE,#C5D8FB' }});display:flex;align-items:center;justify-content:center;font-size:36px;">
                    {{ $isLocal ? '🏖️' : '✈️' }}
                </div>
                @endif
                @if ($sel)
                <div style="position:absolute;top:8px;right:8px;background:var(--primary);border-radius:50%;width:24px;height:24px;display:flex;align-items:center;justify-content:center;">
                    <i class="fa-solid fa-check" style="color:#fff;font-size:10px;"></i>
                </div>
                @endif
                <div style="padding:12px 14px;">
                    <div style="font-size:13px;font-weight:700;color:{{ $sel ? 'var(--primary)' : 'var(--dark)' }};">{{ $dest->name }}</div>
                    @if ($isLocal)
                    <div style="font-size:11px;color:var(--muted);margin-top:2px;">{{ $dest->country }}</div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
        @endforeach
    @endif

    @error('destinationId')<div class="error" style="margin-top:12px;">Please select a destination to continue.</div>@enderror

    <div style="margin-top:24px;">
        <button class="btn btn-back" wire:click="$set('step', 1)">← Back</button>
    </div>
</div>
@endif

{{-- ═══════════════════════════════════════════════════════════════
     STEP 3 — Calendar Date Picker
═══════════════════════════════════════════════════════════════ --}}
@if ($step === 3)
<div style="text-align:center;padding:12px 0 28px;">
    <h1 style="font-size:clamp(24px,3.5vw,38px);font-weight:800;color:var(--dark);margin-bottom:10px;">When are you going?</h1>
    <p class="text-muted" style="font-size:15px;">Click a start date, then click an end date to select your travel range.</p>
</div>

<div style="max-width:420px;margin:0 auto;">
    <div class="card card-body" style="padding:24px;">

        {{-- Month navigation --}}
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
            <button wire:click="prevMonth"
                    style="width:36px;height:36px;border-radius:50%;border:1.5px solid var(--border);background:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:18px;color:var(--dark);">‹</button>
            <span style="font-size:16px;font-weight:700;color:var(--dark);">
                {{ \Carbon\Carbon::createFromDate($calYear, $calMonth, 1)->format('F Y') }}
            </span>
            <button wire:click="nextMonth"
                    style="width:36px;height:36px;border-radius:50%;border:1.5px solid var(--border);background:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:18px;color:var(--dark);">›</button>
        </div>

        {{-- Day-of-week headers --}}
        <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:2px;margin-bottom:6px;">
            @foreach (['Su','Mo','Tu','We','Th','Fr','Sa'] as $dow)
            <div style="text-align:center;font-size:11px;font-weight:700;color:var(--muted);padding:4px 0;">{{ $dow }}</div>
            @endforeach
        </div>

        {{-- Calendar days --}}
        <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:2px;">
            @foreach ($this->calendarDays as $day)
            @if ($day === null)
            <div></div>
            @else
            @php
                $isStartOrEnd = $day['isStart'] || $day['isEnd'];
                $bgColor  = $isStartOrEnd ? 'var(--primary)' : ($day['inRange'] ? '#F5D99A' : 'transparent');
                $txtColor = $isStartOrEnd ? '#fff' : ($day['isPast'] ? '#D1D5DB' : ($day['inRange'] ? '#7A3200' : 'var(--dark)'));
                $fWeight  = $isStartOrEnd ? '700' : '400';
                $outline  = ($day['isToday'] && !$isStartOrEnd) ? 'box-shadow:0 0 0 1.5px var(--primary);' : '';
                $cursor   = $day['isPast'] ? 'default' : 'pointer';
                $radius   = $isStartOrEnd ? 'border-radius:50%;' : ($day['inRange'] ? 'border-radius:4px;' : 'border-radius:50%;');
            @endphp
            <button
                @if (!$day['isPast']) wire:click="selectDay('{{ $day['date'] }}')" @endif
                style="width:100%;aspect-ratio:1/1;border:none;{{ $radius }}font-size:13px;
                       font-weight:{{ $fWeight }};cursor:{{ $cursor }};
                       background:{{ $bgColor }};color:{{ $txtColor }};{{ $outline }}
                       padding:0;display:flex;align-items:center;justify-content:center;">
                {{ $day['day'] }}
            </button>
            @endif
            @endforeach
        </div>

        {{-- Selected range summary --}}
        @if ($startDate)
        <div style="margin-top:16px;padding:12px 16px;background:var(--primary-light);border-radius:10px;text-align:center;">
            @php $displayEnd = $endDate ?: $startDate; @endphp
            <span style="font-size:14px;font-weight:600;color:var(--primary);">
                {{ \Carbon\Carbon::parse($startDate)->format('M j') }} → {{ \Carbon\Carbon::parse($displayEnd)->format('M j, Y') }}
                &nbsp;·&nbsp; {{ max(1, \Carbon\Carbon::parse($startDate)->diffInDays(\Carbon\Carbon::parse($displayEnd)) + 1) }} day{{ max(1, \Carbon\Carbon::parse($startDate)->diffInDays(\Carbon\Carbon::parse($displayEnd)) + 1) !== 1 ? 's' : '' }}
            </span>
        </div>
        @endif

        @error('startDate')<div class="error" style="margin-top:8px;text-align:center;">{{ $message }}</div>@enderror
        @error('endDate')<div class="error" style="margin-top:4px;text-align:center;">{{ $message }}</div>@enderror
    </div>

    <div style="display:flex;align-items:center;justify-content:space-between;margin-top:20px;">
        <button class="btn btn-back" wire:click="$set('step', 2)">← Back</button>
        <button class="btn btn-primary" wire:click="proceedFromCalendar">Next →</button>
    </div>
</div>

{{-- ── Calendar validation modal ─────────────────────────────── --}}
<div id="calendar-validation-modal"
     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1000;align-items:center;justify-content:center;padding:16px;">
    <div style="background:#fff;border-radius:16px;width:100%;max-width:380px;box-shadow:0 24px 64px rgba(0,0,0,.18);padding:28px 24px 24px;">
        <h2 style="font-size:17px;font-weight:700;color:var(--dark);margin:0 0 10px;">Selection Required</h2>
        <p style="font-size:14px;color:var(--muted);margin:0 0 24px;line-height:1.55;">Please select a start and end date to proceed.</p>
        <button onclick="document.getElementById('calendar-validation-modal').style.display='none';"
                class="btn btn-primary" style="width:100%;">Got it</button>
    </div>
</div>
@script
<script>
$wire.on('calendar-validation-error', () => {
    document.getElementById('calendar-validation-modal').style.display = 'flex';
});
</script>
@endscript
@endif

{{-- ═══════════════════════════════════════════════════════════════
     STEP 4 — Group Type + Budget Tier
═══════════════════════════════════════════════════════════════ --}}
@if ($step === 4)
<div style="text-align:center;padding:12px 0 28px;">
    <h1 style="font-size:clamp(24px,3.5vw,38px);font-weight:800;color:var(--dark);margin-bottom:10px;">Who's coming?</h1>
    <p class="text-muted" style="font-size:15px;">Select your travel group and budget style.</p>
</div>

<div style="max-width:680px;margin:0 auto;">

    {{-- Group cards --}}
    <div style="margin-bottom:8px;">
        <div style="font-size:13px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:0.6px;margin-bottom:14px;">Travel Group</div>
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;">
            @foreach ([
                'Solo'    => ['icon' => '🧍', 'count' => '1 traveler'],
                'Couple'  => ['icon' => '👫', 'count' => '2 travelers'],
                'Family'  => ['icon' => '👨‍👩‍👧', 'count' => 'Adjustable'],
                'Friends' => ['icon' => '👥',  'count' => 'Adjustable'],
            ] as $group => $info)
            @php $selG = $groupType === $group; @endphp
            <div wire:click="selectGroup('{{ $group }}')"
                 style="text-align:center;padding:22px 12px;border-radius:16px;cursor:pointer;
                        border:2px solid {{ $selG ? 'var(--primary)' : 'var(--border)' }};
                        background:{{ $selG ? 'var(--primary-light)' : '#fff' }};
                        transition:all 0.15s;"
                 onmouseover="{{ !$selG ? 'this.style.borderColor=\"var(--primary-light)\"' : '' }}"
                 onmouseout="{{ !$selG ? 'this.style.borderColor=\"var(--border)\"' : '' }}">
                <div style="font-size:34px;margin-bottom:8px;">{{ $info['icon'] }}</div>
                <div style="font-size:13px;font-weight:700;color:{{ $selG ? 'var(--primary)' : 'var(--dark)' }};margin-bottom:4px;">{{ $group }}</div>
                <div style="font-size:11px;color:var(--muted);">{{ $info['count'] }}</div>
            </div>
            @endforeach
        </div>
        @error('groupType')<div class="error" style="margin-top:8px;">{{ $message }}</div>@enderror
    </div>

    {{-- Traveler counter (Family / Friends only) --}}
    @if ($groupType === 'Family' || $groupType === 'Friends')
    <div style="display:flex;align-items:center;gap:16px;margin:16px 0;padding:16px 20px;background:#fff;border:1.5px solid var(--border);border-radius:14px;">
        <div style="flex:1;">
            <div style="font-size:12px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:2px;">
                <i class="fa-solid fa-user-group" style="color:var(--primary);margin-right:6px;"></i>Travelers
            </div>
        </div>
        <button wire:click="decrementTravelers"
                style="width:36px;height:36px;border-radius:50%;border:1.5px solid var(--border);background:#fff;cursor:pointer;font-size:20px;display:flex;align-items:center;justify-content:center;color:var(--dark);">−</button>
        <span style="font-size:22px;font-weight:800;color:var(--dark);min-width:32px;text-align:center;">{{ $travelers }}</span>
        <button wire:click="incrementTravelers"
                style="width:36px;height:36px;border-radius:50%;border:1.5px solid var(--border);background:#fff;cursor:pointer;font-size:20px;display:flex;align-items:center;justify-content:center;color:var(--dark);">+</button>
    </div>
    @endif

    {{-- Budget tier cards --}}
    <div style="margin-top:28px;">
        <div style="font-size:13px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:0.6px;margin-bottom:14px;">Budget Style</div>
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px;">
            @foreach ([
                'Shoestring' => ['icon' => '🎒', 'label' => 'Shoestring / Backpacker', 'desc' => 'Hostels, street food & free sights'],
                'Mid-range'  => ['icon' => '🏨', 'label' => 'Mid-Range',                'desc' => '3-star hotels, restaurants, tours'],
                'Luxury'     => ['icon' => '💎', 'label' => 'Luxury / Premium',         'desc' => '5-star resorts, fine dining, private transfers'],
            ] as $tier => $info)
            @php $selT = $budgetTier === $tier; @endphp
            <div wire:click="selectBudgetTier('{{ $tier }}')"
                 style="text-align:center;padding:26px 16px;border-radius:16px;cursor:pointer;
                        border:2px solid {{ $selT ? 'var(--primary)' : 'var(--border)' }};
                        background:{{ $selT ? 'var(--primary-light)' : '#fff' }};
                        transition:all 0.15s;">
                <div style="font-size:36px;margin-bottom:10px;">{{ $info['icon'] }}</div>
                <div style="font-size:13px;font-weight:700;color:{{ $selT ? 'var(--primary)' : 'var(--dark)' }};margin-bottom:6px;">{{ $info['label'] }}</div>
                <div style="font-size:11px;color:var(--muted);line-height:1.5;">{{ $info['desc'] }}</div>
            </div>
            @endforeach
        </div>
        @error('budgetTier')<div class="error" style="margin-top:8px;">{{ $message }}</div>@enderror
    </div>

    <div style="display:flex;align-items:center;justify-content:space-between;margin-top:28px;">
        <button class="btn btn-back" wire:click="$set('step', 3)">← Back</button>
        <button class="btn btn-primary btn-lg" wire:click="calculateAndProceed"
                wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="calculateAndProceed">Calculate Estimate →</span>
            <span wire:loading wire:target="calculateAndProceed"><i class="fa-solid fa-spinner fa-spin"></i> Calculating…</span>
        </button>
    </div>
</div>
@endif

{{-- ── Validation modal (step 4) ─────────────────────────────── --}}
<div id="step4-validation-modal"
     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1000;align-items:center;justify-content:center;padding:16px;">
    <div style="background:#fff;border-radius:16px;width:100%;max-width:380px;box-shadow:0 24px 64px rgba(0,0,0,.18);padding:28px 24px 24px;">
        <h2 style="font-size:17px;font-weight:700;color:var(--dark);margin:0 0 10px;">Selection Required</h2>
        <p id="step4-modal-msg" style="font-size:14px;color:var(--muted);margin:0 0 24px;line-height:1.55;"></p>
        <button onclick="document.getElementById('step4-validation-modal').style.display='none';"
                class="btn btn-primary" style="width:100%;">Got it</button>
    </div>
</div>
@script
<script>
$wire.on('validation-error', ({ missingGroup, missingBudget }) => {
    let msg = '';
    if (missingGroup && missingBudget) {
        msg = 'Please select a travel group and a budget style to continue.';
    } else if (missingGroup) {
        msg = 'Please select a travel group to continue.';
    } else {
        msg = 'Please select a budget style to continue.';
    }
    document.getElementById('step4-modal-msg').textContent = msg;
    document.getElementById('step4-validation-modal').style.display = 'flex';
});
</script>
@endscript

{{-- ═══════════════════════════════════════════════════════════════
     STEP 5 — Trip Cost Estimator
═══════════════════════════════════════════════════════════════ --}}
@if ($step === 5)
<div style="margin-bottom:28px;">
    <h1 style="font-size:clamp(24px,3vw,34px);font-weight:800;color:var(--dark);margin-bottom:8px;">Trip Cost Estimator</h1>
    <p class="text-muted" style="font-size:15px;">
        Reviewing your estimated budget for <strong style="color:var(--dark);">{{ $destinationName }}</strong>.
    </p>
</div>

<div style="display:grid;grid-template-columns:1fr 2fr;gap:20px;align-items:start;">

    {{-- Left column: destination details + smart tip --}}
    <div style="display:flex;flex-direction:column;gap:16px;">

        {{-- Destination Details card --}}
        <div class="card card-body" style="padding:20px;">
            <div style="font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:0.7px;margin-bottom:14px;">Destination Details</div>
            <div style="font-size:20px;font-weight:800;color:var(--dark);margin-bottom:16px;">{{ $destinationName }}</div>

            <div style="display:flex;flex-direction:column;gap:10px;">
                <div style="display:flex;align-items:center;justify-content:space-between;font-size:13px;">
                    <span style="color:var(--muted);display:flex;align-items:center;gap:7px;">
                        <i class="fa-regular fa-calendar-days" style="color:var(--primary);"></i> Duration
                    </span>
                    <span style="font-weight:600;color:var(--dark);">{{ $this->days }} Day{{ $this->days !== 1 ? 's' : '' }}</span>
                </div>
                <div style="display:flex;align-items:center;justify-content:space-between;font-size:13px;">
                    <span style="color:var(--muted);display:flex;align-items:center;gap:7px;">
                        <i class="fa-solid fa-user-group" style="color:var(--primary);"></i> Travelers
                    </span>
                    <span style="font-weight:600;color:var(--dark);">{{ $this->travelersLabel }}</span>
                </div>
                <div style="display:flex;align-items:center;justify-content:space-between;font-size:13px;">
                    <span style="color:var(--muted);display:flex;align-items:center;gap:7px;">
                        <i class="fa-solid fa-people-group" style="color:var(--primary);"></i> Travel Type
                    </span>
                    <span style="font-weight:600;color:var(--dark);">{{ $groupType }}</span>
                </div>
                <div style="display:flex;align-items:center;justify-content:space-between;font-size:13px;">
                    <span style="color:var(--muted);display:flex;align-items:center;gap:7px;">
                        <i class="fa-solid fa-star" style="color:var(--primary);"></i> Comfort Level
                    </span>
                    <span style="font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px;
                          background:{{ $budgetTier === 'Luxury' ? '#FEF3C7' : ($budgetTier === 'Shoestring' ? '#F0FDF4' : '#EFF6FF') }};
                          color:{{ $budgetTier === 'Luxury' ? '#92400E' : ($budgetTier === 'Shoestring' ? '#166534' : '#1E40AF') }};">
                        {{ $this->comfortLevel }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Smart Tip card --}}
        <div style="background:#EFF6FF;border-radius:14px;padding:18px 20px;">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;">
                <span style="font-size:20px;">💡</span>
                <span style="font-size:13px;font-weight:700;color:#1E40AF;">Smart Tip</span>
            </div>
            <p style="font-size:12px;color:#1E3A8A;line-height:1.65;margin:0;">{{ $this->smartTip }}</p>
        </div>

        {{-- Variance note --}}
        <p style="font-size:11px;color:var(--muted);text-align:center;line-height:1.5;">
            Estimated variance: +/- ₱{{ number_format($this->variance, 0) }} based on current market data.
        </p>

    </div>

    {{-- Right column: 6 category cards --}}
    <div>
        @php
        $catDefs = [
            ['key'=>'transportation','label'=>'Transportation',    'sub'=>($tripScope==='international'?'Flights + Local Transit':'Transport + Local Transit'),'icon'=>'fa-plane',         'icolor'=>'#3B82F6','ibg'=>'#EFF6FF'],
            ['key'=>'accommodation', 'label'=>'Accommodation',     'sub'=>match($budgetTier){'Shoestring'=>'Hostel / budget room','Luxury'=>'5-star resort',default=>'3-4 star hotel'},   'icon'=>'fa-bed',           'icolor'=>'#16A34A','ibg'=>'#F0FDF4'],
            ['key'=>'food',          'label'=>'Food & Dining',     'sub'=>match($budgetTier){'Shoestring'=>'Street food & local eateries','Luxury'=>'Fine dining & room service',default=>'Street & mid-range dining'},'icon'=>'fa-utensils',     'icolor'=>'#EA580C','ibg'=>'#FFF7ED'],
            ['key'=>'attractions',   'label'=>'Tourist Attractions','sub'=>'Entry fees + guided tours',                                                           'icon'=>'fa-landmark',      'icolor'=>'#7C3AED','ibg'=>'#F5F3FF'],
            ['key'=>'shopping',      'label'=>'Shopping',          'sub'=>'Souvenirs & retail',                                                                   'icon'=>'fa-bag-shopping',  'icolor'=>'#DB2777','ibg'=>'#FDF2F8'],
            ['key'=>'emergency',     'label'=>'Emergency Funds',    'sub'=>'5% buffer recommendation',                                                             'icon'=>'fa-shield-halved', 'icolor'=>'#DC2626','ibg'=>'#FEF2F2'],
        ];
        @endphp
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
            @foreach ($catDefs as $cat)
            @php $val = $this->{$cat['key']}; $isEditing = $editingCategory === $cat['key']; @endphp
            <div style="background:#fff;border:1.5px solid var(--border);border-radius:14px;padding:18px;">
                <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:12px;">
                    <div style="width:38px;height:38px;border-radius:10px;background:{{ $cat['ibg'] }};display:flex;align-items:center;justify-content:center;">
                        <i class="fa-solid {{ $cat['icon'] }}" style="color:{{ $cat['icolor'] }};font-size:16px;"></i>
                    </div>
                    @if (!$isEditing)
                    <button wire:click="startEditing('{{ $cat['key'] }}')"
                            style="font-size:12px;font-weight:600;color:var(--primary);background:none;border:none;cursor:pointer;padding:4px 8px;border-radius:6px;text-decoration:underline;">
                        Edit
                    </button>
                    @else
                    <button wire:click="stopEditing"
                            style="font-size:12px;font-weight:600;color:#16A34A;background:none;border:none;cursor:pointer;padding:4px 8px;border-radius:6px;">
                        ✓ Save
                    </button>
                    @endif
                </div>
                <div style="font-size:13px;font-weight:700;color:var(--dark);margin-bottom:2px;">{{ $cat['label'] }}</div>
                <div style="font-size:11px;color:var(--muted);margin-bottom:10px;">{{ $cat['sub'] }}</div>
                @if ($isEditing)
                <div style="position:relative;">
                    <span style="position:absolute;left:10px;top:50%;transform:translateY(-50%);font-size:13px;font-weight:600;color:var(--dark);">₱</span>
                    <input type="number" wire:model.live="{{ $cat['key'] }}"
                           style="width:100%;padding:8px 10px 8px 26px;border:1.5px solid var(--primary);border-radius:8px;font-size:14px;font-weight:700;box-sizing:border-box;outline:none;">
                </div>
                @else
                <div style="font-size:18px;font-weight:800;color:var(--dark);">₱{{ number_format($val, 0) }}</div>
                @endif
            </div>
            @endforeach
        </div>
    </div>

</div>

{{-- Dark total bar + confirm button --}}
<div style="background:var(--neutral);border-radius:16px;padding:22px 28px;margin-top:24px;display:flex;align-items:center;justify-content:space-between;gap:20px;flex-wrap:wrap;">
    <div>
        <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;color:rgba(255,255,255,0.5);margin-bottom:4px;">Total Estimated Cost</div>
        <div style="font-size:32px;font-weight:900;color:#fff;">₱{{ number_format($budgetLimit, 0) }}</div>
        <div style="font-size:12px;color:rgba(255,255,255,0.55);margin-top:2px;">{{ $this->days }} day{{ $this->days !== 1 ? 's' : '' }} · {{ $this->travelersLabel }} · {{ $budgetTier }}</div>
    </div>
    <button class="btn btn-lg" wire:click="confirm" wire:loading.attr="disabled"
            style="background:var(--secondary);color:var(--neutral);font-weight:700;border:none;white-space:nowrap;">
        <span wire:loading.remove wire:target="confirm">
            <i class="fa-solid fa-check-circle"></i> Confirm and Create Savings Goal
        </span>
        <span wire:loading wire:target="confirm">
            <i class="fa-solid fa-spinner fa-spin"></i> Creating trip…
        </span>
    </button>
</div>

@if ($errors->has('budgetLimit') || $errors->has('groupType') || $errors->has('destinationName'))
<div class="alert" style="background:#FEF2F2;border:1px solid #FECACA;color:#DC2626;border-radius:10px;padding:12px 16px;margin-top:12px;">
    {{ $errors->first() }}
</div>
@endif

<div style="text-align:center;margin-top:16px;">
    <button class="btn btn-back" wire:click="$set('step', 4)">← Back</button>
</div>
@endif

@endif {{-- end !$showList --}}

</div>
