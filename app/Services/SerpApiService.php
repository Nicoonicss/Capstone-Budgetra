<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;

class SerpApiService
{
    private string $key;

    // Coordinates for major destinations — used for 15km radius searches
    // Format: [lat, lng]
    private array $coords = [
        // Philippine cities
        'manila'           => [14.5995, 120.9842],
        'makati'           => [14.5547, 121.0244],
        'quezon city'      => [14.6760, 121.0437],
        'cebu'             => [10.3157, 123.8854],
        'cebu city'        => [10.3157, 123.8854],
        'davao'            => [7.1907,  125.4553],
        'davao city'       => [7.1907,  125.4553],
        'boracay'          => [11.9674, 121.9248],
        'bohol'            => [9.8500,  124.1435],
        'palawan'          => [9.8349,  118.7384],
        'puerto princesa'  => [9.7392,  118.7353],
        'el nido'          => [11.1784, 119.4074],
        'coron'            => [12.0031, 120.2040],
        'siargao'          => [9.8490,  126.0458],
        'bacolod'          => [10.6765, 122.9509],
        'iloilo'           => [10.7202, 122.5621],
        'iloilo city'      => [10.7202, 122.5621],
        'zamboanga'        => [6.9214,  122.0790],
        'cagayan de oro'   => [8.4542,  124.6319],
        'general santos'   => [6.1164,  125.1716],
        'tagaytay'         => [14.1153, 120.9621],
        'baguio'           => [16.4023, 120.5960],
        'vigan'            => [17.5747, 120.3869],
        'batangas'         => [13.7565, 121.0583],
        'tacloban'         => [11.2543, 124.9900],
        'dumaguete'        => [9.3076,  123.3080],
        'surigao'          => [9.7527,  125.4874],
        'camiguin'         => [9.1737,  124.7197],
        'siquijor'         => [9.2100,  123.5100],
        'batanes'          => [20.4487, 121.9700],
        'sagada'           => [17.0880, 120.9010],
        'pagudpud'         => [18.5600, 120.7900],
        'laoag'            => [18.1977, 120.5937],
        'puerto galera'    => [13.5021, 120.9539],
        // International
        'singapore'        => [1.3521,  103.8198],
        'bangkok'          => [13.7563, 100.5018],
        'phuket'           => [7.8804,  98.3923],
        'bali'             => [-8.3405, 115.0920],
        'kuala lumpur'     => [3.1390,  101.6869],
        'hong kong'        => [22.3193, 114.1694],
        'tokyo'            => [35.6762, 139.6503],
        'osaka'            => [34.6937, 135.5023],
        'seoul'            => [37.5665, 126.9780],
        'taipei'           => [25.0330, 121.5654],
        'dubai'            => [25.2048, 55.2708],
        'london'           => [51.5074, -0.1278],
        'paris'            => [48.8566, 2.3522],
        'new york'         => [40.7128, -74.0060],
        'new york city'    => [40.7128, -74.0060],
        'sydney'           => [-33.8688, 151.2093],
        'rome'             => [41.9028, 12.4964],
        'barcelona'        => [41.3851, 2.1734],
        'amsterdam'        => [52.3676, 4.9041],
        'maldives'         => [3.2028,  73.2207],
    ];

    public function __construct()
    {
        $this->key = config('services.serpapi.key');
    }

    // Returns "@lat,lng,14z" string for 15km radius (~zoom 12-13)
    private function ll(string $destination): ?string
    {
        $key = strtolower(trim($destination));
        if (!isset($this->coords[$key])) return null;
        [$lat, $lng] = $this->coords[$key];
        return "@{$lat},{$lng},13z";  // zoom 13 ≈ 15km radius
    }

