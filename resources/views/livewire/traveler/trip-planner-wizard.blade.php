<div>

{{-- ═══════════════════════════════════════════════════════════════
     STEP 1 — Plan Your Trip (manual)
═══════════════════════════════════════════════════════════════ --}}
@if ($planningMode === 'manual' && $step === 1)
@php
$localCities = [
    ['name'=>'Manila','code'=>'MNL'],['name'=>'Cebu City','code'=>'CEB'],['name'=>'Davao City','code'=>'DVO'],
    ['name'=>'Boracay','code'=>'KLO'],['name'=>'Puerto Princesa','code'=>'PPS'],['name'=>'Tagbilaran (Bohol)','code'=>'TAG'],
    ['name'=>'Siargao','code'=>'IAO'],['name'=>'Iloilo City','code'=>'ILO'],['name'=>'Bacolod','code'=>'BCD'],
    ['name'=>'Zamboanga','code'=>'ZAM'],['name'=>'Cagayan de Oro','code'=>'CGY'],['name'=>'General Santos','code'=>'GES'],
    ['name'=>'Tacloban','code'=>'TAC'],['name'=>'Dumaguete','code'=>'DGT'],['name'=>'El Nido','code'=>'ENI'],
    ['name'=>'Coron','code'=>'USU'],['name'=>'Baguio','code'=>'BAG'],['name'=>'Tagaytay','code'=>'MNL'],
    ['name'=>'Vigan','code'=>'VIG'],['name'=>'Batanes','code'=>'BSO'],['name'=>'Camiguin','code'=>'CGM'],
    ['name'=>'Siquijor','code'=>'DGT'],['name'=>'Surigao','code'=>'SUG'],['name'=>'Laoag','code'=>'LAO'],
    ['name'=>'Legazpi','code'=>'LGP'],
];
$intlCities = [
    ['name'=>'Singapore','code'=>'SIN'],['name'=>'Bangkok','code'=>'BKK'],['name'=>'Bali','code'=>'DPS'],
    ['name'=>'Tokyo','code'=>'NRT'],['name'=>'Seoul','code'=>'ICN'],['name'=>'Kuala Lumpur','code'=>'KUL'],
    ['name'=>'Hong Kong','code'=>'HKG'],['name'=>'Dubai','code'=>'DXB'],['name'=>'London','code'=>'LHR'],
    ['name'=>'Paris','code'=>'CDG'],['name'=>'New York','code'=>'JFK'],['name'=>'Sydney','code'=>'SYD'],
    ['name'=>'Osaka','code'=>'KIX'],['name'=>'Taipei','code'=>'TPE'],['name'=>'Rome','code'=>'FCO'],
    ['name'=>'Barcelona','code'=>'BCN'],['name'=>'Amsterdam','code'=>'AMS'],['name'=>'Maldives','code'=>'MLE'],
    ['name'=>'Phuket','code'=>'HKT'],['name'=>'Ho Chi Minh City','code'=>'SGN'],['name'=>'Hanoi','code'=>'HAN'],
    ['name'=>'Doha','code'=>'DOH'],['name'=>'Istanbul','code'=>'IST'],['name'=>'Toronto','code'=>'YYZ'],
    ['name'=>'Los Angeles','code'=>'LAX'],
];
$allCities = array_merge(
    array_map(fn($c)=>array_merge($c,['group'=>'Local']),$localCities),
    array_map(fn($c)=>array_merge($c,['group'=>'International']),$intlCities)
);
@endphp

