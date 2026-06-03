<?php
require_once 'config.php';
$user = requireLogin();

if ($user['role'] !== 'seeker') {
    redirect('index.php');
}

// Get saved jobs
$sql = "SELECT j.*, u.name AS employer_name,
               (SELECT COUNT(*) FROM applications a WHERE a.job_id = j.id) AS app_count
        FROM jobs j
        JOIN users u ON j.employer_id = u.id
        JOIN saved_jobs f ON f.job_id = j.id
        WHERE f.user_id = ?
        ORDER BY f.saved_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user['id']);
$stmt->execute();
$result = $stmt->get_result();
$job_count = $result->num_rows;

require 'header.php';
?>

<main class="page-body">
<div class="container">

    <!-- Header -->
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;flex-wrap:wrap;gap:12px;">
        <div>
            <h1 class="h3">Saved Jobs</h1>
            <p style="color:var(--text2);font-size:14px;margin-top:3px;">
                <?= $job_count ?> saved job<?= $job_count !== 1 ? 's' : '' ?>
            </p>
        </div>
    </div>

    <!-- Jobs grid -->
    <?php if ($job_count > 0): ?>

        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:20px;">
        <?php while ($job = $result->fetch_assoc()):
            // Deadline
            $days      = daysUntil($job['deadline']);
            $is_closed = ($days !== null && $days < 0);

            // Job type badge class
            $type_class = jobTypeClass($job['job_type']);
            $type_label = jobTypeLabel($job['job_type']);
        ?>
        <article class="card card-hover reveal" style="<?= $is_closed ? 'opacity:0.55;' : '' ?>">


            <!-- Top row -->
            <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:14px;">
                <div style="width:44px;height:44px;border-radius:10px;background:rgba(79,110,247,0.12);display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0;">
                    💼
                </div>
                <div style="display:flex;gap:6px;flex-wrap:wrap;justify-content:flex-end;">
                    <form method="POST" action="favorite.php" style="display:inline;">
                        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                        <input type="hidden" name="job_id" value="<?= $job['id'] ?>">
                        <input type="hidden" name="action" value="remove">
                        <button type="submit" class="badge" style="background:var(--red-bg);color:var(--red);border:none;cursor:pointer;padding:4px 8px;">
                            <i class="fa fa-heart"></i> Saved
                        </button>
                    </form>
                    <span class="badge <?= $type_class ?>"><?= e($type_label) ?></span>
                    <?php if ($is_closed): ?>
                        <span class="badge badge-red">Closed</span>
                    <?php elseif ($days !== null && $days <= 5): ?>
                        <span class="badge badge-amber"><i class="fa fa-clock"></i> <?= $days ?>d left</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Title & company -->
            <h3 style="font-size:16px;font-weight:700;margin-bottom:5px;color:var(--text);">
                <?= e($job['title']) ?>
            </h3>
            <p style="font-size:13px;color:var(--text2);margin-bottom:12px;">
                <i class="fa fa-building"></i> <?= e($job['employer_name']) ?>
                &ensp;·&ensp;
                <i class="fa fa-location-dot"></i> <?= e($job['location']) ?>
            </p>

            <!-- Salary -->
            <?php if ($job['salary']): ?>
                <p style="color:var(--green);font-size:15px;font-weight:700;margin-bottom:14px;">
                    <i class="fa fa-money-bill-wave"></i> <?= e($job['salary']) ?>
                </p>
            <?php endif; ?>

            <!-- Description snippet -->
            <p style="color:var(--text2);font-size:13px;line-height:1.6;margin-bottom:18px;display:-webkit-box;-webkit-line-clamp:2;line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                <?= e(strip_tags($job['description'])) ?>
            </p>

            <!-- Footer -->
            <div style="display:flex;justify-content:space-between;align-items:center;margin-top:auto;">
                <span style="font-size:12px;color:var(--text3);">
                    <i class="fa fa-users"></i> <?= $job['app_count'] ?> applied
                    &ensp;·&ensp;
                    <?= niceDate($job['posted_at']) ?>
                </span>
                <?php if (!$is_closed): ?>
                    <a href="job-detail.php?id=<?= $job['id'] ?>" class="btn btn-primary btn-sm">
                        View <i class="fa fa-arrow-right"></i>
                    </a>
                <?php else: ?>
                    <span class="badge badge-red">Closed</span>
                <?php endif; ?>
            </div>
        </article>
        <?php endwhile; ?>
        </div>

    <?php else: ?>
        <div class="card reveal">
            <div class="empty-state">
                <div class="empty-icon">❤️</div>
                <h3>No saved jobs yet</h3>
                <p>Start saving jobs you're interested in to keep track of them here.</p>
                <a href="index.php" class="btn btn-primary">Browse Jobs</a>
            </div>
        </div>
    <?php endif; ?>

</div>
</main>

<?php require 'footer.php'; ?>