    // ── Raw flight list for manual selection UI ───────────────────────────
    public function searchFlightsRaw(
        string $fromCode,
        string $toCode,
        string $departDate,
        string $returnDate = ''
    ): ?array {
        $params = [
            'engine'        => 'google_flights',
            'departure_id'  => $fromCode,
            'arrival_id'    => $toCode,
            'outbound_date' => $departDate,
            'currency'      => 'PHP',
            'hl'            => 'en',
            'api_key'       => $this->key,
        ];
        if ($returnDate) {
            $params['return_date'] = $returnDate;
            $params['type']        = '1';
        } else {
            $params['type'] = '2';
        }

        $response = Http::timeout(20)->get('https://serpapi.com/search', $params);
        if (!$response->successful()) return null;

        $data    = $response->json();
        $raw     = array_merge($data['best_flights'] ?? [], $data['other_flights'] ?? []);
        if (empty($raw)) return null;

        $results = [];
        foreach ($raw as $item) {
            $flight  = $item['flights'][0] ?? [];
            $airline = $flight['airline'] ?? 'Airline';
            $logo    = $flight['airline_logo'] ?? null;
            $num     = $flight['flight_number'] ?? '';
            $depart  = $flight['departure_airport']['time'] ?? '';
            $arrive  = $flight['arrival_airport']['time'] ?? '';
            $dep_id  = $flight['departure_airport']['id'] ?? $fromCode;
            $arr_id  = $flight['arrival_airport']['id'] ?? $toCode;
            $dur     = $item['total_duration'] ?? ($flight['duration'] ?? 0);
            $price   = (int)($item['price'] ?? 0);
            $type    = isset($params['return_date']) ? 'Round Trip' : 'One Way';
            $bags    = !empty($item['carbon_emissions']) ? 'Carry-on baggage included' : 'Carry-on baggage included';

            if (!$price) continue;

            $results[] = [
                'airline'  => $airline,
                'logo'     => $logo,
                'number'   => $num,
                'depart'   => $depart,
                'arrive'   => $arrive,
                'dep_id'   => $dep_id,
                'arr_id'   => $arr_id,
                'duration' => $dur,
                'price'    => $price,
                'type'     => $type,
                'bags'     => $bags,
            ];
        }

        return $results ?: null;
    }

    // ── Flights ────────────────────────────────────────────────────────────
    public function searchFlights(
        string $fromCode,
        string $toCode,
        string $departDate,
        string $returnDate = '',
        int    $gen = 0,
        int    $budgetCap = 0
    ): ?array {
        $params = [
            'engine'        => 'google_flights',
            'departure_id'  => $fromCode,
            'arrival_id'    => $toCode,
            'outbound_date' => $departDate,
            'currency'      => 'PHP',
            'hl'            => 'en',
            'api_key'       => $this->key,
        ];

        if ($returnDate) {
            $params['return_date'] = $returnDate;
            $params['type']        = '1';
        } else {
            $params['type'] = '2';
        }

        $response = Http::timeout(20)->get('https://serpapi.com/search', $params);
        if (!$response->successful()) return null;

        $data        = $response->json();
        $bestFlights = $data['best_flights'] ?? $data['other_flights'] ?? [];
        if (empty($bestFlights)) return null;

        // Filter out flights exceeding budget cap
        if ($budgetCap > 0) {
            $bestFlights = array_values(array_filter($bestFlights, fn($f) => ($f['price'] ?? 0) <= $budgetCap));
        }
        if (empty($bestFlights)) return null;

        // Sort: gen=0 most expensive within budget (best quality), gen>0 cheaper options
        usort($bestFlights, fn($a, $b) => $gen === 0
            ? ($b['price'] ?? 0) <=> ($a['price'] ?? 0)
            : ($a['price'] ?? 0) <=> ($b['price'] ?? 0)
        );
        // gen=0: top 3 (most expensive that fit), gen>0: slide into cheaper tier
        $offset = $gen === 0 ? 0 : min($gen, max(0, count($bestFlights) - 3));
        $pool   = array_slice($bestFlights, $offset, 3);
        $top    = $pool[array_rand($pool)];
        $flight  = $top['flights'][0] ?? [];
        $airline = trim(($flight['airline'] ?? 'Airline') . ' ' . ($flight['flight_number'] ?? ''));
        $price   = (int)($top['price'] ?? 0);

        return [
            'detail' => $airline . ' · ' . ($returnDate ? 'Round Trip' : 'One Way'),
            'cost'   => $price,
        ];
    }

