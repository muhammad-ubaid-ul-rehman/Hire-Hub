<?php
require_once 'config.php';
$user = requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    
    $job_id = (int)($_POST['job_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    
    if (!$job_id) {
        redirect('index.php');
    }
    
    if ($action === 'add') {
        $stmt = $conn->prepare("INSERT IGNORE INTO saved_jobs (user_id, job_id, saved_at) VALUES (?, ?, NOW())");
        $stmt->bind_param('ii', $user['id'], $job_id);
        $stmt->execute();
        $_SESSION['flash'] = ['msg' => 'Job saved to your list!', 'type' => 'success'];
    } elseif ($action === 'remove') {
        $stmt = $conn->prepare("DELETE FROM saved_jobs WHERE user_id = ? AND job_id = ?");
        $stmt->bind_param('ii', $user['id'], $job_id);
        $stmt->execute();
        $_SESSION['flash'] = ['msg' => 'Job removed from saved jobs.', 'type' => 'info'];
    }
}

redirect($_SERVER['HTTP_REFERER'] ?? 'index.php');
?>