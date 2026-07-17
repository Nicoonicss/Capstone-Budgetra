<?php
namespace App\Livewire\Traveler;

use App\Models\Destination;
use App\Models\SavingsGoal;
use App\Models\Trip;
use App\Models\TripBudget;
use App\Services\GeminiService;
use App\Services\SerpApiService;
use Carbon\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('layouts.app', ['active' => 'trips'])]
class TripPlannerWizard extends Component
{
    // ── Trips list view (shown when user already has trips) ─
    public bool   $showList      = false;
    public bool   $showEmpty     = false;
    public bool   $showAiPlanner = false;
    public string $aiPrompt      = '';
    public ?int   $tripToDelete  = null;

    // ── AI Planner state ───────────────────────────────────
    public string $aiStep        = '';   // '' | 'loading' | 'results'
    public string $aiFrom        = '';
    public string $aiTo          = '';
    public int    $aiBudgetMin   = 0;
    public int    $aiBudgetMax   = 0;
    public string $aiDateFrom    = '';
    public string $aiDateTo      = '';
    public int    $aiDays        = 0;
    public array  $aiPackage     = [];
    public int    $aiGenCount   = 0;   // 0 = first gen (expensive), 1+ = cheaper options

    // ── Navigation ─────────────────────────────────────────
    public int    $step     = 0;
    public string $planningMode = ''; // 'manual' | 'ai'

    // ── Step 1: trip details form (new) ───────────────────
    public string $manualFrom      = '';
    public string $manualTo        = '';
    public string $manualBudgetMin = '';
    public string $manualBudgetMax = '';
    public string $travelWith      = ''; // 'solo' | 'group'

    // ── Step 2: flight selection ───────────────────────────
    public array  $flightResults     = [];
    public bool   $flightLoading     = false;
    public string $flightTripType    = 'one_way'; // 'one_way' | 'round_trip' | 'multi_city'
    public ?array $selectedFlight    = null;
    // multi-city leg 2 (synced on search)
    public string $mcTo              = '';
    public string $mcStartDate       = '';
    public string $mcEndDate         = '';
    public array  $mcFlightResults   = [];
    public bool   $mcFlightLoading   = false;
    public bool   $mcFlightStep      = false; // true = showing leg 2 flight list
    public ?array $selectedMcFlight  = null;

    // ── Step 3: accommodation ──────────────────────────────
    public array  $hotelResults      = [];
    public bool   $hotelLoading      = false;
    public ?array $selectedHotel     = null;
    public int    $hotelGuests       = 1;
    public string $hotelType         = 'hotel'; // hotel | apartment | inn
    // multi-city leg 2 accommodation
    public bool   $mcHotelStep       = false;
    public array  $mcHotelResults    = [];
    public bool   $mcHotelLoading    = false;
    public ?array $selectedMcHotel   = null;

    // ── Step 3 (was 2): scope ─────────────────────────────
    public string $tripScope = '';

    // ── Step 3: destination ────────────────────────────────
    public ?int   $destinationId   = null;
    public string $destinationName = '';
    public string $destSearch      = '';

    // ── Step 4: calendar ───────────────────────────────────
    public string $startDate  = '';
    public string $endDate    = '';
    public int    $calYear;
    public int    $calMonth;

    // ── Step 4: group + budget ─────────────────────────────
    public string $groupType  = '';
    public int    $travelers  = 1;
    public string $budgetTier = '';

    // ── Step 5: editable cost categories ──────────────────
    public float  $transportation = 0;
    public float  $accommodation  = 0;
    public float  $food           = 0;
    public float  $attractions    = 0;
    public float  $shopping       = 0;
    public float  $emergency      = 0;
    public float  $budgetLimit    = 0;
    public string $editingCategory = '';

    // ── Cost rate tables (₱ per trip) ─────────────────────
    private const RATES = [
        'local' => [
            'Shoestring' => ['transport_base' => 2000,   'transport_daily' => 100,  'accommodation_night' => 800,   'food_day' => 350,  'attractions_day' => 150,  'shopping_per_person' => 800  ],
            'Mid-range'  => ['transport_base' => 8000,   'transport_daily' => 400,  'accommodation_night' => 3000,  'food_day' => 1200, 'attractions_day' => 600,  'shopping_per_person' => 3000 ],
            'Luxury'     => ['transport_base' => 20000,  'transport_daily' => 1000, 'accommodation_night' => 8000,  'food_day' => 3000, 'attractions_day' => 1500, 'shopping_per_person' => 10000],
        ],
        'international' => [
            'Shoestring' => ['transport_base' => 25000,  'transport_daily' => 500,  'accommodation_night' => 2000,  'food_day' => 1000, 'attractions_day' => 500,  'shopping_per_person' => 2000 ],
            'Mid-range'  => ['transport_base' => 55000,  'transport_daily' => 1000, 'accommodation_night' => 5000,  'food_day' => 2500, 'attractions_day' => 1500, 'shopping_per_person' => 7000 ],
            'Luxury'     => ['transport_base' => 150000, 'transport_daily' => 3000, 'accommodation_night' => 15000, 'food_day' => 6000, 'attractions_day' => 4000, 'shopping_per_person' => 25000],
        ],
    ];

    public function mount(): void
    {
        $this->calYear  = (int) date('Y');
        $this->calMonth = (int) date('n');
        // /trips/plan always starts the wizard fresh; /trips shows list or empty state
        $hasTrips = auth()->user()->trips()->exists();
        $isPlanRoute = request()->routeIs('trips.plan');
        $this->showList  = !$isPlanRoute && $hasTrips;
        $this->showEmpty = !$isPlanRoute && !$hasTrips;
    }

    public function startNewTrip(): mixed
    {
        return $this->redirect(route('trips.plan'), navigate: true);
    }

    public function startFromEmpty(): void
    {
        $this->showEmpty = false;
    }

    public function confirmDelete(int $id): void
    {
        $this->tripToDelete = $id;
    }

    public function cancelDelete(): void
    {
        $this->tripToDelete = null;
    }

    public function deleteTrip(): void
    {
        if (!$this->tripToDelete) return;
        $trip = Trip::where('id', $this->tripToDelete)->where('user_id', auth()->id())->firstOrFail();
        $trip->delete();
        $this->tripToDelete = null;
        if (!auth()->user()->trips()->exists()) {
            $this->showList = false;
            $this->step     = 1;
        }
    }

    public function getMyTripsProperty()
    {
        return auth()->user()->trips()
            ->withSum('budgets as total_estimated', 'estimated_cost')
            ->withSum('expenses as total_spent', 'amount')
            ->latest('start_date')
            ->get();
    }

    // ── Step 1 ─────────────────────────────────────────────
    public function selectPlanningMode(string $mode): void
    {
        $this->planningMode = $mode;
        if ($mode === 'ai') {
            $this->showAiPlanner = true;
            $this->aiStep        = '';
            $this->aiPrompt      = '';
            $this->aiPackage     = [];
        } else {
            $this->step = 1;
        }
    }

    public function automateTrip(): void
    {
        $this->parseAiPrompt();
        $this->aiGenCount = 0;
        $this->aiStep = 'loading';
        $this->dispatch('ai-process-trip');
    }

    public function showResults(): void
    {
        $this->aiStep = 'results';
    }

    #[On('ai-process-trip')]
    public function processAiTrip(): void
    {
        // Layer 1: Gemini — parse natural language + full package
        $gemini  = new GeminiService();
        $package = $gemini->planTrip($this->aiPrompt);

        if ($package && !empty($package['to'])) {
            $this->aiFrom      = $this->cleanCityName($package['from']      ?? $this->aiFrom);
            $this->aiTo        = $this->cleanCityName($package['to']        ?? $this->aiTo);
            $this->aiBudgetMin = (int)($package['budget_min'] ?? $this->aiBudgetMin);
            $this->aiBudgetMax = (int)($package['budget_max'] ?? $this->aiBudgetMax ?: $this->aiBudgetMin);
            $this->aiDateFrom  = $package['date_from'] ?? $this->aiDateFrom;
            $this->aiDateTo    = $package['date_to']   ?? $this->aiDateTo;
            $this->aiDays      = (int)($package['days'] ?? $this->aiDays);
            $this->aiPackage   = [
                'transport'     => $package['transport']     ?? [],
                'accommodation' => $package['accommodation'] ?? [],
                'food'          => $package['food']          ?? [],
                'attractions'   => $package['attractions']   ?? ['items'=>[],'cost'=>0],
                'total'         => (int)($package['total']  ?? 0),
                'budget'        => (int)($package['budget'] ?? $this->aiBudgetMax),
                'pct'           => (int)($package['pct']    ?? 0),
            ];
            $this->aiStep = 'results';
            return;
        }

        // Layer 2: SerpAPI — real live data per category
        $this->parseAiPrompt();
        $serpPackage = $this->buildSerpApiPackage();
        if ($serpPackage) {
            $this->aiPackage = $serpPackage;
            $this->aiStep    = 'results';
            return;
        }

        // Layer 3: Static lookup + generic fallback
        $this->generateAiPackage();
        $this->aiStep = 'results';
    }