    // ── Raw hotel list for manual accommodation selection ─────────────────
    public function searchHotelsRaw(string $destination, string $checkIn, string $checkOut, int $nights): ?array
    {
        $params = [
            'engine'         => 'google_hotels',
            'q'              => $destination,
            'check_in_date'  => $checkIn,
            'check_out_date' => $checkOut,
            'currency'       => 'PHP',
            'hl'             => 'en',
            'gl'             => 'ph',
            'api_key'        => $this->key,
        ];
        $ll = $this->ll($destination);
        if ($ll) $params['ll'] = $ll;

        $response = Http::timeout(20)->get('https://serpapi.com/search', $params);
        if (!$response->successful()) return null;

        $data   = $response->json();
        $hotels = $data['properties'] ?? [];
        if (empty($hotels)) return null;

        $results = [];
        foreach ($hotels as $h) {
            $name    = $h['name'] ?? null;
            if (!$name) continue;
            $nightly = (int)preg_replace('/[^\d]/', '', $h['rate_per_night']['lowest'] ?? '0');
            $total   = (int)preg_replace('/[^\d]/', '', $h['total_rate']['lowest']     ?? '0');
            $cost    = $total > 0 ? $total : ($nightly > 0 ? $nightly * $nights : 0);
            if ($cost <= 0) continue;
            $stars   = (int)($h['hotel_class'] ?? $h['stars'] ?? 3);
            $image   = $h['images'][0]['thumbnail'] ?? $h['thumbnail'] ?? null;
            $dist    = $h['nearby_places'][0]['transportations'][0]['duration'] ?? null;
            $results[] = [
                'name'    => $name,
                'stars'   => $stars,
                'image'   => $image,
                'nightly' => $nightly ?: (int)round($cost / $nights),
                'total'   => $cost,
                'nights'  => $nights,
                'dist'    => $dist,
                'type'    => $h['type'] ?? 'Hotel',
            ];
        }
        return $results ?: null;
    }

    // ── Hotels — within 15km of destination ───────────────────────────────
    public function searchHotels(
        string $destination,
        string $checkIn,
        string $checkOut,
        int    $nights,
        int    $gen = 0,
        int    $budgetCap = 0
    ): ?array {
        $params = [
            'engine'         => 'google_hotels',
            'q'              => $destination,
            'check_in_date'  => $checkIn,
            'check_out_date' => $checkOut,
            'currency'       => 'PHP',
            'hl'             => 'en',
            'gl'             => 'ph',
            'api_key'        => $this->key,
        ];

        // Add coordinates for tighter radius-based results
        $ll = $this->ll($destination);
        if ($ll) $params['ll'] = $ll;

        $response = Http::timeout(20)->get('https://serpapi.com/search', $params);
        if (!$response->successful()) return null;

        $data   = $response->json();
        $hotels = $data['properties'] ?? [];
        if (empty($hotels)) return null;

        // Build valid hotel list with cost, filter by budget cap
        $valid = [];
        foreach ($hotels as $h) {
            $name    = $h['name'] ?? null;
            if (!$name) continue;

            $nightly = (int)preg_replace('/[^\d]/', '', $h['rate_per_night']['lowest'] ?? '0');
            $total   = (int)preg_replace('/[^\d]/', '', $h['total_rate']['lowest']     ?? '0');
            $cost    = $total > 0 ? $total : ($nightly > 0 ? $nightly * $nights : 0);
            if ($cost <= 0) continue;
            if ($budgetCap > 0 && $cost > $budgetCap) continue; // skip over budget

            $stars = (int)($h['hotel_class'] ?? $h['stars'] ?? 3);
            $highlights = $h['room_highlights'] ?? [];
            $type = '';
            foreach ($highlights as $hl) {
                if (is_string($hl) && strlen($hl) > 3) { $type = $hl; break; }
                if (is_array($hl))  { $type = $hl['highlighted_text'] ?? $hl[0] ?? ''; break; }
            }
            $type = $type ?: 'Standard Room';

            $valid[] = [
                'name'   => $name,
                'stars'  => $stars,
                'detail' => $nights . ' Nights · ' . $type . ' · ' . $destination,
                'cost'   => $cost,
            ];
        }

        if (empty($valid)) return null;

        // gen=0: sort most expensive first (best quality within budget)
        // gen>0: sort cheapest first (budget-friendly alternatives)
        usort($valid, fn($a, $b) => $gen === 0
            ? $b['cost'] <=> $a['cost']
            : $a['cost'] <=> $b['cost']
        );
        $offset = $gen === 0 ? 0 : min($gen, max(0, count($valid) - 3));
        $pool   = array_slice($valid, $offset, 3);
        return $pool[array_rand($pool)];
    }

