<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/currencies.php';

header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$dest        = trim($_GET['destination'] ?? '');
$group       = $_GET['group']       ?? 'Solo';
$budget_tier = $_GET['budget_tier'] ?? 'Mid-range';
$days        = max(1, intval($_GET['days'] ?? 5));
$travelers   = max(1, intval($_GET['travelers'] ?? 1));

$cur_rate   = (float)($_SESSION['user_currency_rate'] ?? 1.0);
if ($cur_rate <= 0) $cur_rate = 1.0;
$cur_symbol = $_SESSION['user_currency_symbol'] ?? '$';

if (empty($dest)) {
    echo json_encode(['success' => false]);
    exit;
}

// Base presets (USD, per 5 days, 1 traveler)
$presets = [
    'Shoestring/Backpacker' => ['transport' => 400,  'accom' => 350,  'food' => 200,  'attractions' => 100,  'emergency' => 80],
    'Mid-range'             => ['transport' => 850,  'accom' => 800,  'food' => 400,  'attractions' => 250,  'emergency' => 150],
    'Luxury/Premium'        => ['transport' => 2200, 'accom' => 2800, 'food' => 1200, 'attractions' => 600,  'emergency' => 400],
];

// Cost multipliers keyed by destination keyword (relative to global average = 1.0)
$dest_multipliers = [
    // Southeast Asia
    'bali'          => 0.85, 'indonesia'     => 0.85,
    'thailand'      => 0.90, 'bangkok'       => 0.90, 'chiang mai'   => 0.85, 'phuket'      => 0.92,
    'vietnam'       => 0.80, 'hanoi'         => 0.80, 'ho chi minh'  => 0.82, 'da nang'     => 0.82,
    'cambodia'      => 0.78, 'siem reap'     => 0.78, 'phnom penh'   => 0.75,
    'myanmar'       => 0.75, 'laos'          => 0.72,
    'malaysia'      => 0.88, 'kuala lumpur'  => 0.88, 'penang'       => 0.85,
    'singapore'     => 1.35,
    // Philippines (local destinations)
    'philippines'   => 0.88, 'manila'        => 0.88,
    'cebu'          => 0.85, 'bohol'         => 0.82, 'palawan'      => 0.85,
    'boracay'       => 0.90, 'siargao'       => 0.80, 'davao'        => 0.80,
    'batangas'      => 0.78, 'tagaytay'      => 0.82, 'iloilo'       => 0.80,
    'dumaguete'     => 0.80, 'sagada'        => 0.75, 'vigan'        => 0.75,
    'batad'         => 0.72, 'coron'         => 0.84, 'el nido'      => 0.86,
    // East Asia
    'japan'         => 1.20, 'tokyo'         => 1.25, 'kyoto'        => 1.20, 'osaka'       => 1.18,
    'korea'         => 1.15, 'seoul'         => 1.18, 'busan'        => 1.10,
    'china'         => 1.05, 'beijing'       => 1.08, 'shanghai'     => 1.10,
    'hong kong'     => 1.30, 'taiwan'        => 1.05, 'taipei'       => 1.05,
    // South Asia
    'india'         => 0.75, 'new delhi'     => 0.75, 'mumbai'       => 0.80, 'goa'         => 0.82,
    'nepal'         => 0.70, 'sri lanka'     => 0.80,
    // Middle East
    'dubai'         => 1.40, 'abu dhabi'     => 1.35, 'qatar'        => 1.30,
    // Europe
    'paris'         => 1.50, 'france'        => 1.45,
    'london'        => 1.60, 'uk'            => 1.55,
    'rome'          => 1.35, 'italy'         => 1.30,
    'barcelona'     => 1.30, 'spain'         => 1.25,
    'amsterdam'     => 1.40, 'germany'       => 1.35, 'berlin'       => 1.30,
    'prague'        => 1.10, 'budapest'      => 1.05,
    'greece'        => 1.20, 'athens'        => 1.20, 'santorini'    => 1.35,
    // Americas
    'new york'      => 1.55, 'usa'           => 1.45, 'los angeles'  => 1.50,
    'mexico'        => 0.95, 'cancun'        => 1.00,
    'brazil'        => 1.00, 'peru'          => 0.88,
    // Oceania
    'australia'     => 1.45, 'sydney'        => 1.50, 'melbourne'    => 1.45,
    'new zealand'   => 1.40,
    // Africa
    'south africa'  => 0.90, 'cape town'     => 0.92,
    'egypt'         => 0.85, 'cairo'         => 0.85,
    'morocco'       => 0.88, 'marrakech'     => 0.88,
];

if (!in_array($group, ['Solo', 'Couple', 'Family', 'Friends'])) $group = 'Solo';
if (!in_array($budget_tier, ['Shoestring/Backpacker', 'Mid-range', 'Luxury/Premium'])) $budget_tier = 'Mid-range';
if ($group === 'Solo')   $travelers = 1;
if ($group === 'Couple') $travelers = 2;

$dest_lower = strtolower($dest);
$dest_mult  = 1.0;
foreach ($dest_multipliers as $keyword => $mult) {
    if (str_contains($dest_lower, $keyword)) {
        $dest_mult = $mult;
        break;
    }
}

$p     = $presets[$budget_tier];
$scale = ($days / 5) * $dest_mult;
$m     = $travelers;

$usd = [
    'transport'   => round($p['transport']   * $scale * (1 + ($m - 1) * 0.4)),
    'accom'       => round($p['accom']       * $scale),
    'food'        => round($p['food']        * $scale * (1 + ($m - 1) * 0.6)),
    'attractions' => round($p['attractions'] * $scale),
    'emergency'   => round($p['emergency']),
];
$usd_total = array_sum($usd);

$loc = [];
foreach ($usd as $k => $v) {
    $loc[$k] = (int)ceil($v * $cur_rate);
}
$loc_total = array_sum($loc);

echo json_encode([
    'success'     => true,
    'destination' => $dest,
    'days'        => $days,
    'travelers'   => $travelers,
    'group'       => $group,
    'budget_tier' => $budget_tier,
    'transport'   => $loc['transport'],
    'accom'       => $loc['accom'],
    'food'        => $loc['food'],
    'attractions' => $loc['attractions'],
    'emergency'   => $loc['emergency'],
    'total'       => $loc_total,
    'usd_total'   => $usd_total,
    'currency'    => $cur_symbol,
    'dest_mult'   => $dest_mult,
]);
