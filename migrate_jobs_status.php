<?php
require_once 'config.php';

// Only admins can run migrations
if (!isAdmin()) {
    die('Access denied. Admin only.');
}

$migrations = [
    [
        'name' => 'Set missing job statuses to active',
        'sql' => "UPDATE jobs SET status = 'active' WHERE status IS NULL OR status = ''"
    ],
    [
        'name' => 'Set draft jobs to active that were created',
        'sql' => "UPDATE jobs SET status = 'active' WHERE status = 'draft' AND created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)"
    ]
];

echo "<pre style='font-family: monospace; background: #f5f5f5; padding: 20px; border-radius: 8px;'>";
echo "Running Migrations...\n\n";

foreach ($migrations as $migration) {
    echo "→ " . $migration['name'] . "\n";
    if ($conn->query($migration['sql'])) {
        $affected = $conn->affected_rows;
        echo "  ✓ Success ({$affected} rows affected)\n\n";
    } else {
        echo "  ✗ Error: " . $conn->error . "\n\n";
    }
}

// Verify
$total_jobs = $conn->query("SELECT COUNT(*) AS c FROM jobs")->fetch_assoc()['c'];
$active_jobs = $conn->query("SELECT COUNT(*) AS c FROM jobs WHERE status = 'active'")->fetch_assoc()['c'];

echo "===========================================\n";
echo "Status Summary:\n";
echo "  Total Jobs: {$total_jobs}\n";
echo "  Active Jobs: {$active_jobs}\n";
echo "===========================================\n";

echo "\nMigrations completed! You can now close this window.\n";
echo "</pre>";
?>
