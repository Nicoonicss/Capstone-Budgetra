<div style="min-height:100vh;background:#F5F0EB;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:24px;">

<style>
.pb-card{background:#fff;border-radius:20px;padding:40px 36px;width:100%;max-width:480px;box-shadow:0 4px 24px rgba(0,0,0,0.08);}
.pb-icon-wrap{width:56px;height:56px;border-radius:14px;background:#F5F0EB;display:flex;align-items:center;justify-content:center;margin-bottom:20px;}
.pb-title{font-size:22px;font-weight:800;color:#1A1A1A;margin:0 0 8px;}
.pb-sub{font-size:13px;color:#9B8E85;line-height:1.6;margin:0 0 28px;}
.pb-label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:#9B8E85;margin-bottom:10px;}
.pb-input-wrap{display:flex;align-items:center;gap:10px;border:1.5px solid #E8E0D8;border-radius:10px;padding:13px 16px;background:#FAFAF9;}
.pb-input-wrap:focus-within{border-color:#7B3F00;}
.pb-input{border:none;background:transparent;font-size:14px;font-weight:500;color:#1A1A1A;outline:none;width:100%;}
.pb-input::placeholder{color:#C4B8AF;font-weight:400;}
.pb-suggest{font-size:12px;color:#9B8E85;margin-top:8px;}
.pb-suggest span{cursor:pointer;color:#7B3F00;font-weight:600;text-decoration:underline;margin-left:4px;}
.pb-btn{display:inline-flex;align-items:center;gap:8px;padding:13px 28px;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;border:none;}
.pb-btn-primary{background:#3B1A08;color:#fff;}
.pb-btn-primary:hover{background:#2D1206;}
.pb-btn-ghost{background:#fff;border:1.5px solid #E8E0D8;color:#1A1A1A;}
.pb-btn-ghost:hover{background:#F5F0EB;}
.pb-nav{display:flex;align-items:center;justify-content:space-between;width:100%;max-width:480px;margin-top:20px;}

/* Interest grid */
.int-card{border:1.5px solid #E8E0D8;border-radius:14px;padding:18px 12px;text-align:center;cursor:pointer;transition:all .15s;background:#fff;}
.int-card:hover{border-color:#7B3F00;background:#FDF8F4;}
.int-card.active{border-color:#7B3F00;background:#FDF8F4;box-shadow:0 0 0 2px #7B3F0033;}
.int-icon{width:44px;height:44px;border-radius:12px;background:#F5F0EB;display:flex;align-items:center;justify-content:center;margin:0 auto 10px;}
.int-card.active .int-icon{background:#7B3F0015;}
.int-label{font-size:12px;font-weight:600;color:#1A1A1A;}
.sub-chip{display:inline-flex;align-items:center;padding:6px 14px;border-radius:20px;font-size:12px;font-weight:600;border:1.5px solid #E8E0D8;background:#fff;cursor:pointer;transition:all .15s;}
.sub-chip:hover{border-color:#7B3F00;}
.sub-chip.active{background:#7B3F00;color:#fff;border-color:#7B3F00;}

/* Review */
.rv-card{border:1.5px solid #E8E0D8;border-radius:14px;padding:18px 20px;margin-bottom:14px;background:#fff;display:flex;align-items:flex-start;justify-content:space-between;gap:16px;}
.rv-edit{font-size:12px;font-weight:700;color:#7B3F00;cursor:pointer;white-space:nowrap;flex-shrink:0;text-decoration:none;}
.rv-edit:hover{text-decoration:underline;}
</style>

{{-- ── STEP 1: Home Location ── --}}
@if($step === 1)
<div class="pb-card" wire:key="step-1">
    <div class="pb-icon-wrap">
        <i class="fa-solid fa-location-dot" style="font-size:24px;color:#7B3F00;"></i>
    </div>
    <h1 class="pb-title">Where does your journey begin?</h1>
    <p class="pb-sub">We'll use your home location to calculate estimated travel costs, flight durations, and currency defaults for your adventures.</p>

    <div class="pb-label">Home Location / Starting Point</div>
    <div class="pb-input-wrap">
        <i class="fa-solid fa-location-dot" style="color:#C4B8AF;font-size:13px;flex-shrink:0;"></i>
        <input type="text" wire:model="homeCity" class="pb-input" placeholder="City of Manila">
    </div>
    @error('homeCity') <p style="color:#e74c3c;font-size:12px;margin-top:6px;">{{ $message }}</p> @enderror

    <p class="pb-suggest">Suggested:
        @foreach($suggested as $city)
            <span wire:click="$set('homeCity', '{{ $city }}')">{{ $city }}</span>{{ !$loop->last ? '' : '' }}
        @endforeach
    </p>
</div>

{{-- ── STEP 2: Budget ── --}}
@elseif($step === 2)
<div class="pb-card" wire:key="step-2">
    <div class="pb-icon-wrap">
        <i class="fa-solid fa-wallet" style="font-size:22px;color:#7B3F00;"></i>
    </div>
    <h1 class="pb-title">What is your preferred budget range?</h1>
    <p class="pb-sub">Select the budget level that best fits your travel style for personalized cost estimates.</p>

    <div class="pb-label">Budget Level</div>
    <div class="pb-input-wrap" x-data="{ display: '{{ $dailyBudgetDisplay }}' }" x-init="$nextTick(() => { $el.querySelector('input').value = display; })">
        <i class="fa-solid fa-wallet" style="color:#C4B8AF;font-size:13px;flex-shrink:0;"></i>
        <input type="text" class="pb-input" placeholder="Enter your daily budget (e.g., ₱1,000)"
               x-ref="budgetInput"
               :value="display"
               @input="
                   let raw = $event.target.value.replace(/[^\d]/g, '');
                   let fmt = raw ? Number(raw).toLocaleString('en-PH') : '';
                   $event.target.value = fmt;
                   display = fmt;
               "
               @change="
                   let raw = $event.target.value.replace(/[^\d]/g, '');
                   $wire.set('dailyBudget', raw ? Number(raw) : 0);
                   $wire.set('dailyBudgetDisplay', $event.target.value);
               ">
    </div>
    <p style="font-size:12px;color:#9B8E85;margin-top:8px;">This helps us calculate more accurate estimates for your trips.</p>
</div>

{{-- ── STEP 3: Interests ── --}}
@elseif($step === 3)
<div style="background:#fff;border-radius:20px;padding:40px 36px;width:100%;max-width:640px;box-shadow:0 4px 24px rgba(0,0,0,0.08);">
    <h1 style="font-size:22px;font-weight:800;color:#1A1A1A;text-align:center;margin:0 0 8px;">What do you enjoy doing?</h1>
    <p style="font-size:13px;color:#9B8E85;text-align:center;margin:0 0 28px;">Select all travel interests that apply to your exploration style.</p>

    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:20px;">
        @foreach($interests as $name => $subs)
        <div class="int-card {{ in_array($name, $selectedInterests) ? 'active' : '' }}"
             wire:click="toggleInterest('{{ $name }}')">
            <div class="int-icon">
                <i class="fa-solid {{ $icons[$name] ?? 'fa-star' }}" style="font-size:18px;color:#7B3F00;"></i>
            </div>
            <div class="int-label">{{ $name }}</div>
        </div>
        @if(in_array($name, $selectedInterests))
        {{-- Sub-interests appear inline after the selected card's row --}}
        @endif
        @endforeach
    </div>

    {{-- Sub-interests below grid, grouped by selected interest --}}
    @foreach($selectedInterests as $interest)
    @if(isset($interests[$interest]))
    <div style="margin-bottom:16px;">
        <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:#9B8E85;margin-bottom:8px;">
            Explore {{ strtoupper($interest) }} Interests
        </div>
        <div style="display:flex;flex-wrap:wrap;gap:8px;">
            @foreach($interests[$interest] as $sub)
            <div class="sub-chip {{ in_array($sub, $selectedSubInterests) ? 'active' : '' }}"
                 wire:click="toggleSubInterest('{{ $sub }}')">
                {{ $sub }}
            </div>
            @endforeach
        </div>
    </div>
    @endif
    @endforeach
</div>

{{-- ── STEP 4: Review ── --}}
@elseif($step === 4)
<div class="pb-card" wire:key="step-4" style="max-width:520px;">
    <h1 class="pb-title" style="margin-bottom:4px;">Review your profile</h1>
    <p class="pb-sub">Verify your travel preferences before we finalize your workspace. These settings will help us tailor your budgeting tools.</p>

    {{-- Starting Point --}}
    <div class="rv-card">
        <div style="display:flex;align-items:flex-start;gap:14px;flex:1;min-width:0;">
            <div style="width:36px;height:36px;border-radius:10px;background:#F5F0EB;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fa-solid fa-location-dot" style="color:#7B3F00;font-size:14px;"></i>
            </div>
            <div>
                <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:#9B8E85;margin-bottom:4px;">Starting Point</div>
                <div style="font-size:15px;font-weight:700;color:#1A1A1A;">{{ $homeCity ?: '—' }}</div>
                <div style="font-size:12px;color:#9B8E85;">Luzon, Philippines</div>
            </div>
        </div>
        <span class="rv-edit" wire:click="$set('step', 1)">Edit</span>
    </div>

    {{-- Budget --}}
    <div class="rv-card">
        <div style="display:flex;align-items:flex-start;gap:14px;flex:1;min-width:0;">
            <div style="width:36px;height:36px;border-radius:10px;background:#F5F0EB;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fa-solid fa-wallet" style="color:#7B3F00;font-size:14px;"></i>
            </div>
            <div>
                <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:#9B8E85;margin-bottom:4px;">Budget Preference</div>
                <div style="font-size:15px;font-weight:700;color:#1A1A1A;">
                    @if($dailyBudget)
                        ₱{{ number_format($dailyBudget) }}
                    @else
                        —
                    @endif
                </div>
                <div style="font-size:12px;color:#9B8E85;">Recommended baseline for your next trip</div>
            </div>
        </div>
        <span class="rv-edit" wire:click="$set('step', 2)">Edit</span>
    </div>

    {{-- Travel Interests --}}
    <div class="rv-card" style="flex-direction:column;align-items:flex-start;">
        <div style="display:flex;align-items:center;justify-content:space-between;width:100%;margin-bottom:12px;">
            <div style="display:flex;align-items:center;gap:14px;">
                <div style="width:36px;height:36px;border-radius:10px;background:#F5F0EB;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fa-solid fa-heart" style="color:#7B3F00;font-size:14px;"></i>
                </div>
                <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:#9B8E85;">Travel Interests</div>
            </div>
            <span class="rv-edit" wire:click="$set('step', 3)">Edit</span>
        </div>
        <div style="display:flex;flex-wrap:wrap;gap:8px;">
            @forelse(array_merge($selectedInterests, $selectedSubInterests) as $tag)
            <span style="background:#F5F0EB;color:#7B3F00;font-size:12px;font-weight:600;padding:5px 12px;border-radius:20px;">{{ $tag }}</span>
            @empty
            <span style="font-size:13px;color:#9B8E85;">No interests selected.</span>
            @endforelse
        </div>
    </div>

    <p style="font-size:13px;color:#9B8E85;text-align:center;margin:16px 0 0;">
        <i class="fa-solid fa-shield-halved" style="margin-right:6px;color:#7B3F00;"></i>
        Everything looks good! Once confirmed, we'll generate your first budget draft based on these choices.
    </p>
</div>
@endif

{{-- Navigation --}}
<div class="pb-nav" style="max-width:{{ $step === 3 ? '640px' : '480px' }};">
    @if($step > 1)
    <button class="pb-btn pb-btn-ghost" wire:click="prevStep">
        <i class="fa-solid fa-arrow-left" style="font-size:11px;"></i> Back
    </button>
    @else
    <div></div>
    @endif

    @if($step < 4)
    <button class="pb-btn pb-btn-primary" wire:click="nextStep">
        Next Step <i class="fa-solid fa-arrow-right" style="font-size:11px;"></i>
    </button>
    @else
    <button class="pb-btn pb-btn-primary" wire:click="confirmProfile" wire:loading.attr="disabled">
        <span wire:loading.remove wire:target="confirmProfile">Confirm Profile</span>
        <span wire:loading wire:target="confirmProfile"><i class="fa-solid fa-spinner fa-spin"></i></span>
    </button>
    @endif
</div>

</div>
