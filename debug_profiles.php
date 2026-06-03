<?php
require 'config.php';
echo "<pre>";
echo "=== SEEKER PROFILES DEBUG ===\n\n";

$result = $conn->query('SELECT u.id, u.name, u.role, u.is_active, sp.id as sp_id, sp.job_title, sp.status FROM users u LEFT JOIN seeker_profiles sp ON sp.user_id = u.id WHERE u.role = "seeker" ORDER BY u.id');

if (!$result) {
    echo "Query Error: " . $conn->error . "\n";
} else {
    while ($row = $result->fetch_assoc()) {
        echo "User: {$row['name']} (ID: {$row['id']}, Active: {$row['is_active']}) - Profile: ";
        if ($row['sp_id']) {
            echo "YES (ID: {$row['sp_id']}, Title: {$row['job_title']}, Status: {$row['status']})\n";
        } else {
            echo "NO - NO SEEKER PROFILE RECORD\n";
        }
    }
}

echo "\n=== TABLE INFO ===\n";
$tables = $conn->query("SHOW TABLES LIKE 'seeker_profiles'");
if ($tables->num_rows > 0) {
    echo "seeker_profiles table EXISTS\n";
    $cols = $conn->query("SHOW COLUMNS FROM seeker_profiles");
    echo "\nColumns:\n";
    while ($col = $cols->fetch_assoc()) {
        echo "  - {$col['Field']} ({$col['Type']})\n";
    }
} else {
    echo "seeker_profiles table DOES NOT EXIST\n";
}

echo "</pre>";
