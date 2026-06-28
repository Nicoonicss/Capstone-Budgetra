<?php
/**
 * OCR simulation endpoint.
 * Accepts a receipt image via AJAX POST, returns JSON with extracted data.
 * Amounts are returned in the user's local currency.
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/currencies.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$cur_rate   = (float)($_SESSION['user_currency_rate'] ?? 1.0);
$cur_symbol = $_SESSION['user_currency_symbol'] ?? '$';

if (empty($_FILES['receipt']) || $_FILES['receipt']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => 'No file received']);
    exit;
}

// ── Receipt templates (base amounts in USD) ──────────────────────────────
$templates = [
    'Food' => [
        ['merchant' => 'Local Restaurant', 'usd_min' => 5,  'usd_max' => 40],
        ['merchant' => 'Café & Bakery',    'usd_min' => 3,  'usd_max' => 15],
        ['merchant' => 'Street Food Stall','usd_min' => 2,  'usd_max' => 8],
        ['merchant' => 'Seafood Grill',    'usd_min' => 12, 'usd_max' => 55],
        ['merchant' => 'Fast Food Outlet', 'usd_min' => 4,  'usd_max' => 12],
        ['merchant' => 'Rooftop Restaurant','usd_min'=> 20, 'usd_max' => 80],
    ],
    'Transportation' => [
        ['merchant' => 'Grab / Taxi',        'usd_min' => 3,  'usd_max' => 20],
        ['merchant' => 'Bus Ticket',          'usd_min' => 1,  'usd_max' => 8],
        ['merchant' => 'Airline Booking',     'usd_min' => 60, 'usd_max' => 350],
        ['merchant' => 'Ferry Ticket',        'usd_min' => 5,  'usd_max' => 30],
        ['merchant' => 'Train / MRT',         'usd_min' => 1,  'usd_max' => 6],
        ['merchant' => 'Car Rental',          'usd_min' => 30, 'usd_max' => 120],
    ],
    'Accommodation' => [
        ['merchant' => 'Hotel Check-out',     'usd_min' => 40,  'usd_max' => 250],
        ['merchant' => 'AirBnb Booking',      'usd_min' => 30,  'usd_max' => 150],
        ['merchant' => 'Resort Stay',         'usd_min' => 80,  'usd_max' => 400],
        ['merchant' => 'Hostel',              'usd_min' => 10,  'usd_max' => 40],
        ['merchant' => 'Inn & Suites',        'usd_min' => 25,  'usd_max' => 90],
    ],
    'Shopping' => [
        ['merchant' => 'SM / Ayala Mall',     'usd_min' => 10,  'usd_max' => 120],
        ['merchant' => 'Souvenir Shop',       'usd_min' => 5,   'usd_max' => 40],
        ['merchant' => 'Duty Free',           'usd_min' => 20,  'usd_max' => 200],
        ['merchant' => 'Clothing Store',      'usd_min' => 15,  'usd_max' => 90],
        ['merchant' => 'Grocery / Mart',      'usd_min' => 8,   'usd_max' => 60],
    ],
    'Activities' => [
        ['merchant' => 'Island Hopping Tour', 'usd_min' => 15,  'usd_max' => 60],
        ['merchant' => 'Museum Entry',        'usd_min' => 3,   'usd_max' => 15],
        ['merchant' => 'Theme Park Ticket',   'usd_min' => 10,  'usd_max' => 50],
        ['merchant' => 'Snorkeling Package',  'usd_min' => 12,  'usd_max' => 45],
        ['merchant' => 'City Tour',           'usd_min' => 8,   'usd_max' => 35],
        ['merchant' => 'Water Park',          'usd_min' => 10,  'usd_max' => 30],
    ],
];

// ── Detect category from filename ──────────────────────────────────────
$filename = strtolower($_FILES['receipt']['name']);
$category = 'Food'; // default

$keyword_map = [
    'hotel|inn|resort|airbnb|hostel|accommodation|room|stay' => 'Accommodation',
    'grab|taxi|uber|bus|train|mrt|airline|flight|ferry|boat|car' => 'Transportation',
    'mall|shop|store|duty|souvenir|grocery|mart|fashion' => 'Shopping',
    'tour|museum|park|beach|island|snorkel|dive|ticket|activity' => 'Activities',
    'restaurant|cafe|food|eat|dining|pizza|sushi|bbq|grill|coffee' => 'Food',
];

foreach ($keyword_map as $pattern => $cat) {
    if (preg_match('/(' . $pattern . ')/', $filename)) {
        $category = $cat;
        break;
    }
}

// ── Pick a random template from the detected category ──────────────────
$tpl   = $templates[$category][array_rand($templates[$category])];

// Amount in local currency (USD base × rate, rounded to a "clean" value)
$usd_raw   = $tpl['usd_min'] + mt_rand(0, 100) / 100 * ($tpl['usd_max'] - $tpl['usd_min']);
$local_raw = $usd_raw * $cur_rate;

// Round to nearest sensible value
if ($cur_rate >= 1000) {
    $local_amount = round($local_raw / 100) * 100;         // IDR, VND etc.
} elseif ($cur_rate >= 10) {
    $local_amount = round($local_raw / 5) * 5;             // PHP, THB, INR etc.
} else {
    $local_amount = round($local_raw * 100) / 100;         // USD, EUR, SGD etc.
}
$local_amount = max(1, $local_amount);

echo json_encode([
    'success'     => true,
    'amount'      => $local_amount,
    'description' => $tpl['merchant'],
    'category'    => $category,
    'date'        => date('Y-m-d'),
    'confidence'  => mt_rand(88, 98),
    'currency'    => $cur_symbol,
]);