    private function buildSerpApiPackage(): ?array
    {
        if (empty($this->aiTo)) return null;

        $serp   = new SerpApiService();
        $days   = max(1, $this->aiDays);
        $budget = $this->aiBudgetMax ?: $this->aiBudgetMin ?: 30000;

        // Convert parsed display dates back to Y-m-d for API params
        // aiDateFrom: "Aug 3"  aiDateTo: "Aug 10, 2026"
        $year = date('Y');
        if ($this->aiDateTo && preg_match('/(\d{4})$/', $this->aiDateTo, $ym)) {
            $year = $ym[1];
        }
        $checkIn  = $this->aiDateFrom
            ? date('Y-m-d', strtotime($this->aiDateFrom . ', ' . $year))
            : date('Y-m-d');
        $checkOut = $this->aiDateTo
            ? date('Y-m-d', strtotime($this->aiDateTo))
            : date('Y-m-d', strtotime("+{$days} days"));

        // Determine origin IATA code
        $fromCode = $this->resolveCode($this->aiFrom ?: 'Manila');
        $toCode   = $this->resolveCode($this->aiTo);

        // Budget split targets
        $transportBudget     = (int)round($budget * 0.18);
        $accommodationBudget = (int)round($budget * 0.50);
        $foodBudget          = (int)round($budget * 0.28);
        $attractionBudget    = (int)round($budget * 0.04);

        // Fetch all 4 from SerpAPI (each call independent with its own timeout)
        $gen = $this->aiGenCount; // 0 = first (best within budget), 1+ = cheaper
        $flightData  = $serp->searchFlights($fromCode, $toCode, $checkIn, $checkOut, $gen, $transportBudget);
        $hotelData   = $serp->searchHotels($this->aiTo, $checkIn, $checkOut, $days, $gen, $accommodationBudget);
        $restaurData = $serp->searchRestaurants($this->aiTo, $days, $foodBudget, $gen);
        $attrItems   = $serp->searchAttractions($this->aiTo, $gen);

        // Fall through to static lookup only if every call failed
        if (!$flightData && !$hotelData && !$restaurData && !$attrItems) return null;

        // ── Transport ────────────────────────────────────────────────────
        $transport = $flightData
            ? array_merge(['label'=>'TRANSPORTATION','icon'=>'fa-solid fa-plane','from_code'=>$fromCode,'to_code'=>$toCode], $flightData)
            : ['label'=>'TRANSPORTATION','icon'=>'fa-solid fa-plane','from_code'=>$fromCode,'to_code'=>$toCode,'detail'=>'Direct Flight · Round Trip','cost'=>$transportBudget];

        // ── Accommodation (15km radius via ll param) ──────────────────────
        $accommodation = $hotelData
            ? array_merge(['label'=>'ACCOMMODATION','icon'=>'fa-solid fa-bed'], $hotelData)
            : ['label'=>'ACCOMMODATION','icon'=>'fa-solid fa-bed','name'=>'Hotel in '.$this->aiTo,'stars'=>3,'detail'=>$days.' Nights · Standard Room · '.$this->aiTo,'cost'=>$accommodationBudget];

        // ── Food & Dining (15km radius via ll param) ──────────────────────
        $food = $restaurData
            ? array_merge(['label'=>'FOOD & DINING','icon'=>'fa-solid fa-utensils'], $restaurData)
            : ['label'=>'FOOD & DINING','icon'=>'fa-solid fa-utensils','name'=>'Dining in '.$this->aiTo,'detail'=>$days.' Days · Breakfast, Lunch, & Dinner · '.$this->aiTo,'cost'=>$foodBudget];

        // ── Attractions (google_travel_explore → google_maps fallback) ────
        $items    = $attrItems ?? [[$this->aiTo . ' City Tour', '₱300'],['Local Market Visit','Free']];
        $attrCost = array_sum(array_map(
            fn($a) => is_numeric(str_replace(['₱',','], '', $a[1])) ? (int)str_replace(['₱',','], '', $a[1]) : 0,
            $items
        )); // 0 is valid — don't fallback to budget when all are free
        $attractions = ['label'=>'ATTRACTIONS','icon'=>'fa-solid fa-landmark','items'=>$items,'cost'=>$attrCost];

        $rawPackage = [
            'transport'     => $transport,
            'accommodation' => $accommodation,
            'food'          => $food,
            'attractions'   => $attractions,
        ];

        // ── Gemini enrichment — fix generic names / missing prices ────────
        try {
            $gemini   = new GeminiService();
            $enriched = $gemini->enrichPackage($rawPackage, $this->aiTo, $days, $budget);
            if ($enriched && is_array($enriched)) {
                // Merge enriched strings back but preserve all numeric costs
                foreach (['transport','accommodation','food','attractions'] as $key) {
                    if (!isset($enriched[$key])) continue;
                    foreach ($enriched[$key] as $field => $val) {
                        if (!is_numeric($val)) {
                            $rawPackage[$key][$field] = $val;
                        }
                    }
                }
                // Re-sum attraction cost in case Gemini added real prices
                if (!empty($rawPackage['attractions']['items'])) {
                    $rawPackage['attractions']['cost'] = array_sum(array_map(
                        fn($a) => is_numeric(str_replace(['₱',','], '', $a[1])) ? (int)str_replace(['₱',','], '', $a[1]) : 0,
                        $rawPackage['attractions']['items']
                    ));
                }
            }
        } catch (\Throwable) {
            // Gemini failure is non-fatal; proceed with SerpAPI data as-is
        }

        $total = $rawPackage['transport']['cost']
               + $rawPackage['accommodation']['cost']
               + $rawPackage['food']['cost']
               + $rawPackage['attractions']['cost'];

        return array_merge($rawPackage, [
            'total'  => $total,
            'budget' => $budget,
            'pct'    => min(100, (int)round($total / $budget * 100)),
        ]);
    }

    private function currencySymbol(string $destination): string
    {
        $map = [
            // Philippine peso (default)
            'manila'=>'₱','cebu'=>'₱','cebu city'=>'₱','davao'=>'₱','boracay'=>'₱','bohol'=>'₱',
            'palawan'=>'₱','el nido'=>'₱','coron'=>'₱','siargao'=>'₱','bacolod'=>'₱','iloilo'=>'₱',
            'zamboanga'=>'₱','cagayan de oro'=>'₱','general santos'=>'₱','tagaytay'=>'₱','baguio'=>'₱',
            'vigan'=>'₱','batangas'=>'₱','leyte'=>'₱','tacloban'=>'₱','dumaguete'=>'₱','surigao'=>'₱',
            'camiguin'=>'₱','siquijor'=>'₱','batanes'=>'₱','sagada'=>'₱','pagudpud'=>'₱','laoag'=>'₱',
            'puerto galera'=>'₱','caramoan'=>'₱','puerto princesa'=>'₱','cotabato'=>'₱',
            // International
            'singapore'=>'S$',
            'bangkok'=>'฿','thailand'=>'฿','phuket'=>'฿',
            'bali'=>'Rp','indonesia'=>'Rp',
            'kuala lumpur'=>'RM','malaysia'=>'RM',
            'hong kong'=>'HK$',
            'tokyo'=>'¥','japan'=>'¥','osaka'=>'¥',
            'seoul'=>'₩','korea'=>'₩',
            'taipei'=>'NT$','taiwan'=>'NT$',
            'dubai'=>'AED','uae'=>'AED',
            'london'=>'£',
            'paris'=>'€','rome'=>'€','barcelona'=>'€','amsterdam'=>'€',
            'new york'=>'$','new york city'=>'$','australia'=>'A$','sydney'=>'A$',
            'maldives'=>'$',
        ];
        return $map[strtolower(trim($destination))] ?? '₱';
    }

    public static function staticIataCode(string $city): string
    {
        return (new self)->iataCode($city);
    }

    public function iataCode(string $city): string
    {
        $map = [
            // Philippines
            'manila' => 'MNL', 'pasay' => 'MNL', 'makati' => 'MNL', 'quezon city' => 'MNL',
            'paranaque' => 'MNL', 'pasig' => 'MNL', 'taguig' => 'MNL', 'las pinas' => 'MNL',
            'ncr' => 'MNL', 'metro manila' => 'MNL', 'tagaytay' => 'MNL',
            'cebu' => 'CEB', 'cebu city' => 'CEB', 'mactan' => 'CEB',
            'davao' => 'DVO', 'davao city' => 'DVO',
            'boracay' => 'MPH', 'kalibo' => 'KLO', 'malay' => 'MPH',
            'bohol' => 'TAG', 'tagbilaran' => 'TAG', 'tagbilaran (bohol)' => 'TAG',
            'palawan' => 'PPS', 'puerto princesa' => 'PPS',
            'el nido' => 'ENI',
            'coron' => 'USU', 'busuanga' => 'USU',
            'siargao' => 'IAO', 'del carmen' => 'IAO',
            'bacolod' => 'BCD',
            'iloilo' => 'ILO', 'iloilo city' => 'ILO',
            'zamboanga' => 'ZAM',
            'cagayan de oro' => 'CGY', 'cagayan' => 'CGY',
            'general santos' => 'GES', 'gensan' => 'GES',
            'tacloban' => 'TAC', 'leyte' => 'TAC',
            'dumaguete' => 'DGT', 'siquijor' => 'DGT',
            'surigao' => 'SUG',
            'cotabato' => 'CBO',
            'batanes' => 'BSO', 'basco' => 'BSO',
            'camiguin' => 'CGM',
            'laoag' => 'LAO',
            'vigan' => 'VIG',
            'baguio' => 'BAG',
            'legazpi' => 'LGP', 'legazpi city' => 'LGP',
            'naga' => 'WNP', 'naga city' => 'WNP',
            'roxas' => 'RXS', 'roxas city' => 'RXS',
            'san jose' => 'SJI',
            'ozamiz' => 'OZC',
            'dipolog' => 'DPL',
            'butuan' => 'BXU',
            'pagadian' => 'PAG',
            'virac' => 'VRC',
            'tuguegarao' => 'TUG',
            'cauayan' => 'CYZ',
            'puerto galera' => 'MNL',
            // Southeast Asia
            'singapore' => 'SIN',
            'bangkok' => 'BKK', 'thailand' => 'BKK', 'suvarnabhumi' => 'BKK',
            'phuket' => 'HKT', 'krabi' => 'KBV', 'chiang mai' => 'CNX',
            'bali' => 'DPS', 'denpasar' => 'DPS',
            'jakarta' => 'CGK', 'indonesia' => 'CGK',
            'kuala lumpur' => 'KUL', 'malaysia' => 'KUL', 'kl' => 'KUL',
            'penang' => 'PEN', 'langkawi' => 'LGK', 'kota kinabalu' => 'BKI',
            'hong kong' => 'HKG',
            'macau' => 'MFM',
            'ho chi minh city' => 'SGN', 'ho chi minh' => 'SGN', 'hcmc' => 'SGN', 'saigon' => 'SGN',
            'hanoi' => 'HAN', 'vietnam' => 'SGN',
            'da nang' => 'DAD',
            'yangon' => 'RGN', 'myanmar' => 'RGN',
            'phnom penh' => 'PNH', 'cambodia' => 'PNH',
            'siem reap' => 'REP',
            'vientiane' => 'VTE', 'laos' => 'VTE',
            'colombo' => 'CMB', 'sri lanka' => 'CMB',
            'dhaka' => 'DAC', 'bangladesh' => 'DAC',
            'kathmandu' => 'KTM', 'nepal' => 'KTM',
            // East Asia
            'tokyo' => 'NRT', 'japan' => 'NRT',
            'osaka' => 'KIX',
            'nagoya' => 'NGO',
            'fukuoka' => 'FUK',
            'sapporo' => 'CTS',
            'okinawa' => 'OKA',
            'seoul' => 'ICN', 'korea' => 'ICN', 'incheon' => 'ICN',
            'busan' => 'PUS',
            'taipei' => 'TPE', 'taiwan' => 'TPE',
            'kaohsiung' => 'KHH',
            'beijing' => 'PEK', 'china' => 'PEK',
            'shanghai' => 'PVG',
            'guangzhou' => 'CAN',
            'shenzhen' => 'SZX',
            // South Asia
            'delhi' => 'DEL', 'new delhi' => 'DEL', 'india' => 'DEL',
            'mumbai' => 'BOM', 'bombay' => 'BOM',
            'bangalore' => 'BLR', 'bengaluru' => 'BLR',
            'chennai' => 'MAA', 'madras' => 'MAA',
            'kolkata' => 'CCU',
            'hyderabad' => 'HYD',
            // Middle East
            'dubai' => 'DXB', 'uae' => 'DXB',
            'abu dhabi' => 'AUH',
            'doha' => 'DOH', 'qatar' => 'DOH',
            'riyadh' => 'RUH', 'saudi arabia' => 'RUH',
            'jeddah' => 'JED',
            'kuwait' => 'KWI', 'kuwait city' => 'KWI',
            'bahrain' => 'BAH',
            'muscat' => 'MCT', 'oman' => 'MCT',
            'amman' => 'AMM', 'jordan' => 'AMM',
            'tel aviv' => 'TLV', 'israel' => 'TLV',
            'istanbul' => 'IST', 'turkey' => 'IST',
            // Europe
            'london' => 'LHR',
            'paris' => 'CDG',
            'amsterdam' => 'AMS',
            'frankfurt' => 'FRA', 'germany' => 'FRA',
            'rome' => 'FCO', 'italy' => 'FCO',
            'madrid' => 'MAD', 'spain' => 'MAD',
            'barcelona' => 'BCN',
            'vienna' => 'VIE', 'austria' => 'VIE',
            'zurich' => 'ZRH', 'switzerland' => 'ZRH',
            'brussels' => 'BRU', 'belgium' => 'BRU',
            'lisbon' => 'LIS', 'portugal' => 'LIS',
            'athens' => 'ATH', 'greece' => 'ATH',
            'prague' => 'PRG', 'czech republic' => 'PRG',
            'budapest' => 'BUD', 'hungary' => 'BUD',
            'warsaw' => 'WAW', 'poland' => 'WAW',
            'stockholm' => 'ARN', 'sweden' => 'ARN',
            'oslo' => 'OSL', 'norway' => 'OSL',
            'copenhagen' => 'CPH', 'denmark' => 'CPH',
            'helsinki' => 'HEL', 'finland' => 'HEL',
            'moscow' => 'SVO', 'russia' => 'SVO',
            // Oceania
            'sydney' => 'SYD', 'australia' => 'SYD',
            'melbourne' => 'MEL',
            'brisbane' => 'BNE',
            'perth' => 'PER',
            'auckland' => 'AKL', 'new zealand' => 'AKL',
            // Americas
            'new york' => 'JFK', 'new york city' => 'JFK', 'nyc' => 'JFK',
            'los angeles' => 'LAX', 'la' => 'LAX',
            'san francisco' => 'SFO',
            'chicago' => 'ORD',
            'miami' => 'MIA',
            'toronto' => 'YYZ', 'canada' => 'YYZ',
            'vancouver' => 'YVR',
            'sao paulo' => 'GRU', 'brazil' => 'GRU',
            // Africa
            'nairobi' => 'NBO', 'kenya' => 'NBO',
            'johannesburg' => 'JNB', 'south africa' => 'JNB',
            'cairo' => 'CAI', 'egypt' => 'CAI',
            'casablanca' => 'CMN', 'morocco' => 'CMN',
            // Maldives
            'maldives' => 'MLE', 'male' => 'MLE',
        ];

        return $map[strtolower(trim($city))] ?? '';
    }

