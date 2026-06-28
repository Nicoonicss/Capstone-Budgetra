<?php
/**
 * Country → Currency mapping
 * Each entry: [ code, symbol, name, usd_rate ]
 * usd_rate = how many units of this currency equal 1 USD (approx.)
 */
define('COUNTRY_CURRENCIES', [
    // ── Southeast Asia ───────────────────────────────────
    'Philippines'            => ['PHP', '₱',   'Philippine Peso',        57.0],
    'Indonesia'              => ['IDR', 'Rp',  'Indonesian Rupiah',   16350.0],
    'Thailand'               => ['THB', '฿',   'Thai Baht',              35.0],
    'Vietnam'                => ['VND', '₫',   'Vietnamese Dong',     25100.0],
    'Malaysia'               => ['MYR', 'RM',  'Malaysian Ringgit',       4.7],
    'Singapore'              => ['SGD', 'S$',  'Singapore Dollar',        1.35],
    'Myanmar'                => ['MMK', 'K',   'Myanmar Kyat',          2100.0],
    'Cambodia'               => ['KHR', '៛',   'Cambodian Riel',        4100.0],
    'Laos'                   => ['LAK', '₭',   'Lao Kip',             21600.0],
    'Brunei'                 => ['BND', 'B$',  'Brunei Dollar',           1.35],
    'Timor-Leste'            => ['USD', '$',   'US Dollar',               1.0],

    // ── East Asia ────────────────────────────────────────
    'Japan'                  => ['JPY', '¥',   'Japanese Yen',          149.0],
    'South Korea'            => ['KRW', '₩',   'South Korean Won',     1335.0],
    'China'                  => ['CNY', '¥',   'Chinese Yuan',            7.25],
    'Taiwan'                 => ['TWD', 'NT$', 'New Taiwan Dollar',       32.0],
    'Hong Kong'              => ['HKD', 'HK$', 'Hong Kong Dollar',        7.82],
    'Macau'                  => ['MOP', 'MOP$','Macanese Pataca',          8.06],
    'Mongolia'               => ['MNT', '₮',   'Mongolian Tögrög',     3400.0],

    // ── South Asia ──────────────────────────────────────
    'India'                  => ['INR', '₹',   'Indian Rupee',           83.5],
    'Pakistan'               => ['PKR', '₨',   'Pakistani Rupee',       278.0],
    'Bangladesh'             => ['BDT', '৳',   'Bangladeshi Taka',      110.0],
    'Sri Lanka'              => ['LKR', 'Rs',  'Sri Lankan Rupee',      325.0],
    'Nepal'                  => ['NPR', 'Rs',  'Nepalese Rupee',        133.0],
    'Maldives'               => ['MVR', 'Rf',  'Maldivian Rufiyaa',      15.4],

    // ── Middle East ──────────────────────────────────────
    'United Arab Emirates'   => ['AED', 'AED', 'UAE Dirham',              3.67],
    'Saudi Arabia'           => ['SAR', 'SR',  'Saudi Riyal',             3.75],
    'Qatar'                  => ['QAR', 'QR',  'Qatari Riyal',            3.64],
    'Kuwait'                 => ['KWD', 'KD',  'Kuwaiti Dinar',           0.31],
    'Bahrain'                => ['BHD', 'BD',  'Bahraini Dinar',          0.38],
    'Oman'                   => ['OMR', 'OMR', 'Omani Rial',              0.385],
    'Israel'                 => ['ILS', '₪',   'Israeli Shekel',          3.7],
    'Jordan'                 => ['JOD', 'JD',  'Jordanian Dinar',         0.71],
    'Turkey'                 => ['TRY', '₺',   'Turkish Lira',           32.0],

    // ── Europe ──────────────────────────────────────────
    'Germany'                => ['EUR', '€',   'Euro',                    0.92],
    'France'                 => ['EUR', '€',   'Euro',                    0.92],
    'Italy'                  => ['EUR', '€',   'Euro',                    0.92],
    'Spain'                  => ['EUR', '€',   'Euro',                    0.92],
    'Netherlands'            => ['EUR', '€',   'Euro',                    0.92],
    'Belgium'                => ['EUR', '€',   'Euro',                    0.92],
    'Portugal'               => ['EUR', '€',   'Euro',                    0.92],
    'Austria'                => ['EUR', '€',   'Euro',                    0.92],
    'Greece'                 => ['EUR', '€',   'Euro',                    0.92],
    'Ireland'                => ['EUR', '€',   'Euro',                    0.92],
    'Finland'                => ['EUR', '€',   'Euro',                    0.92],
    'United Kingdom'         => ['GBP', '£',   'British Pound',           0.79],
    'Switzerland'            => ['CHF', 'Fr',  'Swiss Franc',             0.90],
    'Sweden'                 => ['SEK', 'kr',  'Swedish Krona',          10.5],
    'Norway'                 => ['NOK', 'kr',  'Norwegian Krone',        10.6],
    'Denmark'                => ['DKK', 'kr',  'Danish Krone',            6.88],
    'Poland'                 => ['PLN', 'zł',  'Polish Złoty',            4.0],
    'Czech Republic'         => ['CZK', 'Kč',  'Czech Koruna',           23.3],
    'Hungary'                => ['HUF', 'Ft',  'Hungarian Forint',      360.0],
    'Romania'                => ['RON', 'lei', 'Romanian Leu',            4.97],
    'Russia'                 => ['RUB', '₽',   'Russian Ruble',          92.0],
    'Ukraine'                => ['UAH', '₴',   'Ukrainian Hryvnia',      39.0],

    // ── Americas ────────────────────────────────────────
    'United States'          => ['USD', '$',   'US Dollar',               1.0],
    'Canada'                 => ['CAD', 'C$',  'Canadian Dollar',         1.36],
    'Mexico'                 => ['MXN', '$',   'Mexican Peso',           17.1],
    'Brazil'                 => ['BRL', 'R$',  'Brazilian Real',          4.97],
    'Argentina'              => ['ARS', '$',   'Argentine Peso',        900.0],
    'Chile'                  => ['CLP', '$',   'Chilean Peso',          950.0],
    'Colombia'               => ['COP', '$',   'Colombian Peso',       4200.0],
    'Peru'                   => ['PEN', 'S/',  'Peruvian Sol',            3.73],
    'Ecuador'                => ['USD', '$',   'US Dollar',               1.0],

    // ── Africa ──────────────────────────────────────────
    'South Africa'           => ['ZAR', 'R',   'South African Rand',     18.7],
    'Nigeria'                => ['NGN', '₦',   'Nigerian Naira',        1600.0],
    'Egypt'                  => ['EGP', '£',   'Egyptian Pound',          49.0],
    'Kenya'                  => ['KES', 'Ksh', 'Kenyan Shilling',        130.0],
    'Ghana'                  => ['GHS', 'GH₵', 'Ghanaian Cedi',          15.8],
    'Ethiopia'               => ['ETB', 'Br',  'Ethiopian Birr',         56.6],
    'Morocco'                => ['MAD', 'MAD', 'Moroccan Dirham',        10.1],
    'Tanzania'               => ['TZS', 'Tsh', 'Tanzanian Shilling',   2650.0],

    // ── Oceania ─────────────────────────────────────────
    'Australia'              => ['AUD', 'A$',  'Australian Dollar',       1.53],
    'New Zealand'            => ['NZD', 'NZ$', 'New Zealand Dollar',      1.63],
    'Papua New Guinea'       => ['PGK', 'K',   'Papua New Guinean Kina',  3.73],
    'Fiji'                   => ['FJD', 'FJ$', 'Fijian Dollar',           2.25],
]);

/**
 * Get currency info for a country.
 * Returns ['code'=>'PHP','symbol'=>'₱','name'=>'Philippine Peso','usd_rate'=>57.0]
 */
function get_currency_for_country(string $country): array {
    $map = COUNTRY_CURRENCIES;
    if (isset($map[$country])) {
        [$code, $symbol, $name, $rate] = $map[$country];
        return compact('code', 'symbol', 'name', 'rate');
    }
    return ['code' => 'USD', 'symbol' => '$', 'name' => 'US Dollar', 'rate' => 1.0];
}

/**
 * Convert a USD amount to the given currency.
 */
function usd_to_currency(float $usd, float $rate): float {
    return round($usd * $rate, 2);
}

/**
 * Format an amount with a currency symbol.
 */
function format_currency(float $amount, string $symbol): string {
    // For currencies with very large values, skip decimals
    if ($amount >= 10000) {
        return $symbol . number_format(round($amount));
    }
    return $symbol . number_format($amount, 2);
}