<style>
[x-cloak]{display:none!important;}
.pyt-field{background:#fff;border:1.5px solid var(--border);border-radius:10px;padding:14px 16px;cursor:pointer;transition:border-color .15s;}
.pyt-field:focus-within{border-color:#7B3F00;}
.pyt-label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.7px;color:var(--muted);margin-bottom:4px;}
.pyt-value{font-size:14px;font-weight:600;color:var(--dark);}
.pyt-placeholder{font-size:14px;color:#C4B8AC;}
.city-drop{position:absolute;top:calc(100% + 6px);left:0;right:0;background:#fff;border:1.5px solid var(--border);border-radius:12px;box-shadow:0 8px 32px rgba(0,0,0,0.10);z-index:200;max-height:320px;overflow:hidden;display:flex;flex-direction:column;}
.city-search{padding:10px 14px;border-bottom:1px solid var(--border);}
.city-search input{width:100%;border:none;outline:none;font-size:13px;color:var(--dark);background:transparent;}
.city-list{overflow-y:auto;flex:1;}
.city-group-label{padding:8px 14px 4px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.7px;color:var(--muted);}
.city-item{padding:10px 14px;font-size:13px;font-weight:500;color:var(--dark);cursor:pointer;display:flex;align-items:center;gap:10px;}
.city-item:hover{background:#F5F0EB;}
.city-item .code{font-size:11px;font-weight:700;color:var(--muted);background:#F0EDE8;border-radius:4px;padding:2px 6px;width:36px;text-align:center;flex-shrink:0;}
.mini-cal{position:absolute;top:calc(100% + 6px);left:0;background:#fff;border:1.5px solid var(--border);border-radius:12px;box-shadow:0 8px 32px rgba(0,0,0,0.10);z-index:200;padding:16px;min-width:260px;}
.cal-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;}
.cal-nav{background:none;border:none;cursor:pointer;color:var(--muted);font-size:14px;padding:4px 8px;}
.cal-grid{display:grid;grid-template-columns:repeat(7,1fr);gap:2px;text-align:center;}
.cal-day-name{font-size:10px;font-weight:700;color:var(--muted);padding:4px 0;}
.cal-day{font-size:12px;font-weight:500;padding:6px 4px;border-radius:6px;cursor:pointer;}
.cal-day:hover{background:#F5F0EB;}
.cal-day.selected{background:#7B3F00;color:#fff;}
.cal-day.empty{cursor:default;}
.cal-day.in-range{background:#F5EDE7;color:#7B3F00;}
.cal-day.past{color:#D1C8C0;cursor:not-allowed;}
</style>

<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:80vh;padding:40px 24px;">

    {{-- Title --}}
    <div style="text-align:center;margin-bottom:32px;">
        <h1 style="font-size:clamp(22px,3vw,28px);font-weight:800;color:var(--dark);margin:0 0 10px;">Plan Your Trip</h1>
        <p style="font-size:14px;color:var(--muted);line-height:1.6;max-width:400px;margin:0 auto;">Design your upcoming journey with precision. Organize your travel routes, schedules, and initial budget estimations in one place.</p>
    </div>

    {{-- Card --}}
    <div x-data="pytManual()" x-init="init()"
         style="background:#fff;border:1.5px solid var(--border);border-radius:20px;width:100%;max-width:520px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.06);">

        <div style="padding:28px 28px 0;">

            {{-- FROM / TO --}}
            <div style="display:grid;grid-template-columns:1fr 40px 1fr;align-items:end;gap:0;margin-bottom:14px;">

                {{-- FROM --}}
                <div style="position:relative;" x-ref="fromWrap">
                    <div class="pyt-label">From</div>
                    <div class="pyt-field" @click="toggleDrop('from')" style="display:flex;align-items:center;justify-content:space-between;border-radius:10px 0 0 10px;border-right:none;">
                        <div style="display:flex;align-items:center;gap:8px;min-width:0;">
                            <i class="fa-solid fa-plane-departure" style="color:#7B3F00;font-size:13px;flex-shrink:0;"></i>
                            <span x-show="!fromLabel" class="pyt-placeholder" style="font-size:13px;">Select origin…</span>
                            <span x-show="fromLabel" x-text="fromLabel" class="pyt-value" style="font-size:13px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"></span>
                        </div>
                    </div>
                    <div class="city-drop" x-show="activeDrop==='from'" @click.outside="activeDrop=''" x-cloak>
                        <div class="city-search"><input type="text" x-model="fromSearch" placeholder="Search city…" x-ref="fromSearch"></div>
                        <div class="city-list">
                            <template x-for="grp in ['Local','International']" :key="grp">
                                <div>
                                    <div class="city-group-label" x-text="grp + ' Destinations'"></div>
                                    <template x-for="c in filteredCities('from',grp)" :key="c.code+c.name">
                                        <div class="city-item" @click="selectCity('from',c)">
                                            <span class="code" x-text="c.code"></span><span x-text="c.name"></span>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- Swap --}}
                <div style="display:flex;align-items:center;justify-content:center;">
                    <button @click="swapCities()"
                            style="width:32px;height:32px;border-radius:50%;background:#F5F0EB;border:1.5px solid var(--border);display:flex;align-items:center;justify-content:center;cursor:pointer;z-index:1;flex-shrink:0;">
                        <i class="fa-solid fa-arrow-right-arrow-left" style="font-size:10px;color:#7B3F00;transform:rotate(90deg);"></i>
                    </button>
                </div>

                {{-- TO --}}
                <div style="position:relative;" x-ref="toWrap">
                    <div class="pyt-label">To</div>
                    <div class="pyt-field" @click="toggleDrop('to')" style="display:flex;align-items:center;justify-content:space-between;border-radius:0 10px 10px 0;border-left:none;">
                        <div style="display:flex;align-items:center;gap:8px;min-width:0;">
                            <i class="fa-solid fa-plane-arrival" style="color:#7B3F00;font-size:13px;flex-shrink:0;"></i>
                            <span x-show="!toLabel" class="pyt-placeholder" style="font-size:13px;">Select destination…</span>
                            <span x-show="toLabel" x-text="toLabel" class="pyt-value" style="font-size:13px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"></span>
                        </div>
                    </div>
                    <div class="city-drop" x-show="activeDrop==='to'" @click.outside="activeDrop=''" x-cloak>
                        <div class="city-search"><input type="text" x-model="toSearch" placeholder="Search city…" x-ref="toSearch"></div>
                        <div class="city-list">
                            <template x-for="grp in ['Local','International']" :key="grp">
                                <div>
                                    <div class="city-group-label" x-text="grp + ' Destinations'"></div>
                                    <template x-for="c in filteredCities('to',grp)" :key="c.code+c.name">
                                        <div class="city-item" @click="selectCity('to',c)">
                                            <span class="code" x-text="c.code"></span><span x-text="c.name"></span>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            {{-- BUDGET --}}
            <div style="margin-bottom:14px;">
                <div class="pyt-label">Preferred Budget Range</div>
                <div class="pyt-field" style="cursor:default;display:flex;align-items:center;gap:10px;">
                    <i class="fa-solid fa-peso-sign" style="color:#7B3F00;font-size:13px;flex-shrink:0;"></i>
                    <input type="text"
                           placeholder="Enter your budget range…"
                           style="border:none;outline:none;font-size:14px;color:var(--dark);background:transparent;width:100%;font-family:inherit;"
                           x-ref="budgetInput"
                           @input="
                               const fmt = p => { const n = p.trim().replace(/[^0-9]/g,''); return n ? parseInt(n).toLocaleString('en-PH') : ''; };
                               const raw = $el.value; const parts = raw.split('-');
                               $el.value = parts.length===2 ? fmt(parts[0])+' - '+fmt(parts[1]) : fmt(parts[0]);
                           "
                           @change="$wire.set('manualBudgetMin', $el.value)"
                           x-init="$el.value = '{{ $manualBudgetMin }}'">
                </div>
            </div>

            {{-- TRAVEL DATES — two separate fields --}}
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:24px;">

                {{-- Start Date --}}
                <div>
                    <div class="pyt-label">Start Date</div>
                    <div style="position:relative;">
                        <div class="pyt-field" @click.stop="toggleCal('start')" style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                            <i class="fa-regular fa-calendar" style="color:#7B3F00;font-size:13px;flex-shrink:0;"></i>
                            <span x-show="!startLabel" class="pyt-placeholder" style="font-size:13px;">Select date…</span>
                            <span x-show="startLabel" x-text="startLabel" class="pyt-value" style="font-size:13px;"></span>
                        </div>
                        <div class="mini-cal" x-show="activeCal==='start'" x-cloak
                             @click.stop style="min-width:260px;z-index:300;">
                            <div class="cal-header">
                                <button class="cal-nav" type="button" @click.stop="prevMonth('start');rebuildCells()"><i class="fa-solid fa-chevron-left"></i></button>
                                <span style="font-size:13px;font-weight:700;color:var(--dark);" x-text="monthName(startYear,startMonth)+' '+startYear"></span>
                                <button class="cal-nav" type="button" @click.stop="nextMonth('start');rebuildCells()"><i class="fa-solid fa-chevron-right"></i></button>
                            </div>
                            <div class="cal-grid">
                                <template x-for="d in ['Su','Mo','Tu','We','Th','Fr','Sa']"><div class="cal-day-name" x-text="d"></div></template>
                                <template x-for="cell in startCells" :key="cell.key">
                                    <div class="cal-day"
                                         :class="{'selected': cell.d && cell.val===startVal, 'past': cell.past, 'empty': !cell.d}"
                                         @click.stop="cell.d && !cell.past && pickDate('start',cell.d)"
                                         x-text="cell.d||''"></div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- End Date --}}
                <div>
                    <div class="pyt-label">End Date</div>
                    <div style="position:relative;">
                        <div class="pyt-field" @click.stop="toggleCal('end')" style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                            <i class="fa-regular fa-calendar" style="color:#7B3F00;font-size:13px;flex-shrink:0;"></i>
                            <span x-show="!endLabel" class="pyt-placeholder" style="font-size:13px;">Select date…</span>
                            <span x-show="endLabel" x-text="endLabel" class="pyt-value" style="font-size:13px;"></span>
                        </div>
                        <div class="mini-cal" x-show="activeCal==='end'" x-cloak
                             @click.stop style="min-width:260px;z-index:300;">
                            <div class="cal-header">
                                <button class="cal-nav" type="button" @click.stop="prevMonth('end');rebuildCells()"><i class="fa-solid fa-chevron-left"></i></button>
                                <span style="font-size:13px;font-weight:700;color:var(--dark);" x-text="monthName(endYear,endMonth)+' '+endYear"></span>
                                <button class="cal-nav" type="button" @click.stop="nextMonth('end');rebuildCells()"><i class="fa-solid fa-chevron-right"></i></button>
                            </div>
                            <div class="cal-grid">
                                <template x-for="d in ['Su','Mo','Tu','We','Th','Fr','Sa']"><div class="cal-day-name" x-text="d"></div></template>
                                <template x-for="cell in endCells" :key="cell.key">
                                    <div class="cal-day"
                                         :class="{'selected': cell.d && cell.val===endVal, 'past': cell.past, 'empty': !cell.d}"
                                         @click.stop="cell.d && !cell.past && pickDate('end',cell.d)"
                                         x-text="cell.d||''"></div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

        </div>

        {{-- Card footer / bottom bar --}}
        <div style="border-top:1.5px solid var(--border);padding:16px 24px;display:flex;align-items:center;gap:12px;">
            <div style="display:flex;align-items:center;gap:6px;flex:1;min-width:0;">
                <i class="fa-solid fa-circle-info" style="color:var(--muted);font-size:12px;flex-shrink:0;"></i>
                <span style="font-size:12px;color:var(--muted);">Fill in the details to start your journey calculation.</span>
            </div>
            <button wire:click="saveDraft"
                    style="background:#fff;border:1.5px solid var(--border);color:var(--dark);border-radius:10px;padding:10px 20px;font-size:13px;font-weight:600;cursor:pointer;white-space:nowrap;"
                    onmouseenter="this.style.background='#F5F0EB'" onmouseleave="this.style.background='#fff'">
                Save Draft
            </button>
            <button wire:click="proceedFromTripDetails" wire:loading.attr="disabled" wire:target="proceedFromTripDetails"
                    style="background:#7B3F00;color:#fff;border:none;border-radius:10px;padding:10px 28px;font-size:13px;font-weight:700;cursor:pointer;white-space:nowrap;"
                    onmouseenter="this.style.background='#6A3500'" onmouseleave="this.style.background='#7B3F00'">
                <span wire:loading.remove wire:target="proceedFromTripDetails">Next</span>
                <span wire:loading wire:target="proceedFromTripDetails"><i class="fa-solid fa-spinner fa-spin"></i></span>
            </button>
        </div>

    </div>
</div>

@script
<script>
window.pytManual = function() {
    const cities = @json($allCities);
    const months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
    const now = new Date();

    return {
        activeDrop: '',
        activeCal: '',
        fromLabel: @json($manualFrom ? $manualFrom . ' (' . \App\Livewire\Traveler\TripPlannerWizard::staticIataCode($manualFrom) . ')' : ''),
        toLabel: @json($manualTo ? $manualTo . ' (' . \App\Livewire\Traveler\TripPlannerWizard::staticIataCode($manualTo) . ')' : ''),
        fromSearch: '',
        toSearch: '',
        startLabel: @json($startDate ? \Carbon\Carbon::parse($startDate)->format('M d, Y') : ''),
        endLabel:   @json($endDate   ? \Carbon\Carbon::parse($endDate)->format('M d, Y')   : ''),
        startVal:   @json($startDate ?? ''),
        endVal:     @json($endDate   ?? ''),
        startYear: now.getFullYear(), startMonth: now.getMonth()+1,
        endYear:   now.getFullYear(), endMonth:   now.getMonth()+1,
        startCells: [],
        endCells: [],

        init() {
            this.startCells = this._buildCells(this.startYear, this.startMonth);
            this.endCells   = this._buildCells(this.endYear,   this.endMonth);
            this.$watch('startYear',  () => { this.startCells = this._buildCells(this.startYear,  this.startMonth); });
            this.$watch('startMonth', () => { this.startCells = this._buildCells(this.startYear,  this.startMonth); });
            this.$watch('endYear',    () => { this.endCells   = this._buildCells(this.endYear,    this.endMonth);   });
            this.$watch('endMonth',   () => { this.endCells   = this._buildCells(this.endYear,    this.endMonth);   });
            document.addEventListener('click', () => this.closeCals());
        },

        _buildCells(y, m) {
            const todayStr = now.getFullYear()+'-'+String(now.getMonth()+1).padStart(2,'0')+'-'+String(now.getDate()).padStart(2,'0');
            const first = new Date(y, m-1, 1).getDay();
            const days  = new Date(y, m, 0).getDate();
            const cells = [];
            for (let i = 0; i < first; i++) cells.push({ d: null, key: 'e'+y+m+i, val: '', past: false });
            for (let d = 1; d <= days; d++) {
                const val = y + '-' + String(m).padStart(2,'0') + '-' + String(d).padStart(2,'0');
                cells.push({ d, key: 'd'+y+m+d, val, past: val < todayStr });
            }
            return cells;
        },

        rebuildCells() {
            this.startCells = this._buildCells(this.startYear, this.startMonth);
            this.endCells   = this._buildCells(this.endYear,   this.endMonth);
        },

        toggleDrop(which) {
            this.activeDrop = this.activeDrop === which ? '' : which;
            this.activeCal = '';
            if (this.activeDrop === which) {
                this.$nextTick(() => {
                    const el = this.$refs[which+'Search'];
                    if (el) el.focus();
                });
            }
        },

        toggleCal(which) {
            this.activeCal  = this.activeCal === which ? '' : which;
            this.activeDrop = '';
        },

        closeCals() { this.activeCal = ''; this.activeDrop = ''; },

        filteredCities(which, group) {
            const q = (which === 'from' ? this.fromSearch : this.toSearch).toLowerCase();
            return cities.filter(c => c.group === group && (!q || c.name.toLowerCase().includes(q) || c.code.toLowerCase().includes(q)));
        },

        selectCity(which, c) {
            if (which === 'from') {
                this.fromLabel = c.name + ' (' + c.code + ')';
                this.fromSearch = '';
                $wire.set('manualFrom', c.name);
            } else {
                this.toLabel = c.name + ' (' + c.code + ')';
                this.toSearch = '';
                $wire.set('manualTo', c.name);
            }
            this.activeDrop = '';
        },

        swapCities() {
            [this.fromLabel, this.toLabel] = [this.toLabel, this.fromLabel];
            const fromName = this.fromLabel ? this.fromLabel.replace(/\s*\([^)]+\)$/, '') : '';
            const toName   = this.toLabel   ? this.toLabel.replace(/\s*\([^)]+\)$/, '') : '';
            $wire.set('manualFrom', toName);
            $wire.set('manualTo', fromName);
        },

        formatDate(y, m, d) {
            return y + '-' + String(m).padStart(2,'0') + '-' + String(d).padStart(2,'0');
        },

        pickDate(which, d) {
            const y = which === 'start' ? this.startYear : this.endYear;
            const m = which === 'start' ? this.startMonth : this.endMonth;
            const val = y + '-' + String(m).padStart(2,'0') + '-' + String(d).padStart(2,'0');
            const label = months[m-1].slice(0,3) + ' ' + String(d).padStart(2,'0') + ', ' + y;
            if (which === 'start') { this.startVal = val; this.startLabel = label; }
            else                   { this.endVal   = val; this.endLabel   = label; }
            $wire.set(which === 'start' ? 'startDate' : 'endDate', val);
            this.rebuildCells();
            this.activeCal = '';
        },

        pickRangeDate(y, m, d) {
            const val   = this.formatDate(y, m, d);
            const label = months[m-1].slice(0,3) + ' ' + String(d).padStart(2,'0') + ', ' + y;
            if (!this.startVal || (this.startVal && this.endVal)) {
                // First pick — reset range
                this.startVal = val; this.startLabel = label;
                this.endVal = ''; this.endLabel = '';
                $wire.set('startDate', val);
                $wire.set('endDate', '');
            } else if (val >= this.startVal) {
                // Second pick — end date
                this.endVal = val; this.endLabel = label;
                $wire.set('endDate', val);
                this.activeCal = '';
            } else {
                // Picked before start — shift start
                this.startVal = val; this.startLabel = label;
                $wire.set('startDate', val);
            }
        },

        prevMonth(which) {
            if (which === 'start') {
                this.startMonth--; if (this.startMonth < 1) { this.startMonth = 12; this.startYear--; }
            } else {
                this.endMonth--;   if (this.endMonth < 1)   { this.endMonth   = 12; this.endYear--;   }
            }
        },
        nextMonth(which) {
            if (which === 'start') {
                this.startMonth++; if (this.startMonth > 12) { this.startMonth = 1; this.startYear++; }
            } else {
                this.endMonth++;   if (this.endMonth > 12)   { this.endMonth   = 1; this.endYear++;   }
            }
        },

        monthName(y, m) { return months[m-1]; },

        calCells(y, m) {
            const first = new Date(y, m-1, 1).getDay();
            const days  = new Date(y, m, 0).getDate();
            const todayStr = now.getFullYear()+'-'+String(now.getMonth()+1).padStart(2,'0')+'-'+String(now.getDate()).padStart(2,'0');
            const cells = [];
            for (let i=0; i<first; i++) cells.push({d:null, key:'e'+i, past:false});
            for (let d=1; d<=days; d++) {
                const ds = this.formatDate(y,m,d);
                cells.push({d, key:'d'+d, past: ds < todayStr});
            }
            return cells;
        },
    };
};
</script>
@endscript
@endif

{{-- ═══════════════════════════════════════════════════════════════
     STEP 2 — Select Your Flight (manual)
═══════════════════════════════════════════════════════════════ --}}
@if ($planningMode === 'manual' && $step === 2)
@php
$localCities2 = [
    ['name'=>'Manila','code'=>'MNL'],['name'=>'Cebu City','code'=>'CEB'],['name'=>'Davao City','code'=>'DVO'],
    ['name'=>'Boracay','code'=>'KLO'],['name'=>'Puerto Princesa','code'=>'PPS'],['name'=>'Tagbilaran (Bohol)','code'=>'TAG'],
    ['name'=>'Siargao','code'=>'IAO'],['name'=>'Iloilo City','code'=>'ILO'],['name'=>'Bacolod','code'=>'BCD'],
    ['name'=>'Zamboanga','code'=>'ZAM'],['name'=>'Cagayan de Oro','code'=>'CGY'],['name'=>'General Santos','code'=>'GES'],
    ['name'=>'Tacloban','code'=>'TAC'],['name'=>'Dumaguete','code'=>'DGT'],['name'=>'El Nido','code'=>'ENI'],
    ['name'=>'Coron','code'=>'USU'],['name'=>'Baguio','code'=>'BAG'],['name'=>'Tagaytay','code'=>'MNL'],
    ['name'=>'Vigan','code'=>'VIG'],['name'=>'Batanes','code'=>'BSO'],['name'=>'Camiguin','code'=>'CGM'],
    ['name'=>'Siquijor','code'=>'DGT'],['name'=>'Surigao','code'=>'SUG'],['name'=>'Laoag','code'=>'LAO'],
    ['name'=>'Legazpi','code'=>'LGP'],
];
$intlCities2 = [
    ['name'=>'Singapore','code'=>'SIN'],['name'=>'Bangkok','code'=>'BKK'],['name'=>'Bali','code'=>'DPS'],
    ['name'=>'Tokyo','code'=>'NRT'],['name'=>'Seoul','code'=>'ICN'],['name'=>'Kuala Lumpur','code'=>'KUL'],
    ['name'=>'Hong Kong','code'=>'HKG'],['name'=>'Dubai','code'=>'DXB'],['name'=>'London','code'=>'LHR'],
    ['name'=>'Paris','code'=>'CDG'],['name'=>'New York','code'=>'JFK'],['name'=>'Sydney','code'=>'SYD'],
    ['name'=>'Osaka','code'=>'KIX'],['name'=>'Taipei','code'=>'TPE'],['name'=>'Rome','code'=>'FCO'],
    ['name'=>'Barcelona','code'=>'BCN'],['name'=>'Amsterdam','code'=>'AMS'],['name'=>'Maldives','code'=>'MLE'],
    ['name'=>'Phuket','code'=>'HKT'],['name'=>'Ho Chi Minh City','code'=>'SGN'],['name'=>'Hanoi','code'=>'HAN'],
    ['name'=>'Doha','code'=>'DOH'],['name'=>'Istanbul','code'=>'IST'],['name'=>'Toronto','code'=>'YYZ'],
    ['name'=>'Los Angeles','code'=>'LAX'],
];
$allCities2 = array_merge(
    array_map(fn($c)=>array_merge($c,['group'=>'Local']),$localCities2),
    array_map(fn($c)=>array_merge($c,['group'=>'International']),$intlCities2)
);
@endphp

<style>
[x-cloak]{display:none!important;}
.city-drop{position:absolute;top:calc(100% + 6px);left:0;right:0;background:#fff;border:1.5px solid var(--border);border-radius:12px;box-shadow:0 8px 32px rgba(0,0,0,.12);z-index:500;max-height:320px;overflow:hidden;display:flex;flex-direction:column;}
.city-search{padding:10px 14px;border-bottom:1px solid var(--border);}
.city-search input{width:100%;border:none;outline:none;font-size:13px;color:var(--dark);background:transparent;}
.city-list{overflow-y:auto;flex:1;}
.city-group-label{padding:8px 14px 4px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:var(--muted);}
.city-item{padding:10px 14px;font-size:13px;font-weight:500;color:var(--dark);cursor:pointer;display:flex;align-items:center;gap:10px;}
.city-item:hover{background:#F5F0EB;}
.city-item .code{font-size:11px;font-weight:700;color:#7B3F00;background:#F5F0EB;border-radius:4px;padding:2px 7px;width:36px;text-align:center;flex-shrink:0;}
.mini-cal{position:absolute;top:calc(100% + 6px);left:0;background:#fff;border:1.5px solid var(--border);border-radius:12px;box-shadow:0 8px 32px rgba(0,0,0,.12);z-index:500;padding:16px;min-width:260px;}
.cal-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;}
.cal-nav{background:none;border:none;cursor:pointer;color:var(--muted);font-size:14px;padding:4px 8px;}
.cal-grid{display:grid;grid-template-columns:repeat(7,1fr);gap:2px;text-align:center;}
.cal-day-name{font-size:10px;font-weight:700;color:var(--muted);padding:4px 0;}
.cal-day{font-size:12px;font-weight:500;padding:6px 4px;border-radius:6px;cursor:pointer;}
.cal-day:hover:not(.past):not(.empty){background:#F5F0EB;}
.cal-day.selected{background:#7B3F00;color:#fff;}
.cal-day.empty{cursor:default;}
.cal-day.in-range{background:#F5EDE7;color:#7B3F00;}
.cal-day.past{color:#D1C8C0;cursor:not-allowed;}
</style>

<div x-data="pytFlight()" x-init="init()" style="padding-bottom:20px;">

    {{-- Page header --}}
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:20px;margin-bottom:24px;flex-wrap:wrap;">
        <div>
            <button wire:click="$set('step', 1)"
                    style="display:inline-flex;align-items:center;gap:6px;background:none;border:none;color:#7B3F00;font-size:13px;font-weight:600;cursor:pointer;padding:0;margin-bottom:10px;">
                <i class="fa-solid fa-arrow-left" style="font-size:11px;"></i> Back to Planner
            </button>
            <h1 style="font-size:26px;font-weight:800;color:var(--dark);margin:0 0 6px;">Select Flight</h1>
            <p style="font-size:14px;color:var(--muted);margin:0;">
                @if($mcSearched && $mcTo)
                    Showing the best flight options for your {{ $manualFrom }} to {{ $manualTo }} and {{ $manualTo }} to {{ $mcTo }} trip.
                @else
                    Showing the best flight options for your {{ $manualFrom }} to {{ $manualTo }} trip.
                @endif
            </p>
        </div>
        {{-- Route + Date badge — shows both legs for multi-city --}}
        <div style="background:#fff;border:1.5px solid var(--border);border-radius:14px;padding:14px 24px;display:inline-flex;align-items:stretch;gap:0;flex-shrink:0;box-shadow:0 1px 4px rgba(0,0,0,0.06);">
            <div style="padding-right:24px;border-right:1px solid var(--border);display:flex;flex-direction:column;align-items:flex-start;">
                <div style="font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--muted);margin-bottom:6px;">Route</div>
                {{-- Leg 1 route --}}
                <div style="font-size:16px;font-weight:800;color:var(--dark);display:flex;align-items:center;gap:8px;">
                    {{ \App\Livewire\Traveler\TripPlannerWizard::staticIataCode($manualFrom) }}
                    <span style="color:var(--muted);font-weight:400;">→</span>
                    {{ \App\Livewire\Traveler\TripPlannerWizard::staticIataCode($manualTo) }}
                </div>
                {{-- Leg 2 route — shown only after search --}}
                @if($mcSearched && $mcTo)
                <div style="font-size:16px;font-weight:800;color:var(--dark);display:flex;align-items:center;gap:8px;margin-top:4px;">
                    {{ \App\Livewire\Traveler\TripPlannerWizard::staticIataCode($manualTo) }}
                    <span style="color:var(--muted);font-weight:400;">→</span>
                    {{ \App\Livewire\Traveler\TripPlannerWizard::staticIataCode($mcTo) }}
                </div>
                @endif
            </div>
            <div style="padding-left:24px;display:flex;flex-direction:column;align-items:flex-start;">
                <div style="font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--muted);margin-bottom:6px;">Date</div>
                {{-- Leg 1 date --}}
                <div style="font-size:15px;font-weight:700;color:var(--dark);">
                    @if($startDate)
                        {{ \Carbon\Carbon::parse($startDate)->format('M j, Y') }}
                        @if($endDate) – {{ \Carbon\Carbon::parse($endDate)->format('M j, Y') }}@endif
                    @else
                        —
                    @endif
                </div>
                {{-- Leg 2 date — shown only after search --}}
                @if($mcSearched && $mcStartDate && $mcEndDate)
                <div style="font-size:15px;font-weight:700;color:var(--dark);margin-top:4px;">
                    {{ \Carbon\Carbon::parse($mcStartDate)->format('M j, Y') }} – {{ \Carbon\Carbon::parse($mcEndDate)->format('M j, Y') }}
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Search panel --}}
    <div style="background:#fff;border:1.5px solid var(--border);border-radius:16px;overflow:visible;box-shadow:0 2px 8px rgba(0,0,0,0.04);margin-bottom:14px;width:100%;">

        {{-- LEG 1: FROM | TO | START DATE | END DATE --}}
        <div style="display:flex;align-items:stretch;border-bottom:1px solid var(--border);min-width:0;">

            {{-- FROM --}}
            <div style="flex:1;min-width:0;padding:16px 20px;border-right:1px solid var(--border);position:relative;">
                <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:var(--muted);margin-bottom:6px;">From</div>
                <div style="display:flex;align-items:center;gap:8px;cursor:pointer;" @click="toggleDrop2('from')">
                    <i class="fa-solid fa-plane-departure" style="color:#7B3F00;font-size:13px;flex-shrink:0;"></i>
                    <span style="font-size:14px;font-weight:600;color:var(--dark);flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" x-text="fromLabel || '{{ $manualFrom }}'"></span>
                    <i class="fa-solid fa-chevron-down" style="font-size:10px;color:var(--muted);flex-shrink:0;"></i>
                </div>
                <div class="city-drop" x-show="activeDrop2==='from'" @click.outside="activeDrop2=''" x-cloak style="min-width:260px;">
                    <div class="city-search"><input type="text" x-model="fromSearch2" placeholder="Search city…" @input="$forceUpdate()"></div>
                    <div class="city-list">
                        <template x-for="grp in ['Local','International']" :key="grp">
                            <div>
                                <div class="city-group-label" x-text="grp+' Destinations'"></div>
                                <template x-for="c in filteredCities2('from',grp)" :key="c.code+c.name">
                                    <div class="city-item" @click="selectCity2('from',c)"><span class="code" x-text="c.code"></span><span x-text="c.name"></span></div>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            {{-- TO --}}
            <div style="flex:1;min-width:0;padding:16px 20px;border-right:1px solid var(--border);position:relative;">
                <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:var(--muted);margin-bottom:6px;">To</div>
                <div style="display:flex;align-items:center;gap:8px;cursor:pointer;" @click="toggleDrop2('to')">
                    <i class="fa-solid fa-plane-arrival" style="color:#7B3F00;font-size:13px;flex-shrink:0;"></i>
                    <span style="font-size:14px;font-weight:600;color:var(--dark);flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" x-text="toLabel || '{{ $manualTo }}'"></span>
                    <i class="fa-solid fa-chevron-down" style="font-size:10px;color:var(--muted);flex-shrink:0;"></i>
                </div>
                <div class="city-drop" x-show="activeDrop2==='to'" @click.outside="activeDrop2=''" x-cloak style="min-width:260px;">
                    <div class="city-search"><input type="text" x-model="toSearch2" placeholder="Search city…" @input="$forceUpdate()"></div>
                    <div class="city-list">
                        <template x-for="grp in ['Local','International']" :key="grp">
                            <div>
                                <div class="city-group-label" x-text="grp+' Destinations'"></div>
                                <template x-for="c in filteredCities2('to',grp)" :key="c.code+c.name">
                                    <div class="city-item" @click="selectCity2('to',c)"><span class="code" x-text="c.code"></span><span x-text="c.name"></span></div>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            {{-- START DATE --}}
            <div style="flex:1;min-width:0;padding:16px 20px;border-right:1px solid var(--border);position:relative;">
                <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:var(--muted);margin-bottom:6px;">Start Date</div>
                <div style="display:flex;align-items:center;gap:8px;cursor:pointer;" @click="toggleCal2('start')">
                    <i class="fa-regular fa-calendar" style="color:#7B3F00;font-size:13px;flex-shrink:0;"></i>
                    <span x-show="!(startLabel2||'{{ $startDate }}')" style="font-size:14px;color:#C4B8AC;flex:1;">Select date</span>
                    <span x-show="startLabel2||'{{ $startDate }}'" style="font-size:14px;font-weight:600;color:var(--dark);flex:1;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"
                          x-text="startLabel2||'{{ $startDate ? \Carbon\Carbon::parse($startDate)->format("M j, Y") : "" }}'"></span>
                    <i class="fa-solid fa-chevron-down" style="font-size:10px;color:var(--muted);flex-shrink:0;"></i>
                </div>
                <div class="mini-cal" x-show="activeCal2==='start'" @click.outside="activeCal2=''" x-cloak>
                    <div class="cal-header">
                        <button class="cal-nav" @click.stop="prevMonth2('start')"><i class="fa-solid fa-chevron-left"></i></button>
                        <span style="font-size:13px;font-weight:700;" x-text="monthName2(sY,sM)+' '+sY"></span>
                        <button class="cal-nav" @click.stop="nextMonth2('start')"><i class="fa-solid fa-chevron-right"></i></button>
                    </div>
                    <div class="cal-grid">
                        <template x-for="d in ['Su','Mo','Tu','We','Th','Fr','Sa']"><div class="cal-day-name" x-text="d"></div></template>
                        <template x-for="cell in calCells2(sY,sM)" :key="cell.key">
                            <div class="cal-day" :class="{'selected':cell.d&&fmt2(sY,sM,cell.d)===startVal2,'past':cell.past,'empty':!cell.d}"
                                 @click.stop="cell.d&&!cell.past&&pickDate2('start',cell.d)" x-text="cell.d||''"></div>
                        </template>
                    </div>
                </div>
            </div>

            {{-- END DATE --}}
            <div style="flex:1;min-width:0;padding:16px 20px;position:relative;">
                <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:var(--muted);margin-bottom:6px;">End Date</div>
                <div style="display:flex;align-items:center;gap:8px;cursor:pointer;" @click="toggleCal2('end')">
                    <i class="fa-regular fa-calendar" style="color:#7B3F00;font-size:13px;flex-shrink:0;"></i>
                    <span x-show="!(endLabel2||'{{ $endDate }}')" style="font-size:14px;color:#C4B8AC;flex:1;">Select date</span>
                    <span x-show="endLabel2||'{{ $endDate }}'" style="font-size:14px;font-weight:600;color:var(--dark);flex:1;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"
                          x-text="endLabel2||'{{ $endDate ? \Carbon\Carbon::parse($endDate)->format("M j, Y") : "" }}'"></span>
                    <i class="fa-solid fa-chevron-down" style="font-size:10px;color:var(--muted);flex-shrink:0;"></i>
                </div>
                <div class="mini-cal" x-show="activeCal2==='end'" @click.outside="activeCal2=''" x-cloak style="right:0;left:auto;">
                    <div class="cal-header">
                        <button class="cal-nav" @click.stop="prevMonth2('end')"><i class="fa-solid fa-chevron-left"></i></button>
                        <span style="font-size:13px;font-weight:700;" x-text="monthName2(eY,eM)+' '+eY"></span>
                        <button class="cal-nav" @click.stop="nextMonth2('end')"><i class="fa-solid fa-chevron-right"></i></button>
                    </div>
                    <div class="cal-grid">
                        <template x-for="d in ['Su','Mo','Tu','We','Th','Fr','Sa']"><div class="cal-day-name" x-text="d"></div></template>
                        <template x-for="cell in calCells2(eY,eM)" :key="cell.key">
                            <div class="cal-day" :class="{'selected':cell.d&&fmt2(eY,eM,cell.d)===endVal2,'past':cell.past,'empty':!cell.d}"
                                 @click.stop="cell.d&&!cell.past&&pickDate2('end',cell.d)" x-text="cell.d||''"></div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        {{-- LEG 2 — Multi-city: FROM (locked) + TO + START DATE + END DATE --}}
        <div :style="tripType==='multi_city'?'display:flex;align-items:stretch;border-bottom:1px solid var(--border);min-width:0;':'display:none;'">

            {{-- FROM (locked = leg 1 TO) --}}
            <div style="flex:1;min-width:0;padding:16px 20px;border-right:1px solid var(--border);">
                <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:var(--muted);margin-bottom:6px;">From</div>
                <div style="display:flex;align-items:center;gap:8px;">
                    <i class="fa-solid fa-plane-departure" style="color:#7B3F00;font-size:13px;flex-shrink:0;"></i>
                    <span style="font-size:14px;font-weight:600;color:var(--dark);flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
                          x-text="toLabel ? toLabel.replace(/\s*\([^)]+\)$/,'') : '{{ $manualTo }}'"></span>
                </div>
            </div>

            {{-- TO (mc) --}}
            <div style="flex:1;min-width:0;padding:16px 20px;border-right:1px solid var(--border);position:relative;">
                <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:var(--muted);margin-bottom:6px;">To</div>
                <div style="display:flex;align-items:center;gap:8px;cursor:pointer;" @click.stop="$nextTick(()=>toggleDrop2('mc'))">
                    <i class="fa-solid fa-plane-arrival" style="color:#7B3F00;font-size:13px;flex-shrink:0;"></i>
                    <span x-show="!mcLabel" style="font-size:14px;color:#C4B8AC;flex:1;">Where to?</span>
                    <span x-show="mcLabel" x-text="mcLabel" style="font-size:14px;font-weight:600;color:var(--dark);flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"></span>
                    <i class="fa-solid fa-chevron-down" style="font-size:10px;color:var(--muted);flex-shrink:0;"></i>
                </div>
                <div class="city-drop" x-show="activeDrop2==='mc'" @click.outside="activeDrop2=''" style="min-width:260px;z-index:1000;">
                    <div class="city-search"><input type="text" x-model="mcSearch" placeholder="Search city…" @input="$forceUpdate()"></div>
                    <div class="city-list">
                        <template x-for="grp in ['Local','International']" :key="grp">
                            <div>
                                <div class="city-group-label" x-text="grp+' Destinations'"></div>
                                <template x-for="c in filteredCities2('mc',grp)" :key="c.code+c.name">
                                    <div class="city-item" @click="selectCity2('mc',c)"><span class="code" x-text="c.code"></span><span x-text="c.name"></span></div>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            {{-- MC START DATE --}}
            <div style="flex:1;min-width:0;padding:16px 20px;border-right:1px solid var(--border);position:relative;">
                <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:var(--muted);margin-bottom:6px;">Start Date</div>
                <div style="display:flex;align-items:center;gap:8px;cursor:pointer;" @click.stop="$nextTick(()=>toggleCal2('mc-start'))">
                    <i class="fa-regular fa-calendar" style="color:#7B3F00;font-size:13px;flex-shrink:0;"></i>
                    <span x-show="!mcStartLabel" style="font-size:14px;color:#C4B8AC;flex:1;">Select date</span>
                    <span x-show="mcStartLabel" x-text="mcStartLabel" style="font-size:14px;font-weight:600;color:var(--dark);flex:1;"></span>
                    <i class="fa-solid fa-chevron-down" style="font-size:10px;color:var(--muted);flex-shrink:0;"></i>
                </div>
                <div class="mini-cal" x-show="activeCal2==='mc-start'" @click.outside="activeCal2=''" style="z-index:1000;">
                    <div class="cal-header">
                        <button class="cal-nav" @click.stop="prevMonth2('mc')"><i class="fa-solid fa-chevron-left"></i></button>
                        <span style="font-size:13px;font-weight:700;" x-text="monthName2(mcY,mcM)+' '+mcY"></span>
                        <button class="cal-nav" @click.stop="nextMonth2('mc')"><i class="fa-solid fa-chevron-right"></i></button>
                    </div>
                    <div class="cal-grid">
                        <template x-for="d in ['Su','Mo','Tu','We','Th','Fr','Sa']"><div class="cal-day-name" x-text="d"></div></template>
                        <template x-for="cell in calCells2(mcY,mcM)" :key="cell.key">
                            <div class="cal-day" :class="{'selected':cell.d&&fmt2(mcY,mcM,cell.d)===mcStartVal,'past':cell.past,'empty':!cell.d}"
                                 @click.stop="cell.d&&!cell.past&&pickDate2('mc-start',cell.d)" x-text="cell.d||''"></div>
                        </template>
                    </div>
                </div>
            </div>

            {{-- MC END DATE --}}
            <div style="flex:1;min-width:0;padding:16px 20px;position:relative;">
                <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:var(--muted);margin-bottom:6px;">End Date</div>
                <div style="display:flex;align-items:center;gap:8px;cursor:pointer;" @click.stop="$nextTick(()=>toggleCal2('mc-end'))">
                    <i class="fa-regular fa-calendar" style="color:#7B3F00;font-size:13px;flex-shrink:0;"></i>
                    <span x-show="!mcEndLabel" style="font-size:14px;color:#C4B8AC;flex:1;">Select date</span>
                    <span x-show="mcEndLabel" x-text="mcEndLabel" style="font-size:14px;font-weight:600;color:var(--dark);flex:1;"></span>
                    <i class="fa-solid fa-chevron-down" style="font-size:10px;color:var(--muted);flex-shrink:0;"></i>
                </div>
                <div class="mini-cal" x-show="activeCal2==='mc-end'" @click.outside="activeCal2=''" style="right:0;left:auto;z-index:1000;">
                    <div class="cal-header">
                        <button class="cal-nav" @click.stop="prevMonth2('mc2')"><i class="fa-solid fa-chevron-left"></i></button>
                        <span style="font-size:13px;font-weight:700;" x-text="monthName2(mc2Y,mc2M)+' '+mc2Y"></span>
                        <button class="cal-nav" @click.stop="nextMonth2('mc2')"><i class="fa-solid fa-chevron-right"></i></button>
                    </div>
                    <div class="cal-grid">
                        <template x-for="d in ['Su','Mo','Tu','We','Th','Fr','Sa']"><div class="cal-day-name" x-text="d"></div></template>
                        <template x-for="cell in calCells2(mc2Y,mc2M)" :key="cell.key">
                            <div class="cal-day" :class="{'selected':cell.d&&fmt2(mc2Y,mc2M,cell.d)===mcEndVal,'past':cell.past,'empty':!cell.d}"
                                 @click.stop="cell.d&&!cell.past&&pickDate2('mc-end',cell.d)" x-text="cell.d||''"></div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        {{-- Search Flights button --}}
        <div style="display:flex;justify-content:flex-end;padding:14px 20px;">
            <button wire:click="searchManualFlights" wire:loading.attr="disabled" wire:target="searchManualFlights"
                    style="background:#5C2D0A;color:#fff;border:none;border-radius:10px;padding:11px 24px;font-size:13px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:8px;"
                    onmouseenter="this.style.background='#4A2408'"
                    onmouseleave="this.style.background='#5C2D0A'">
                <span wire:loading.remove wire:target="searchManualFlights"><i class="fa-solid fa-magnifying-glass"></i> Search Flights</span>
                <span wire:loading wire:target="searchManualFlights"><i class="fa-solid fa-spinner fa-spin"></i> Searching…</span>
            </button>
        </div>
    </div>

    {{-- Filter row: Price sort + Trip type --}}
    <div style="display:flex;align-items:center;gap:16px;margin-top:14px;margin-bottom:14px;flex-wrap:wrap;">

        {{-- Price dropdown --}}
        <div style="position:relative;">
            <button @click="priceOpen=!priceOpen"
                    style="display:inline-flex;align-items:center;gap:10px;background:#fff;color:var(--dark);border:1.5px solid var(--border);border-radius:24px;padding:9px 18px;font-size:13px;font-weight:600;cursor:pointer;box-shadow:0 1px 3px rgba(0,0,0,0.06);">
                <span x-text="priceDir==='asc'?'Price: Low to High':'Price: High to Low'"></span>
                <i class="fa-solid fa-chevron-down" style="font-size:10px;color:var(--muted);transition:transform .15s;" :style="priceOpen?'transform:rotate(180deg)':''"></i>
            </button>
            <div x-show="priceOpen" @click.outside="priceOpen=false" x-cloak
                 style="position:absolute;top:calc(100% + 6px);left:0;background:#fff;border:1.5px solid var(--border);border-radius:16px;box-shadow:0 8px 28px rgba(0,0,0,0.10);z-index:50;min-width:200px;overflow:hidden;padding:6px 0;">
                <button @click="priceDir='asc';priceOpen=false;sortFlights()"
                        style="width:100%;text-align:left;padding:14px 20px;border:none;background:none;font-size:14px;font-weight:500;color:var(--dark);cursor:pointer;"
                        onmouseenter="this.style.background='#F8F5F2'" onmouseleave="this.style.background='none'">
                    Price: Low to High
                </button>
                <div style="height:1px;background:var(--border);margin:0 12px;"></div>
                <button @click="priceDir='desc';priceOpen=false;sortFlights()"
                        style="width:100%;text-align:left;padding:14px 20px;border:none;background:none;font-size:14px;font-weight:500;color:var(--dark);cursor:pointer;"
                        onmouseenter="this.style.background='#F8F5F2'" onmouseleave="this.style.background='none'">
                    Price: High to Low
                </button>
            </div>
        </div>

        {{-- Trip type radios --}}
        <div style="display:flex;align-items:center;gap:20px;">
            @foreach(['one_way'=>'One-way','round_trip'=>'Round Trip','multi_city'=>'Multi-city'] as $val => $label)
            <label style="display:inline-flex;align-items:center;gap:7px;cursor:pointer;font-size:13px;font-weight:500;color:var(--dark);">
                <input type="radio" name="trip_type" value="{{ $val }}"
                       wire:model.live="flightTripType"
                       @change="tripType='{{ $val }}'"
                       {{ $flightTripType === $val ? 'checked' : '' }}
                       style="accent-color:#7B3F00;width:15px;height:15px;cursor:pointer;">
                {{ $label }}
            </label>
            @endforeach
        </div>
    </div>

    {{-- Loading --}}
    @if ($flightLoading)
    <div style="text-align:center;padding:60px 20px;">
        <i class="fa-solid fa-spinner fa-spin" style="font-size:32px;color:#7B3F00;margin-bottom:16px;display:block;"></i>
        <p style="color:var(--muted);font-size:15px;">Searching for flights…</p>
    </div>
    @elseif ($flightError)
    <div style="text-align:center;padding:60px 20px;">
        <i class="fa-solid fa-triangle-exclamation" style="font-size:40px;color:#e74c3c;margin-bottom:16px;display:block;"></i>
        <p style="color:#e74c3c;font-size:15px;font-weight:600;">{{ $flightError }}</p>
    </div>
    @elseif (empty($flightResults))
    <div style="text-align:center;padding:60px 20px;">
        <i class="fa-solid fa-plane-slash" style="font-size:40px;color:var(--border);margin-bottom:16px;display:block;"></i>
        <p style="color:var(--muted);font-size:15px;">No flights found. Try searching above.</p>
    </div>
    @elseif($mcFlightStep)
    @if($mcFlightLoading)
    <div style="text-align:center;padding:60px 0;color:var(--muted);"><i class="fa-solid fa-spinner fa-spin" style="font-size:22px;"></i></div>
    @elseif(empty($mcFlightResults))
    <div style="text-align:center;padding:60px 0;color:var(--muted);">No flights found for this leg.</div>
    @else
    <div style="display:flex;flex-direction:column;gap:12px;">
        @foreach ($mcFlightResults as $idx => $flight)
        @php
            $dur    = $flight['duration'] ?? 0;
            $durStr = $dur ? (floor($dur/60).'h '.($dur%60).'m') : 'Nonstop';
            $dep    = $flight['depart'] ?? '';
            $arr    = $flight['arrive'] ?? '';
            $fmtTime = fn($t) => $t ? date('g:i A', strtotime($t)) : '';
        @endphp
        <div style="background:#fff;border:1.5px solid var(--border);border-radius:14px;overflow:hidden;transition:box-shadow .15s;"
             onmouseenter="this.style.boxShadow='0 4px 20px rgba(0,0,0,0.08)'"
             onmouseleave="this.style.boxShadow='none'">
            @if(!empty($flight['bags']))
            <div style="padding:7px 20px;font-size:11px;font-weight:600;color:#7B5C3A;display:flex;align-items:center;gap:6px;border-bottom:1px solid var(--border);">
                <i class="fa-solid fa-suitcase" style="font-size:10px;"></i> {{ $flight['bags'] }}
            </div>
            @endif
            <div style="padding:18px 24px;display:flex;align-items:center;gap:20px;">
                <div style="width:100px;flex-shrink:0;">
                    @if(!empty($flight['logo']))<img src="{{ $flight['logo'] }}" alt="{{ $flight['airline'] }}" style="height:28px;object-fit:contain;max-width:90px;display:block;margin-bottom:6px;">@endif
                    <div style="font-size:12px;font-weight:700;color:var(--dark);line-height:1.3;">{{ $flight['airline'] ?? '' }}</div>
                    <div style="font-size:11px;color:var(--muted);">{{ $flight['number'] ?? '' }}</div>
                </div>
                <div style="text-align:left;min-width:72px;">
                    <div style="font-size:22px;font-weight:800;color:var(--dark);line-height:1;">{{ $fmtTime($dep) }}</div>
                    <div style="font-size:12px;color:var(--muted);margin-top:3px;">{{ $flight['dep_id'] ?? '' }}</div>
                </div>
                <div style="flex:1;text-align:center;padding:0 8px;">
                    <div style="font-size:11px;color:var(--muted);margin-bottom:5px;">{{ $durStr }}</div>
                    <div style="position:relative;height:1px;background:var(--border);">
                        <span style="position:absolute;left:50%;top:50%;transform:translate(-50%,-50%);background:#fff;padding:0 4px;">
                            <i class="fa-solid fa-plane" style="font-size:13px;color:#7B3F00;"></i>
                        </span>
                    </div>
                    <div style="font-size:11px;color:var(--muted);margin-top:5px;">Nonstop</div>
                </div>
                <div style="text-align:left;min-width:72px;">
                    <div style="font-size:22px;font-weight:800;color:var(--dark);line-height:1;">{{ $fmtTime($arr) }}</div>
                    <div style="font-size:12px;color:var(--muted);margin-top:3px;">{{ $flight['arr_id'] ?? '' }}</div>
                </div>
                <div style="margin-left:auto;text-align:right;flex-shrink:0;">
                    <div style="font-size:22px;font-weight:800;color:#7B3F00;line-height:1;">PHP {{ number_format($flight['price'] ?? 0) }}</div>
                    <div style="font-size:11px;color:var(--muted);margin-top:3px;margin-bottom:12px;">{{ $flight['type'] ?? 'One-way' }}</div>
                    <button wire:click="selectMcFlight({{ $idx }})" wire:loading.attr="disabled" wire:target="selectMcFlight({{ $idx }})"
                            style="background:#5C2D0A;color:#fff;border:none;border-radius:10px;padding:11px 24px;font-size:14px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:8px;"
                            onmouseenter="this.style.background='#4A2408'"
                            onmouseleave="this.style.background='#5C2D0A'">
                        <span wire:loading.remove wire:target="selectMcFlight({{ $idx }})">Select <i class="fa-solid fa-chevron-right" style="font-size:11px;"></i></span>
                        <span wire:loading wire:target="selectMcFlight({{ $idx }})"><i class="fa-solid fa-spinner fa-spin"></i></span>
                    </button>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    @else
    {{-- Leg 1 Flight cards --}}
    <div id="flight-list" style="display:flex;flex-direction:column;gap:12px;">
        @foreach ($flightResults as $idx => $flight)
        @php
            $dur    = $flight['duration'] ?? 0;
            $durStr = $dur ? (floor($dur/60).'h '.($dur%60).'m') : 'Nonstop';
            $dep    = $flight['depart'] ?? '';
            $arr    = $flight['arrive'] ?? '';
            // Format "2026-07-22 03:50" → "3:50 AM"
            $fmtTime = fn($t) => $t ? date('g:i A', strtotime($t)) : '';
        @endphp
        <div class="flight-card" data-price="{{ $flight['price'] ?? 0 }}"
             style="background:#fff;border:1.5px solid var(--border);border-radius:14px;overflow:hidden;transition:box-shadow .15s;"
             onmouseenter="this.style.boxShadow='0 4px 20px rgba(0,0,0,0.08)'"
             onmouseleave="this.style.boxShadow='none'">
            {{-- Baggage strip --}}
            @if(!empty($flight['bags']))
            <div style="padding:7px 20px;font-size:11px;font-weight:600;color:#7B5C3A;display:flex;align-items:center;gap:6px;border-bottom:1px solid var(--border);">
                <i class="fa-solid fa-suitcase" style="font-size:10px;"></i> {{ $flight['bags'] }}
            </div>
            @endif
            <div style="padding:18px 24px;display:flex;align-items:center;gap:20px;">
                {{-- Logo + airline --}}
                <div style="width:100px;flex-shrink:0;">
                    @if(!empty($flight['logo']))
                    <img src="{{ $flight['logo'] }}" alt="{{ $flight['airline'] }}" style="height:28px;object-fit:contain;max-width:90px;display:block;margin-bottom:6px;">
                    @endif
                    <div style="font-size:12px;font-weight:700;color:var(--dark);line-height:1.3;">{{ $flight['airline'] ?? '' }}</div>
                    <div style="font-size:11px;color:var(--muted);">{{ $flight['number'] ?? '' }}</div>
                </div>
                {{-- Depart --}}
                <div style="text-align:left;min-width:72px;">
                    <div style="font-size:22px;font-weight:800;color:var(--dark);line-height:1;">{{ $fmtTime($dep) }}</div>
                    <div style="font-size:12px;color:var(--muted);margin-top:3px;">{{ $flight['dep_id'] ?? '' }}</div>
                </div>
                {{-- Duration --}}
                <div style="flex:1;text-align:center;padding:0 8px;">
                    <div style="font-size:11px;color:var(--muted);margin-bottom:5px;">{{ $durStr }}</div>
                    <div style="position:relative;height:1px;background:var(--border);">
                        <span style="position:absolute;left:50%;top:50%;transform:translate(-50%,-50%);background:#fff;padding:0 4px;">
                            <i class="fa-solid fa-plane" style="font-size:13px;color:#7B3F00;"></i>
                        </span>
                    </div>
                    <div style="font-size:11px;color:var(--muted);margin-top:5px;">Nonstop</div>
                </div>
                {{-- Arrive --}}
                <div style="text-align:left;min-width:72px;">
                    <div style="font-size:22px;font-weight:800;color:var(--dark);line-height:1;">{{ $fmtTime($arr) }}</div>
                    <div style="font-size:12px;color:var(--muted);margin-top:3px;">{{ $flight['arr_id'] ?? '' }}</div>
                </div>
                {{-- Price + Select --}}
                <div style="margin-left:auto;text-align:right;flex-shrink:0;">
                    <div style="font-size:22px;font-weight:800;color:#7B3F00;line-height:1;">PHP {{ number_format($flight['price'] ?? 0) }}</div>
                    <div style="font-size:11px;color:var(--muted);margin-top:3px;margin-bottom:12px;">{{ $flight['type'] ?? 'One-way' }}</div>
                    <button wire:click="selectFlight({{ $idx }})" wire:loading.attr="disabled" wire:target="selectFlight({{ $idx }})"
                            style="background:#5C2D0A;color:#fff;border:none;border-radius:10px;padding:11px 24px;font-size:14px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:8px;"
                            onmouseenter="this.style.background='#4A2408'"
                            onmouseleave="this.style.background='#5C2D0A'">
                        <span wire:loading.remove wire:target="selectFlight({{ $idx }})">Select <i class="fa-solid fa-chevron-right" style="font-size:11px;"></i></span>
                        <span wire:loading wire:target="selectFlight({{ $idx }})"><i class="fa-solid fa-spinner fa-spin"></i></span>
                    </button>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif

</div>

@script
<script>
window.pytFlight = function() {
    const cities = @json($allCities2);
    const months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
    const now = new Date();

    return {
        activeDrop2: '',
        activeCal2: '',
        priceOpen: false,
        priceDir: 'asc',
        tripType: @json($flightTripType),
        fromLabel: '', toLabel: '', toCode: '', mcCode: '',
        fromSearch2: '', toSearch2: '',
        startLabel2: '', endLabel2: '',
        startVal2: @json($startDate ?? ''),
        endVal2:   @json($endDate   ?? ''),
        sY: now.getFullYear(), sM: now.getMonth()+1,
        eY: now.getFullYear(), eM: now.getMonth()+1,
        mcLabel: '', mcSearch: '',
        mcStartLabel: '', mcStartVal: '',
        mcEndLabel: '',   mcEndVal: '',
        mcY: now.getFullYear(), mcM: now.getMonth()+1,
        mc2Y: now.getFullYear(), mc2M: now.getMonth()+1,

        init() {},

        toggleDrop2(w) { this.activeDrop2 = this.activeDrop2===w?'':w; this.activeCal2=''; },
        toggleCal2(w)  { this.activeCal2 = this.activeCal2===w?'':w; this.activeDrop2=''; },

        filteredCities2(which, grp) {
            const q = (which==='from'?this.fromSearch2:which==='to'?this.toSearch2:this.mcSearch).toLowerCase();
            return cities.filter(c=>c.group===grp&&(!q||c.name.toLowerCase().includes(q)||c.code.toLowerCase().includes(q)));
        },

        selectCity2(which, c) {
            const label = c.name+' ('+c.code+')';
            if (which==='from')      { this.fromLabel=label; $wire.set('manualFrom',c.name); }
            else if (which==='to')   { this.toLabel=label; this.toCode=c.code; $wire.set('manualTo',c.name); }
            else if (which==='mc')   { this.mcLabel=label; this.mcCode=c.code; $wire.set('mcTo',c.name); }
            this.activeDrop2='';
        },

        fmt2(y,m,d) { return y+'-'+String(m).padStart(2,'0')+'-'+String(d).padStart(2,'0'); },

        pickDateAndSwitch(which, d) {
            this.pickDate2(which, d);
            if (which === 'start') { this.eY = this.sY; this.eM = this.sM; this.activeCal2 = 'end'; }
        },

        pickDate2(which, d) {
            let y, m;
            if      (which==='start')    { y=this.sY;   m=this.sM; }
            else if (which==='end')      { y=this.eY;   m=this.eM; }
            else if (which==='mc-start') { y=this.mcY;  m=this.mcM; }
            else                         { y=this.mc2Y; m=this.mc2M; }
            const val   = this.fmt2(y,m,d);
            const label = months[m-1].slice(0,3)+' '+String(d).padStart(2,'0')+', '+y;
            if      (which==='start')    { this.startVal2=val;   this.startLabel2=label;   $wire.set('startDate',val); }
            else if (which==='end')      { this.endVal2=val;     this.endLabel2=label;     $wire.set('endDate',val);   }
            else if (which==='mc-start') { this.mcStartVal=val;  this.mcStartLabel=label; $wire.set('mcStartDate',val); }
            else                         { this.mcEndVal=val;    this.mcEndLabel=label;   $wire.set('mcEndDate',val); }
            this.activeCal2='';
        },

        prevMonth2(w) {
            if      (w==='start')  { this.sM--;   if(this.sM<1)   {this.sM=12;  this.sY--; } }
            else if (w==='end')    { this.eM--;   if(this.eM<1)   {this.eM=12;  this.eY--; } }
            else if (w==='mc')     { this.mcM--;  if(this.mcM<1)  {this.mcM=12; this.mcY--;} }
            else                   { this.mc2M--; if(this.mc2M<1) {this.mc2M=12;this.mc2Y--;} }
        },
        nextMonth2(w) {
            if      (w==='start')  { this.sM++;   if(this.sM>12)   {this.sM=1;  this.sY++; } }
            else if (w==='end')    { this.eM++;   if(this.eM>12)   {this.eM=1;  this.eY++; } }
            else if (w==='mc')     { this.mcM++;  if(this.mcM>12)  {this.mcM=1; this.mcY++;} }
            else                   { this.mc2M++; if(this.mc2M>12) {this.mc2M=1;this.mc2Y++;} }
        },

        monthName2(y,m) { return months[m-1]; },

        calCells2(y,m) {
            const first = new Date(y,m-1,1).getDay();
            const days  = new Date(y,m,0).getDate();
            const todayStr = now.getFullYear()+'-'+String(now.getMonth()+1).padStart(2,'0')+'-'+String(now.getDate()).padStart(2,'0');
            const cells=[];
            for(let i=0;i<first;i++) cells.push({d:null,key:'e'+i,past:false});
            for(let d=1;d<=days;d++) { const ds=this.fmt2(y,m,d); cells.push({d,key:'d'+d,past:ds<todayStr}); }
            return cells;
        },

        sortFlights() {
            const list = document.getElementById('flight-list');
            if (!list) return;
            const cards = Array.from(list.querySelectorAll('.flight-card'));
            cards.sort((a,b) => {
                const pa=parseInt(a.dataset.price)||0, pb=parseInt(b.dataset.price)||0;
                return this.priceDir==='asc' ? pa-pb : pb-pa;
            });
            cards.forEach(c=>list.appendChild(c));
        },

    };
};

window.sortAccommodations = function(dir) {
    const list = document.getElementById('acc-list');
    if (!list) return;
    const cards = Array.from(list.querySelectorAll('.acc-card'));
    cards.sort((a,b) => {
        const pa=parseInt(a.dataset.price)||0, pb=parseInt(b.dataset.price)||0;
        return dir==='asc' ? pa-pb : pb-pa;
    });
    cards.forEach(c=>list.appendChild(c));
};
</script>
@endscript
@endif

{{-- ═══════════════════════════════════════════════════════════════
     STEP 3 — Select Your Accommodation (manual)
═══════════════════════════════════════════════════════════════ --}}
@if ($planningMode === 'manual' && $step === 3)
<style>
[x-cloak]{display:none!important;}
.acc-card{background:#fff;border:1.5px solid var(--border);border-radius:14px;overflow:hidden;display:flex;align-items:stretch;transition:box-shadow .15s;}
.acc-card:hover{box-shadow:0 4px 20px rgba(0,0,0,.08);}
.acc-img{width:140px;flex-shrink:0;object-fit:cover;}
.acc-body{flex:1;padding:16px 20px;display:flex;flex-direction:column;justify-content:center;gap:4px;}
.acc-action{padding:16px 20px;display:flex;align-items:center;flex-shrink:0;}
</style>

<div x-data="{guestOpen:false,guests:'1 Adult',filterType:'hotel'}" style="padding-bottom:20px;">

    {{-- Page header --}}
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:20px;margin-bottom:24px;flex-wrap:wrap;">
        <div>
            <button wire:click="backToFlights"
                    style="display:inline-flex;align-items:center;gap:6px;background:none;border:none;color:#7B3F00;font-size:13px;font-weight:600;cursor:pointer;padding:0;margin-bottom:10px;">
                <i class="fa-solid fa-arrow-left" style="font-size:11px;"></i> Back to Planner
            </button>
            <h1 style="font-size:26px;font-weight:800;color:var(--dark);margin:0 0 6px;">Select Your Accommodation</h1>
            <p style="font-size:14px;color:var(--muted);margin:0;">Showing the best stays within 15 km of {{ $mcHotelStep ? $mcTo : $manualTo }}.</p>
        </div>
        {{-- Destination + Date badge --}}
        <div style="background:#F5F0EB;border:1.5px solid var(--border);border-radius:12px;padding:12px 20px;display:flex;align-items:center;gap:0;flex-shrink:0;">
            <div style="padding-right:20px;border-right:1px solid var(--border);">
                <div style="font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:var(--muted);margin-bottom:4px;">Destination</div>
                <div style="font-size:14px;font-weight:700;color:var(--dark);">{{ $mcHotelStep ? $mcTo : $manualTo }}</div>
            </div>
            <div style="padding-left:20px;">
                <div style="font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:var(--muted);margin-bottom:4px;">Date</div>
                @php $accPillSd = $mcHotelStep && $mcStartDate ? $mcStartDate : $startDate; $accPillEd = $mcHotelStep && $mcEndDate ? $mcEndDate : $endDate; @endphp
                <div style="font-size:13px;font-weight:600;color:var(--dark);">
                    @if($accPillSd && $accPillEd)
                        {{ \Carbon\Carbon::parse($accPillSd)->format('M j, Y') }} · {{ \Carbon\Carbon::parse($accPillEd)->format('M j, Y') }}
                    @elseif($accPillSd)
                        {{ \Carbon\Carbon::parse($accPillSd)->format('M j, Y') }}
                    @else —
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Search panel --}}
    <div style="background:#fff;border:1.5px solid var(--border);border-radius:16px;overflow:visible;box-shadow:0 2px 8px rgba(0,0,0,.04);margin-bottom:14px;width:100%;">
        <div style="display:flex;align-items:stretch;border-bottom:1px solid var(--border);min-width:0;">

            {{-- LOCATION --}}
            <div style="flex:1;min-width:0;padding:16px 20px;border-right:1px solid var(--border);">
                <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:var(--muted);margin-bottom:6px;">Location</div>
                <div style="display:flex;align-items:center;gap:8px;">
                    <i class="fa-solid fa-plane-arrival" style="color:#7B3F00;font-size:13px;flex-shrink:0;"></i>
                    <span style="font-size:14px;font-weight:600;color:var(--dark);">{{ $mcHotelStep ? $mcTo : $manualTo }}</span>
                </div>
            </div>

            {{-- GUESTS --}}
            <div style="flex:1;min-width:0;padding:16px 20px;border-right:1px solid var(--border);position:relative;">
                <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:var(--muted);margin-bottom:6px;">Guests</div>
                <div style="display:flex;align-items:center;gap:8px;cursor:pointer;" @click="guestOpen=!guestOpen">
                    <i class="fa-solid fa-user-group" style="color:#7B3F00;font-size:12px;flex-shrink:0;"></i>
                    <span style="font-size:14px;font-weight:600;color:var(--dark);flex:1;" x-text="guests"></span>
                    <i class="fa-solid fa-chevron-down" style="font-size:10px;color:var(--muted);flex-shrink:0;"></i>
                </div>
                <div x-show="guestOpen" @click.outside="guestOpen=false" x-cloak
                     style="position:absolute;top:calc(100% + 6px);left:0;background:#fff;border:1.5px solid var(--border);border-radius:12px;box-shadow:0 8px 24px rgba(0,0,0,.10);z-index:200;min-width:180px;overflow:hidden;">
                    @foreach(['1 Adult','2 Adults','3 Adults','4 Adults','2 Adults + 1 Child','2 Adults + 2 Children'] as $opt)
                    <button @click="guests='{{ $opt }}';guestOpen=false"
                            :style="guests==='{{ $opt }}'?'color:#7B3F00;font-weight:700;background:#FDF8F4;':''"
                            style="width:100%;text-align:left;padding:11px 16px;border:none;background:none;font-size:13px;cursor:pointer;">
                        {{ $opt }}
                    </button>
                    @endforeach
                </div>
            </div>

            {{-- TRAVEL DATES --}}
            <div style="flex:1;min-width:0;padding:16px 20px;">
                <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:var(--muted);margin-bottom:6px;">Travel Dates</div>
                <div style="display:flex;align-items:center;gap:8px;">
                    <i class="fa-regular fa-calendar" style="color:#7B3F00;font-size:13px;flex-shrink:0;"></i>
                    @php $accSd = $mcHotelStep && $mcStartDate ? $mcStartDate : $startDate; $accEd = $mcHotelStep && $mcEndDate ? $mcEndDate : $endDate; @endphp
                    <span style="font-size:14px;font-weight:600;color:var(--dark);">
                        @if($accSd && $accEd)
                            {{ \Carbon\Carbon::parse($accSd)->format('M j, Y') }} – {{ \Carbon\Carbon::parse($accEd)->format('M j, Y') }}
                        @elseif($accSd)
                            {{ \Carbon\Carbon::parse($accSd)->format('M j, Y') }}
                        @else Select dates
                        @endif
                    </span>
                </div>
            </div>
        </div>

        {{-- Search Stays button --}}
        <div style="display:flex;justify-content:flex-end;padding:14px 20px;">
            <button wire:click="searchAccommodations" wire:loading.attr="disabled" wire:target="searchAccommodations"
                    style="background:#5C2D0A;color:#fff;border:none;border-radius:10px;padding:11px 24px;font-size:13px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:8px;"
                    onmouseenter="this.style.background='#4A2408'"
                    onmouseleave="this.style.background='#5C2D0A'">
                <span wire:loading.remove wire:target="searchAccommodations"><i class="fa-solid fa-magnifying-glass"></i> Search Stays</span>
                <span wire:loading wire:target="searchAccommodations"><i class="fa-solid fa-spinner fa-spin"></i> Searching…</span>
            </button>
        </div>
    </div>

    {{-- Filter row --}}
    <div style="display:flex;align-items:center;gap:20px;margin-bottom:16px;flex-wrap:wrap;">
        <div style="position:relative;" x-data="{accPriceOpen:false,accPriceDir:'asc'}">
            <button @click="accPriceOpen=!accPriceOpen"
                    style="display:inline-flex;align-items:center;gap:8px;background:#2D1A0E;color:#fff;border:none;border-radius:24px;padding:9px 18px;font-size:13px;font-weight:600;cursor:pointer;">
                <span x-text="accPriceDir==='asc'?'Price: Low to High':'Price: High to Low'"></span>
                <i class="fa-solid fa-chevron-down" style="font-size:10px;transition:transform .15s;" :style="accPriceOpen?'transform:rotate(180deg)':''"></i>
            </button>
            <div x-show="accPriceOpen" @click.outside="accPriceOpen=false" x-cloak
                 style="position:absolute;top:calc(100% + 6px);left:0;background:#fff;border:1.5px solid var(--border);border-radius:12px;box-shadow:0 8px 24px rgba(0,0,0,0.10);z-index:50;min-width:180px;overflow:hidden;">
                <button @click="accPriceDir='asc';accPriceOpen=false;sortAccommodations('asc')"
                        :style="accPriceDir==='asc'?'color:#7B3F00;font-weight:700;background:#FDF8F4;':''"
                        style="width:100%;text-align:left;padding:12px 16px;border:none;background:none;font-size:13px;cursor:pointer;">
                    Price: Low to High
                </button>
                <div style="height:1px;background:var(--border);"></div>
                <button @click="accPriceDir='desc';accPriceOpen=false;sortAccommodations('desc')"
                        :style="accPriceDir==='desc'?'color:#7B3F00;font-weight:700;background:#FDF8F4;':''"
                        style="width:100%;text-align:left;padding:12px 16px;border:none;background:none;font-size:13px;cursor:pointer;">
                    Price: High to Low
                </button>
            </div>
        </div>
        @foreach(['hotel'=>'Hotel','apartment'=>'Apartment','inn'=>'Inn'] as $val => $label)
        <label style="display:inline-flex;align-items:center;gap:7px;cursor:pointer;font-size:13px;font-weight:500;color:var(--dark);">
            <input type="radio" name="acc_type" value="{{ $val }}"
                   x-model="filterType"
                   @change="$wire.set('hotelType', filterType)"
                   style="accent-color:#7B3F00;width:15px;height:15px;cursor:pointer;">
            {{ $label }}
        </label>
        @endforeach
    </div>

    {{-- Results --}}
    @if ($hotelLoading || ($mcHotelStep && $mcHotelLoading))
    <div style="text-align:center;padding:60px 20px;">
        <i class="fa-solid fa-spinner fa-spin" style="font-size:32px;color:#7B3F00;margin-bottom:16px;display:block;"></i>
        <p style="color:var(--muted);font-size:15px;">Searching for accommodations…</p>
    </div>
    @else
    @php
        $activeHotels   = $mcHotelStep ? $mcHotelResults : $hotelResults;
        $isMcHotel      = $mcHotelStep;
        $hasHotels      = !empty($activeHotels);
    @endphp

    @if (!$hasHotels)
    <div style="text-align:center;padding:60px 20px;">
        <i class="fa-solid fa-hotel" style="font-size:40px;color:var(--border);margin-bottom:16px;display:block;"></i>
        <p style="color:var(--muted);font-size:15px;">No stays found. Try searching above.</p>
    </div>
    @else
    <div id="acc-list" style="display:flex;flex-direction:column;gap:12px;">
            @foreach ($activeHotels as $idx => $hotel)
            <div class="acc-card" data-price="{{ $hotel['nightly'] ?? 0 }}">
                @if(!empty($hotel['image']))
                <img src="{{ $hotel['image'] }}" alt="{{ $hotel['name'] }}" class="acc-img">
                @else
                <div class="acc-img" style="background:#F5F0EB;display:flex;align-items:center;justify-content:center;">
                    <i class="fa-solid fa-hotel" style="font-size:28px;color:var(--muted);"></i>
                </div>
                @endif
                <div class="acc-body">
                    <div style="font-size:16px;font-weight:700;color:var(--dark);margin-bottom:2px;">{{ $hotel['name'] }}</div>
                    @if(!empty($hotel['dist']))
                    <div style="font-size:12px;color:var(--muted);display:flex;align-items:center;gap:5px;">
                        <i class="fa-solid fa-location-dot" style="font-size:10px;color:#7B3F00;"></i>
                        {{ $hotel['dist'] }} from city center
                    </div>
                    @endif
                    @if(!empty($hotel['stars']))
                    <div style="margin-top:4px;">
                        @for($s=0;$s<min($hotel['stars'],5);$s++)<i class="fa-solid fa-star" style="font-size:10px;color:#E8A87C;"></i>@endfor
                    </div>
                    @endif
                    <div style="margin-top:8px;">
                        <span style="font-size:18px;font-weight:800;color:var(--dark);">PHP {{ number_format($hotel['nightly'] ?? 0) }}</span>
                        <span style="font-size:12px;color:var(--muted);margin-left:4px;">per night</span>
                    </div>
                    @if(!empty($hotel['total']) && !empty($hotel['nights']))
                    <div style="font-size:12px;color:var(--muted);">PHP {{ number_format($hotel['total']) }} total · {{ $hotel['nights'] }} night{{ $hotel['nights'] > 1 ? 's' : '' }}</div>
                    @endif
                </div>
                <div class="acc-action">
                    @if($isMcHotel)
                    <button wire:click="selectMcAccommodation({{ $idx }})" wire:loading.attr="disabled" wire:target="selectMcAccommodation({{ $idx }})"
                            style="background:#5C2D0A;color:#fff;border:none;border-radius:8px;padding:10px 20px;font-size:13px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:6px;"
                            onmouseenter="this.style.background='#4A2408'" onmouseleave="this.style.background='#5C2D0A'">
                        <span wire:loading.remove wire:target="selectMcAccommodation({{ $idx }})">Select <i class="fa-solid fa-arrow-right" style="font-size:11px;"></i></span>
                        <span wire:loading wire:target="selectMcAccommodation({{ $idx }})"><i class="fa-solid fa-spinner fa-spin"></i></span>
                    </button>
                    @else
                    <button wire:click="selectAccommodation({{ $idx }})" wire:loading.attr="disabled" wire:target="selectAccommodation({{ $idx }})"
                            style="background:#5C2D0A;color:#fff;border:none;border-radius:8px;padding:10px 20px;font-size:13px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:6px;"
                            onmouseenter="this.style.background='#4A2408'" onmouseleave="this.style.background='#5C2D0A'">
                        <span wire:loading.remove wire:target="selectAccommodation({{ $idx }})">Select <i class="fa-solid fa-arrow-right" style="font-size:11px;"></i></span>
                        <span wire:loading wire:target="selectAccommodation({{ $idx }})"><i class="fa-solid fa-spinner fa-spin"></i></span>
                    </button>
                    @endif
                </div>
            </div>
            @endforeach
        </div>

    @endif
    @endif

</div>
@endif

{{-- ═══════════════════════════════════════════════════════════════
     STEP 4 — Select Food & Dining
═══════════════════════════════════════════════════════════════ --}}
@if ($planningMode === 'manual' && $step === 4)
<style>
.venue-card{background:#fff;border:1.5px solid var(--border);border-radius:14px;overflow:hidden;display:flex;align-items:stretch;box-shadow:0 2px 8px rgba(0,0,0,.04);transition:box-shadow .15s;}
.venue-card:hover{box-shadow:0 6px 20px rgba(0,0,0,.09);}
.venue-img{width:130px;flex-shrink:0;object-fit:cover;}
.venue-body{flex:1;padding:14px 18px;min-width:0;}
.venue-action{padding:16px 20px;display:flex;align-items:center;flex-shrink:0;}
</style>

<div style="padding-bottom:20px;">

    {{-- Header --}}
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:20px;margin-bottom:24px;flex-wrap:wrap;">
        <div>
            <button wire:click="$set('step', 3)"
                    style="display:inline-flex;align-items:center;gap:6px;background:none;border:none;color:#7B3F00;font-size:13px;font-weight:600;cursor:pointer;padding:0;margin-bottom:10px;">
                <i class="fa-solid fa-arrow-left" style="font-size:11px;"></i> Back to Planner
            </button>
            <h1 style="font-size:26px;font-weight:800;color:var(--dark);margin:0 0 6px;">Select Food &amp; Dining</h1>
            <p style="font-size:14px;color:var(--muted);margin:0;">Showing the best dining options within 15 km of {{ $mcVenueStep ? $mcTo : ($manualTo ?: $mcTo) }}.</p>
        </div>
        {{-- Destination + Date badge --}}
        <div style="background:#F5F0EB;border:1.5px solid var(--border);border-radius:12px;padding:12px 20px;display:flex;align-items:center;gap:0;flex-shrink:0;">
            <div style="padding-right:20px;border-right:1px solid var(--border);">
                <div style="font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:var(--muted);margin-bottom:4px;">Destination</div>
                <div style="font-size:14px;font-weight:700;color:var(--dark);">{{ $mcVenueStep ? $mcTo : ($manualTo ?: $mcTo) }}</div>
            </div>
            <div style="padding-left:20px;">
                <div style="font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:var(--muted);margin-bottom:4px;">Date</div>
                @php $vPillSd = $mcVenueStep && $mcStartDate ? $mcStartDate : $startDate; $vPillEd = $mcVenueStep && $mcEndDate ? $mcEndDate : $endDate; @endphp
                <div style="font-size:13px;font-weight:600;color:var(--dark);">
                    @if($vPillSd && $vPillEd)
                        {{ \Carbon\Carbon::parse($vPillSd)->format('M j, Y') }} – {{ \Carbon\Carbon::parse($vPillEd)->format('M j, Y') }}
                    @elseif($vPillSd)
                        {{ \Carbon\Carbon::parse($vPillSd)->format('M j, Y') }}
                    @else —
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Search bar --}}
    <div style="background:#fff;border:1.5px solid var(--border);border-radius:16px;overflow:visible;box-shadow:0 2px 8px rgba(0,0,0,.04);margin-bottom:16px;width:100%;">
        <div style="display:flex;align-items:stretch;border-bottom:1px solid var(--border);min-width:0;">
            {{-- Location --}}
            <div style="flex:1;min-width:0;padding:16px 20px;border-right:1px solid var(--border);">
                <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:var(--muted);margin-bottom:6px;">Location</div>
                <div style="display:flex;align-items:center;gap:8px;">
                    <i class="fa-solid fa-location-dot" style="color:#7B3F00;font-size:13px;flex-shrink:0;"></i>
                    <span style="font-size:14px;font-weight:600;color:var(--dark);">{{ $mcVenueStep ? $mcTo : ($manualTo ?: $mcTo) }}</span>
                </div>
            </div>
            {{-- Category --}}
            <div style="flex:1;min-width:0;padding:16px 20px;border-right:1px solid var(--border);">
                <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:var(--muted);margin-bottom:6px;">Category</div>
                <div style="display:flex;align-items:center;gap:8px;">
                    <i class="fa-solid fa-utensils" style="color:#7B3F00;font-size:12px;flex-shrink:0;"></i>
                    <select wire:model="venueCategory" style="border:none;background:transparent;font-size:14px;font-weight:600;color:var(--dark);outline:none;cursor:pointer;width:100%;">
                        @foreach(['All Cuisines','Filipino','Asian','International','Seafood','BBQ','Fast Food','Cafe','Bakery'] as $cat)
                        <option value="{{ $cat }}">{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            {{-- Travel dates --}}
            <div style="flex:1;min-width:0;padding:16px 20px;">
                <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:var(--muted);margin-bottom:6px;">Travel Dates</div>
                <div style="display:flex;align-items:center;gap:8px;">
                    <i class="fa-solid fa-calendar" style="color:#7B3F00;font-size:12px;flex-shrink:0;"></i>
                    @php
                        $venueSd = $mcVenueStep && $mcStartDate ? $mcStartDate : $startDate;
                        $venueEd = $mcVenueStep && $mcEndDate   ? $mcEndDate   : $endDate;
                    @endphp
                    <span style="font-size:13px;font-weight:600;color:var(--dark);">
                        @if($venueSd && $venueEd)
                            {{ \Carbon\Carbon::parse($venueSd)->format('M j, Y') }} – {{ \Carbon\Carbon::parse($venueEd)->format('M j, Y') }}
                        @elseif($venueSd)
                            {{ \Carbon\Carbon::parse($venueSd)->format('M j, Y') }}
                        @else Select dates
                        @endif
                    </span>
                </div>
            </div>
        </div>
        <div style="display:flex;justify-content:flex-end;padding:14px 20px;">
            <button wire:click="searchVenues" wire:loading.attr="disabled" wire:target="searchVenues"
                    style="background:#3B1A08;color:#fff;border:none;border-radius:10px;padding:12px 28px;font-size:13px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:8px;"
                    onmouseenter="this.style.background='#2A1205'" onmouseleave="this.style.background='#3B1A08'">
                <span wire:loading.remove wire:target="searchVenues"><i class="fa-solid fa-magnifying-glass" style="font-size:12px;"></i> Search Venues</span>
                <span wire:loading wire:target="searchVenues"><i class="fa-solid fa-spinner fa-spin"></i> Searching…</span>
            </button>
        </div>
    </div>


    {{-- Results --}}
    @if(!$mcVenueStep)
    {{-- Leg 1 venue list --}}
    @if($venueLoading)
    <div style="text-align:center;padding:60px 20px;">
        <i class="fa-solid fa-spinner fa-spin" style="font-size:32px;color:#7B3F00;margin-bottom:16px;display:block;"></i>
        <p style="color:var(--muted);font-size:15px;">Searching for dining options…</p>
    </div>
    @elseif(empty($venueResults))
    <div style="text-align:center;padding:60px 20px;">
        <i class="fa-solid fa-utensils" style="font-size:40px;color:var(--border);margin-bottom:16px;display:block;"></i>
        <p style="color:var(--muted);font-size:15px;">No venues found. Try searching above.</p>
    </div>
    @else
    <div id="venue-list" style="display:flex;flex-direction:column;gap:12px;">
        @foreach($venueResults as $vi => $venue)
        <div class="venue-card">
            @if(!empty($venue['image']))
            <img src="{{ $venue['image'] }}" alt="{{ $venue['name'] }}" class="venue-img">
            @else
            <div class="venue-img" style="background:#F5F0EB;display:flex;align-items:center;justify-content:center;">
                <i class="fa-solid fa-utensils" style="font-size:28px;color:var(--muted);"></i>
            </div>
            @endif
            <div class="venue-body">
                <div style="font-size:16px;font-weight:700;color:var(--dark);margin-bottom:3px;">{{ $venue['name'] }}</div>
                <div style="font-size:12px;color:var(--muted);display:flex;align-items:center;gap:12px;margin-bottom:4px;flex-wrap:wrap;">
                    <span><i class="fa-solid fa-tag" style="font-size:10px;color:#7B3F00;margin-right:3px;"></i>{{ $venue['cuisine'] }}</span>
                    <span><i class="fa-solid fa-location-dot" style="font-size:10px;color:#7B3F00;margin-right:3px;"></i>{{ $venue['city'] }}</span>
                </div>
                @if(!empty($venue['rating']))
                <div style="display:flex;align-items:center;gap:6px;margin-bottom:6px;">
                    <i class="fa-solid fa-star" style="font-size:12px;color:#E8A87C;"></i>
                    <span style="font-size:13px;font-weight:700;color:var(--dark);">{{ $venue['rating'] }}</span>
                    @if(!empty($venue['reviews']))<span style="font-size:11px;color:var(--muted);">({{ number_format($venue['reviews']) }})</span>@endif
                </div>
                @endif
                <div style="font-size:13px;color:#7B3F00;font-weight:600;">
                    ₱{{ number_format($venue['priceMin']) }} – ₱{{ number_format($venue['priceMax']) }}
                    <span style="font-size:11px;color:var(--muted);font-weight:400;"> Average price per person</span>
                </div>
            </div>
            <div class="venue-action">
                <button wire:click="selectVenue({{ $vi }})" wire:loading.attr="disabled" wire:target="selectVenue({{ $vi }})"
                        style="background:#5C2D0A;color:#fff;border:none;border-radius:8px;padding:10px 20px;font-size:13px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:6px;white-space:nowrap;"
                        onmouseenter="this.style.background='#4A2408'" onmouseleave="this.style.background='#5C2D0A'">
                    <span wire:loading.remove wire:target="selectVenue({{ $vi }})">Select <i class="fa-solid fa-chevron-right" style="font-size:10px;"></i></span>
                    <span wire:loading wire:target="selectVenue({{ $vi }})"><i class="fa-solid fa-spinner fa-spin"></i></span>
                </button>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    @else
    {{-- Leg 2 venue list (multi-city second destination) --}}
    @if($mcVenueLoading)
    <div style="text-align:center;padding:60px 20px;">
        <i class="fa-solid fa-spinner fa-spin" style="font-size:32px;color:#7B3F00;margin-bottom:16px;display:block;"></i>
        <p style="color:var(--muted);font-size:15px;">Searching for dining options in {{ $mcTo }}…</p>
    </div>
    @elseif(empty($mcVenueResults))
    <div style="text-align:center;padding:60px 20px;">
        <i class="fa-solid fa-utensils" style="font-size:40px;color:var(--border);margin-bottom:16px;display:block;"></i>
        <p style="color:var(--muted);font-size:15px;">No venues found for {{ $mcTo }}.</p>
    </div>
    @else
    <div id="mc-venue-list" style="display:flex;flex-direction:column;gap:12px;">
        @foreach($mcVenueResults as $vi => $venue)
        <div class="venue-card">
            @if(!empty($venue['image']))
            <img src="{{ $venue['image'] }}" alt="{{ $venue['name'] }}" class="venue-img">
            @else
            <div class="venue-img" style="background:#F5F0EB;display:flex;align-items:center;justify-content:center;">
                <i class="fa-solid fa-utensils" style="font-size:28px;color:var(--muted);"></i>
            </div>
            @endif
            <div class="venue-body">
                <div style="font-size:16px;font-weight:700;color:var(--dark);margin-bottom:3px;">{{ $venue['name'] }}</div>
                <div style="font-size:12px;color:var(--muted);display:flex;align-items:center;gap:12px;margin-bottom:4px;flex-wrap:wrap;">
                    <span><i class="fa-solid fa-tag" style="font-size:10px;color:#7B3F00;margin-right:3px;"></i>{{ $venue['cuisine'] }}</span>
                    <span><i class="fa-solid fa-location-dot" style="font-size:10px;color:#7B3F00;margin-right:3px;"></i>{{ $venue['city'] }}</span>
                </div>
                @if(!empty($venue['rating']))
                <div style="display:flex;align-items:center;gap:6px;margin-bottom:6px;">
                    <i class="fa-solid fa-star" style="font-size:12px;color:#E8A87C;"></i>
                    <span style="font-size:13px;font-weight:700;color:var(--dark);">{{ $venue['rating'] }}</span>
                    @if(!empty($venue['reviews']))<span style="font-size:11px;color:var(--muted);">({{ number_format($venue['reviews']) }})</span>@endif
                </div>
                @endif
                <div style="font-size:13px;color:#7B3F00;font-weight:600;">
                    ₱{{ number_format($venue['priceMin']) }} – ₱{{ number_format($venue['priceMax']) }}
                    <span style="font-size:11px;color:var(--muted);font-weight:400;"> Average price per person</span>
                </div>
            </div>
            <div class="venue-action">
                <button wire:click="selectVenue({{ $vi }})" wire:loading.attr="disabled" wire:target="selectVenue({{ $vi }})"
                        style="background:#5C2D0A;color:#fff;border:none;border-radius:8px;padding:10px 20px;font-size:13px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:6px;white-space:nowrap;"
                        onmouseenter="this.style.background='#4A2408'" onmouseleave="this.style.background='#5C2D0A'">
                    <span wire:loading.remove wire:target="selectVenue({{ $vi }})">Select <i class="fa-solid fa-chevron-right" style="font-size:10px;"></i></span>
                    <span wire:loading wire:target="selectVenue({{ $vi }})"><i class="fa-solid fa-spinner fa-spin"></i></span>
                </button>
            </div>
        </div>
        @endforeach
    </div>
    @endif
    @endif

</div>

@endif

{{-- ═══════════════════════════════════════════════════════════════
     STEP 5 — Select Attractions
═══════════════════════════════════════════════════════════════ --}}
@if ($planningMode === 'manual' && $step === 5)
<style>
.attr-card{background:#fff;border:1.5px solid var(--border);border-radius:14px;overflow:hidden;display:flex;align-items:stretch;box-shadow:0 2px 8px rgba(0,0,0,.04);transition:box-shadow .15s;min-height:110px;max-height:130px;}
.attr-card:hover{box-shadow:0 6px 20px rgba(0,0,0,.09);}
.attr-img{width:130px;height:100%;flex-shrink:0;object-fit:cover;display:block;}
.attr-body{flex:1;padding:12px 16px;min-width:0;overflow:hidden;display:flex;flex-direction:column;justify-content:center;}
.attr-action{padding:16px 20px;display:flex;align-items:center;flex-shrink:0;}
</style>

<div style="padding-bottom:20px;">

    {{-- Header --}}
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:20px;margin-bottom:24px;flex-wrap:wrap;">
        <div>
            <button wire:click="$set('step', 4)"
                    style="display:inline-flex;align-items:center;gap:6px;background:none;border:none;color:#7B3F00;font-size:13px;font-weight:600;cursor:pointer;padding:0;margin-bottom:10px;">
                <i class="fa-solid fa-arrow-left" style="font-size:11px;"></i> Back to Planner
            </button>
            <h1 style="font-size:26px;font-weight:800;color:var(--dark);margin:0 0 6px;">Select Attractions</h1>
            @php $attrDest = $mcAttractionStep ? $mcTo : ($manualTo ?: $mcTo); @endphp
            <p style="font-size:14px;color:var(--muted);margin:0;">Showing the best attractions in {{ $attrDest }}.</p>
        </div>
        <div style="background:#F5F0EB;border:1.5px solid var(--border);border-radius:12px;padding:12px 20px;display:flex;align-items:center;gap:0;flex-shrink:0;">
            <div style="padding-right:20px;border-right:1px solid var(--border);">
                <div style="font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:var(--muted);margin-bottom:4px;">Destination</div>
                <div style="font-size:14px;font-weight:700;color:var(--dark);">{{ $attrDest }}</div>
            </div>
            <div style="padding-left:20px;">
                <div style="font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:var(--muted);margin-bottom:4px;">Date</div>
                @php $attrSd = $mcAttractionStep && $mcStartDate ? $mcStartDate : $startDate; $attrEd = $mcAttractionStep && $mcEndDate ? $mcEndDate : $endDate; @endphp
                <div style="font-size:13px;font-weight:600;color:var(--dark);">
                    @if($attrSd && $attrEd)
                        {{ \Carbon\Carbon::parse($attrSd)->format('M j, Y') }} – {{ \Carbon\Carbon::parse($attrEd)->format('M j, Y') }}
                    @elseif($attrSd)
                        {{ \Carbon\Carbon::parse($attrSd)->format('M j, Y') }}
                    @else —
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Search bar --}}
    <div style="background:#fff;border:1.5px solid var(--border);border-radius:16px;overflow:visible;box-shadow:0 2px 8px rgba(0,0,0,.04);margin-bottom:16px;">
        <div style="display:flex;align-items:stretch;border-bottom:1px solid var(--border);">
            <div style="flex:1;min-width:0;padding:16px 20px;border-right:1px solid var(--border);">
                <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:var(--muted);margin-bottom:6px;">Location</div>
                <div style="display:flex;align-items:center;gap:8px;">
                    <i class="fa-solid fa-location-dot" style="color:#7B3F00;font-size:13px;flex-shrink:0;"></i>
                    <span style="font-size:14px;font-weight:600;color:var(--dark);">{{ $attrDest }}</span>
                </div>
            </div>
            <div style="flex:1;min-width:0;padding:16px 20px;border-right:1px solid var(--border);">
                <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:var(--muted);margin-bottom:6px;">Type</div>
                <div style="display:flex;align-items:center;gap:8px;">
                    <i class="fa-solid fa-binoculars" style="color:#7B3F00;font-size:12px;flex-shrink:0;"></i>
                    <select wire:model="attractionType" style="border:none;background:transparent;font-size:14px;font-weight:600;color:var(--dark);outline:none;cursor:pointer;width:100%;">
                        @foreach(['All Attractions','Religious','Historical','Nature','Theme Park','Beach','Museum','Shopping'] as $t)
                        <option value="{{ $t }}">{{ $t }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div style="flex:1;min-width:0;padding:16px 20px;">
                <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:var(--muted);margin-bottom:6px;">Travel Dates</div>
                <div style="display:flex;align-items:center;gap:8px;">
                    <i class="fa-solid fa-calendar" style="color:#7B3F00;font-size:12px;flex-shrink:0;"></i>
                    <span style="font-size:13px;font-weight:600;color:var(--dark);">
                        @if($startDate && $endDate)
                            {{ \Carbon\Carbon::parse($startDate)->format('M j, Y') }} – {{ \Carbon\Carbon::parse($endDate)->format('M j, Y') }}
                        @elseif($startDate)
                            {{ \Carbon\Carbon::parse($startDate)->format('M j, Y') }}
                        @else Select dates
                        @endif
                    </span>
                </div>
            </div>
        </div>
        <div style="display:flex;justify-content:flex-end;padding:14px 20px;">
            <button wire:click="searchAttractionsList" wire:loading.attr="disabled" wire:target="searchAttractionsList"
                    style="background:#3B1A08;color:#fff;border:none;border-radius:10px;padding:12px 28px;font-size:13px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:8px;"
                    onmouseenter="this.style.background='#2A1205'" onmouseleave="this.style.background='#3B1A08'">
                <span wire:loading.remove wire:target="searchAttractionsList"><i class="fa-solid fa-magnifying-glass" style="font-size:12px;"></i> Search Attractions</span>
                <span wire:loading wire:target="searchAttractionsList"><i class="fa-solid fa-spinner fa-spin"></i> Searching…</span>
            </button>
        </div>
    </div>

    {{-- Results --}}
    @php $activeAttractions = $mcAttractionStep ? $mcAttractionResults : $attractionResults; @endphp
    @if($attractionLoading || ($mcAttractionStep && $mcAttractionLoading))
    <div style="text-align:center;padding:60px 20px;">
        <i class="fa-solid fa-spinner fa-spin" style="font-size:32px;color:#7B3F00;margin-bottom:16px;display:block;"></i>
        <p style="color:var(--muted);font-size:15px;">Searching for attractions…</p>
    </div>
    @elseif(empty($activeAttractions))
    <div style="text-align:center;padding:60px 20px;">
        <i class="fa-solid fa-binoculars" style="font-size:40px;color:var(--border);margin-bottom:16px;display:block;"></i>
        <p style="color:var(--muted);font-size:15px;">No attractions found. Try searching above.</p>
    </div>
    @else
    <div style="display:flex;flex-direction:column;gap:12px;">
        @foreach($activeAttractions as $ai => $attr)
        <div class="attr-card">
            @if(!empty($attr['image']))
            <img src="{{ $attr['image'] }}" alt="{{ $attr['name'] }}" class="attr-img">
            @else
            <div class="attr-img" style="background:#F5F0EB;display:flex;align-items:center;justify-content:center;">
                <i class="fa-solid fa-landmark" style="font-size:28px;color:var(--muted);"></i>
            </div>
            @endif
            <div class="attr-body">
                <div style="font-size:16px;font-weight:700;color:var(--dark);margin-bottom:3px;">{{ $attr['name'] }}</div>
                @php
                    $attrLocation = $attr['address'] ?? $attr['city'] ?? null;
                    $attrPriceRaw = (int) preg_replace('/[^\d]/', '', $attr['price'] ?? '0');
                @endphp
                @if($attrLocation)
                <div style="font-size:11px;color:var(--muted);display:flex;align-items:center;gap:4px;margin-bottom:3px;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;">
                    <i class="fa-solid fa-location-dot" style="font-size:10px;color:#7B3F00;flex-shrink:0;"></i>
                    <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $attrLocation }}</span>
                </div>
                @endif
                @if(!empty($attr['rating']))
                <div style="display:flex;align-items:center;gap:6px;margin-bottom:6px;">
                    <i class="fa-solid fa-star" style="font-size:12px;color:#E8A87C;"></i>
                    <span style="font-size:13px;font-weight:700;color:var(--dark);">{{ $attr['rating'] }}</span>
                    @if(!empty($attr['reviews']))<span style="font-size:11px;color:var(--muted);">({{ number_format($attr['reviews']) }})</span>@endif
                </div>
                @endif
                <div>
                    @if($attr['isFree'])
                    <span style="font-size:14px;font-weight:700;color:#16A34A;">FREE</span>
                    <span style="font-size:11px;color:var(--muted);margin-left:4px;">Entrance Fee</span>
                    @elseif($attrPriceRaw > 0)
                    <span style="font-size:14px;font-weight:700;color:var(--dark);">₱{{ number_format($attrPriceRaw) }}</span>
                    <span style="font-size:11px;color:var(--muted);margin-left:4px;">Entrance Fee</span>
                    @else
                    <span style="font-size:12px;color:var(--muted);">Entrance fee may apply</span>
                    @endif
                </div>
            </div>
            <div class="attr-action">
                <button wire:click="selectAttraction({{ $ai }})" wire:loading.attr="disabled" wire:target="selectAttraction({{ $ai }})"
                        style="background:#5C2D0A;color:#fff;border:none;border-radius:8px;padding:10px 20px;font-size:13px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:6px;white-space:nowrap;"
                        onmouseenter="this.style.background='#4A2408'" onmouseleave="this.style.background='#5C2D0A'">
                    <span wire:loading.remove wire:target="selectAttraction({{ $ai }})">Select <i class="fa-solid fa-chevron-right" style="font-size:10px;"></i></span>
                    <span wire:loading wire:target="selectAttraction({{ $ai }})"><i class="fa-solid fa-spinner fa-spin"></i></span>
                </button>
            </div>
        </div>
        @endforeach
    </div>
    @endif

</div>
@endif

{{-- ═══════════════════════════════════════════════════════════════
     STEP 6 — Emergency Fund
═══════════════════════════════════════════════════════════════ --}}
@if ($planningMode === 'manual' && $step === 6)
<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:70vh;padding:40px 24px;text-align:center;">

    <h1 style="font-size:28px;font-weight:800;color:var(--dark);margin:0 0 10px;">Emergency Fund</h1>
    <p style="font-size:14px;color:var(--muted);margin:0 0 36px;max-width:400px;">Set aside a safety net for unexpected expenses during your journey.</p>

    <div style="background:#fff;border:1.5px solid var(--border);border-radius:16px;padding:32px;width:100%;max-width:480px;box-shadow:0 2px 12px rgba(0,0,0,0.06);">
        <div style="text-align:left;margin-bottom:20px;">
            <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:var(--muted);margin-bottom:10px;">Your Allocated Emergency Fund</div>
            <div x-data="{
                    display: '',
                    init() {
                        if ($wire.emergency) this.display = Number($wire.emergency).toLocaleString('en-PH');
                    },
                    format(e) {
                        let raw = e.target.value.replace(/[^\d]/g, '');
                        this.display = raw ? Number(raw).toLocaleString('en-PH') : '';
                        $wire.set('emergency', raw ? Number(raw) : 0);
                    }
                 }"
                 style="display:flex;align-items:center;gap:10px;background:#F8F5F2;border:1.5px solid var(--border);border-radius:10px;padding:14px 16px;">
                <i class="fa-solid fa-shield-halved" style="color:#7B3F00;font-size:16px;flex-shrink:0;"></i>
                <input type="text" x-model="display" @input="format($event)" placeholder="Enter amount…"
                       style="border:none;background:transparent;font-size:15px;font-weight:400;color:var(--dark);outline:none;width:100%;appearance:none;-moz-appearance:textfield;"
                       autocomplete="off">
            </div>
            <style>
                input[placeholder="Enter amount…"]::placeholder { color: #B0A89E; font-weight:400; }
            </style>
        </div>

        <div style="display:flex;align-items:center;gap:8px;margin-bottom:28px;">
            <i class="fa-solid fa-circle-info" style="font-size:12px;color:var(--muted);flex-shrink:0;"></i>
            <span style="font-size:12px;color:var(--muted);">This amount is excluded from your daily budget.</span>
        </div>

        <div style="display:flex;align-items:center;justify-content:flex-end;gap:12px;">
            <button wire:click="saveDraft"
                    style="background:#fff;border:1.5px solid var(--border);color:var(--dark);border-radius:10px;padding:11px 24px;font-size:13px;font-weight:700;cursor:pointer;"
                    onmouseenter="this.style.background='#F5F0EB'" onmouseleave="this.style.background='#fff'">
                Save Draft
            </button>
            <button wire:click="confirmEmergencyFund" wire:loading.attr="disabled" wire:target="confirmEmergencyFund"
                    style="background:#5C2D0A;color:#fff;border:none;border-radius:10px;padding:11px 28px;font-size:13px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:8px;"
                    onmouseenter="this.style.background='#4A2408'" onmouseleave="this.style.background='#5C2D0A'">
                <span wire:loading.remove wire:target="confirmEmergencyFund">Confirm Amount</span>
                <span wire:loading wire:target="confirmEmergencyFund"><i class="fa-solid fa-spinner fa-spin"></i></span>
            </button>
        </div>
    </div>

</div>
@endif

{{-- ═══════════════════════════════════════════════════════════════
     STEP 7 — Generate Itinerary
═══════════════════════════════════════════════════════════════ --}}
@if ($planningMode === 'manual' && $step === 7)
@php
    $profile      = auth()->user()?->userProfile;
    $interests    = $profile?->interests    ?? [];
    $subInterests = $profile?->sub_interests ?? [];
    $allTags      = array_unique(array_merge($interests, $subInterests));

    $dest = $manualTo ?: $mcTo ?: 'Unknown';
    $route = $flightTripType === 'multi_city' && $mcTo
        ? trim($manualFrom) . ' to ' . trim($manualTo) . ' · ' . trim($mcTo)
        : trim($manualFrom) . ' to ' . trim($manualTo);

    $sd = $startDate ? \Carbon\Carbon::parse($startDate)->format('F j, Y') : '—';
    $ed = $endDate   ? \Carbon\Carbon::parse($endDate)->format('F j, Y')   : '—';

    $budMaxRaw = (int) preg_replace('/[^\d]/', '', $manualBudgetMax ?: $manualBudgetMin);
    $budMaxRaw = $budMaxRaw ?: 0;

    // First value = profile preferred budget (daily_budget from Profile Builder)
    $budMinRaw = (int) ($profile?->daily_budget ?? 0);
    if ($budMinRaw <= 0 || $budMinRaw >= $budMaxRaw) {
        $budMinRaw = (int) preg_replace('/[^\d]/', '', $manualBudgetMin);
    }

    $budMin = $budMinRaw ? number_format($budMinRaw) : '0';
    $budMax = $budMaxRaw ? number_format($budMaxRaw) : '0';
@endphp
<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:70vh;padding:40px 24px;">

    <div style="background:#fff;border:1.5px solid var(--border);border-radius:20px;padding:32px 28px;width:100%;max-width:480px;box-shadow:0 4px 20px rgba(0,0,0,0.07);">

        {{-- Header --}}
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:28px;">
            <div style="width:32px;height:32px;border-radius:8px;background:#F5F0EB;display:flex;align-items:center;justify-content:center;">
                <i class="fa-regular fa-calendar-days" style="color:#7B3F00;font-size:14px;"></i>
            </div>
            <span style="font-size:16px;font-weight:800;color:var(--dark);">Generate Itinerary</span>
        </div>

        {{-- Destination --}}
        <div style="display:flex;align-items:flex-start;gap:12px;margin-bottom:22px;">
            <div style="min-width:90px;display:flex;align-items:center;gap:6px;">
                <i class="fa-solid fa-location-dot" style="color:var(--muted);font-size:11px;"></i>
                <span style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:var(--muted);">Destination</span>
            </div>
            <div style="flex:1;">
                <div style="background:#F8F5F2;border:1.5px solid var(--border);border-radius:10px;padding:11px 14px;display:flex;align-items:center;justify-content:space-between;">
                    <span style="font-size:14px;font-weight:600;color:var(--dark);">{{ $route }}</span>
                    <i class="fa-solid fa-chevron-down" style="color:var(--muted);font-size:11px;"></i>
                </div>
            </div>
        </div>

        {{-- Travel Dates --}}
        <div style="display:flex;align-items:flex-start;gap:12px;margin-bottom:22px;">
            <div style="min-width:90px;display:flex;align-items:center;gap:6px;">
                <i class="fa-regular fa-calendar" style="color:var(--muted);font-size:11px;"></i>
                <span style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:var(--muted);">Travel Dates</span>
            </div>
            <div style="flex:1;">
                <div style="background:#F8F5F2;border:1.5px solid var(--border);border-radius:10px;padding:11px 14px;display:flex;align-items:center;justify-content:space-between;">
                    <span style="font-size:14px;font-weight:600;color:var(--dark);">{{ $sd }} – {{ $ed }}</span>
                    <i class="fa-regular fa-clock" style="color:#7B3F00;font-size:13px;"></i>
                </div>
            </div>
        </div>

        {{-- Budget Range --}}
        <div style="display:flex;align-items:flex-start;gap:12px;margin-bottom:22px;">
            <div style="min-width:90px;display:flex;align-items:center;gap:6px;">
                <i class="fa-solid fa-wallet" style="color:var(--muted);font-size:11px;"></i>
                <span style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:var(--muted);">Budget Range</span>
            </div>
            <div style="flex:1;">
                <div style="background:#F8F5F2;border:1.5px solid var(--border);border-radius:10px;padding:11px 14px;display:flex;align-items:center;justify-content:space-between;">
                    <span style="font-size:14px;font-weight:600;color:var(--dark);">{{ $budMin }} – {{ $budMax }}</span>
                    <i class="fa-solid fa-coins" style="color:#7B3F00;font-size:13px;"></i>
                </div>
            </div>
        </div>

        {{-- Selected Interests --}}
        <div style="display:flex;align-items:flex-start;gap:12px;margin-bottom:28px;">
            <div style="min-width:90px;display:flex;align-items:center;gap:6px;padding-top:4px;">
                <i class="fa-regular fa-heart" style="color:var(--muted);font-size:11px;"></i>
                <span style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:var(--muted);">Selected Interests</span>
            </div>
            <div style="flex:1;display:flex;flex-wrap:wrap;gap:6px;padding-top:2px;">
                @forelse($allTags as $tag)
                <span style="display:inline-flex;align-items:center;gap:5px;background:#F5F0EB;color:#5C2D0A;font-size:11px;font-weight:700;padding:5px 11px;border-radius:20px;">
                    <i class="fa-solid fa-tag" style="font-size:9px;"></i> {{ $tag }}
                </span>
                @empty
                <span style="font-size:13px;color:var(--muted);">No interests set.</span>
                @endforelse
            </div>
        </div>

        {{-- Generate button --}}
        <button wire:click="generateItinerary" wire:loading.attr="disabled"
                style="width:100%;display:flex;align-items:center;justify-content:center;gap:10px;background:#3B1A08;color:#fff;border:none;border-radius:12px;padding:14px 24px;font-size:14px;font-weight:700;cursor:pointer;letter-spacing:0.3px;"
                onmouseenter="this.style.background='#2D1206'" onmouseleave="this.style.background='#3B1A08'">
            <span wire:loading.remove wire:target="generateItinerary">
                Generate Itinerary <i class="fa-solid fa-wand-magic-sparkles" style="font-size:13px;"></i>
            </span>
            <span wire:loading wire:target="generateItinerary">
                <i class="fa-solid fa-spinner fa-spin"></i>
            </span>
        </button>

    </div>
</div>
@endif

{{-- ═══════════════════════════════════════════════════════════════
     STEP 8 — Itinerary Preview
═══════════════════════════════════════════════════════════════ --}}
@if ($planningMode === 'manual' && $step === 8)
@php
    $profile8      = auth()->user()?->userProfile;
    $interests8    = $profile8?->interests    ?? [];
    $subInterests8 = $profile8?->sub_interests ?? [];
    $allTags8      = array_unique(array_merge($interests8, $subInterests8));

    $route8  = trim($manualFrom) . ' to ' . trim($manualTo);
    if ($flightTripType === 'multi_city' && $mcTo) $route8 .= ' · ' . trim($mcTo);

    $sd8 = $startDate ? \Carbon\Carbon::parse($startDate)->format('M j, Y') : '—';
    $ed8 = $endDate   ? \Carbon\Carbon::parse($endDate)->format('M j, Y')   : '—';

    $budMax8      = (int) preg_replace('/[^\d]/', '', $manualBudgetMax ?: $manualBudgetMin);
    $profileMin8  = (int) ($profile8?->daily_budget ?? 0);
    $budMin8      = ($profileMin8 > 0 && $profileMin8 < $budMax8) ? $profileMin8 : (int) round($budMax8 * 0.7);
    $budLabel8    = $budMin8 ? ('₱' . number_format($budMin8) . ($budMax8 && $budMax8 !== $budMin8 ? ' – ₱' . number_format($budMax8) : '')) : '—';

    // Selections as activity-card entries (icon, type, title, sub, time, cost, isFree)
    // For round-trip flights, split cost evenly: half on arrival, half on departure
    $selCards = [];
    if ($selectedFlight) {
        $flightIsRT   = strtolower($selectedFlight['type'] ?? '') === 'round trip';
        $flightCost8  = (float)($selectedFlight['price'] ?? 0);
        $flightArrCost = $flightIsRT ? round($flightCost8 / 2) : $flightCost8;
        $flightDepCost = $flightIsRT ? ($flightCost8 - $flightArrCost) : 0;
        $selCards[] = ['icon'=>'fa-plane','type'=>'Flight','title'=>($selectedFlight['airline']??'Flight').' '.($selectedFlight['number']??''),'sub'=>($selectedFlight['dep_id']??'').' → '.($selectedFlight['arr_id']??''),'time'=>$selectedFlight['depart']??'','cost'=>$flightArrCost,'isFree'=>false];
    } else { $flightIsRT = false; $flightDepCost = 0; }
    if ($selectedMcFlight) {
        $mcFlightIsRT  = strtolower($selectedMcFlight['type'] ?? '') === 'round trip';
        $mcFlightCost8 = (float)($selectedMcFlight['price'] ?? 0);
        $mcFlightArrCost = $mcFlightIsRT ? round($mcFlightCost8 / 2) : $mcFlightCost8;
        $mcFlightDepCost = $mcFlightIsRT ? ($mcFlightCost8 - $mcFlightArrCost) : 0;
        $selCards[] = ['icon'=>'fa-plane','type'=>'Flight (Leg 2)','title'=>($selectedMcFlight['airline']??'Flight').' '.($selectedMcFlight['number']??''),'sub'=>($selectedMcFlight['dep_id']??'').' → '.($selectedMcFlight['arr_id']??''),'time'=>$selectedMcFlight['depart']??'','cost'=>$mcFlightArrCost,'isFree'=>false];
    } else { $mcFlightIsRT = false; $mcFlightDepCost = 0; }
    if ($selectedHotel)       $selCards[] = ['icon'=>'fa-bed',      'type'=>'Accommodation',        'title'=>$selectedHotel['name']??'Hotel',   'sub'=>($selectedHotel['stars']??3).'★ · '.($selectedHotel['nights']??1).' nights', 'time'=>'Check-in',  'cost'=>$selectedHotel['total']??0,    'isFree'=>false];
    if ($selectedMcHotel)     $selCards[] = ['icon'=>'fa-bed',      'type'=>'Accommodation (Leg 2)','title'=>$selectedMcHotel['name']??'Hotel', 'sub'=>($selectedMcHotel['stars']??3).'★ · '.($selectedMcHotel['nights']??1).' nights','time'=>'Check-in','cost'=>$selectedMcHotel['total']??0,  'isFree'=>false];
    if ($selectedVenue)       $selCards[] = ['icon'=>'fa-utensils', 'type'=>'Food & Dining',        'title'=>$selectedVenue['name']??'Restaurant',   'sub'=>$selectedVenue['cuisine']??'',   'time'=>'Dinner',    'cost'=>($selectedVenue['priceMin']??0).($selectedVenue['priceMax']??0 ? '–'.($selectedVenue['priceMax']??0) : ''), 'isFree'=>false];
    if ($selectedMcVenue)     $selCards[] = ['icon'=>'fa-utensils', 'type'=>'Food & Dining (Leg 2)','title'=>$selectedMcVenue['name']??'Restaurant', 'sub'=>$selectedMcVenue['cuisine']??'', 'time'=>'Dinner',    'cost'=>($selectedMcVenue['priceMin']??0).($selectedMcVenue['priceMax']??0 ? '–'.($selectedMcVenue['priceMax']??0) : ''), 'isFree'=>false];
    if ($selectedAttraction)  $selCards[] = ['icon'=>'fa-camera',   'type'=>'Attraction',           'title'=>$selectedAttraction['name']??'Attraction',   'sub'=>$selectedAttraction['type']??'',   'time'=>'Activity', 'cost'=>$selectedAttraction['price']??0,   'isFree'=>$selectedAttraction['isFree']??false];
    if ($selectedMcAttraction)$selCards[] = ['icon'=>'fa-camera',   'type'=>'Attraction (Leg 2)',   'title'=>$selectedMcAttraction['name']??'Attraction', 'sub'=>$selectedMcAttraction['type']??'', 'time'=>'Activity', 'cost'=>$selectedMcAttraction['price']??0, 'isFree'=>$selectedMcAttraction['isFree']??false];

    $totalCost8 = 0;
    foreach ($selCards as $c) {
        if (!$c['isFree'] && is_numeric($c['cost'])) $totalCost8 += (float) $c['cost'];
    }
    // Add AI activity costs
    if ($aiItinerary && !empty($aiItinerary['days'])) {
        foreach ($aiItinerary['days'] as $d) {
            foreach ($d['activities'] ?? [] as $a) {
                if (isset($a['cost']) && is_numeric($a['cost'])) $totalCost8 += (float) $a['cost'];
            }
        }
    }

    // Map activity type to icon
    $actIcons = ['food'=>'fa-utensils','attraction'=>'fa-camera','transport'=>'fa-plane','leisure'=>'fa-umbrella-beach','hotel'=>'fa-bed','default'=>'fa-map-pin'];

    // Merge user selections into day structure alongside AI days
    // Build selection activities to prepend to Day 1
    $selActivities = [];
    foreach ($selCards as $sc) {
        $selActivities[] = ['time'=>$sc['time'],'title'=>$sc['title'],'description'=>$sc['sub'],'type'=>$sc['type'],'cost'=>$sc['cost'],'isFree'=>$sc['isFree'],'icon'=>$sc['icon'],'isUserPick'=>true];
    }

    // Day 1 = traveler selections only
    $allDays = [['day'=>1,'label'=>'Arrival','activities'=>$selActivities,'isUserDay'=>true]];

    // AI days start from Day 2; inject return-flight cost onto last day's departure activity
    $returnDepCost = $flightDepCost + ($mcFlightDepCost ?? 0);
    if ($aiItinerary && !empty($aiItinerary['days'])) {
        $aiDayList = $aiItinerary['days'];
        $lastIdx   = count($aiDayList) - 1;
        foreach ($aiDayList as $i => $aiDay) {
            $aiDay['isUserDay'] = false;
            $aiDay['day']       = $i + 2;
            // On the last AI day, add return cost to the "Head to Airport" activity
            if ($i === $lastIdx && $returnDepCost > 0) {
                foreach ($aiDay['activities'] as &$actItem) {
                    if (stripos($actItem['title'] ?? '', 'airport') !== false || stripos($actItem['title'] ?? '', 'departure') !== false) {
                        $actItem['cost'] = $returnDepCost;
                        break;
                    }
                }
                unset($actItem);
            }
            $allDays[] = $aiDay;
        }
    }
@endphp

<style>
.itin8-wrap{padding:20px 0;}
.itin8-topbar{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:16px;flex-wrap:wrap;}
.itin8-meta{display:flex;align-items:center;gap:14px;flex-wrap:wrap;}
.itin8-tag{display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:700;color:var(--muted);}
.itin8-cost-box{text-align:right;}
.itin8-cost-label{font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:var(--muted);}
.itin8-cost-val{font-size:26px;font-weight:900;color:#7B3F00;line-height:1.1;}
.itin8-cost-budget{font-size:11px;color:var(--muted);}
.itin8-desc{font-size:13px;color:var(--muted);line-height:1.6;margin:0 0 18px;max-width:680px;}
.itin8-actions{display:flex;align-items:center;gap:10px;margin-bottom:28px;}
.itin8-btn-ghost{background:#fff;border:1.5px solid var(--border);color:var(--dark);border-radius:10px;padding:10px 22px;font-size:13px;font-weight:700;cursor:pointer;}
.itin8-btn-save{background:#3B1A08;color:#fff;border:none;border-radius:10px;padding:10px 24px;font-size:13px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:8px;}
.itin8-btn-save:hover{background:#2D1206;}

/* Day columns */
.itin8-days{display:flex;gap:14px;overflow-x:auto;padding-bottom:12px;}
.itin8-day-col{flex:0 0 210px;min-width:210px;background:#fff;border:1.5px solid var(--border);border-radius:16px;overflow:hidden;box-shadow:0 2px 10px rgba(0,0,0,0.05);}
.itin8-day-header{display:flex;align-items:center;gap:10px;padding:12px 14px;border-bottom:1px solid var(--border);}
.itin8-day-num{width:26px;height:26px;border-radius:50%;background:#7B3F00;color:#fff;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:800;flex-shrink:0;}
.itin8-day-num.ai{background:#C4892A;}
.itin8-day-label{font-size:11px;font-weight:700;color:var(--dark);line-height:1.3;}
.itin8-day-date{font-size:9px;color:var(--muted);font-weight:600;}
.itin8-act-card{padding:12px 14px;border-bottom:1px solid var(--border);}
.itin8-act-card:last-child{border-bottom:none;}
.itin8-act-time{font-size:10px;color:var(--muted);margin-bottom:6px;display:flex;align-items:center;gap:4px;}
.itin8-act-icon{width:28px;height:28px;border-radius:8px;background:#F5F0EB;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-bottom:6px;}
.itin8-act-title{font-size:12px;font-weight:700;color:var(--dark);margin-bottom:2px;}
.itin8-act-sub{font-size:10px;color:var(--muted);line-height:1.4;margin-bottom:6px;}
.itin8-act-cost{font-size:10px;font-weight:700;color:var(--muted);}
.itin8-act-cost span{color:#7B3F00;}
.itin8-loading{display:flex;align-items:center;gap:10px;padding:32px 0;color:var(--muted);font-size:13px;}
</style>

<div class="itin8-wrap">

    {{-- Top bar --}}
    <div class="itin8-topbar">
        <div>
            <div class="itin8-meta" style="margin-bottom:8px;">
                <div class="itin8-tag"><i class="fa-regular fa-calendar" style="font-size:10px;"></i> {{ $sd8 }} – {{ $ed8 }}</div>
                @foreach($allTags8 as $t8)
                <div class="itin8-tag"><i class="fa-solid fa-tag" style="font-size:8px;"></i> {{ $t8 }}</div>
                @endforeach
            </div>
            <p class="itin8-desc">
                A perfectly balanced trip exploring <strong style="color:var(--dark);">{{ trim($manualTo ?: $mcTo) }}</strong> built from your selections and AI-suggested activities.
            </p>
        </div>
        <div class="itin8-cost-box">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:2px;">
                <div class="itin8-cost-label">Estimated Cost</div>
                @php
                    $overBudget8  = $budMax8 > 0 && $totalCost8 > $budMax8;
                    $underBudget8 = $budMin8 > 0 && $totalCost8 < $budMin8;
                    $onBudget8    = !$overBudget8 && !$underBudget8;
                @endphp
                @if($overBudget8)
                    <span style="font-size:10px;font-weight:700;background:#FEE2E2;color:#B91C1C;padding:2px 8px;border-radius:20px;letter-spacing:.4px;text-transform:uppercase;">Over Budget</span>
                @elseif($underBudget8)
                    <span style="font-size:10px;font-weight:700;background:#FEF3C7;color:#92400E;padding:2px 8px;border-radius:20px;letter-spacing:.4px;text-transform:uppercase;">Under Budget</span>
                @else
                    <span style="font-size:10px;font-weight:700;background:#D1FAE5;color:#065F46;padding:2px 8px;border-radius:20px;letter-spacing:.4px;text-transform:uppercase;">On Budget</span>
                @endif
            </div>
            <div class="itin8-cost-val" style="{{ $overBudget8 ? 'color:#B91C1C;' : ($underBudget8 ? 'color:#92400E;' : '') }}">₱{{ number_format($totalCost8) }}</div>
        </div>
    </div>

    {{-- Action buttons --}}
    <div class="itin8-actions">
        <button class="itin8-btn-ghost" wire:click="regenerateItinerary" wire:loading.attr="disabled" wire:target="regenerateItinerary">
            <span wire:loading.remove wire:target="regenerateItinerary"><i class="fa-solid fa-rotate" style="font-size:11px;margin-right:6px;"></i> Generate Other Options</span>
            <span wire:loading wire:target="regenerateItinerary"><i class="fa-solid fa-spinner fa-spin" style="margin-right:6px;"></i> Generating…</span>
        </button>
        <button class="itin8-btn-save" wire:click="goToSummary" wire:loading.attr="disabled" wire:target="goToSummary">
            <span wire:loading.remove wire:target="goToSummary">Save Itinerary <i class="fa-solid fa-floppy-disk" style="font-size:12px;"></i></span>
            <span wire:loading wire:target="goToSummary"><i class="fa-solid fa-spinner fa-spin"></i></span>
        </button>
    </div>


    {{-- AI loading state --}}
    @if($aiLoading)
    <div class="itin8-loading">
        <i class="fa-solid fa-spinner fa-spin" style="color:#7B3F00;font-size:18px;"></i>
        Generating AI-suggested activities for your trip…
    </div>
    @endif

    {{-- Day columns --}}
    <div class="itin8-days">
        @foreach($allDays as $dayItem)
        @php
            $isUserDay = $dayItem['isUserDay'] ?? false;
            $dayNum    = $dayItem['day'] ?? ($loop->iteration);
            $dayLabel  = $dayItem['label'] ?? ('Day ' . $dayNum);
            $dayDate   = $startDate ? \Carbon\Carbon::parse($startDate)->addDays($dayNum - 1)->format('M j') : '';
        @endphp
        <div class="itin8-day-col">
            <div class="itin8-day-header">
                <div class="itin8-day-num ai">{{ $dayNum }}</div>
                <div>
                    <div class="itin8-day-label">{{ $dayLabel }}</div>
                    @if($dayDate)<div class="itin8-day-date">{{ $dayDate }}</div>@endif
                </div>
            </div>
            @foreach($dayItem['activities'] ?? [] as $act)
            @php
                $actIcon = $act['icon'] ?? ($actIcons[$act['type'] ?? 'default'] ?? 'fa-map-pin');
                $actCost = $act['cost'] ?? null;
                $actFree = $act['isFree'] ?? false;
            @endphp
            <div class="itin8-act-card">
                <div class="itin8-act-time"><i class="fa-regular fa-clock" style="font-size:9px;"></i> {{ $act['time'] ?? '' }}</div>
                <div class="itin8-act-icon">
                    <i class="fa-solid {{ $actIcon }}" style="font-size:12px;color:#7B3F00;"></i>
                </div>
                <div class="itin8-act-title">{{ $act['title'] ?? ($act['name'] ?? '') }}</div>
                @if($act['description'] ?? ($act['sub'] ?? ''))
                <div class="itin8-act-sub">"{{ $act['description'] ?? $act['sub'] }}"</div>
                @endif
                <div class="itin8-act-cost">Est. Cost
                    @if($actFree) <span>FREE</span>
                    @elseif($actCost !== null && $actCost !== '' && $actCost != 0)
                        <span>{{ is_numeric($actCost) ? '₱'.number_format((float)$actCost) : $actCost }}</span>
                    @else <span>—</span>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
        @endforeach

        {{-- Add Custom Activity placeholder --}}
        <div style="flex:0 0 180px;min-width:180px;border:2px dashed var(--border);border-radius:16px;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:24px 16px;gap:10px;cursor:pointer;color:var(--muted);">
            <div style="width:36px;height:36px;border-radius:50%;border:2px dashed var(--border);display:flex;align-items:center;justify-content:center;">
                <i class="fa-solid fa-plus" style="font-size:14px;"></i>
            </div>
            <span style="font-size:12px;font-weight:600;text-align:center;">Add Custom Activity</span>
        </div>
    </div>

</div>
@endif

{{-- ═══════════════════════════════════════════════════════════════
     STEP 9 — Trip Summary & Cost Estimation
═══════════════════════════════════════════════════════════════ --}}
@if ($planningMode === 'manual' && $step === 9)
@php
    $s9dest  = trim($manualTo ?: $mcTo ?: 'Unknown');
    $s9from  = trim($manualFrom ?: 'Manila');
    $s9days  = ($startDate && $endDate) ? max(1,(int)round((strtotime($endDate)-strtotime($startDate))/86400)) : 1;
    $s9sd    = $startDate ? \Carbon\Carbon::parse($startDate)->format('F j, Y') : '—';
    $s9ed    = $endDate   ? \Carbon\Carbon::parse($endDate)->format('F j, Y')   : '—';
    $s9sdow  = $startDate ? \Carbon\Carbon::parse($startDate)->format('l') : '';
    $s9edow  = $endDate   ? \Carbon\Carbon::parse($endDate)->format('l')   : '';

    // Cost breakdown from selections
    $s9flight = ($selectedFlight['price'] ?? 0) + ($selectedMcFlight['price'] ?? 0);
    $s9hotel  = ($selectedHotel['total']  ?? 0) + ($selectedMcHotel['total']  ?? 0);
    $s9venue  = (($selectedVenue['priceMax'] ?? $selectedVenue['priceMin'] ?? 0)) + (($selectedMcVenue['priceMax'] ?? $selectedMcVenue['priceMin'] ?? 0));
    $s9attr   = ($selectedAttraction['isFree']   ?? false ? 0 : (int) preg_replace('/[^\d]/', '', $selectedAttraction['price']   ?? '0'))
              + ($selectedMcAttraction['isFree'] ?? false ? 0 : (int) preg_replace('/[^\d]/', '', $selectedMcAttraction['price'] ?? '0'));

    // AI activity costs
    $s9ai = 0;
    if ($aiItinerary && !empty($aiItinerary['days'])) {
        foreach ($aiItinerary['days'] as $d) {
            foreach ($d['activities'] ?? [] as $a) {
                if (isset($a['cost']) && is_numeric($a['cost'])) $s9ai += (float)$a['cost'];
            }
        }
    }

    $s9emergency = (float) $emergency;
    $s9budget    = (int) preg_replace('/[^\d]/', '', $manualBudgetMax ?: $manualBudgetMin);
    $s9total     = $s9flight + $s9hotel + $s9venue + $s9attr + $s9ai + $s9emergency;
    $s9over      = $s9budget > 0 && $s9total > $s9budget;

    // Selections for summary list
    $s9picks = [];
    if ($selectedFlight)      $s9picks[] = ['icon'=>'fa-plane',    'label'=>'Flight',         'val'=>($selectedFlight['airline']??'').' '.($selectedFlight['number']??''),  'cost'=>$s9flight, 'editStep'=>2];
    if ($selectedHotel)       $s9picks[] = ['icon'=>'fa-bed',      'label'=>'Accommodation',  'val'=>$selectedHotel['name']??'Hotel',                                      'cost'=>$s9hotel,  'editStep'=>3];
    if ($selectedVenue)       $s9picks[] = ['icon'=>'fa-utensils', 'label'=>'Food & Dining',  'val'=>$selectedVenue['name']??'Restaurant',                                 'cost'=>$s9venue,  'editStep'=>4];
    if ($selectedAttraction)  $s9picks[] = ['icon'=>'fa-camera',   'label'=>'Attraction',     'val'=>$selectedAttraction['name']??'Attraction',                            'cost'=>$s9attr,   'editStep'=>5];
@endphp

<div style="max-width:900px;margin:0 auto;padding:24px 0;">

    {{-- Header --}}
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:28px;flex-wrap:wrap;gap:12px;">
        <button wire:click="$set('step', 8)" style="background:none;border:none;cursor:pointer;font-size:13px;color:var(--muted);display:flex;align-items:center;gap:6px;padding:0;">
            <i class="fa-solid fa-arrow-left" style="font-size:11px;"></i> Back to Itinerary
        </button>
        <div style="display:flex;gap:10px;">
            <button style="padding:9px 20px;border:1.5px solid var(--border);border-radius:9px;background:#fff;font-size:13px;font-weight:600;color:var(--dark);cursor:pointer;">
                <i class="fa-solid fa-download" style="font-size:11px;margin-right:6px;"></i> Download PDF
            </button>
            <button wire:click="saveItinerary" wire:loading.attr="disabled"
                    style="padding:9px 22px;border:none;border-radius:9px;background:#1A0A00;color:#fff;font-size:13px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:8px;">
                <span wire:loading.remove wire:target="saveItinerary">Confirm Trip <i class="fa-solid fa-check" style="font-size:11px;"></i></span>
                <span wire:loading wire:target="saveItinerary"><i class="fa-solid fa-spinner fa-spin"></i> Saving…</span>
            </button>
        </div>
    </div>

    <h2 style="font-size:22px;font-weight:800;color:var(--dark);margin:0 0 24px;">Trip Summary &amp; Cost Estimation</h2>

    <div style="display:grid;grid-template-columns:1fr 300px;gap:20px;align-items:start;">

        {{-- Left column --}}
        <div>
            {{-- Route & dates card --}}
            <div style="background:#fff;border:1.5px solid var(--border);border-radius:14px;padding:20px 22px;margin-bottom:16px;">
                <div style="display:flex;align-items:center;gap:20px;flex-wrap:wrap;">
                    <div>
                        <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:var(--muted);">FROM</div>
                        <div style="font-size:20px;font-weight:800;color:#C4892A;">{{ $s9from }}</div>
                        <div style="font-size:11px;color:var(--muted);">{{ $selectedFlight['dep_id'] ?? 'MNL' }}</div>
                    </div>
                    <div style="flex:1;text-align:center;">
                        <div style="font-size:11px;color:var(--muted);margin-bottom:4px;">{{ $s9days }}-Day Journey</div>
                        <div style="border-top:2px dashed var(--border);position:relative;">
                            <i class="fa-solid fa-plane" style="position:absolute;top:-8px;left:50%;transform:translateX(-50%);font-size:13px;color:var(--muted);background:#fff;padding:0 4px;"></i>
                        </div>
                    </div>
                    <div>
                        <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:var(--muted);">TO</div>
                        <div style="font-size:20px;font-weight:800;color:#C4892A;">{{ $s9dest }}</div>
                        <div style="font-size:11px;color:var(--muted);">{{ $selectedFlight['arr_id'] ?? '' }}</div>
                    </div>
                    <div style="border-left:1.5px solid var(--border);padding-left:20px;">
                        <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:var(--muted);margin-bottom:4px;">DATES</div>
                        <div style="font-size:14px;font-weight:700;color:var(--dark);">{{ \Carbon\Carbon::parse($startDate)->format('M j') }} – {{ \Carbon\Carbon::parse($endDate)->format('M j, Y') }}</div>
                        <div style="font-size:11px;color:var(--muted);">{{ $s9sdow }} – {{ $s9edow }}</div>
                    </div>
                </div>
            </div>

            {{-- Itinerary collapsible --}}
            <div x-data="{ open: false }" style="background:#fff;border:1.5px solid var(--border);border-radius:14px;overflow:hidden;margin-bottom:12px;">
                <button @click="open=!open" style="width:100%;display:flex;align-items:center;justify-content:space-between;padding:16px 20px;background:none;border:none;cursor:pointer;">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <i class="fa-regular fa-calendar-check" style="color:var(--muted);font-size:13px;"></i>
                        <span style="font-size:14px;font-weight:700;color:var(--dark);">Itinerary</span>
                    </div>
                    <i class="fa-solid fa-chevron-down" style="font-size:11px;color:var(--muted);transition:.2s;" :style="open?'transform:rotate(180deg)':''"></i>
                </button>
                <div x-show="open" x-transition style="border-top:1px solid var(--border);padding:16px 20px;">
                    {{-- Day 1: traveler selections --}}
                    @if(!empty($s9picks))
                    <div style="margin-bottom:12px;">
                        <div style="font-size:11px;font-weight:700;color:#7B3F00;margin-bottom:6px;">Day 1 — Arrival</div>
                        @foreach($s9picks as $pk9)
                        <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--dark);padding:4px 0;border-bottom:1px solid #F5F0EB;">
                            <span>{{ $pk9['label'] }} · {{ $pk9['val'] }}</span>
                            <span style="color:var(--muted);font-weight:600;">{{ $pk9['cost'] ? '₱'.number_format($pk9['cost']) : 'Free' }}</span>
                        </div>
                        @endforeach
                    </div>
                    @endif

                    {{-- AI days starting from Day 2 --}}
                    @if($aiItinerary && !empty($aiItinerary['days']))
                        @foreach($aiItinerary['days'] as $i => $d9)
                        <div style="margin-bottom:12px;">
                            <div style="font-size:11px;font-weight:700;color:#7B3F00;margin-bottom:6px;">Day {{ $i+2 }} — {{ $d9['label'] ?? '' }}</div>
                            @foreach($d9['activities'] ?? [] as $a9)
                            <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--dark);padding:4px 0;border-bottom:1px solid #F5F0EB;">
                                <span>{{ $a9['time'] ?? '' }} · {{ $a9['title'] ?? '' }}</span>
                                <span style="color:var(--muted);font-weight:600;">{{ $a9['cost'] ? '₱'.number_format($a9['cost']) : 'Free' }}</span>
                            </div>
                            @endforeach
                        </div>
                        @endforeach
                    @endif

                    @if(empty($s9picks) && (!$aiItinerary || empty($aiItinerary['days'])))
                        <p style="color:var(--muted);font-size:13px;margin:0;">No itinerary generated yet.</p>
                    @endif
                </div>
            </div>

            {{-- Selection Summary collapsible --}}
            <div x-data="{ open: false }" style="background:#fff;border:1.5px solid var(--border);border-radius:14px;overflow:hidden;">
                <button @click="open=!open" style="width:100%;display:flex;align-items:center;justify-content:space-between;padding:16px 20px;background:none;border:none;cursor:pointer;">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <i class="fa-regular fa-bookmark" style="color:var(--muted);font-size:13px;"></i>
                        <span style="font-size:14px;font-weight:700;color:var(--dark);">Selection Summary</span>
                    </div>
                    <i class="fa-solid fa-chevron-down" style="font-size:11px;color:var(--muted);transition:.2s;" :style="open?'transform:rotate(180deg)':''"></i>
                </button>
                <div x-show="open" x-transition style="border-top:1px solid var(--border);padding:16px 20px;">
                    @foreach($s9picks as $pk)
                    <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 0;border-bottom:1px solid #F5F0EB;">
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div style="width:32px;height:32px;border-radius:8px;background:#F5F0EB;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <i class="fa-solid {{ $pk['icon'] }}" style="font-size:12px;color:#7B3F00;"></i>
                            </div>
                            <div>
                                <div style="font-size:10px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.4px;">{{ $pk['label'] }}</div>
                                <div style="font-size:13px;font-weight:600;color:var(--dark);">{{ $pk['val'] }}</div>
                            </div>
                        </div>
                        <div style="text-align:right;">
                            <button wire:click="$set('step', {{ $pk['editStep'] }})"
                                    style="display:block;margin-left:auto;font-size:11px;font-weight:600;color:#7B3F00;background:none;border:none;cursor:pointer;padding:0 0 2px;text-decoration:underline;">
                                Edit
                            </button>
                            <div style="font-size:13px;font-weight:700;color:#7B3F00;">{{ $pk['cost'] ? 'PHP '.number_format($pk['cost']) : 'Free' }}</div>
                        </div>
                    </div>
                    @endforeach

                    {{-- Emergency Fund row --}}
                    <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 0;">
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div style="width:32px;height:32px;border-radius:8px;background:#FEE2E2;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <i class="fa-solid fa-shield-halved" style="font-size:12px;color:#B91C1C;"></i>
                            </div>
                            <div>
                                <div style="font-size:10px;font-weight:700;color:#B91C1C;text-transform:uppercase;letter-spacing:.4px;">Emergency Fund</div>
                                <div style="font-size:13px;font-weight:600;color:var(--dark);">Reserved safety budget</div>
                            </div>
                        </div>
                        <div style="text-align:right;">
                            <button wire:click="$set('step', 6)"
                                    style="display:block;margin-left:auto;font-size:11px;font-weight:600;color:#B91C1C;background:none;border:none;cursor:pointer;padding:0 0 2px;text-decoration:underline;">
                                Edit
                            </button>
                            <div style="font-size:13px;font-weight:700;color:#B91C1C;">PHP {{ number_format($s9emergency) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right column: Cost Breakdown --}}
        <div style="background:#fff;border:1.5px solid var(--border);border-radius:14px;padding:22px;position:sticky;top:80px;">
            <div style="font-size:15px;font-weight:800;color:var(--dark);margin-bottom:18px;">Cost Breakdown</div>

            @php
                $s9rows = [
                    ['Transportation', $s9flight],
                    ['Accommodation',  $s9hotel],
                    ['Food & Dining',  $s9venue],
                    ['Attraction',     $s9attr + $s9ai],
                ];
            @endphp
            @foreach($s9rows as [$lbl,$amt])
            <div style="display:flex;justify-content:space-between;align-items:center;font-size:13.5px;padding:10px 0;border-bottom:1px solid #F3F0EB;">
                <span style="color:#666;">{{ $lbl }}</span>
                <span style="font-weight:600;color:#1a1a1a;">P {{ number_format($amt) }}</span>
            </div>
            @endforeach

            <div style="display:flex;justify-content:space-between;align-items:center;font-size:13.5px;padding:10px 0;border-bottom:1px solid #F3F0EB;">
                <span style="color:#B91C1C;font-weight:600;">Emergency Fund</span>
                <span style="font-weight:700;color:#B91C1C;">P {{ number_format($s9emergency) }}</span>
            </div>

            <div style="padding-top:14px;margin-top:2px;">
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <span style="font-size:14px;font-weight:800;color:#1a1a1a;">Total Cost</span>
                    <span style="font-size:20px;font-weight:900;color:{{ $s9over ? '#B91C1C' : '#7B3F00' }};">PHP {{ number_format($s9total) }}</span>
                </div>
                @if($s9budget > 0)
                <div style="margin-top:5px;font-size:12px;color:{{ $s9over ? '#B91C1C' : '#666' }};text-align:right;">
                    {{ $s9over ? 'Over ₱'.number_format($s9budget).' budget' : 'Within ₱'.number_format($s9budget).' budget' }}
                </div>
                @endif
            </div>

            <div style="margin-top:14px;padding-top:12px;border-top:1px solid #F0EDE8;font-size:11px;color:var(--muted);line-height:1.6;">
                All estimates include upper-bound restaurant pricing.
            </div>
        </div>

    </div>
</div>
@endif

{{-- ═══════════════════════════════════════════════════════════════
     EMPTY STATE — no trips yet
═══════════════════════════════════════════════════════════════ --}}
@if ($showEmpty)
<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:70vh;text-align:center;padding:40px 24px;">

    <div style="width:96px;height:96px;border-radius:50%;background:#F0EDE8;display:flex;align-items:center;justify-content:center;margin-bottom:28px;">
        <i class="fa-solid fa-earth-americas" style="font-size:36px;color:#5C4033;"></i>
    </div>

    <h2 style="font-size:26px;font-weight:800;color:var(--dark);margin:0 0 14px;">No trips planned yet</h2>

    <p style="font-size:15px;color:var(--muted);line-height:1.6;max-width:380px;margin:0 0 36px;">
        Start your journey by planning your first adventure. Track expenses, save for goals, and capture moments all in one place.
    </p>

    <button wire:click="startFromEmpty"
            style="display:inline-flex;align-items:center;gap:10px;background:#7B3F00;color:#fff;border:none;border-radius:10px;padding:16px 36px;font-size:14px;font-weight:700;letter-spacing:0.8px;text-transform:uppercase;cursor:pointer;"
            onmouseenter="this.style.background='#6A3500'"
            onmouseleave="this.style.background='#7B3F00'">
        <i class="fa-solid fa-plus"></i> Plan Your First Trip
    </button>

</div>
@endif

{{-- ═══════════════════════════════════════════════════════════════
     MODE SELECT — manual or AI
═══════════════════════════════════════════════════════════════ --}}
@if (!$showEmpty && $planningMode === '' && $step === 0 && !$showAiPlanner)
<div style="display:flex;flex-direction:column;align-items:center;padding:48px 24px;">

    <h1 style="font-size:clamp(22px,3vw,30px);font-weight:800;color:var(--dark);margin:0 0 10px;text-align:center;">Start Your Next Journey</h1>
    <p style="font-size:14px;color:var(--muted);text-align:center;max-width:400px;line-height:1.6;margin:0 0 40px;">
        Choose your preferred method to orchestrate your travel plan with Budgetra's precision planning tools.
    </p>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:24px;width:100%;max-width:760px;">

        {{-- Manual Planning --}}
        <div wire:click="selectPlanningMode('manual')"
             style="background:#fff;border:1.5px solid var(--border);border-radius:16px;overflow:hidden;cursor:pointer;transition:box-shadow .2s,transform .2s;"
             onmouseenter="this.style.boxShadow='0 8px 32px rgba(0,0,0,0.10)';this.style.transform='translateY(-2px)'"
             onmouseleave="this.style.boxShadow='none';this.style.transform='none'">
            <div style="height:200px;overflow:hidden;">
                <img src="{{ asset('stockimages/manualplanning.png') }}" alt="Manual Planning" style="width:100%;height:100%;object-fit:cover;">
            </div>
            <div style="padding:20px 22px 24px;">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:10px;">
                    <span style="font-size:17px;font-weight:800;color:var(--dark);">Manual Planning</span>
                    <span style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.6px;background:#F0EDE8;color:#7B5C3A;border-radius:20px;padding:3px 10px;white-space:nowrap;">Precision Control</span>
                </div>
                <p style="font-size:13px;color:var(--muted);line-height:1.6;margin:0 0 18px;">
                    Build your own trip step-by-step with full control over every detail, from transportation to emergency funds.
                </p>
                <span style="font-size:13px;font-weight:700;color:#7B3F00;display:inline-flex;align-items:center;gap:6px;letter-spacing:0.3px;">
                    GET STARTED <i class="fa-solid fa-arrow-right" style="font-size:11px;"></i>
                </span>
            </div>
        </div>

        {{-- AI Planning --}}
        <div wire:click="selectPlanningMode('ai')"
             style="background:#fff;border:1.5px solid var(--border);border-radius:16px;overflow:hidden;cursor:pointer;transition:box-shadow .2s,transform .2s;"
             onmouseenter="this.style.boxShadow='0 8px 32px rgba(0,0,0,0.10)';this.style.transform='translateY(-2px)'"
             onmouseleave="this.style.boxShadow='none';this.style.transform='none'">
            <div style="height:200px;overflow:hidden;">
                <img src="{{ asset('stockimages/aiplanning.png') }}" alt="AI Planning" style="width:100%;height:100%;object-fit:cover;">
            </div>
            <div style="padding:20px 22px 24px;">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:10px;">
                    <span style="font-size:17px;font-weight:800;color:var(--dark);">AI Powered Planning</span>
                    <span style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.6px;background:#FEF3E2;color:#B45309;border:1px solid #FDE68A;border-radius:20px;padding:3px 10px;white-space:nowrap;">Recommended</span>
                </div>
                <p style="font-size:13px;color:var(--muted);line-height:1.6;margin:0 0 18px;">
                    Enter your preferences and let our AI build the perfect trip for you. Intelligent suggestions, and budget balancing.
                </p>
                <span style="font-size:13px;font-weight:700;color:#7B3F00;display:inline-flex;align-items:center;gap:6px;letter-spacing:0.3px;">
                    LAUNCH ASSISTANT <i class="fa-solid fa-arrow-right" style="font-size:11px;"></i>
                </span>
            </div>
        </div>

    </div>
</div>
@endif

{{-- ═══════════════════════════════════════════════════════════════
     AI PLANNER — prompt input
═══════════════════════════════════════════════════════════════ --}}
{{-- ═══════════════════════════════════════════════════════════════
     AI PLANNER — results
═══════════════════════════════════════════════════════════════ --}}
@if ($showAiPlanner && $aiStep === 'results' && !empty($aiPackage))
@php
    $pkg    = $aiPackage;
    $total  = $pkg['total']  ?? 0;
    $budget = $pkg['budget'] ?? ($aiBudgetMax ?: $aiBudgetMin ?: 0);
    $pct    = $budget > 0 ? min(100, round($total / $budget * 100)) : 0;
@endphp
<div style="padding-bottom:110px;">

    {{-- YOUR REQUEST --}}
    <div style="margin-bottom:24px;">
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;">
            <div style="width:28px;height:28px;border-radius:50%;background:#F5F0EB;display:flex;align-items:center;justify-content:center;">
                <i class="fa-solid fa-user" style="font-size:11px;color:#7B3F00;"></i>
            </div>
            <span style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;color:var(--muted);">Your Request</span>
        </div>
        <div style="background:#5C2D0A;color:#fff;border-radius:12px;padding:16px 22px;font-size:14px;font-weight:600;line-height:1.5;">
            {{ $aiFrom }} to {{ $aiTo }}
            @if ($aiBudgetMin || $aiBudgetMax)
                &nbsp;·&nbsp;
                @if ($aiBudgetMin && $aiBudgetMax && $aiBudgetMin !== $aiBudgetMax)
                    ₱{{ number_format($aiBudgetMin) }}–₱{{ number_format($aiBudgetMax) }}
                @else
                    ₱{{ number_format($aiBudgetMax ?: $aiBudgetMin) }}
                @endif
            @endif
            @if ($aiDateFrom && $aiDateTo)
                &nbsp;·&nbsp; {{ $aiDateFrom }} – {{ $aiDateTo }}
            @endif
        </div>
    </div>

    {{-- Heading --}}
    <h2 style="font-size:20px;font-weight:800;color:var(--dark);margin:0 0 20px;display:flex;align-items:center;gap:10px;">
        <span style="width:32px;height:32px;background:#F5F0EB;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0;">✦</span>
        Your trip package for {{ $aiTo }}
    </h2>

    {{-- Package cards --}}
    <div style="display:flex;flex-direction:column;gap:12px;margin-bottom:32px;">

        @php
        $cards = [
            ['key'=>'transport',     'label'=>'Transportation', 'icon'=>'fa-solid fa-plane',    'name_field'=>null,    'name_fallback'=>($pkg['transport']['from_code'] ?? 'MNL').' → '.($pkg['transport']['to_code'] ?? '')],
            ['key'=>'accommodation', 'label'=>'Accommodation',  'icon'=>'fa-solid fa-bed',      'name_field'=>'name',  'name_fallback'=>''],
            ['key'=>'food',          'label'=>'Food & Dining',  'icon'=>'fa-solid fa-utensils', 'name_field'=>'name',  'name_fallback'=>''],
            ['key'=>'attractions',   'label'=>'Attractions',    'icon'=>'fa-solid fa-building-columns', 'name_field'=>null, 'name_fallback'=>''],
        ];
        @endphp

        @foreach ($cards as $card)
        @php $sec = $pkg[$card['key']] ?? []; @endphp
        @if (!empty($sec))
        <div style="background:#fff;border:1.5px solid var(--border);border-radius:14px;padding:20px 22px;display:flex;align-items:flex-start;gap:16px;">

            {{-- Icon --}}
            <div style="width:44px;height:44px;border-radius:10px;background:#F5F0EB;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:2px;">
                <i class="{{ $card['icon'] }}" style="color:#7B3F00;font-size:17px;"></i>
            </div>

            {{-- Content --}}
            <div style="flex:1;min-width:0;">
                <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;color:var(--muted);margin-bottom:5px;">{{ $card['label'] }}</div>

                @if ($card['key'] === 'transport')
                    <div style="font-size:16px;font-weight:800;color:var(--dark);margin-bottom:4px;">
                        {{ $sec['from_code'] ?? 'MNL' }} → {{ $sec['to_code'] ?? '' }}
                    </div>
                    <div style="font-size:12px;color:var(--muted);">{{ $sec['detail'] ?? '' }}</div>

                @elseif ($card['key'] === 'accommodation')
                    <div style="font-size:16px;font-weight:800;color:var(--dark);margin-bottom:4px;display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
                        {{ $sec['name'] ?? '' }}
                        @if (!empty($sec['stars']))
                            <span>@for ($s=0;$s<$sec['stars'];$s++)<i class="fa-solid fa-star" style="font-size:10px;color:#F59E0B;"></i>@endfor</span>
                        @endif
                    </div>
                    <div style="font-size:12px;color:var(--muted);">{{ $sec['detail'] ?? '' }}</div>

                @elseif ($card['key'] === 'food')
                    <div style="font-size:16px;font-weight:800;color:var(--dark);margin-bottom:4px;">{{ $sec['name'] ?? '' }}</div>
                    <div style="font-size:12px;color:var(--muted);">{{ $sec['detail'] ?? '' }}</div>

                @elseif ($card['key'] === 'attractions')
                    <div style="display:flex;flex-wrap:wrap;gap:6px;margin-top:2px;">
                        @foreach ($sec['items'] ?? [] as $att)
                        <span style="display:inline-flex;align-items:center;background:#F5F0EB;border-radius:20px;padding:4px 12px;font-size:12px;font-weight:600;color:#5C2D0A;">
                            {{ $att[0] }} ({{ $att[1] }})
                        </span>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Cost --}}
            <div style="text-align:right;flex-shrink:0;min-width:80px;">
                <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.6px;color:var(--muted);margin-bottom:4px;">Est. Cost</div>
                <div style="font-size:18px;font-weight:800;color:var(--dark);">₱{{ number_format($sec['cost'] ?? 0) }}</div>
            </div>

        </div>
        @endif
        @endforeach

    </div>
</div>

{{-- ── Bottom bar ── --}}
<div style="position:fixed;bottom:0;left:var(--sidebar-width,220px);right:0;background:#fff;border-top:1.5px solid var(--border);padding:14px 28px;display:flex;align-items:center;gap:20px;z-index:100;">
    <div style="flex:1;min-width:0;">
        <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.7px;color:var(--muted);margin-bottom:5px;">Estimated Cost (Total)</div>
        <div style="font-size:18px;font-weight:800;color:var(--dark);margin-bottom:6px;">
            ₱{{ number_format($total) }} of ₱{{ number_format($budget) }} budget
        </div>
        <div style="display:flex;align-items:center;gap:10px;">
            <div style="flex:1;height:6px;background:#EDE8E3;border-radius:99px;overflow:hidden;">
                <div style="height:100%;background:#7B3F00;border-radius:99px;width:{{ $pct }}%;"></div>
            </div>
            <span style="font-size:12px;font-weight:600;color:var(--muted);">{{ $pct }}%</span>
        </div>
    </div>
    <button wire:click="regeneratePackage" wire:loading.attr="disabled" wire:target="regeneratePackage"
            style="background:#fff;border:1.5px solid var(--border);color:var(--dark);border-radius:10px;padding:11px 20px;font-size:13px;font-weight:700;cursor:pointer;white-space:nowrap;display:inline-flex;align-items:center;gap:8px;"
            onmouseenter="this.style.background='#F5F0EB'"
            onmouseleave="this.style.background='#fff'">
        <span wire:loading.remove wire:target="regeneratePackage"><i class="fa-solid fa-rotate"></i> Regenerate</span>
        <span wire:loading wire:target="regeneratePackage"><i class="fa-solid fa-spinner fa-spin"></i> Regenerating…</span>
    </button>
    <button wire:click="saveAiTrip" wire:loading.attr="disabled" wire:target="saveAiTrip"
            style="background:#5C2D0A;color:#fff;border:none;border-radius:10px;padding:11px 28px;font-size:13px;font-weight:700;cursor:pointer;white-space:nowrap;"
            onmouseenter="this.style.background='#4A2408'"
            onmouseleave="this.style.background='#5C2D0A'">
        <span wire:loading.remove wire:target="saveAiTrip">Next</span>
        <span wire:loading wire:target="saveAiTrip"><i class="fa-solid fa-spinner fa-spin"></i> Saving…</span>
    </button>
</div>
@endif

{{-- ═══════════════════════════════════════════════════════════════
     AI PLANNER — loading / building package
═══════════════════════════════════════════════════════════════ --}}
@if ($showAiPlanner && $aiStep === 'loading')
<style>@keyframes aiSpin{to{transform:rotate(360deg)}}</style>
<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:70vh;text-align:center;padding:40px 24px;">
    <svg style="width:48px;height:48px;animation:aiSpin 1s linear infinite;margin-bottom:24px;" viewBox="0 0 24 24" fill="none" stroke="#7B3F00" stroke-width="2.5" stroke-linecap="round">
        <path d="M12 2a10 10 0 1 0 10 10" />
    </svg>
    <h2 style="font-size:20px;font-weight:800;color:var(--dark);margin:0 0 8px;">Building your trip package…</h2>
    <p style="font-size:14px;color:var(--muted);margin:0;">This will just take a moment.</p>
</div>
@script
<script>
    setTimeout(() => $wire.call('showResults'), 3000);
</script>
@endscript
@endif

{{-- ═══════════════════════════════════════════════════════════════
     AI PLANNER — prompt input
═══════════════════════════════════════════════════════════════ --}}
@if ($showAiPlanner && $aiStep === '')
<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:70vh;padding:40px 24px;text-align:center;">

    <style>
        @keyframes pillBounce {
            0%,100% { transform:translateY(0); }
            40%      { transform:translateY(-6px); }
            60%      { transform:translateY(-3px); }
        }
    </style>
    <span style="display:inline-flex;align-items:center;gap:7px;background:#C97B4B;color:#fff;border-radius:24px;padding:8px 20px;font-size:11px;font-weight:700;letter-spacing:0.8px;text-transform:uppercase;margin-bottom:22px;animation:pillBounce 2s ease-in-out infinite;">
        <span style="font-size:15px;line-height:1;">✦</span> AI Powered Planning
    </span>

    <h1 style="font-size:clamp(22px,3vw,30px);font-weight:800;color:var(--dark);margin:0 0 12px;">Plan your trip with AI</h1>
    <p style="font-size:14px;color:var(--muted);line-height:1.6;max-width:360px;margin:0 0 32px;">
        Enter your destination, budget, and dates, we'll build the whole package.
    </p>

    <div style="width:100%;max-width:480px;background:#fff;border:1.5px solid var(--border);border-radius:20px;padding:24px 28px;box-shadow:0 4px 24px rgba(0,0,0,0.06);">
        <style>#ai-prompt::placeholder{color:#C4B8AC;opacity:1;}</style>
        <textarea id="ai-prompt" wire:model.live="aiPrompt"
                  placeholder="Tell me about your trip. e.g. I'm in Manila and I want to travel to Cebu City from July 22 to July 27, 2026, and my budget is around 40,000"
                  rows="4"
                  style="width:100%;border:none;outline:none;font-size:15px;color:var(--dark);resize:none;font-family:inherit;line-height:1.7;box-sizing:border-box;background:transparent;"></textarea>

        <div style="margin-top:24px;">
            <button wire:click="automateTrip" wire:loading.attr="disabled" wire:target="automateTrip"
                    style="width:100%;background:#7B3F00;color:#fff;border:none;border-radius:12px;padding:17px;font-size:15px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;gap:10px;"
                    onmouseenter="this.style.background='#6A3500'"
                    onmouseleave="this.style.background='#7B3F00'">
                <span wire:loading.remove wire:target="automateTrip">
                    <span style="font-size:16px;line-height:1;">✦</span> Automate
                </span>
                <span wire:loading wire:target="automateTrip">
                    <i class="fa-solid fa-spinner fa-spin"></i> Planning your trip…
                </span>
            </button>
        </div>
    </div>

</div>
@endif

</div>