    private function resolveCode(string $city): string
    {
        $code = $this->iataCode($city);
        return $code !== '' ? $code : trim($city);
    }

    public function finishLoading(): void
    {
        $this->aiStep = 'results';
    }

    public function regeneratePackage(): void
    {
        $this->aiGenCount++;
        $this->generateAiPackage();
    }

    public function backToModeSelect(): void
    {
        $this->showAiPlanner = false;
        $this->aiStep = '';
        $this->step = 0;
        $this->planningMode = '';
    }

    public function backToAiForm(): void
    {
        $this->aiStep = '';
    }

    public function saveAiTrip(): mixed
    {
        if (empty($this->aiPackage)) return null;

        $pkg = $this->aiPackage;

        // Parse dates from display format back to Y-m-d
        $startDate = $this->aiDateFrom ? date('Y-m-d', strtotime($this->aiDateFrom . ' ' . date('Y'))) : now()->toDateString();
        $endDate   = $this->aiDateTo   ? date('Y-m-d', strtotime($this->aiDateTo))                     : now()->addDays($this->aiDays)->toDateString();

        $trip = Trip::create([
            'user_id'      => auth()->id(),
            'destination'  => $this->aiTo,
            'start_date'   => $startDate,
            'end_date'     => $endDate,
            'num_travelers'=> 1,
            'budget_limit' => $pkg['budget'] ?? $this->aiBudgetMax,
            'travel_type'  => 'local',
            'notes'        => 'Generated by AI Planner from: ' . $this->aiPrompt,
        ]);

        // Save budget categories
        $categories = [
            'Transportation' => $pkg['transport']['cost']     ?? 0,
            'Accommodation'  => $pkg['accommodation']['cost'] ?? 0,
            'Food'           => $pkg['food']['cost']          ?? 0,
            'Attractions'    => $pkg['attractions']['cost']   ?? 0,
        ];
        foreach ($categories as $cat => $amount) {
            TripBudget::create([
                'trip_id'        => $trip->id,
                'category'       => $cat,
                'estimated_cost' => $amount,
                'actual_spent'   => 0,
            ]);
        }

        return $this->redirect(route('trips.dashboard', $trip), navigate: true);
    }

    private function cleanCityName(string $name): string
    {
        $name = trim($name);
        // Strip leading travel verbs
        $name = preg_replace('/^\s*(go(?:ing)?|travel(?:ling)?|fly(?:ing)?|visit(?:ing)?|head(?:ing)?|trip)\s+(?:to\s+)?/i', '', $name);
        // Strip trailing month names that bled in (e.g. "Cebu City August 3")
        $name = preg_replace('/\s+(january|february|march|april|may|june|july|august|september|october|november|december|jan|feb|mar|apr|jun|jul|aug|sep|oct|nov|dec)\b.*/i', '', $name);
        // Strip trailing bare numbers
        $name = preg_replace('/\s+\d+.*$/', '', $name);
        return ucwords(strtolower(trim($name)));
    }

    private function parseAiPrompt(): void
    {
        $raw = $this->aiPrompt;

        $monthMap = [
            'january'=>1,'february'=>2,'march'=>3,'april'=>4,'may'=>5,'june'=>6,
            'july'=>7,'august'=>8,'september'=>9,'october'=>10,'november'=>11,'december'=>12,
            'jan'=>1,'feb'=>2,'mar'=>3,'apr'=>4,'jun'=>6,
            'jul'=>7,'aug'=>8,'sep'=>9,'oct'=>10,'nov'=>11,'dec'=>12,
        ];
        $mp = implode('|', array_keys($monthMap));

        // ── Step 1: extract & erase the date span from a working copy ─────
        // This prevents day-numbers (3, 10) from being grabbed as budget amounts.
        $withoutDate = $raw;

        // Cross-month: "August 3 to September 10, 2026"
        if (preg_match('/\b(' . $mp . ')\.?\s+(\d{1,2})\s*(?:[-–]|to)\s+(' . $mp . ')\.?\s+(\d{1,2})(?:,?\s*(\d{4}))?\b/i', $raw, $m)) {
            $mon1  = $monthMap[strtolower($m[1])];
            $mon2  = $monthMap[strtolower($m[3])];
            $year  = !empty($m[5]) ? (int)$m[5] : (int)date('Y');
            $ts1   = mktime(0,0,0,$mon1,(int)$m[2],$year);
            $ts2   = mktime(0,0,0,$mon2,(int)$m[4],$year);
            $this->aiDateFrom = date('M j', $ts1);
            $this->aiDateTo   = date('M j, Y', $ts2);
            $this->aiDays     = (int)ceil(abs($ts2-$ts1)/86400)+1;
            $withoutDate      = str_replace($m[0], '', $raw);

        // Same-month: "August 3 to 10, 2026" | "Aug 3-10" | "Aug 3 - 10, 2026"
        } elseif (preg_match('/\b(' . $mp . ')\.?\s+(\d{1,2})\s*(?:[-–]|to)\s*(\d{1,2})(?:,?\s*(\d{4}))?\b/i', $raw, $m)) {
            $mon  = $monthMap[strtolower($m[1])];
            $year = !empty($m[4]) ? (int)$m[4] : (int)date('Y');
            $d1   = (int)$m[2]; $d2 = (int)$m[3];
            $this->aiDateFrom = date('M j', mktime(0,0,0,$mon,$d1,$year));
            $this->aiDateTo   = date('M j, Y', mktime(0,0,0,$mon,$d2,$year));
            $this->aiDays     = abs($d2-$d1)+1;
            $withoutDate      = str_replace($m[0], '', $raw);

        // Single date: "August 3, 2026" — treat as start, add aiDays
        } elseif (preg_match('/\b(' . $mp . ')\.?\s+(\d{1,2})(?:,?\s*(\d{4}))?\b/i', $raw, $m)) {
            $mon  = $monthMap[strtolower($m[1])];
            $year = !empty($m[3]) ? (int)$m[3] : (int)date('Y');
            $ts1  = mktime(0,0,0,$mon,(int)$m[2],$year);
            $this->aiDateFrom = date('M j', $ts1);
            $this->aiDateTo   = date('M j, Y', $ts1 + 5*86400);
            $this->aiDays     = 6;
            $withoutDate      = str_replace($m[0], '', $raw);

        } else {
            $this->aiDateFrom = date('M j');
            $this->aiDateTo   = date('M j, Y', strtotime('+5 days'));
            $this->aiDays     = 6;
        }

        // ── Step 2: budget — work on date-free copy ───────────────────────
        // "large number" = 4+ digits OR comma-grouped thousands (e.g. 30,000)
        $big = '(?:\d{1,3}(?:,\d{3})+|\d{4,})';

        // Range: "30,000 to 35,000" | "₱30,000-₱35,000" | "30000-35000"
        if (preg_match('/[₱P]?\s*(' . $big . ')\s*(?:[-–]|to)\s*[₱P]?\s*(' . $big . ')/ui', $withoutDate, $m)) {
            $a = (int)str_replace(',','',$m[1]);
            $b = (int)str_replace(',','',$m[2]);
            $this->aiBudgetMin = min($a,$b);
            $this->aiBudgetMax = max($a,$b);

        // Keyword: "budget is/of 30,000" | "budget: 30000"
        } elseif (preg_match('/budget\s*(?:is|of|:)?\s*[₱P]?\s*(' . $big . ')/ui', $withoutDate, $m)) {
            $v = (int)str_replace(',','',$m[1]);
            $this->aiBudgetMin = $this->aiBudgetMax = $v;

        // Peso sign: ₱30,000
        } elseif (preg_match('/[₱]\s*(' . $big . ')/u', $withoutDate, $m)) {
            $v = (int)str_replace(',','',$m[1]);
            $this->aiBudgetMin = $this->aiBudgetMax = $v;

        // Trailing keyword: "30,000 pesos" | "30000 php"
        } elseif (preg_match('/(' . $big . ')\s*(?:pesos?|php)\b/ui', $withoutDate, $m)) {
            $v = (int)str_replace(',','',$m[1]);
            $this->aiBudgetMin = $this->aiBudgetMax = $v;

        // Bare large standalone number
        } elseif (preg_match('/\b(' . $big . ')\b/', $withoutDate, $m)) {
            $v = (int)str_replace(',','',$m[1]);
            $this->aiBudgetMin = $this->aiBudgetMax = $v;

        } else {
            $this->aiBudgetMin = $this->aiBudgetMax = 20000;
        }

        // ── Step 3: cities — fully order-independent ──────────────────────
        // Use date-free copy so month names (August, July…) can't match as cities.
        $months = 'january|february|march|april|may|june|july|august|september|october|november|december|jan|feb|mar|apr|jun|jul|aug|sep|oct|nov|dec';
        $city   = '(?!(?:' . $months . ')\b)[A-Z][a-z]+(?: [A-Z][a-z]+){0,2}';

        $hasFrom = preg_match('/\bfrom\s+(' . $city . ')\b/u', $withoutDate, $mf);
        $hasTo   = preg_match('/\b(?:travel(?:l?ing)?\s+to|go(?:ing)?\s+to|visit(?:ing)?|fly(?:ing)?\s+to|heading\s+to|to)\s+(' . $city . ')\b/ui', $withoutDate, $mt);

        if ($hasFrom && $hasTo) {
            $this->aiFrom = trim($mf[1]);
            $this->aiTo   = trim($mt[1]);
        } elseif ($hasFrom) {
            $this->aiFrom = trim($mf[1]);
            $this->aiTo   = 'Cebu';
        } elseif ($hasTo) {
            $this->aiFrom = 'Manila';
            $this->aiTo   = trim($mt[1]);
        } elseif (preg_match('/(' . $city . ')\s+to\s+(' . $city . ')/u', $withoutDate, $m)) {
            $this->aiFrom = trim($m[1]);
            $this->aiTo   = trim($m[2]);
        } else {
            $this->aiFrom = 'Manila';
            $this->aiTo   = 'Cebu';
        }

        $this->aiFrom = $this->cleanCityName($this->aiFrom);
        $this->aiTo   = $this->cleanCityName($this->aiTo);
    }

