<?php
require 'config.php';

// Check if we're connected
if (!$conn) {
    die("❌ Database connection failed");
}

// Check jobs table structure
$check = $conn->query("SELECT id, title, posted_at FROM jobs LIMIT 1");
if (!$check) {
    die("❌ Jobs table query failed: " . $conn->error);
}

$job = $check->fetch_assoc();
if (!$job) {
    die("❌ No jobs found in database");
}

if ($job['posted_at'] === null || empty($job['posted_at'])) {
    die("❌ Jobs have no posted_at dates - database needs update");
}

// Count jobs with posted_at dates
$count_with_dates = $conn->query("SELECT COUNT(*) as cnt FROM jobs WHERE posted_at IS NOT NULL AND posted_at != '0000-00-00'")->fetch_assoc();
$total_jobs = $conn->query("SELECT COUNT(*) as cnt FROM jobs")->fetch_assoc();

echo "✓ Database verified successfully\n";
echo "✓ Jobs with posted_at dates: {$count_with_dates['cnt']}/{$total_jobs['cnt']}\n";
echo "✓ Sample job: \"{$job['title']}\" (posted: {$job['posted_at']})\n\n";
echo "Setup Steps:\n";
echo "1. Hard refresh browser (Ctrl+F5 or Cmd+Shift+R)\n";
echo "2. Visit http://localhost/Hire-Hub-main/index.php to see jobs\n";
echo "3. Admin: Click lock 3x → password 'this' → dashboard\n";
echo "4. Test: Click contact on any candidate profile\n";
?>
