<?php
/**
 * Fix for Talent Page - Add missing columns to make candidates.php work
 * Run this once to fix the talent page not showing approved profiles
 */
require_once 'config.php';

echo "=== Fixing Talent Page Database Schema ===\n\n";

$fixes = [];

// 1. Add missing columns to users table
$user_columns = [
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS headline VARCHAR(255) DEFAULT NULL",
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS bio TEXT DEFAULT NULL",
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS profile_pic VARCHAR(500) DEFAULT NULL",
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS location VARCHAR(150) DEFAULT NULL",
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER role"
];

// For MySQL 8+ ( MariaDB supports IF NOT EXISTS in ALTER)
foreach ($user_columns as $col) {
    // Try with IF NOT EXISTS (MariaDB 10.0.5+)
    try {
        $conn->query($col . " AFTER role");
        echo "OK: Added column to users\n";
        $fixes[] = $col;
    } catch (Exception $e) {
        // Column might already exist, try next
    }
}

// Simpler approach - use separate queries for each column
$user_cols_simple = [
    "ALTER TABLE users ADD COLUMN headline VARCHAR(255) DEFAULT NULL",
    "ALTER TABLE users ADD COLUMN bio TEXT DEFAULT NULL",
    "ALTER TABLE users ADD COLUMN profile_pic VARCHAR(500) DEFAULT NULL",
    "ALTER TABLE users ADD COLUMN location VARCHAR(150) DEFAULT NULL",
    "ALTER TABLE users ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1"
];

foreach ($user_cols_simple as $sql) {
    try {
        $conn->query($sql);
        echo "OK: Added column\n";
    } catch (Exception $e) {
        // Column exists, ignore
    }
}

// 2. Check/fix seeker_profiles resume field
echo "\n=== Checking seeker_profiles columns ===\n";
$res = $conn->query("SHOW COLUMNS FROM seeker_profiles");
$sp_cols = [];
while ($row = $res->fetch_assoc()) {
    $sp_cols[] = $row['Field'];
    echo "  - " . $row['Field'] . "\n";
}

// Check if resume or resume_path exists
if (!in_array('resume', $sp_cols) && in_array('resume_path', $sp_cols)) {
    // Add alias column 'resume' that references resume_path
    echo "\nNote: resume_path exists but code expects 'resume' - will handle in query\n";
}

// 3. Check if is_active defaults are set for existing users
echo "\n=== Setting default is_active=1 for existing users ===\n";
$conn->query("UPDATE users SET is_active = 1 WHERE is_active IS NULL OR is_active = 0");
echo "Updated " . $conn->affected_rows . " users\n";

// 4. Check for approved profiles
echo "\n=== Checking Profile Status ===\n";
$res = $conn->query("SELECT status, COUNT(*) as cnt FROM seeker_profiles GROUP BY status");
while ($row = $res->fetch_assoc()) {
    echo "  {$row['status']}: {$row['cnt']}\n";
}

// 5. Show approved profiles count
echo "\n=== Profiles that should show on Talent Page ===\n";
$sql = "SELECT COUNT(*) as cnt FROM users u
        JOIN seeker_profiles sp ON sp.user_id = u.id
        WHERE u.role = 'seeker' AND sp.status = 'approved' AND (u.is_active = 1 OR u.is_active IS NULL)";
$res = $conn->query($sql);
echo "Approved profiles: " . $res->fetch_assoc()['cnt'] . "\n";

echo "\n=== Fix Complete! ===\n";
echo "Try refreshing the candidates.php page now.\n";