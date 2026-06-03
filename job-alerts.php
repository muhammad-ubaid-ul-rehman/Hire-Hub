<?php
require_once 'config.php';
$user = requireLogin();

// Ensure alerts table exists
$conn->query("CREATE TABLE IF NOT EXISTS job_alerts (
    id INT(11) NOT NULL AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    keywords VARCHAR(255) NOT NULL,
    location VARCHAR(150) DEFAULT NULL,
    job_type ENUM('full-time','part-time','remote','contract','internship'),
    frequency ENUM('daily','weekly','instant') NOT NULL DEFAULT 'daily',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP(),
    PRIMARY KEY (id),
    UNIQUE KEY unique_user (user_id),
    KEY fk_alert_user (user_id),
    CONSTRAINT fk_alert_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    
    $keywords = trim($_POST['keywords'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $job_type = !empty($_POST['job_type']) ? $_POST['job_type'] : null;  // Convert empty to NULL
    $frequency = $_POST['frequency'] ?? 'daily';
    
    // Validate frequency - must be one of the allowed ENUM values
    $valid_frequencies = ['daily', 'weekly', 'instant'];
    if (!in_array($frequency, $valid_frequencies)) {
        $frequency = 'daily';
    }
    
    if (!$keywords) {
        $error = 'Please enter keywords for your job alert.';
    } else {
        $stmt = $conn->prepare("INSERT INTO job_alerts (user_id, keywords, location, job_type, frequency, created_at) 
                               VALUES (?, ?, ?, ?, ?, NOW()) 
                               ON DUPLICATE KEY UPDATE keywords=?, location=?, job_type=?, frequency=?, updated_at = NOW()");
        $stmt->bind_param('isssissss', $user['id'], $keywords, $location, $job_type, $frequency, $keywords, $location, $job_type, $frequency);
        
        if ($stmt->execute()) {
            $_SESSION['flash'] = ['msg' => 'Job alert saved successfully!', 'type' => 'success'];
            redirect('job-alerts.php');
        } else {
            $error = 'Failed to save job alert.';
        }
    }
}

// Get current alert
$stmt = $conn->prepare("SELECT * FROM job_alerts WHERE user_id = ?");
$stmt->bind_param('i', $user['id']);
$stmt->execute();
$alert = $stmt->get_result()->fetch_assoc();

require 'header.php';
?>

<main class="page-body">
<div class="container" style="max-width:600px;">

    <div style="margin-bottom:32px;">
        <h1 class="h3">Job Alerts</h1>
        <p style="color:var(--text2);font-size:14px;margin-top:4px;">
            Get notified when new jobs matching your criteria are posted.
        </p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-error"><i class="fa fa-circle-xmark"></i> <?= e($error) ?></div>
    <?php endif; ?>

    <div class="card">
        <form method="POST" action="job-alerts.php">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            
            <div class="form-group">
                <label class="form-label">Keywords</label>
                <input type="text" name="keywords" class="form-control" 
                       placeholder="e.g. PHP, Developer, Remote" 
                       value="<?= e($alert['keywords'] ?? '') ?>" required>
                <small style="color:var(--text3);font-size:12px;">Separate multiple keywords with commas</small>
            </div>

            <div class="form-group">
                <label class="form-label">Location (Optional)</label>
                <input type="text" name="location" class="form-control" 
                       placeholder="e.g. Lahore, Karachi" 
                       value="<?= e($alert['location'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label class="form-label">Job Type</label>
                <select name="job_type" class="form-control">
                    <option value="">Any Type</option>
                    <?php foreach (['full-time'=>'Full-Time','part-time'=>'Part-Time','remote'=>'Remote','contract'=>'Contract','internship'=>'Internship'] as $val => $label): ?>
                        <option value="<?= $val ?>" <?= ($alert['job_type'] ?? '') === $val ? 'selected' : '' ?>>
                            <?= $label ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Email Frequency</label>
                <select name="frequency" class="form-control">
                    <option value="daily" <?= ($alert['frequency'] ?? 'daily') === 'daily' ? 'selected' : '' ?>>Daily</option>
                    <option value="weekly" <?= ($alert['frequency'] ?? '') === 'weekly' ? 'selected' : '' ?>>Weekly</option>
                    <option value="instant" <?= ($alert['frequency'] ?? '') === 'instant' ? 'selected' : '' ?>>Instant</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary btn-lg btn-block">
                <i class="fa fa-bell"></i> Save Job Alert
            </button>
        </form>
    </div>

    <?php if ($alert): ?>
    <div class="card" style="margin-top:24px;">
        <h3 style="font-size:16px;font-weight:700;margin-bottom:16px;">Current Alert Settings</h3>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;font-size:14px;">
            <div><strong>Keywords:</strong> <?= e($alert['keywords']) ?></div>
            <div><strong>Location:</strong> <?= e($alert['location'] ?: 'Any') ?></div>
            <div><strong>Type:</strong> <?= e($alert['job_type'] ?: 'Any') ?></div>
            <div><strong>Frequency:</strong> <?= ucfirst($alert['frequency']) ?></div>
        </div>
    </div>
    <?php endif; ?>

</div>
</main>

<?php require 'footer.php'; ?>