    private function generateAiPackage(): void
    {
        $dest    = strtolower($this->aiTo);
        $days    = max(1, $this->aiDays);
        $budget  = $this->aiBudgetMax ?: $this->aiBudgetMin ?: 30000;

        // Destination lookup table — PH local + international
        $lookup = [
            // ── Philippine Destinations ──────────────────────────────────────
            'manila'          => ['code'=>'MNL','airline'=>'N/A – Origin City','hotel'=>'New World Manila Bay Hotel','hotel_stars'=>5,'hotel_type'=>'Deluxe Room','hotel_city'=>'Manila','restaurant'=>'Ilustrado Restaurant (₱1,200)','meal_plan'=>'Breakfast, Lunch, & Dinner','meal_city'=>'Intramuros, Manila','meal_day'=>1200,'attractions'=>[['Intramuros Walls','₱75'],['Rizal Park','Free'],['National Museum of Fine Arts','Free']]],
            'cebu'            => ['code'=>'CEB','airline'=>'Cebu Pacific 5J 567','hotel'=>'Crown Regency Hotel and Towers','hotel_stars'=>4,'hotel_type'=>'Deluxe Room','hotel_city'=>'Cebu City','restaurant'=>'Scape Skydeck Lapu-Lapu (₱1,200)','meal_plan'=>'Breakfast, Lunch, & Dinner','meal_city'=>'Lapu-Lapu City','meal_day'=>1200,'attractions'=>[["Magellan's Cross",'Free'],['Fort San Pedro','₱30'],['Temple of Leah','₱150']]],
            'boracay'         => ['code'=>'KLO','airline'=>'Philippine Airlines PR 201','hotel'=>'Discovery Shores Boracay','hotel_stars'=>5,'hotel_type'=>'Garden View Room','hotel_city'=>'Boracay Island','restaurant'=>'Aria at Discovery Shores (₱1,500)','meal_plan'=>'Breakfast, Lunch, & Dinner','meal_city'=>'Station 1, Boracay','meal_day'=>1500,'attractions'=>[['White Beach Walk','Free'],['Paraw Sailing','₱800'],["Willy's Rock",'Free']]],
            'bohol'           => ['code'=>'TAG','airline'=>'Cebu Pacific 5J 311','hotel'=>'Bohol Beach Club','hotel_stars'=>4,'hotel_type'=>'Standard Room','hotel_city'=>'Panglao, Bohol','restaurant'=>'Bohol Bee Farm Restaurant (₱900)','meal_plan'=>'Breakfast, Lunch, & Dinner','meal_city'=>'Panglao, Bohol','meal_day'=>900,'attractions'=>[['Chocolate Hills','₱50'],['Tarsier Sanctuary','₱100'],['Loboc River Cruise','₱500']]],
            'palawan'         => ['code'=>'PPS','airline'=>'Philippine Airlines PR 2673','hotel'=>'Sheridan Beach Resort','hotel_stars'=>4,'hotel_type'=>'Deluxe Sea View','hotel_city'=>'Puerto Princesa','restaurant'=>'Halong Restaurant (₱1,100)','meal_plan'=>'Breakfast, Lunch, & Dinner','meal_city'=>'Puerto Princesa','meal_day'=>1100,'attractions'=>[['Underground River','₱150'],['Honda Bay Tour','₱500'],['Iwahig Firefly Watching','₱300']]],
            'el nido'         => ['code'=>'ENI','airline'=>'AirSWIFT T6 461','hotel'=>'El Nido Resorts Miniloc Island','hotel_stars'=>5,'hotel_type'=>'Water Cottage','hotel_city'=>'El Nido, Palawan','restaurant'=>'Trattoria Altrove (₱1,300)','meal_plan'=>'Breakfast, Lunch, & Dinner','meal_city'=>'El Nido','meal_day'=>1300,'attractions'=>[['Big Lagoon Tour A','₱1,200'],['Small Lagoon Kayaking','₱200'],['Nacpan Beach','₱100']]],
            'coron'           => ['code'=>'USU','airline'=>'Cebu Pacific 5J 819','hotel'=>'Two Seasons Coron Island Resort','hotel_stars'=>4,'hotel_type'=>'Deluxe Room','hotel_city'=>'Coron, Palawan','restaurant'=>'Sea Horse Restaurant (₱900)','meal_plan'=>'Breakfast, Lunch, & Dinner','meal_city'=>'Coron Town','meal_day'=>900,'attractions'=>[['Kayangan Lake','₱200'],['Twin Lagoon','₱100'],['Maquinit Hot Spring','₱200']]],
            'davao'           => ['code'=>'DVO','airline'=>'Cebu Pacific 5J 481','hotel'=>'Marco Polo Davao','hotel_stars'=>5,'hotel_type'=>'Superior Room','hotel_city'=>'Davao City','restaurant'=>"Claude's Le Coq d'Or (₱1,000)",'meal_plan'=>'Breakfast, Lunch, & Dinner','meal_city'=>'Davao City','meal_day'=>1000,'attractions'=>[['Philippine Eagle Center','₱200'],['Eden Nature Park','₱150'],['Crocodile Park','₱250']]],
            'siargao'         => ['code'=>'IAO','airline'=>'Cebu Pacific 5J 711','hotel'=>'Siargao Bleu Resort','hotel_stars'=>3,'hotel_type'=>'Deluxe Room','hotel_city'=>'General Luna, Siargao','restaurant'=>'Bravo Beach Resort Restaurant (₱850)','meal_plan'=>'Breakfast, Lunch, & Dinner','meal_city'=>'General Luna','meal_day'=>850,'attractions'=>[['Cloud 9 Surfing','₱500'],['Sugba Lagoon','₱150'],['Magpupungko Tidal Pools','₱50']]],
            'bacolod'         => ['code'=>'BCD','airline'=>'Cebu Pacific 5J 461','hotel'=>"L'Fisher Hotel Bacolod",'hotel_stars'=>4,'hotel_type'=>'Superior Room','hotel_city'=>'Bacolod City','restaurant'=>'Calea Pastries & Coffee (₱600)','meal_plan'=>'Breakfast, Lunch, & Dinner','meal_city'=>'Bacolod City','meal_day'=>800,'attractions'=>[['The Ruins','₱100'],['Panaad Park','Free'],['Masskara Festival Site','Free']]],
            'iloilo'          => ['code'=>'ILO','airline'=>'Philippine Airlines PR 2031','hotel'=>'Richmonde Hotel Iloilo','hotel_stars'=>4,'hotel_type'=>'Deluxe Room','hotel_city'=>'Iloilo City','restaurant'=>"Tatoy's Manokan & Seafoods (₱800)",'meal_plan'=>'Breakfast, Lunch, & Dinner','meal_city'=>'Iloilo City','meal_day'=>800,'attractions'=>[['Miagao Church','Free'],['Garin Farm','₱200'],['Islas de Gigantes','₱500']]],
            'zamboanga'       => ['code'=>'ZAM','airline'=>'Cebu Pacific 5J 921','hotel'=>'Grand Astoria Hotel','hotel_stars'=>3,'hotel_type'=>'Standard Room','hotel_city'=>'Zamboanga City','restaurant'=>'Alavar Seafood Restaurant (₱900)','meal_plan'=>'Breakfast, Lunch, & Dinner','meal_city'=>'Zamboanga City','meal_day'=>900,'attractions'=>[['Santa Cruz Island','₱200'],['Fort Pilar','Free'],['Yakan Weaving Village','Free']]],
            'cagayan de oro'  => ['code'=>'CGY','airline'=>'Cebu Pacific 5J 831','hotel'=>'Seda Centrio Cagayan de Oro','hotel_stars'=>4,'hotel_type'=>'Deluxe Room','hotel_city'=>'Cagayan de Oro','restaurant'=>'Kagay-anon Restaurant (₱700)','meal_plan'=>'Breakfast, Lunch, & Dinner','meal_city'=>'Cagayan de Oro','meal_day'=>700,'attractions'=>[['Mapawa Nature Park','₱200'],['Macahambus Cave','₱50'],['7107 Beach Resort','₱150']]],
            'cagayan'         => ['code'=>'CGY','airline'=>'Cebu Pacific 5J 831','hotel'=>'Seda Centrio Cagayan de Oro','hotel_stars'=>4,'hotel_type'=>'Deluxe Room','hotel_city'=>'Cagayan de Oro','restaurant'=>'Kagay-anon Restaurant (₱700)','meal_plan'=>'Breakfast, Lunch, & Dinner','meal_city'=>'Cagayan de Oro','meal_day'=>700,'attractions'=>[['Mapawa Nature Park','₱200'],['Macahambus Cave','₱50'],['7107 Beach Resort','₱150']]],
            'general santos'  => ['code'=>'GES','airline'=>'Cebu Pacific 5J 951','hotel'=>'Phela Grande Hotel','hotel_stars'=>3,'hotel_type'=>'Standard Room','hotel_city'=>'General Santos City','restaurant'=>'Greenfield Restaurant (₱700)','meal_plan'=>'Breakfast, Lunch, & Dinner','meal_city'=>'General Santos','meal_day'=>700,'attractions'=>[['SOCSKSARGEN Museum','Free'],['Sarangani Bay','Free'],['Libi Lake','₱100']]],
            'tagaytay'        => ['code'=>'MNL','airline'=>'Bus from Cubao (₱180)','hotel'=>'Taal Vista Hotel','hotel_stars'=>4,'hotel_type'=>'Deluxe Room','hotel_city'=>'Tagaytay City','restaurant'=>'Café Voila (₱900)','meal_plan'=>'Breakfast, Lunch, & Dinner','meal_city'=>'Tagaytay City','meal_day'=>900,'attractions'=>[['Taal Volcano Island','₱1,000'],['Sky Ranch Tagaytay','₱200'],['Picnic Grove','₱50']]],
            'baguio'          => ['code'=>'MNL','airline'=>'Bus from Pasay (₱500)','hotel'=>'Manor at Camp John Hay','hotel_stars'=>4,'hotel_type'=>'Standard Room','hotel_city'=>'Baguio City','restaurant'=>'Forest House (₱700)','meal_plan'=>'Breakfast, Lunch, & Dinner','meal_city'=>'Baguio City','meal_day'=>700,'attractions'=>[['Burnham Park','Free'],['Mines View Park','Free'],['Strawberry Farm La Trinidad','₱50']]],
            'vigan'           => ['code'=>'VIG','airline'=>'Bus from Pasay (₱600)','hotel'=>'Villa Angela Heritage House','hotel_stars'=>3,'hotel_type'=>'Heritage Room','hotel_city'=>'Vigan City','restaurant'=>'Cafe Leona (₱600)','meal_plan'=>'Breakfast, Lunch, & Dinner','meal_city'=>'Vigan City','meal_day'=>600,'attractions'=>[['Calle Crisologo','Free'],['Bantay Bell Tower','Free'],['Pagburnayan Jar Factory','Free']]],
            'batangas'        => ['code'=>'MNL','airline'=>'Bus from Cubao (₱300)','hotel'=>'Maya Maya Reef Club','hotel_stars'=>3,'hotel_type'=>'Standard Room','hotel_city'=>'Batangas','restaurant'=>"D'Talipapa (₱800)",'meal_plan'=>'Breakfast, Lunch, & Dinner','meal_city'=>'Batangas City','meal_day'=>800,'attractions'=>[['Anilao Dive Sites','₱500'],['Matabungkay Beach','₱100'],['Fortune Island','₱300']]],
            'leyte'           => ['code'=>'TAC','airline'=>'Cebu Pacific 5J 141','hotel'=>'Leyte Park Resort Hotel','hotel_stars'=>3,'hotel_type'=>'Deluxe Room','hotel_city'=>'Tacloban City','restaurant'=>'Kusina Leyte (₱700)','meal_plan'=>'Breakfast, Lunch, & Dinner','meal_city'=>'Tacloban City','meal_day'=>700,'attractions'=>[['MacArthur Landing Memorial','Free'],['San Juanico Bridge','Free'],['Kalanggaman Island','₱300']]],
            'tacloban'        => ['code'=>'TAC','airline'=>'Cebu Pacific 5J 141','hotel'=>'Leyte Park Resort Hotel','hotel_stars'=>3,'hotel_type'=>'Deluxe Room','hotel_city'=>'Tacloban City','restaurant'=>'Kusina Leyte (₱700)','meal_plan'=>'Breakfast, Lunch, & Dinner','meal_city'=>'Tacloban City','meal_day'=>700,'attractions'=>[['MacArthur Landing Memorial','Free'],['San Juanico Bridge','Free'],['Kalanggaman Island','₱300']]],
            'dumaguete'       => ['code'=>'DGT','airline'=>'Cebu Pacific 5J 241','hotel'=>'The Florentina Homes','hotel_stars'=>3,'hotel_type'=>'Standard Room','hotel_city'=>'Dumaguete City','restaurant'=>'Lab-as Seafood Restaurant (₱700)','meal_plan'=>'Breakfast, Lunch, & Dinner','meal_city'=>'Dumaguete City','meal_day'=>700,'attractions'=>[['Apo Island','₱500'],['Twin Lakes Balinsasayao','₱50'],['Casaroro Falls','₱100']]],
            'surigao'         => ['code'=>'SUG','airline'=>'Cebu Pacific 5J 121','hotel'=>'Tavern Hotel Surigao','hotel_stars'=>3,'hotel_type'=>'Standard Room','hotel_city'=>'Surigao City','restaurant'=>'Bay View Restaurant (₱600)','meal_plan'=>'Breakfast, Lunch, & Dinner','meal_city'=>'Surigao City','meal_day'=>600,'attractions'=>[['Sohoton Cave','₱200'],['Bucas Grande Island','₱300'],['Britania Islands','₱400']]],
            'cotabato'        => ['code'=>'CBO','airline'=>'Cebu Pacific 5J 981','hotel'=>'Estosan Garden Hotel','hotel_stars'=>3,'hotel_type'=>'Standard Room','hotel_city'=>'Cotabato City','restaurant'=>'Hadji Murad Restaurant (₱500)','meal_plan'=>'Breakfast, Lunch, & Dinner','meal_city'=>'Cotabato City','meal_day'=>500,'attractions'=>[['Kutawato Caves','₱50'],['Tamontaka Church','Free'],['Lake Lanao (Day Trip)','₱300']]],
            'puerto galera'   => ['code'=>'MNL','airline'=>'Bus + Ferry from Manila (₱400)','hotel'=>'Coco Beach Island Resort','hotel_stars'=>3,'hotel_type'=>'Beachfront Cottage','hotel_city'=>'Puerto Galera, Mindoro','restaurant'=>'La Laguna Beach Restaurant (₱800)','meal_plan'=>'Breakfast, Lunch, & Dinner','meal_city'=>'Puerto Galera','meal_day'=>800,'attractions'=>[['Sabang Beach','Free'],['White Beach','Free'],['Coral Garden Diving','₱800']]],
            'sagada'          => ['code'=>'MNL','airline'=>'Bus from Pasay (₱700)','hotel'=>'Misty Lodge and Cafe','hotel_stars'=>2,'hotel_type'=>'Standard Room','hotel_city'=>'Sagada, Mountain Province','restaurant'=>'Log Cabin Cafe (₱500)','meal_plan'=>'Breakfast, Lunch, & Dinner','meal_city'=>'Sagada','meal_day'=>500,'attractions'=>[['Sumaguing Cave','₱500'],['Hanging Coffins','₱50'],['Kiltepan Peak Sunrise','₱30']]],
            'batanes'         => ['code'=>'BSO','airline'=>'Philippine Airlines PR 241','hotel'=>'Fundacion Pacita','hotel_stars'=>3,'hotel_type'=>'Deluxe Room','hotel_city'=>'Batan Island, Batanes','restaurant'=>'Pension Ivatan (₱600)','meal_plan'=>'Breakfast, Lunch, & Dinner','meal_city'=>'Basco, Batanes','meal_day'=>600,'attractions'=>[['Vayang Rolling Hills','Free'],['Marlboro Country','Free'],['Valugan Boulder Beach','Free']]],
            'camiguin'        => ['code'=>'CGM','airline'=>'Cebu Pacific 5J 851','hotel'=>'Enigmata Treehouse Eco-Retreat','hotel_stars'=>3,'hotel_type'=>'Treehouse Room','hotel_city'=>'Camiguin Island','restaurant'=>'Volcan Restaurant (₱700)','meal_plan'=>'Breakfast, Lunch, & Dinner','meal_city'=>'Mambajao, Camiguin','meal_day'=>700,'attractions'=>[['White Island Sandbar','₱100'],['Sunken Cemetery','₱50'],['Katibawasan Falls','₱30']]],
            'siquijor'        => ['code'=>'DGT','airline'=>'Ferry from Dumaguete (₱200)','hotel'=>'Coco Grove Beach Resort','hotel_stars'=>3,'hotel_type'=>'Garden Room','hotel_city'=>'Siquijor Island','restaurant'=>'Islander Restaurant (₱600)','meal_plan'=>'Breakfast, Lunch, & Dinner','meal_city'=>'San Juan, Siquijor','meal_day'=>600,'attractions'=>[['Cambugahay Falls','Free'],['Lazi Church','Free'],['Salagdoong Beach','₱50']]],
            'pagudpud'        => ['code'=>'MNL','airline'=>'Bus from Pasay (₱900)','hotel'=>'Kapuluan Vista Resort','hotel_stars'=>3,'hotel_type'=>'Ocean View Room','hotel_city'=>'Pagudpud, Ilocos Norte','restaurant'=>'Kapuluan Beach Bar (₱600)','meal_plan'=>'Breakfast, Lunch, & Dinner','meal_city'=>'Pagudpud','meal_day'=>600,'attractions'=>[['Saud Beach','Free'],['Blue Lagoon Beach','Free'],['Bangui Windmills','Free']]],
            'laoag'           => ['code'=>'LAO','airline'=>'Philippine Airlines PR 223','hotel'=>'Fort Ilocandia Resort Hotel','hotel_stars'=>4,'hotel_type'=>'Deluxe Room','hotel_city'=>'Laoag City, Ilocos Norte','restaurant'=>'Saramsam Ylocano Restaurant (₱600)','meal_plan'=>'Breakfast, Lunch, & Dinner','meal_city'=>'Laoag City','meal_day'=>600,'attractions'=>[['Paoay Church','Free'],['La Paz Sand Dunes','₱300'],["Marcos Museum & Mausoleum",'₱20']]],
            'caramoan'        => ['code'=>'MNL','airline'=>'Bus + Jeep from Naga (₱500)','hotel'=>'Tugawe Cove Resort','hotel_stars'=>3,'hotel_type'=>'Beachfront Room','hotel_city'=>'Caramoan, Camarines Sur','restaurant'=>'Local Eatery (₱400)','meal_plan'=>'Breakfast, Lunch, & Dinner','meal_city'=>'Caramoan','meal_day'=>400,'attractions'=>[['Lahos Island','₱300'],['Matukad Island','₱200'],['Gota Beach','₱100']]],

            // ── International Destinations ────────────────────────────────────
            'singapore'       => ['code'=>'SIN','airline'=>'Singapore Airlines SQ 921','hotel'=>'Marina Bay Sands','hotel_stars'=>5,'hotel_type'=>'Deluxe Room','hotel_city'=>'Marina Bay, Singapore','restaurant'=>'Lau Pa Sat Hawker Centre (SGD 15)','meal_plan'=>'Breakfast, Lunch, & Dinner','meal_city'=>'Singapore CBD','meal_day'=>2500,'attractions'=>[['Gardens by the Bay','₱900'],['Universal Studios Singapore','₱2,500'],['Sentosa Island','₱500']]],
            'bangkok'         => ['code'=>'BKK','airline'=>'Thai Airways TG 621','hotel'=>'Mandarin Oriental Bangkok','hotel_stars'=>5,'hotel_type'=>'Superior Room','hotel_city'=>'Bangkok, Thailand','restaurant'=>'Sirocco Sky Bar (₱1,500)','meal_plan'=>'Breakfast, Lunch, & Dinner','meal_city'=>'Silom, Bangkok','meal_day'=>1500,'attractions'=>[['Grand Palace','₱500'],['Wat Pho','₱200'],['Chatuchak Weekend Market','Free']]],
            'thailand'        => ['code'=>'BKK','airline'=>'Thai Airways TG 621','hotel'=>'Mandarin Oriental Bangkok','hotel_stars'=>5,'hotel_type'=>'Superior Room','hotel_city'=>'Bangkok, Thailand','restaurant'=>'Sirocco Sky Bar (₱1,500)','meal_plan'=>'Breakfast, Lunch, & Dinner','meal_city'=>'Silom, Bangkok','meal_day'=>1500,'attractions'=>[['Grand Palace','₱500'],['Wat Pho','₱200'],['Chatuchak Weekend Market','Free']]],
            'phuket'          => ['code'=>'HKT','airline'=>'AirAsia Z2 791','hotel'=>'Banyan Tree Phuket','hotel_stars'=>5,'hotel_type'=>'Pool Villa','hotel_city'=>'Phuket, Thailand','restaurant'=>'Kan Eang@Pier (THB 400)','meal_plan'=>'Breakfast, Lunch, & Dinner','meal_city'=>'Rawai, Phuket','meal_day'=>1800,'attractions'=>[['Phang Nga Bay Tour','₱2,000'],['Big Buddha Phuket','Free'],['Patong Beach','Free']]],
            'bali'            => ['code'=>'DPS','airline'=>'Garuda Indonesia GA 862','hotel'=>'Four Seasons Resort Bali at Sayan','hotel_stars'=>5,'hotel_type'=>'Suite','hotel_city'=>'Ubud, Bali','restaurant'=>'Locavore Restaurant (₱1,800)','meal_plan'=>'Breakfast, Lunch, & Dinner','meal_city'=>'Ubud, Bali','meal_day'=>1800,'attractions'=>[['Tegallalang Rice Terraces','₱100'],['Tanah Lot Temple','₱300'],['Seminyak Beach','Free']]],
            'indonesia'       => ['code'=>'DPS','airline'=>'Garuda Indonesia GA 862','hotel'=>'Four Seasons Resort Bali at Sayan','hotel_stars'=>5,'hotel_type'=>'Suite','hotel_city'=>'Ubud, Bali','restaurant'=>'Locavore Restaurant (₱1,800)','meal_plan'=>'Breakfast, Lunch, & Dinner','meal_city'=>'Ubud, Bali','meal_day'=>1800,'attractions'=>[['Tegallalang Rice Terraces','₱100'],['Tanah Lot Temple','₱300'],['Seminyak Beach','Free']]],
            'kuala lumpur'    => ['code'=>'KUL','airline'=>'AirAsia Z2 511','hotel'=>'The Ritz-Carlton Kuala Lumpur','hotel_stars'=>5,'hotel_type'=>'Deluxe Room','hotel_city'=>'Kuala Lumpur, Malaysia','restaurant'=>'Jalan Alor Food Street (MYR 30)','meal_plan'=>'Breakfast, Lunch, & Dinner','meal_city'=>'Bukit Bintang, KL','meal_day'=>1200,'attractions'=>[['Petronas Twin Towers','₱300'],['Batu Caves','Free'],['KL Bird Park','₱500']]],
            'malaysia'        => ['code'=>'KUL','airline'=>'AirAsia Z2 511','hotel'=>'The Ritz-Carlton Kuala Lumpur','hotel_stars'=>5,'hotel_type'=>'Deluxe Room','hotel_city'=>'Kuala Lumpur, Malaysia','restaurant'=>'Jalan Alor Food Street (MYR 30)','meal_plan'=>'Breakfast, Lunch, & Dinner','meal_city'=>'Bukit Bintang, KL','meal_day'=>1200,'attractions'=>[['Petronas Twin Towers','₱300'],['Batu Caves','Free'],['KL Bird Park','₱500']]],
            'hong kong'       => ['code'=>'HKG','airline'=>'Cathay Pacific CX 911','hotel'=>'The Peninsula Hong Kong','hotel_stars'=>5,'hotel_type'=>'Deluxe Room','hotel_city'=>'Tsim Sha Tsui, Hong Kong','restaurant'=>'Tim Ho Wan Dim Sum (HKD 100)','meal_plan'=>'Breakfast, Lunch, & Dinner','meal_city'=>'Kowloon, Hong Kong','meal_day'=>2500,'attractions'=>[['Victoria Peak','₱800'],['Disneyland Hong Kong','₱3,500'],['Tian Tan Buddha','₱500']]],
            'tokyo'           => ['code'=>'NRT','airline'=>'Philippine Airlines PR 432','hotel'=>'Park Hyatt Tokyo','hotel_stars'=>5,'hotel_type'=>'Park Room','hotel_city'=>'Shinjuku, Tokyo','restaurant'=>'Ichiran Ramen (JPY 1,000)','meal_plan'=>'Breakfast, Lunch, & Dinner','meal_city'=>'Shinjuku, Tokyo','meal_day'=>3500,'attractions'=>[['Senso-ji Temple','Free'],['teamLab Borderless','₱2,000'],['Mt. Fuji Day Tour','₱3,000']]],
            'japan'           => ['code'=>'NRT','airline'=>'Philippine Airlines PR 432','hotel'=>'Park Hyatt Tokyo','hotel_stars'=>5,'hotel_type'=>'Park Room','hotel_city'=>'Shinjuku, Tokyo','restaurant'=>'Ichiran Ramen (JPY 1,000)','meal_plan'=>'Breakfast, Lunch, & Dinner','meal_city'=>'Shinjuku, Tokyo','meal_day'=>3500,'attractions'=>[['Senso-ji Temple','Free'],['teamLab Borderless','₱2,000'],['Mt. Fuji Day Tour','₱3,000']]],
            'osaka'           => ['code'=>'KIX','airline'=>'Cebu Pacific 5J 117','hotel'=>'The St. Regis Osaka','hotel_stars'=>5,'hotel_type'=>'Superior Room','hotel_city'=>'Chuo-ku, Osaka','restaurant'=>'Dotonbori Street Food (JPY 800)','meal_plan'=>'Breakfast, Lunch, & Dinner','meal_city'=>'Dotonbori, Osaka','meal_day'=>3000,'attractions'=>[['Osaka Castle','₱500'],['Universal Studios Japan','₱4,500'],['Namba Yasaka Shrine','Free']]],
            'seoul'           => ['code'=>'ICN','airline'=>'Korean Air KE 621','hotel'=>'Signiel Seoul','hotel_stars'=>5,'hotel_type'=>'Deluxe Room','hotel_city'=>'Jamsil, Seoul','restaurant'=>'Gwangjang Market Street Food (KRW 10,000)','meal_plan'=>'Breakfast, Lunch, & Dinner','meal_city'=>'Myeongdong, Seoul','meal_day'=>2500,'attractions'=>[['Gyeongbokgung Palace','₱500'],['N Seoul Tower','₱600'],['Myeongdong Shopping Street','Free']]],
            'korea'           => ['code'=>'ICN','airline'=>'Korean Air KE 621','hotel'=>'Signiel Seoul','hotel_stars'=>5,'hotel_type'=>'Deluxe Room','hotel_city'=>'Jamsil, Seoul','restaurant'=>'Gwangjang Market Street Food (KRW 10,000)','meal_plan'=>'Breakfast, Lunch, & Dinner','meal_city'=>'Myeongdong, Seoul','meal_day'=>2500,'attractions'=>[['Gyeongbokgung Palace','₱500'],['N Seoul Tower','₱600'],['Myeongdong Shopping Street','Free']]],
            'taipei'          => ['code'=>'TPE','airline'=>'EVA Air BR 261','hotel'=>'Grand Hyatt Taipei','hotel_stars'=>5,'hotel_type'=>'Deluxe Room','hotel_city'=>'Xinyi District, Taipei','restaurant'=>'Din Tai Fung (TWD 400)','meal_plan'=>'Breakfast, Lunch, & Dinner','meal_city'=>'Da\'an District, Taipei','meal_day'=>2000,'attractions'=>[['Taipei 101 Observatory','₱800'],['Jiufen Old Street','₱200'],['Taroko Gorge Day Tour','₱1,500']]],
            'taiwan'          => ['code'=>'TPE','airline'=>'EVA Air BR 261','hotel'=>'Grand Hyatt Taipei','hotel_stars'=>5,'hotel_type'=>'Deluxe Room','hotel_city'=>'Xinyi District, Taipei','restaurant'=>'Din Tai Fung (TWD 400)','meal_plan'=>'Breakfast, Lunch, & Dinner','meal_city'=>'Da\'an District, Taipei','meal_day'=>2000,'attractions'=>[['Taipei 101 Observatory','₱800'],['Jiufen Old Street','₱200'],['Taroko Gorge Day Tour','₱1,500']]],
            'dubai'           => ['code'=>'DXB','airline'=>'Emirates EK 332','hotel'=>'Burj Al Arab Jumeirah','hotel_stars'=>5,'hotel_type'=>'Deluxe Suite','hotel_city'=>'Jumeirah, Dubai','restaurant'=>'At.mosphere Burj Khalifa (AED 200)','meal_plan'=>'Breakfast, Lunch, & Dinner','meal_city'=>'Downtown Dubai','meal_day'=>5000,'attractions'=>[['Burj Khalifa Top Deck','₱2,500'],['Dubai Mall & Fountain','Free'],['Desert Safari','₱3,000']]],
            'uae'             => ['code'=>'DXB','airline'=>'Emirates EK 332','hotel'=>'Burj Al Arab Jumeirah','hotel_stars'=>5,'hotel_type'=>'Deluxe Suite','hotel_city'=>'Jumeirah, Dubai','restaurant'=>'At.mosphere Burj Khalifa (AED 200)','meal_plan'=>'Breakfast, Lunch, & Dinner','meal_city'=>'Downtown Dubai','meal_day'=>5000,'attractions'=>[['Burj Khalifa Top Deck','₱2,500'],['Dubai Mall & Fountain','Free'],['Desert Safari','₱3,000']]],
            'london'          => ['code'=>'LHR','airline'=>'British Airways BA 11','hotel'=>'The Savoy London','hotel_stars'=>5,'hotel_type'=>'Classic Room','hotel_city'=>'City of Westminster, London','restaurant'=>'The Ivy (GBP 50)','meal_plan'=>'Breakfast, Lunch, & Dinner','meal_city'=>'Covent Garden, London','meal_day'=>8000,'attractions'=>[['British Museum','Free'],['Tower of London','₱2,500'],['Buckingham Palace Gardens','₱600']]],
            'paris'           => ['code'=>'CDG','airline'=>'Air France AF 171','hotel'=>'Hotel Le Meurice','hotel_stars'=>5,'hotel_type'=>'Classic Room','hotel_city'=>'1st Arrondissement, Paris','restaurant'=>'Café de Flore (EUR 30)','meal_plan'=>'Breakfast, Lunch, & Dinner','meal_city'=>'Saint-Germain-des-Prés, Paris','meal_day'=>8000,'attractions'=>[['Eiffel Tower','₱1,500'],['Louvre Museum','₱1,000'],['Versailles Palace','₱2,000']]],
            'new york'        => ['code'=>'JFK','airline'=>'Philippine Airlines PR 126','hotel'=>'The Plaza Hotel New York','hotel_stars'=>5,'hotel_type'=>'Classic Room','hotel_city'=>'Midtown Manhattan, New York','restaurant'=>'Katz\'s Delicatessen (USD 25)','meal_plan'=>'Breakfast, Lunch, & Dinner','meal_city'=>'Lower East Side, NYC','meal_day'=>9000,'attractions'=>[['Statue of Liberty','₱1,500'],['Times Square','Free'],['Central Park','Free']]],
            'new york city'   => ['code'=>'JFK','airline'=>'Philippine Airlines PR 126','hotel'=>'The Plaza Hotel New York','hotel_stars'=>5,'hotel_type'=>'Classic Room','hotel_city'=>'Midtown Manhattan, New York','restaurant'=>'Katz\'s Delicatessen (USD 25)','meal_plan'=>'Breakfast, Lunch, & Dinner','meal_city'=>'Lower East Side, NYC','meal_day'=>9000,'attractions'=>[['Statue of Liberty','₱1,500'],['Times Square','Free'],['Central Park','Free']]],
            'sydney'          => ['code'=>'SYD','airline'=>'Qantas QF 21','hotel'=>'Park Hyatt Sydney','hotel_stars'=>5,'hotel_type'=>'Opera House View Room','hotel_city'=>'The Rocks, Sydney','restaurant'=>'Quay Restaurant (AUD 80)','meal_plan'=>'Breakfast, Lunch, & Dinner','meal_city'=>'Circular Quay, Sydney','meal_day'=>7000,'attractions'=>[['Sydney Opera House','₱1,500'],['Sydney Harbour Bridge Climb','₱6,000'],['Bondi Beach','Free']]],
            'australia'       => ['code'=>'SYD','airline'=>'Qantas QF 21','hotel'=>'Park Hyatt Sydney','hotel_stars'=>5,'hotel_type'=>'Opera House View Room','hotel_city'=>'The Rocks, Sydney','restaurant'=>'Quay Restaurant (AUD 80)','meal_plan'=>'Breakfast, Lunch, & Dinner','meal_city'=>'Circular Quay, Sydney','meal_day'=>7000,'attractions'=>[['Sydney Opera House','₱1,500'],['Sydney Harbour Bridge Climb','₱6,000'],['Bondi Beach','Free']]],
            'rome'            => ['code'=>'FCO','airline'=>'Qatar Airways QR 131','hotel'=>'Hotel Eden Rome','hotel_stars'=>5,'hotel_type'=>'Deluxe Room','hotel_city'=>'Via Veneto, Rome','restaurant'=>'Osteria dell\'Enoteca (EUR 35)','meal_plan'=>'Breakfast, Lunch, & Dinner','meal_city'=>'Trastevere, Rome','meal_day'=>7000,'attractions'=>[['Colosseum','₱1,500'],['Vatican Museums','₱2,000'],['Trevi Fountain','Free']]],
            'barcelona'       => ['code'=>'BCN','airline'=>'Qatar Airways QR 141','hotel'=>'W Barcelona','hotel_stars'=>5,'hotel_type'=>'Wonderful Sea View Room','hotel_city'=>'Barceloneta, Barcelona','restaurant'=>'La Boqueria Market (EUR 20)','meal_plan'=>'Breakfast, Lunch, & Dinner','meal_city'=>'Las Ramblas, Barcelona','meal_day'=>7000,'attractions'=>[['Sagrada Família','₱2,000'],['Park Güell','₱800'],['Camp Nou Tour','₱1,500']]],
            'amsterdam'       => ['code'=>'AMS','airline'=>'KLM KL 808','hotel'=>'Waldorf Astoria Amsterdam','hotel_stars'=>5,'hotel_type'=>'Classic Canal View Room','hotel_city'=>'Herengracht, Amsterdam','restaurant'=>'Restaurant Breitner (EUR 40)','meal_plan'=>'Breakfast, Lunch, & Dinner','meal_city'=>'Amsterdam Centre','meal_day'=>7000,'attractions'=>[['Rijksmuseum','₱1,500'],['Anne Frank House','₱1,000'],['Canal Boat Tour','₱800']]],
            'maldives'        => ['code'=>'MLE','airline'=>'Singapore Airlines SQ 471 + Transfer','hotel'=>'Soneva Jani Maldives','hotel_stars'=>5,'hotel_type'=>'Water Villa','hotel_city'=>'Noonu Atoll, Maldives','restaurant'=>'Fresh in the Garden (USD 50)','meal_plan'=>'Breakfast, Lunch, & Dinner','meal_city'=>'Noonu Atoll','meal_day'=>12000,'attractions'=>[['Snorkeling & Diving','₱2,000'],['Sunset Dolphin Cruise','₱1,500'],['Sandbank Picnic','₱3,000']]],
        ];

        // Match destination — check both aiTo and aiFrom
        $data = null;
        foreach ($lookup as $key => $d) {
            if (str_contains($dest, $key)) { $data = $d; break; }
        }

        // Generic fallback for any unlisted destination
        if (!$data) {
            $cityName = ucwords($this->aiTo ?: 'Destination');
            $isIntl   = $budget >= 50000;
            $mealDay  = $isIntl ? 3000 : 700;
            $data = [
                'code'        => $isIntl ? 'INTL' : 'DOM',
                'airline'     => $isIntl ? 'International Carrier · Direct Flight' : 'Cebu Pacific · Direct Flight',
                'hotel'       => "Grand Hotel {$cityName}",
                'hotel_stars' => $isIntl ? 4 : 3,
                'hotel_type'  => 'Deluxe Room',
                'hotel_city'  => $cityName,
                'restaurant'  => "Local Dining at {$cityName}",
                'meal_plan'   => 'Breakfast, Lunch, & Dinner',
                'meal_city'   => $cityName,
                'meal_day'    => $mealDay,
                'attractions' => [
                    ["{$cityName} City Tour", '₱300'],
                    ['Local Market Visit', 'Free'],
                    ['Heritage & Culture Walk', 'Free'],
                ],
            ];
        }

        // Budget allocation: transport 18%, accommodation 50%, food 28%, attractions 4%
        $transport     = (int) round($budget * 0.18);
        $accommodation = (int) round(($budget * 0.50 / $days)) * $days;
        $foodTotal     = $data['meal_day'] * $days;
        $attrTotal     = array_sum(array_map(fn($a) => is_numeric(str_replace(['₱',','], '', $a[1])) ? (int)str_replace(['₱',','], '', $a[1]) : 0, $data['attractions']));
        $totalEst      = $transport + $accommodation + $foodTotal + $attrTotal;

        $this->aiPackage = [
            'transport'     => ['label'=>'TRANSPORTATION','icon'=>'fa-solid fa-plane','from_code'=>'MNL','to_code'=>$data['code'],'detail'=>$data['airline'].' · Direct Flight · Round Trip','cost'=>$transport],
            'accommodation' => ['label'=>'ACCOMMODATION','icon'=>'fa-solid fa-bed','name'=>$data['hotel'],'stars'=>$data['hotel_stars'],'detail'=>$days.' Nights · '.$data['hotel_type'].' · '.$data['hotel_city'],'cost'=>$accommodation],
            'food'          => ['label'=>'FOOD & DINING','icon'=>'fa-solid fa-utensils','name'=>$data['restaurant'],'detail'=>$days.' Days · '.$data['meal_plan'].' · '.$data['meal_city'],'cost'=>$foodTotal],
            'attractions'   => ['label'=>'ATTRACTIONS','icon'=>'fa-solid fa-landmark','items'=>$data['attractions'],'cost'=>$attrTotal],
            'total'         => $totalEst,
            'budget'        => $budget,
            'pct'           => min(100, (int)round($totalEst / $budget * 100)),
        ];
    }

