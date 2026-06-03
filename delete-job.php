<?php
// delete-job.php — pure logic, no HTML
require_once 'config.php';
$user    = requireRole('employer');
$user_id = (int)$_SESSION['user_id'];
$job_id  = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($job_id > 0) {
    // Verify ownership before deleting
    $chk = $conn->prepare("SELECT id FROM jobs WHERE id = ? AND employer_id = ?");
    $chk->bind_param('ii', $job_id, $user_id);
    $chk->execute();
    if ($chk->get_result()->num_rows > 0) {
        // Delete applications first (FK safe even with CASCADE but explicit is clearer)
        $conn->prepare("DELETE FROM applications WHERE job_id = ?")->execute() ||
        $conn->query("DELETE FROM applications WHERE job_id = $job_id");
        $del = $conn->prepare("DELETE FROM jobs WHERE id = ? AND employer_id = ?");
        $del->bind_param('ii', $job_id, $user_id);
        $del->execute();
        $_SESSION['flash'] = ['msg' => 'Job listing deleted.', 'type' => 'success'];
    }
}
redirect('dashboard.php');
?>
