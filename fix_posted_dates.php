<?php
require_once 'config.php';

echo "<h1>Updating Jobs with posted_at dates</h1>";
echo "<pre>";

// Update all jobs with posted_at if NULL
$updates = [
    [14, '2026-06-03'],
    [15, '2026-06-02'],
    [16, '2026-06-01'],
    [17, '2026-05-31'],
    [18, '2026-05-30'],
];

foreach ($updates as [$employer_id, $date]) {
    $stmt = $conn->prepare("
        UPDATE jobs 
        SET posted_at = ? 
        WHERE employer_id = ? AND posted_at IS NULL
    ");
    $stmt->bind_param('si', $date, $employer_id);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    echo "Updated $affected jobs for employer $employer_id with date $date\n";
}

// Verify results
echo "\n" . str_repeat("=", 80) . "\n";
echo "All jobs with posted_at:\n";
$result = $conn->query("
    SELECT j.id, j.title, j.employer_id, j.posted_at, u.name 
    FROM jobs j 
    LEFT JOIN users u ON j.employer_id = u.id 
    ORDER BY j.posted_at DESC
");

$count = 0;
while ($row = $result->fetch_assoc()) {
    echo "ID: {$row['id']} | {$row['title']} | Posted: {$row['posted_at']} | By: {$row['name']}\n";
    $count++;
}

echo "\nTotal jobs: $count\n";
echo "</pre>";
echo "<hr>";
echo "<p><strong>Done!</strong> Now go to <a href='index.php'>Home Page</a> to see the jobs.</p>";
?>