    public function proceedFromTripDetails(): void
    {
        $this->validate([
            'manualFrom' => 'required|string|min:2',
            'manualTo'   => 'required|string|min:2',
        ], [
            'manualFrom.required' => 'Please enter your origin city.',
            'manualTo.required'   => 'Please enter your destination.',
        ]);

        // Parse single budget field: "30,000 - 50,000" or "30000" or "30,000 to 50,000"
        $raw = $this->manualBudgetMin;
        if (preg_match('/(\d[\d,]*)\s*(?:[-–to]+)\s*(\d[\d,]*)/i', $raw, $m)) {
            $this->manualBudgetMin = preg_replace('/[^\d]/', '', $m[1]);
            $this->manualBudgetMax = preg_replace('/[^\d]/', '', $m[2]);
        } else {
            $single = preg_replace('/[^\d]/', '', $raw);
            $this->manualBudgetMin = $single;
            $this->manualBudgetMax = $single;
        }

        $this->step = 2;
        $this->searchManualFlights();
    }

    public function searchManualFlights(): void
    {
        set_time_limit(120);
        $this->flightLoading   = true;
        $this->flightResults   = [];
        $this->mcFlightStep    = false;
        $this->mcFlightResults = [];
        $this->selectedFlight  = null;
        $this->selectedMcFlight = null;

        $serp      = new SerpApiService();
        $fromCode  = $this->resolveCode($this->manualFrom);
        $toCode    = $this->resolveCode($this->manualTo);
        $depart    = $this->startDate ?: date('Y-m-d', strtotime('+7 days'));
        $return    = $this->flightTripType === 'round_trip' && $this->endDate ? $this->endDate : '';

        try {
            $data = $serp->searchFlightsRaw($fromCode, $toCode, $depart, $return);
            $this->flightResults = $data ?? [];
        } catch (\Throwable $e) {
            $this->flightResults = [];
        }

        $this->flightLoading = false;
    }

