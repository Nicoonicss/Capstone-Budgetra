<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;

class GeminiService
{
    private string $key;
    private string $endpoint;

    public function __construct()
    {
        $this->key      = config('services.gemini.key');
        $this->endpoint = config('services.gemini.endpoint');
    }

    public function generate(string $prompt): ?string
    {
        $response = Http::timeout(30)->post("{$this->endpoint}?key={$this->key}", [
            'contents' => [
                ['parts' => [['text' => $prompt]]],
            ],
            'generationConfig' => [
                'temperature'     => round(0.7 + (mt_rand(-2, 3) * 0.1), 1), // 0.5–1.0 for variety
                'maxOutputTokens' => 2048,
            ],
        ]);

        if (!$response->successful()) {
            return null;
        }

        return data_get($response->json(), 'candidates.0.content.parts.0.text');
    }

    public function planTrip(string $userPrompt): ?array
    {
        $prompt = <<<PROMPT
You are a Philippine travel planner AI. Read the traveler's input, extract all details, then generate a realistic trip package. Return JSON only — no markdown, no explanation, no extra text.

Traveler's input: "{$userPrompt}"

Instructions:
1. Extract: origin city (default "Manila" if not mentioned), destination city, budget (min and max in PHP pesos — if single value use it for both), travel start date, travel end date, and number of days.
2. Generate a realistic trip package using REAL hotels, airlines, restaurants, and attractions that exist at the destination.
3. ALL costs combined must NOT exceed the budget_max.
4. Budget split suggestion: transport 18%, accommodation 50%, food 28%, attractions 4%.
5. Use correct Philippine IATA airport codes (MNL=Manila, CEB=Cebu City, DVO=Davao, BCD=Bacolod, ILO=Iloilo, ZAM=Zamboanga, KLO=Kalibo/Boracay, MPH=Malay/Boracay, TAG=Tagbilaran/Bohol, PPS=Puerto Princesa, CGY=Cagayan de Oro, GES=General Santos, etc.).
6. Dates: date_from format "Mon D" (e.g. "Jul 16"), date_to format "Mon D, YYYY" (e.g. "Jul 21, 2026").

Return ONLY this JSON:
{
  "from": "origin city name",
  "to": "destination city name",
  "budget_min": number,
  "budget_max": number,
  "date_from": "Mon D",
  "date_to": "Mon D, YYYY",
  "days": number,
  "transport": {
    "from_code": "IATA",
    "to_code": "IATA",
    "detail": "Airline name · Direct Flight · Round Trip",
    "cost": number
  },
  "accommodation": {
    "name": "Hotel name",
    "stars": number,
    "detail": "N Nights · Room type · City",
    "cost": number
  },
  "food": {
    "name": "Restaurant name (₱amount/pax)",
    "detail": "N Days · Breakfast, Lunch & Dinner · City",
    "cost": number
  },
  "attractions": {
    "items": [["Attraction name", "Free or ₱amount"], ["Attraction name", "Free or ₱amount"]],
    "cost": number
  },
  "total": number,
  "budget": number,
  "pct": number
}
PROMPT;

        $raw = $this->generate($prompt);
        if (!$raw) return null;

        $raw = preg_replace('/```json\s*/i', '', $raw);
        $raw = preg_replace('/```\s*/i', '', $raw);
        $raw = trim($raw);

        return json_decode($raw, true);
    }

    public function enrichPackage(array $package, string $destination, int $days, int $budget): ?array
    {
        $json = json_encode($package, JSON_UNESCAPED_UNICODE);
        $prompt = <<<PROMPT
You are a Philippine travel expert. A trip package has been assembled from live search data for {$destination} ({$days} days, ₱{$budget} budget). Enrich and fix missing or placeholder values only — do NOT change costs or items that already look real.

Current package JSON:
{$json}

Rules:
1. If hotel name is generic (e.g. "Hotel in {$destination}"), replace with a real hotel name that exists in {$destination}.
2. If hotel room type is "Standard Room" and stars ≥ 4, upgrade to "Deluxe Room" or "Superior Room".
3. If any attraction is listed as "Free" but has a known entrance fee in the Philippines, add the real price as "₱NNN".
4. If food detail city says "Philippines" or is empty, replace with "{$destination}".
5. Keep all numeric costs exactly as-is — never change any number.
6. Return the same JSON structure with only the string fields fixed. Return JSON only, no markdown.
PROMPT;

        $raw = $this->generate($prompt);
        if (!$raw) return null;

        $raw = preg_replace('/```json\s*/i', '', $raw);
        $raw = preg_replace('/```\s*/i', '', $raw);
        $decoded = json_decode(trim($raw), true);

        return is_array($decoded) ? $decoded : null;
    }

    public function buildItinerary(string $destination, int $days, string $dateFrom, array $attractions = []): ?string
    {
        $attrList = implode(', ', array_column($attractions, 0));
        $prompt = <<<PROMPT
Create a {$days}-day itinerary for {$destination} starting {$dateFrom}.
Include morning, afternoon, and evening activities each day.
Attractions to include if possible: {$attrList}.
Format as plain text with Day 1:, Day 2:, etc. headings. Keep it concise.
PROMPT;

        return $this->generate($prompt);
    }

    public function parseReceipt(string $ocrText): ?array
    {
        $prompt = <<<PROMPT
Extract expense data from this OCR receipt text and return JSON only (no markdown):
"{$ocrText}"

Return:
{
  "merchant": "store/restaurant name or null",
  "amount": number or null,
  "date": "YYYY-MM-DD or null",
  "category": "one of: Food, Transport, Accommodation, Entertainment, Shopping, Other",
  "items": ["item1", "item2"] or []
}
PROMPT;

        $raw = $this->generate($prompt);
        if (!$raw) return null;

        $raw = preg_replace('/```json\s*/i', '', $raw);
        $raw = preg_replace('/```\s*/i', '', $raw);

        return json_decode(trim($raw), true);
    }
}
