<?php
/**
 * Migration: Add country, currency_code, currency_symbol to users table.
 * Run once: http://localhost:8000/config/migrate_add_country.php
 */
require_once __DIR__ . '/db.php';

$migrations = [
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS country VARCHAR(100) DEFAULT NULL",
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS currency_code VARCHAR(10) DEFAULT 'USD'",
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS currency_symbol VARCHAR(10) DEFAULT '$'",
];

$results = [];
foreach ($migrations as $sql) {
    try {
        $pdo->exec($sql);
        $results[] = ['sql' => $sql, 'status' => 'OK'];
    } catch (PDOException $e) {
        // Column may already exist — not fatal
        $results[] = ['sql' => $sql, 'status' => 'SKIPPED: ' . $e->getMessage()];
    }
}
?>
<!DOCTYPE html>
<html><head><title>Migration</title>
<style>body{font-family:monospace;padding:24px;background:#f5f5f5;}
.ok{color:green;}.skip{color:orange;}.sql{color:#555;font-size:13px;}</style>
</head><body>
<h2>Migration: add country / currency to users</h2>
<?php foreach ($results as $r): ?>
    <p>
        <span class="<?php echo strpos($r['status'],'OK')===0 ? 'ok' : 'skip'; ?>">
            <?php echo htmlspecialchars($r['status']); ?>
        </span><br>
        <span class="sql"><?php echo htmlspecialchars($r['sql']); ?></span>
    </p>
<?php endforeach; ?>
<p><a href="/">← Back to site</a></p>
</body></html>
