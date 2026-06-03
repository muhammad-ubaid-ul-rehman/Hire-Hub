<?php
/**
 * Migration Script: Add missing columns to fix talent page
 * Run this via browser: http://localhost/job_portal/migrate_add_columns.php
 */
require_once 'config.php';

echo "<h2>=== Adding Missing Columns for Talent Page ===</h2>";

$errors = [];
$success = [];

// Helper to add column if not exists
function addColumn($conn, $table, $column, $definition, &$errors, &$success) {
    // Check if column exists
    $res = $conn->query("SHOW COLUMNS FROM {$table} LIKE '{$column}'");
    if ($res->num_rows > 0) {
        $success[] = "Column '{$table}.{$column}' already exists";
        return true;
    }

    // Try to add
    if ($conn->query("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}")) {
        $success[] = "Added column: {$table}.{$column}";
        return true;
    } else {
        $errors[] = "Failed to add {$table}.{$column}: " . $conn->error;
        return false;
    }
}

// 1. Add columns to users table
echo "<h3>1. Adding columns to users table</h3>";
addColumn($conn, 'users', 'headline', 'VARCHAR(255) DEFAULT NULL', $errors, $success);
addColumn($conn, 'users', 'bio', 'TEXT DEFAULT NULL', $errors, $success);
addColumn($conn, 'users', 'profile_pic', 'VARCHAR(500) DEFAULT NULL', $errors, $success);
addColumn($conn, 'users', 'location', 'VARCHAR(150) DEFAULT NULL', $errors, $success);
addColumn($conn, 'users', 'is_active', 'TINYINT(1) NOT NULL DEFAULT 1', $errors, $success);

// 2. Add columns to seeker_profiles table
echo "<h3>2. Adding columns to seeker_profiles table</h3>";
addColumn($conn, 'seeker_profiles', 'certifications', 'TEXT DEFAULT NULL', $errors, $success);
addColumn($conn, 'seeker_profiles', 'languages', 'VARCHAR(255) DEFAULT NULL', $errors, $success);
addColumn($conn, 'seeker_profiles', 'resume', 'VARCHAR(500) DEFAULT NULL', $errors, $success);
addColumn($conn, 'seeker_profiles', 'linkedin', 'VARCHAR(255) DEFAULT NULL', $errors, $success);
addColumn($conn, 'seeker_profiles', 'portfolio', 'VARCHAR(255) DEFAULT NULL', $errors, $success);

// 3. Update existing users set is_active = 1
echo "<h3>3. Setting default values</h3>";
$conn->query("UPDATE users SET is_active = 1 WHERE is_active IS NULL");
$success[] = "Updated " . $conn->affected_rows . " users with is_active = 1";

// 4. Show current status
echo "<h3>4. Current Status</h3>";

// Check users columns
$res = $conn->query("SHOW COLUMNS FROM users");
echo "<strong>Users table columns:</strong><br>";
while ($row = $res->fetch_assoc()) {
    echo "- " . $row['Field'] . "<br>";
}

// Check seeker_profiles columns
$res = $conn->query("SHOW COLUMNS FROM seeker_profiles");
echo "<br><strong>Seeker profiles table columns:</strong><br>";
while ($row = $res->fetch_assoc()) {
    echo "- " . $row['Field'] . "<br>";
}

// Count profiles
echo "<br>";
$total = $conn->query("SELECT COUNT(*) as cnt FROM seeker_profiles")->fetch_assoc()['cnt'];
$approved = $conn->query("SELECT COUNT(*) as cnt FROM seeker_profiles WHERE status = 'approved'")->fetch_assoc()['cnt'];
echo "Total profiles: {$total}<br>";
echo "Approved profiles: {$approved}<br>";

// Show approved profile details
if ($approved > 0) {
    echo "<br><strong>Approved profiles that should appear on talent page:</strong><br>";
    $res = $conn->query("SELECT u.id, u.name, u.role, sp.status, u.is_active
                         FROM users u
                         JOIN seeker_profiles sp ON sp.user_id = u.id
                         WHERE sp.status = 'approved'");
    while ($row = $res->fetch_assoc()) {
        echo "- {$row['name']} (user_id: {$row['id']}, role: {$row['role']}, is_active: {$row['is_active']})<br>";
    }
}

// Show results
echo "<h3>Results</h3>";
if (!empty($success)) {
    echo "<p style='color:green;'>";
    foreach ($success as $s) echo "✓ $s<br>";
    echo "</p>";
}
if (!empty($errors)) {
    echo "<p style='color:red;'>";
    foreach ($errors as $e) echo "✗ $e<br>";
    echo "</p>";
}

echo "<h3>Done!</h3>";
echo "<p><a href='candidates.php'>Go to Talent Page</a></p>";