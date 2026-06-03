<?php
require_once 'config.php';

echo "=== HireHub Talent System — Final Verification ===\n\n";

// Check tables
echo "1. Checking database tables...\n";
$tables = ['users', 'seeker_profiles', 'favorites', 'profile_views', 'jobs', 'applications', 'saved_jobs'];
foreach ($tables as $table) {
    $res = $conn->query("SHOW TABLES LIKE '{$table}'");
    if ($res->num_rows > 0) {
        echo "   ✅ {$table}\n";
    } else {
        echo "   ❌ MISSING: {$table}\n";
    }
}

// Check seeker_profiles columns
echo "\n2. Checking seeker_profiles enhanced fields...\n";
$fields = ['job_title', 'skills', 'experience_level', 'availability', 'status', 'expected_salary', 'career_objective', 'certifications'];
$res = $conn->query("SHOW COLUMNS FROM seeker_profiles");
$existing = [];
while ($row = $res->fetch_assoc()) {
    $existing[] = $row['Field'];
}
foreach ($fields as $field) {
    if (in_array($field, $existing)) {
        echo "   ✅ {$field}\n";
    } else {
        echo "   ❌ MISSING: {$field}\n";
    }
}

// Check sample data
echo "\n3. Checking database statistics...\n";
$stats = $conn->query("SELECT
    (SELECT COUNT(*) FROM users) AS users,
    (SELECT COUNT(*) FROM seeker_profiles) AS profiles,
    (SELECT COUNT(*) FROM favorites) AS favorites,
    (SELECT COUNT(*) FROM profile_views) AS views,
    (SELECT COUNT(*) FROM jobs) AS jobs,
    (SELECT COUNT(*) FROM applications) AS applications")->fetch_assoc();
echo "   📊 Total Users: {$stats['users']}\n";
echo "   👤 Seeker Profiles: {$stats['profiles']}\n";
echo "   ❤️ Saved Candidates: {$stats['favorites']}\n";
echo "   👁️ Profile Views: {$stats['views']}\n";
echo "   💼 Job Listings: {$stats['jobs']}\n";
echo "   📋 Applications: {$stats['applications']}\n";

// Check approval status
echo "\n4. Profile approval status...\n";
$status_check = $conn->query("SELECT status, COUNT(*) as count FROM seeker_profiles GROUP BY status")->fetch_all(MYSQLI_ASSOC);
if (empty($status_check)) {
    echo "   ℹ️  No profiles yet (create one to test)\n";
} else {
    foreach ($status_check as $row) {
        echo "   📊 {$row['status']}: {$row['count']}\n";
    }
}

// File check
echo "\n5. Checking new files...\n";
$files = [
    'candidates.php' => 'Talent search',
    'candidate.php' => 'Profile detail',
    'seeker-profile.php' => 'Profile editor',
    'TALENT_SYSTEM_README.md' => 'Documentation',
    'IMPLEMENTATION_SUMMARY.md' => 'Summary',
    'QUICK_REFERENCE.md' => 'Reference'
];
foreach ($files as $file => $desc) {
    if (file_exists($file)) {
        $size = filesize($file);
        echo "   ✅ {$file} ({$size} bytes) - {$desc}\n";
    } else {
        echo "   ❌ MISSING: {$file}\n";
    }
}

echo "\n=== Verification Complete ===\n";
echo "\n🎉 Your HireHub Talent System is ready to use!\n";
echo "📖 Start with: TALENT_SYSTEM_README.md\n";
echo "🚀 For quick reference: QUICK_REFERENCE.md\n";