    public function selectFlight(int $index): void
    {
        $this->selectedFlight = $this->flightResults[$index] ?? null;

        // Multi-city: search leg 2 flights before going to accommodation
        if ($this->flightTripType === 'multi_city' && $this->mcTo && $this->mcStartDate) {
            $this->mcFlightStep    = true;
            $this->mcFlightResults = [];
            $this->mcFlightLoading = true;
            $serp      = new SerpApiService();
            $fromCode  = $this->resolveCode($this->manualTo);
            $toCode    = $this->resolveCode($this->mcTo);
            try {
                $this->mcFlightResults = $serp->searchFlightsRaw($fromCode, $toCode, $this->mcStartDate, $this->mcEndDate ?: null) ?? [];
            } catch (\Throwable $e) {
                $this->mcFlightResults = [];
            }
            $this->mcFlightLoading = false;
            return;
        }

        // Auto-detect scope from destination
        $intlKeywords = ['singapore','bangkok','phuket','bali','kuala lumpur','hong kong','tokyo','osaka','seoul','taipei','dubai','london','paris','new york','sydney','rome','barcelona','amsterdam','maldives','vietnam','hanoi','ho chi minh','jakarta','yangon','colombo','kathmandu','delhi','mumbai','beijing','shanghai','auckland'];
        $toLower = strtolower($this->manualTo);
        $isIntl  = false;
        foreach ($intlKeywords as $kw) {
            if (str_contains($toLower, $kw)) { $isIntl = true; break; }
        }
        $this->tripScope = $isIntl ? 'international' : 'local';
        $this->step = 3;
        $this->searchAccommodations();
    }

