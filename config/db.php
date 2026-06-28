<?php
// Database Connection
$host = 'localhost';
$dbname = 'budgera';
$username = 'root';
$password = 'root';

try {
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $dbname");
    $pdo->exec("USE $dbname");

    // ── Column migrations (safe on every request) ─────────────────────────
    // INFORMATION_SCHEMA check ensures each ALTER runs only when the column
    // is genuinely missing, so this is a no-op for existing columns.
    $db_name = $pdo->query("SELECT DATABASE()")->fetchColumn();
    $column_migrations = [
        ['users', 'country',         "ALTER TABLE users ADD COLUMN country VARCHAR(100) DEFAULT NULL AFTER password"],
        ['users', 'currency_code',   "ALTER TABLE users ADD COLUMN currency_code VARCHAR(10) DEFAULT 'USD' AFTER country"],
        ['users', 'currency_symbol', "ALTER TABLE users ADD COLUMN currency_symbol VARCHAR(10) DEFAULT '\$' AFTER currency_code"],
    ];
    foreach ($column_migrations as [$tbl, $col, $alter_sql]) {
        $chk = $pdo->prepare(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?"
        );
        $chk->execute([$db_name, $tbl, $col]);
        if ((int)$chk->fetchColumn() === 0) {
            // Silently skip if the table doesn't exist yet (fresh install)
            try { $pdo->exec($alter_sql); } catch (PDOException $ignored) {}
        }
    }

} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
