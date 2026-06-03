<?php
require_once 'config.php';

echo "<h1>Debug: Jobs Table</h1>";
echo "<pre>";

// Count total jobs
$total = $conn->query("SELECT COUNT(*) as c FROM jobs")->fetch_assoc()['c'];
echo "Total jobs in database: $total\n\n";

// Show all jobs with status
$result = $conn->query("
    SELECT j.id, j.title, j.employer_id, j.status, j.location, j.job_type, j.created_at, u.name as employer_name
    FROM jobs j
    LEFT JOIN users u ON j.employer_id = u.id
    ORDER BY j.id
");

echo "All Jobs:\n";
echo str_repeat("=", 100) . "\n";

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "ID: {$row['id']} | Title: {$row['title']} | Employer: {$row['employer_name']} | Status: {$row['status']} | Location: {$row['location']} | Type: {$row['job_type']}\n";
    }
} else {
    echo "No jobs found!\n";
}

echo "\n" . str_repeat("=", 100) . "\n";

// Count by status
echo "\nJobs by Status:\n";
$status_result = $conn->query("SELECT status, COUNT(*) as count FROM jobs GROUP BY status");
while ($row = $status_result->fetch_assoc()) {
    echo "  {$row['status']}: {$row['count']}\n";
}

echo "\n" . str_repeat("=", 100) . "\n";

// Check home page query
echo "\nHome page query (active jobs only):\n";
$home_result = $conn->query("
    SELECT j.id, j.title, u.name as employer_name, j.location
    FROM jobs j
    JOIN users u ON j.employer_id = u.id
    WHERE j.status = 'active'
    ORDER BY j.posted_at DESC
");

echo "Active jobs count: " . $home_result->num_rows . "\n";
if ($home_result->num_rows > 0) {
    while ($row = $home_result->fetch_assoc()) {
        echo "  - {$row['title']} by {$row['employer_name']} in {$row['location']}\n";
    }
} else {
    echo "  No active jobs found!\n";
}

echo "</pre>";
echo "<hr>";
echo "<p><a href='index.php'>Back to Home</a></p>";
?>
