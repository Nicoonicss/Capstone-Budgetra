<div style="height:100%;display:flex;flex-direction:column;">
    <div style="background:#fff;border:1.5px solid var(--border);border-radius:16px;padding:20px;display:flex;flex-direction:column;flex:1;">

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
        $destKey = strtolower(trim($goal->trip?->destination ?? $goal->goal_name ?? ''));
        $countryCode = null;
        foreach ($flagMap as $key => $code) {
            if (str_contains($destKey, $key)) { $countryCode = $code; break; }
        }
        @endphp

        {{-- Top row: flag + name + badge + subtitle --}}
        <div style="display:flex;align-items:flex-start;gap:12px;margin-bottom:14px;">
            <div style="width:44px;height:44px;border-radius:12px;overflow:hidden;flex-shrink:0;background:#E5E7EB;display:flex;align-items:center;justify-content:center;">
                @if ($countryCode)
                <img src="https://flagcdn.com/w160/{{ $countryCode }}.png"
                     srcset="https://flagcdn.com/w320/{{ $countryCode }}.png 2x"
                     alt="{{ $countryCode }}"
                     style="width:100%;height:100%;object-fit:cover;">
                @else
                <i class="fa-solid fa-piggy-bank" style="color:#6B7280;font-size:20px;"></i>
                @endif
            </div>
            <div style="flex:1;min-width:0;">
                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px;">
                    <span style="font-size:16px;font-weight:700;color:var(--dark);">{{ $goal->goal_name }}</span>
                    @if ($this->isCompleted)
                    <span style="flex-shrink:0;font-size:11px;font-weight:700;padding:4px 10px;border-radius:20px;background:#F0FDF4;color:#16A34A;">Completed</span>
                    @else
                    <span style="flex-shrink:0;font-size:11px;font-weight:700;padding:4px 10px;border-radius:20px;background:#FDF3E0;color:#E69A28;">{{ $this->pct }}% Saved</span>
                    @endif
                </div>
                <div style="font-size:12px;color:var(--muted);margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                    @if ($goal->trip)
                    @php $gDays = (int)$goal->trip->start_date->diffInDays($goal->trip->end_date) + 1; @endphp
                    {{ ucfirst($goal->trip->travel_type ?? 'Trip') }} &middot; {{ $goal->trip->start_date->format('M j') }} – {{ $goal->trip->end_date->format('M j, Y') }} &middot; {{ $gDays }} day{{ $gDays !== 1 ? 's' : '' }}
                    @else
                    Deadline: {{ $goal->deadline->format('M j, Y') }}
                    @endif
                </div>
            </div>
        </div>

        {{-- Saved / Goal amounts + progress --}}
        <div style="border-top:1px solid var(--border);padding-top:10px;margin-bottom:14px;">
            <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--muted);margin-bottom:6px;">
                <span>Saved: <strong style="color:var(--dark);">₱{{ number_format($goal->current_savings, 2) }}</strong></span>
                <span>Goal: <strong style="color:var(--dark);">₱{{ number_format($goal->target_amount, 2) }}</strong></span>
            </div>
            <div style="height:5px;background:#F3F4F6;border-radius:99px;overflow:hidden;">
                <div style="height:100%;width:{{ $this->pct }}%;background:{{ $this->isCompleted ? '#16A34A' : 'var(--primary)' }};border-radius:99px;transition:width 0.3s;"></div>
            </div>
        </div>

        {{-- Action buttons --}}
        @if ($this->isCompleted)
        <div style="margin-top:auto;">
            <span style="display:inline-flex;align-items:center;justify-content:center;gap:6px;width:100%;padding:10px 16px;border-radius:8px;background:#F0FDF4;color:#16A34A;font-size:13px;font-weight:600;">
                <i class="fa-solid fa-check"></i> Goal Reached!
            </span>
        </div>
        @else
        <div style="margin-top:auto;">
            <button class="btn btn-primary" style="width:100%;" wire:click="openDeposit">
                <i class="fa-solid fa-circle-plus"></i> Add Savings
            </button>
        </div>
        @endif
    </div>

    {{-- Deposit modal --}}
    @if ($showDeposit)
    <div style="position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;display:flex;align-items:center;justify-content:center;padding:16px;" wire:click.self="closeDeposit">
        <div style="background:#fff;border-radius:20px;width:100%;max-width:400px;box-shadow:0 24px 64px rgba(0,0,0,.2);overflow:hidden;">

            {{-- Header --}}
            <div style="background:var(--primary);padding:20px 20px 18px;">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <div style="width:36px;height:36px;border-radius:10px;background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;">
                            <i class="fa-solid fa-piggy-bank" style="color:#fff;font-size:16px;"></i>
                        </div>
                        <div>
                            <div style="color:#fff;font-size:16px;font-weight:700;line-height:1.2;">Add Savings</div>
                            <div style="color:rgba(255,255,255,.7);font-size:12px;">{{ $goal->goal_name }}</div>
                        </div>
                    </div>
                    <button wire:click="closeDeposit" style="background:rgba(255,255,255,.15);border:none;border-radius:8px;width:30px;height:30px;display:flex;align-items:center;justify-content:center;cursor:pointer;color:#fff;font-size:16px;">&times;</button>
                </div>

                {{-- Progress inside header --}}
                @php $pct = $goal->target_amount > 0 ? min(100, round(($goal->current_savings / $goal->target_amount) * 100)) : 0; @endphp
                <div style="background:rgba(255,255,255,.15);border-radius:12px;padding:12px 14px;">
                    <div style="display:flex;justify-content:space-between;align-items:flex-end;margin-bottom:8px;">
                        <div>
                            <div style="font-size:10px;color:rgba(255,255,255,.6);text-transform:uppercase;letter-spacing:.05em;margin-bottom:2px;">Saved</div>
                            <div style="font-size:18px;font-weight:800;color:#fff;">₱{{ number_format($goal->current_savings, 2) }}</div>
                        </div>
                        <div style="text-align:right;">
                            <div style="font-size:10px;color:rgba(255,255,255,.6);text-transform:uppercase;letter-spacing:.05em;margin-bottom:2px;">Goal</div>
                            <div style="font-size:18px;font-weight:800;color:#fff;">₱{{ number_format($goal->target_amount, 2) }}</div>
                        </div>
                    </div>
                    <div style="height:5px;background:rgba(255,255,255,.25);border-radius:99px;overflow:hidden;">
                        <div style="height:100%;width:{{ $pct }}%;background:#fff;border-radius:99px;transition:width .3s;"></div>
                    </div>
                    <div style="font-size:11px;color:rgba(255,255,255,.65);margin-top:5px;text-align:right;">{{ $pct }}% saved</div>
                </div>
            </div>

            {{-- Body --}}
            <div style="padding:20px;">
                <label style="display:block;font-size:12px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px;">Enter Amount</label>
                <div style="display:flex;align-items:center;gap:0;border:2px solid var(--border);border-radius:12px;overflow:hidden;transition:border-color .15s;"
                     onfocusin="this.style.borderColor='var(--primary)'" onfocusout="this.style.borderColor='var(--border)'">
                    <span style="padding:0 14px;font-size:18px;font-weight:800;color:var(--primary);background:#FFF8F5;border-right:2px solid var(--border);height:52px;display:flex;align-items:center;">₱</span>
                    <input type="number" wire:model.live="depositAmount"
                           style="flex:1;border:none;outline:none;padding:0 14px;font-size:20px;font-weight:800;color:var(--dark);height:52px;background:#fff;"
                           step="0.01" min="0.01" placeholder="0.00">
                </div>
                @error('depositAmount')<div style="color:#DC2626;font-size:12px;margin-top:6px;">{{ $message }}</div>@enderror

                {{-- Quick amounts --}}
                <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-top:12px;">
                    @foreach ([500, 1000, 2000, 5000] as $quick)
                    <button type="button" wire:click="$set('depositAmount', {{ $quick }})"
                            style="padding:8px 4px;border-radius:8px;border:1.5px solid {{ ($depositAmount == $quick) ? 'var(--primary)' : 'var(--border)' }};
                                   background:{{ ($depositAmount == $quick) ? '#FFF5EE' : '#fff' }};
                                   font-size:12px;font-weight:700;color:{{ ($depositAmount == $quick) ? 'var(--primary)' : 'var(--dark)' }};cursor:pointer;">
                        ₱{{ number_format($quick) }}
                    </button>
                    @endforeach
                </div>

                <div style="display:flex;gap:10px;margin-top:16px;">
                    <button class="btn btn-outline" wire:click="closeDeposit" style="flex:1;">Cancel</button>
                    <button class="btn btn-primary" wire:click="submitDeposit" style="flex:2;font-weight:700;">
                        <i class="fa-solid fa-circle-plus"></i>
                        Add ₱{{ number_format($depositAmount ?: 0, 2) }}
                    </button>
                </div>
            </div>

        </div>
    </div>
    @endif
</div>