    public function selectMcFlight(int $index): void
    {
        $this->selectedMcFlight = $this->mcFlightResults[$index] ?? null;
        $this->mcFlightStep = false;

        $intlKeywords = ['singapore','bangkok','phuket','bali','kuala lumpur','hong kong','tokyo','osaka','seoul','taipei','dubai','london','paris','new york','sydney','rome','barcelona','amsterdam','maldives','vietnam','hanoi','ho chi minh','jakarta','yangon','colombo','kathmandu','delhi','mumbai','beijing','shanghai','auckland'];
        $toLower = strtolower($this->mcTo);
        $isIntl  = false;
        foreach ($intlKeywords as $kw) {
            if (str_contains($toLower, $kw)) { $isIntl = true; break; }
        }
        $this->tripScope = $isIntl ? 'international' : 'local';
        $this->step = 3;
        $this->searchAccommodations();
    }

    public function searchAccommodations(): void
    {
        set_time_limit(120);
        $this->hotelLoading = true;
        $this->hotelResults = [];
        $serp     = new SerpApiService();
        $checkIn  = $this->startDate ?: date('Y-m-d', strtotime('+7 days'));
        $checkOut = $this->endDate   ?: date('Y-m-d', strtotime('+12 days'));
        $nights   = max(1, \Carbon\Carbon::parse($checkIn)->diffInDays(\Carbon\Carbon::parse($checkOut)));
        try {
            $data = $serp->searchHotelsRaw($this->manualTo, $checkIn, $checkOut, $nights);
            $this->hotelResults = $data ?? [];
        } catch (\Throwable $e) {
            $this->hotelResults = [];
        }
        $this->hotelLoading = false;
    }

