<?php
require_once 'config.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) redirect('index.php');

// Fetch job with employer name — prepared statement
$stmt = $conn->prepare(
    "SELECT j.*, u.name AS employer_name, u.email AS employer_email
     FROM jobs j JOIN users u ON j.employer_id = u.id
     WHERE j.id = ?"
);
$stmt->bind_param('i', $id);
$stmt->execute();
$job = $stmt->get_result()->fetch_assoc();
if (!$job) redirect('index.php');

// Applicant count
$cnt_stmt = $conn->prepare("SELECT COUNT(*) AS c FROM applications WHERE job_id = ?");
$cnt_stmt->bind_param('i', $id);
$cnt_stmt->execute();
$app_count = $cnt_stmt->get_result()->fetch_assoc()['c'];

// Deadline info
$days      = daysUntil($job['deadline']);
$is_closed = ($days !== null && $days < 0);

// Has current user applied?
$already_applied = false;
$user = null;
if (isLoggedIn()) {
    $user = getCurrentUser();
    if ($user && $user['role'] === 'seeker') {
        $as = $conn->prepare("SELECT id FROM applications WHERE job_id = ? AND user_id = ?");
        $as->bind_param('ii', $id, $user['id']);
        $as->execute();
        $already_applied = $as->get_result()->num_rows > 0;
    }
}

// Job type badge class
$type_class = jobTypeClass($job['job_type']);
$type_label = jobTypeLabel($job['job_type']);

require 'header.php';
?>

<main class="page-body">
<div class="container">

    <!-- Back -->
    <a href="index.php" style="display:inline-flex;align-items:center;gap:6px;color:var(--text2);font-size:14px;margin-bottom:28px;">
        <i class="fa fa-arrow-left"></i> Back to Jobs
    </a>

    <div style="display:grid;grid-template-columns:1fr 320px;gap:24px;align-items:start;" class="detail-grid">

        <!-- ── Main content ── -->
        <div>
<div class="card reveal" style="margin-bottom:20px;">

                <!-- Header -->
                <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:16px;margin-bottom:20px;">
                    <div>
                        <h1 style="font-size:26px;font-weight:800;margin-bottom:8px;"><?= e($job['title']) ?></h1>
                        <p style="color:var(--text2);font-size:15px;margin-bottom:10px;">
                            <i class="fa fa-building"></i> <?= e($job['employer_name']) ?>
                            &ensp;·&ensp;
                            <i class="fa fa-location-dot"></i> <?= e($job['location']) ?>
                        </p>
                        <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                            <span class="badge <?= $type_class ?>"><?= e($type_label) ?></span>
                            <?php if ($is_closed): ?>
                                <span class="badge badge-red"><i class="fa fa-ban"></i> Closed</span>
                            <?php elseif ($days !== null && $days <= 5): ?>
                                <span class="badge badge-amber"><i class="fa fa-clock"></i> <?= $days ?> day<?= $days !== 1 ? 's' : '' ?> left</span>
                            <?php elseif ($days !== null): ?>
                                <span class="badge badge-green"><i class="fa fa-calendar"></i> Closes <?= niceDate($job['deadline']) ?></span>
                            <?php else: ?>
                                <span class="badge badge-green"><i class="fa fa-circle-check"></i> Open</span>
                            <?php endif; ?>
                            <span style="font-size:12px;color:var(--text3);">
                                <i class="fa fa-users"></i> <?= $app_count ?> applicant<?= $app_count !== 1 ? 's' : '' ?>
                            </span>
                        </div>
                    </div>
                    <?php if ($job['salary']): ?>
                        <div style="text-align:right;">
                            <div style="font-size:22px;font-weight:800;color:var(--green);"><?= e($job['salary']) ?></div>
                            <div style="font-size:12px;color:var(--text3);margin-top:3px;">Salary</div>
                        </div>
                    <?php endif; ?>
                </div>

                <hr class="divider">

                <!-- Description -->
                <h3 style="font-size:16px;margin-bottom:12px;">About this Role</h3>
                <div style="color:var(--text2);line-height:1.9;font-size:15px;white-space:pre-line;">
                    <?= e($job['description']) ?>
                </div>

                <?php if ($job['requirements']): ?>
                    <hr class="divider">
                    <h3 style="font-size:16px;margin-bottom:12px;">Requirements</h3>
                    <div style="color:var(--text2);line-height:1.9;font-size:15px;white-space:pre-line;">
                        <?= e($job['requirements']) ?>
                    </div>
                <?php endif; ?>

            </div>
        </div>

        <!-- ── Sidebar ── -->
        <div>
            <!-- Apply card -->
            <div class="card" style="margin-bottom:16px;text-align:center;">
                <?php if (!isLoggedIn()): ?>
                    <p style="color:var(--text2);font-size:14px;margin-bottom:16px;">Sign in to apply for this role</p>
                    <a href="login.php" class="btn btn-primary btn-lg btn-block">
                        <i class="fa fa-sign-in-alt"></i> Login to Apply
                    </a>
        <?php elseif ($user && $user['role'] === 'seeker'): ?>
                    <?php if ($is_closed): ?>
                        <p style="color:var(--red);font-weight:600;margin-bottom:10px;">
                            <i class="fa fa-ban"></i> Applications Closed
                        </p>
                    <?php elseif ($already_applied): ?>
                        <div class="alert alert-success" style="justify-content:center;">
                            <i class="fa fa-circle-check"></i> Application Submitted!
                        </div>
                        <p style="color:var(--text2);font-size:13px;">You already applied. Good luck! 🤞</p>
                    <?php else: ?>
                        <p style="color:var(--text2);font-size:14px;margin-bottom:16px;">You're one click away from applying!</p>
                        <a href="apply.php?id=<?= $job['id'] ?>" class="btn btn-primary btn-lg btn-block">
                            <i class="fa fa-paper-plane"></i> Apply Now
                        </a>
                    <?php endif; ?>
                <?php elseif ($user && $user['role'] === 'employer'): ?>
                    <p style="color:var(--text2);font-size:14px;font-style:italic;">
                        <i class="fa fa-eye"></i> Viewing as employer
                    </p>
                <?php endif; ?>
            </div>

            <!-- Job details summary -->
            <div class="card">
                <h3 style="font-size:15px;margin-bottom:16px;">Job Details</h3>
                <div style="display:flex;flex-direction:column;gap:12px;">
                    <?php
                    $details = [
                        ['fa-briefcase',     'Type',       $job['job_type']],
                        ['fa-location-dot',  'Location',   $job['location']],
                        ['fa-money-bill',    'Salary',     $job['salary'] ?? 'Not specified'],
                        ['fa-calendar-plus', 'Posted',     niceDate($job['posted_at'])],
                        ['fa-calendar-xmark','Deadline',   $job['deadline'] ? niceDate($job['deadline']) : 'No deadline'],
                    ];
                    foreach ($details as $d): ?>
                    <div style="display:flex;align-items:center;gap:12px;">
                        <div style="width:34px;height:34px;border-radius:8px;background:rgba(79,110,247,0.1);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="fa <?= $d[0] ?>" style="color:var(--accent);font-size:14px;"></i>
                        </div>
                        <div>
                            <div style="font-size:11px;color:var(--text3);text-transform:uppercase;letter-spacing:0.05em;"><?= $d[1] ?></div>
                            <div style="font-size:14px;font-weight:600;margin-top:1px;"><?= e($d[2]) ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

    </div>
</div>
</main>

<style>
@media (max-width: 768px) {
    .detail-grid { grid-template-columns: 1fr !important; }
}
</style>

<?php require 'footer.php'; ?>
