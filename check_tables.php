<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=budgetra', 'root', 'root');
$result = $pdo->query('SHOW TABLES');
$tables = $result->fetchAll(PDO::FETCH_COLUMN);
echo "Tables in database (" . count($tables) . "):\n";
foreach ($tables as $table) {
    echo "  - $table\n";
}
echo "\nExit code: 0\n";