    public function selectAccommodation(int $index): void
    {
        $this->selectedHotel = $this->hotelResults[$index] ?? null;

        if ($this->flightTripType === 'multi_city' && $this->mcTo && $this->mcStartDate) {
            $this->mcHotelStep    = true;
            $this->mcHotelResults = [];
            $this->mcHotelLoading = true;
            $checkIn  = $this->mcStartDate;
            $checkOut = $this->mcEndDate ?: date('Y-m-d', strtotime($checkIn . ' +5 days'));
            $nights   = max(1, \Carbon\Carbon::parse($checkIn)->diffInDays(\Carbon\Carbon::parse($checkOut)));
            $serp = new SerpApiService();
            try {
                $this->mcHotelResults = $serp->searchHotelsRaw($this->mcTo, $checkIn, $checkOut, $nights) ?? [];
            } catch (\Throwable $e) {
                $this->mcHotelResults = [];
            }
            $this->mcHotelLoading = false;
            return;
        }

        $this->step = 4;
    }

    public function selectMcAccommodation(int $index): void
    {
        $this->selectedMcHotel = $this->mcHotelResults[$index] ?? null;
        $this->mcHotelStep = false;
        $this->step = 4;
    }

    public function swapCities(): void
    {
        [$this->manualFrom, $this->manualTo] = [$this->manualTo, $this->manualFrom];
    }

    public function saveDraft(): void
    {
        Trip::create([
            'user_id'      => auth()->id(),
            'destination'  => $this->manualTo ?: 'Draft',
            'start_date'   => $this->startDate ?: now()->toDateString(),
            'end_date'     => $this->endDate   ?: now()->toDateString(),
            'budget_limit' => $this->parseBudgetInput($this->manualBudgetMax ?: $this->manualBudgetMin),
            'travel_type'  => 'Solo',
            'num_travelers'=> 1,
        ]);
        session()->flash('success', 'Draft saved!');
        $this->redirect(route('trips.plan'), navigate: true);
    }

    private function parseBudgetInput(string $val): float
    {
        return (float) preg_replace('/[^\d.]/', '', $val);
    }

    public function selectScope(string $scope): void
    {
        $this->tripScope = $scope;
        $this->step = 4;
    }

    // ── Step 4 ─────────────────────────────────────────────
    public function selectDestination(int $id): void
    {
        $dest = Destination::findOrFail($id);
        $this->destinationId   = $dest->id;
        $this->destinationName = $dest->name;
        $this->step = 5;
    }

    // ── Step 4: calendar ───────────────────────────────────
    public function prevMonth(): void
    {
        if ($this->calMonth === 1) { $this->calMonth = 12; $this->calYear--; }
        else $this->calMonth--;
    }

    public function nextMonth(): void
    {
        if ($this->calMonth === 12) { $this->calMonth = 1; $this->calYear++; }
        else $this->calMonth++;
    }

    public function selectDay(string $date): void
    {
        if ($date < date('Y-m-d')) return;

        if (!$this->startDate || ($this->startDate && $this->endDate)) {
            $this->startDate = $date;
            $this->endDate   = '';
        } elseif ($date < $this->startDate) {
            $this->endDate   = $this->startDate;
            $this->startDate = $date;
        } else {
            $this->endDate = $date;
        }
    }

    public function proceedFromCalendar(): void
    {
        if (empty($this->startDate)) {
            $this->dispatch('calendar-validation-error');
            return;
        }

        if (empty($this->endDate)) {
            $this->endDate = $this->startDate;
        }

        $this->step = 6;
    }

    // ── Step 6: group + budget ─────────────────────────────
    public function selectGroup(string $group): void
    {
        if ($this->groupType === $group) {
            $this->groupType = '';
            $this->travelers = 1;
            return;
        }
        $this->groupType = $group;
        $this->travelers = match ($group) {
            'Solo'   => 1,
            'Couple' => 2,
            default  => max($this->travelers, 2),
        };
    }

    public function incrementTravelers(): void
    {
        if ($this->travelers < 20) $this->travelers++;
    }

    public function decrementTravelers(): void
    {
        $min = in_array($this->groupType, ['Family', 'Friends']) ? 2 : 1;
        if ($this->travelers > $min) $this->travelers--;
    }

    public function selectBudgetTier(string $tier): void
    {
        $this->budgetTier = $this->budgetTier === $tier ? '' : $tier;
    }

    public function calculateAndProceed(): void
    {
        set_time_limit(120);
        $missingGroup  = empty($this->groupType);
        $missingBudget = empty($this->budgetTier);

        if ($missingGroup || $missingBudget) {
            $this->dispatch('validation-error',
                missingGroup:  $missingGroup,
                missingBudget: $missingBudget,
            );
            return;
        }

        $this->calculateEstimate();
        $this->step = 7;
    }

    // ── Step 7: inline category editing ───────────────────
    public function startEditing(string $category): void
    {
        $this->editingCategory = $category;
    }

    public function stopEditing(): void
    {
        $this->editingCategory = '';
        $subtotal = $this->transportation + $this->accommodation + $this->food
                  + $this->attractions + $this->shopping;
        $this->emergency   = round($subtotal * 0.05, 2);
        $this->budgetLimit = round($subtotal + $this->emergency, 2);
    }

    // ── Confirm ────────────────────────────────────────────
    public function confirm(): mixed
    {
        $this->validate([
            'destinationName' => 'required|string',
            'startDate'       => 'required|date',
            'endDate'         => 'required|date|after_or_equal:startDate',
            'groupType'       => 'required|in:Solo,Couple,Family,Friends',
            'budgetTier'      => 'required|in:Shoestring,Mid-range,Luxury',
            'budgetLimit'     => 'required|numeric|min:1',
        ]);

        $trip = Trip::create([
            'user_id'       => auth()->id(),
            'destination'   => $this->destinationName,
            'start_date'    => $this->startDate,
            'end_date'      => $this->endDate,
            'num_travelers' => $this->travelers,
            'budget_limit'  => $this->budgetLimit,
            'travel_type'   => $this->groupType,
            'notes'         => "Budget tier: {$this->budgetTier}; Scope: {$this->tripScope}",
        ]);

        foreach ([
            'Transportation'      => $this->transportation,
            'Accommodation'       => $this->accommodation,
            'Food'                => $this->food,
            'Tourist Attractions' => $this->attractions,
            'Shopping'            => $this->shopping,
            'Emergency Funds'     => $this->emergency,
        ] as $cat => $amount) {
            TripBudget::create([
                'trip_id'        => $trip->id,
                'category'       => $cat,
                'estimated_cost' => $amount,
                'actual_spent'   => 0,
            ]);
        }

        SavingsGoal::create([
            'user_id'         => auth()->id(),
            'trip_id'         => $trip->id,
            'goal_name'       => $this->destinationName . ' Trip',
            'target_amount'   => $this->budgetLimit,
            'current_savings' => 0,
            'deadline'        => $this->startDate,
        ]);

        return $this->redirect(route('trips.dashboard', $trip), navigate: true);
    }

    // ── Computed properties ────────────────────────────────
    #[Computed]
    public function aiCurrency(): string
    {
        return $this->currencySymbol($this->aiTo);
    }

    #[Computed]
    public function destinations()
    {
        $userCountry = auth()->user()->country ?? 'Philippines';
        $query = Destination::orderBy('name');
        if ($this->tripScope === 'local') {
            $query->where('country', $userCountry);
        } else {
            $query->where('country', '!=', $userCountry);
        }
        if ($this->destSearch) {
            $query->where('name', 'like', "%{$this->destSearch}%");
        }
        return $query->get();
    }

    public function getDaysProperty(): int
    {
        if (!$this->startDate || !$this->endDate) return 0;
        return max(1, (int) Carbon::parse($this->startDate)->diffInDays($this->endDate));
    }

    public function getCalendarDaysProperty(): array
    {
        $first    = Carbon::createFromDate($this->calYear, $this->calMonth, 1);
        $total    = $first->daysInMonth;
        $startDow = $first->dayOfWeek;
        $today    = date('Y-m-d');

        $days = array_fill(0, $startDow, null);
        for ($d = 1; $d <= $total; $d++) {
            $date   = sprintf('%04d-%02d-%02d', $this->calYear, $this->calMonth, $d);
            $days[] = [
                'day'     => $d,
                'date'    => $date,
                'isPast'  => $date < $today,
                'isToday' => $date === $today,
                'isStart' => $date === $this->startDate,
                'isEnd'   => $date === $this->endDate,
                'inRange' => $this->startDate && $this->endDate
                             && $date > $this->startDate && $date < $this->endDate,
            ];
        }
        return $days;
    }

    public function getComfortLevelProperty(): string
    {
        return match ($this->budgetTier) {
            'Shoestring' => 'BUDGET',
            'Luxury'     => 'PREMIUM',
            default      => 'STANDARD',
        };
    }

    public function getSmartTipProperty(): string
    {
        return match ($this->budgetTier) {
            'Shoestring' => 'Opt for hostels, local street food, and free attractions to stretch your funds further.',
            'Luxury'     => 'Consider hiring a private guide for exclusive access and a personalized premium experience.',
            default      => 'Book accommodation 2+ weeks early to unlock better rates and ensure availability.',
        };
    }

    public function getVarianceProperty(): float
    {
        return round($this->budgetLimit * 0.05, 2);
    }

    public function getTravelersLabelProperty(): string
    {
        return $this->travelers . ' ' . match ($this->groupType) {
            'Family'  => ($this->travelers === 1 ? 'Person' : 'People'),
            'Friends' => ($this->travelers === 1 ? 'Person' : 'People'),
            'Couple'  => 'Adults',
            default   => 'Adult',
        };
    }

    private function calculateEstimate(): void
    {
        $days  = $this->getDaysProperty();
        $n     = $this->travelers;
        $scope = $this->tripScope ?: 'local';
        $r     = self::RATES[$scope][$this->budgetTier] ?? self::RATES['local']['Mid-range'];
        $rooms = max(1, (int) ceil($n / 2));

        $this->transportation = round(($r['transport_base'] + $r['transport_daily'] * $days) * $n, 2);
        $this->accommodation  = round($r['accommodation_night'] * $days * $rooms, 2);
        $this->food           = round($r['food_day'] * $days * $n, 2);
        $this->attractions    = round($r['attractions_day'] * $days * $n, 2);
        $this->shopping       = round($r['shopping_per_person'] * $n, 2);

        $subtotal = $this->transportation + $this->accommodation + $this->food
                  + $this->attractions + $this->shopping;
        $this->emergency   = round($subtotal * 0.05, 2);
        $this->budgetLimit = round($subtotal + $this->emergency, 2);
    }

    public function render()
    {
        return view('livewire.traveler.trip-planner-wizard');
    }
}
