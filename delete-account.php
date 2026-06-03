<?php
// delete-account.php — pure logic, no HTML
require_once 'config.php';
$user    = requireLogin();
$user_id = (int)$_SESSION['user_id'];

if ($user['role'] === 'employer') {
    // Get all job IDs for this employer
    $jobs = $conn->query("SELECT id FROM jobs WHERE employer_id = $user_id");
    if ($jobs) {
        while ($j = $jobs->fetch_assoc()) {
            $jid = (int)$j['id'];
            $conn->query("DELETE FROM applications WHERE job_id = $jid");
        }
    }
    $conn->query("DELETE FROM jobs WHERE employer_id = $user_id");
}

$conn->query("DELETE FROM applications WHERE user_id = $user_id");
$conn->query("DELETE FROM users WHERE id = $user_id");

$_SESSION = [];
session_destroy();
redirect('index.php');
?>