    // ── Food & Dining — top-rated within 15km ─────────────────────────────
    public function searchRestaurants(string $destination, int $days, int $budgetTotal, int $gen = 0): ?array
    {
        $params = [
            'engine'  => 'google_maps',
            'q'       => 'best restaurants ' . $destination,
            'type'    => 'search',
            'hl'      => 'en',
            'api_key' => $this->key,
        ];

        $ll = $this->ll($destination);
        if ($ll) $params['ll'] = $ll;

        $response = Http::timeout(20)->get('https://serpapi.com/search', $params);
        if (!$response->successful()) return null;

        $data    = $response->json();
        $results = $data['local_results'] ?? [];
        if (empty($results)) return null;

        // First gen: highest-rated (premium). Regenerate: rotate through mid-tier options.
        usort($results, fn($a, $b) => ($b['rating'] ?? 0) <=> ($a['rating'] ?? 0));
        $offset = $gen === 0 ? 0 : min($gen, count($results) - 5);
        $pool   = array_slice($results, max(0, $offset), 5);
        $top    = $pool[array_rand($pool)];

        $name    = $top['title'] ?? 'Local Restaurant';
        $address = $top['address'] ?? '';
        // Extract city: use second-to-last segment, skip country-level tail
        $parts = array_filter(array_map('trim', explode(',', $address)));
        $parts = array_values($parts);
        $skip  = ['philippines', 'thailand', 'singapore', 'malaysia', 'indonesia', 'japan', 'korea', 'taiwan', 'australia', 'usa', 'uk', 'uae'];
        $city  = $destination; // default
        foreach (array_reverse($parts) as $part) {
            if (!in_array(strtolower($part), $skip) && strlen($part) > 2) {
                $city = $part; break;
            }
        }

        // gen=0: full food budget. gen>0: reduce by 15% per regeneration (cheaper dining)
        $actualBudget = $gen === 0
            ? $budgetTotal
            : (int)round($budgetTotal * max(0.5, 1 - ($gen * 0.15)));
        $perDay = (int)round($actualBudget / $days);

        return [
            'name'   => $name,
            'detail' => $days . ' Days · Breakfast, Lunch, & Dinner · ' . $city,
            'cost'   => $actualBudget,
            'perDay' => $perDay,
        ];
    }

    // ── Attractions — google_maps ──────────────────────────────────────────
    public function searchAttractions(string $destination, int $gen = 0): ?array
    {
        $params = [
            'engine'  => 'google_maps',
            'q'       => 'tourist attractions things to do in ' . $destination,
            'type'    => 'search',
            'hl'      => 'en',
            'api_key' => $this->key,
        ];

        $ll = $this->ll($destination);
        if ($ll) $params['ll'] = $ll;

        $response = Http::timeout(20)->get('https://serpapi.com/search', $params);
        if (!$response->successful()) return null;

        $data    = $response->json();
        $results = $data['local_results'] ?? [];
        if (empty($results)) return null;

        usort($results, fn($a, $b) => ($b['rating'] ?? 0) <=> ($a['rating'] ?? 0));

        // First gen: top attractions. Regenerate: slide window down the list.
        $offset = $gen === 0 ? 0 : min($gen * 3, max(0, count($results) - 6));
        $pool   = array_slice($results, $offset, 8);
        shuffle($pool);
        $items = [];
        foreach (array_slice($pool, 0, 3) as $r) {
            $title = $r['title'] ?? null;
            if (!$title) continue;
            $items[] = [$title, 'Free'];
        }
        return $items ?: null;
    }
}
