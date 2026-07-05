<?php
use Illuminate\Support\Facades\DB;

// Set foreign key checks off temporarily
DB::statement('SET FOREIGN_KEY_CHECKS = 0');

// Drop all tables
$tables = [
    'migrations',
    'users',
    'trips',
    'trip_budgets',
    'expenses',
    'savings_goals',
    'notifications',
    'itinerary',
    'destination_costs',
    'attractions',
    'reviews',
    'ocr_logs',
    'app_config',
    'password_reset_tokens'
];

foreach ($tables as $table) {
    DB::statement("DROP TABLE IF EXISTS `$table`");
    echo "Dropped table: $table\n";
}

// Re-enable foreign key checks
DB::statement('SET FOREIGN_KEY_CHECKS = 1');
echo "Database cleared!\n";
