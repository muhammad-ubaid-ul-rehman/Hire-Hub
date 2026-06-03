<?php
require_once 'config.php';
$user    = requireLogin();
$user_id = (int)$user['id'];

require 'header.php';
?>

<main class="page-body">
<div class="container">

    <!-- Header -->
    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:32px;flex-wrap:wrap;gap:16px;">
        <div style="display:flex;align-items:center;gap:14px;">
            <div style="width:54px;height:54px;border-radius:14px;background:linear-gradient(135deg,var(--accent),var(--purple));display:flex;align-items:center;justify-content:center;font-size:22px;font-weight:800;color:#fff;flex-shrink:0;">
                <?= initials($user['name']) ?>
            </div>
            <div>
                <h1 class="h3">Welcome back, <?= e(explode(' ', $user['name'])[0]) ?> 👋</h1>
                <p style="color:var(--text2);font-size:14px;margin-top:2px;">
                    <?= $user['role'] === 'employer' ? 'Manage your listings and applicants' : 'Track your job applications' ?>
                </p>
            </div>
        </div>
        <?php if ($user['role'] === 'employer'): ?>
            <div style="display:flex;gap:10px;flex-wrap:wrap;">
                <a href="post-job.php" class="btn btn-primary">
                    <i class="fa fa-plus"></i> Post New Job
                </a>
                <a href="candidates.php" class="btn btn-ghost">
                    <i class="fa fa-users"></i> Browse Talent
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- ═══════════════════════════════════════════
         EMPLOYER DASHBOARD
         ═══════════════════════════════════════════ -->
    <?php if ($user['role'] === 'employer'):
        $jobs = $conn->prepare("SELECT j.*,
            (SELECT COUNT(*) FROM applications a WHERE a.job_id = j.id) AS app_count
            FROM jobs j WHERE j.employer_id = ? ORDER BY j.posted_at DESC");
        $jobs->bind_param('i', $user_id);
        $jobs->execute();
        $my_jobs = $jobs->get_result();

        // Stats
        $total_listings   = $my_jobs->num_rows;
        $total_applicants = $conn->prepare("SELECT COUNT(*) AS c FROM applications a JOIN jobs j ON a.job_id = j.id WHERE j.employer_id = ?");
        $total_applicants->bind_param('i', $user_id);
        $total_applicants->execute();
        $applicant_count = $total_applicants->get_result()->fetch_assoc()['c'];
    ?>

        <!-- Stats row -->
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:16px;margin-bottom:28px;">
            <?php
            $stats = [
                ['fa-list-check', $total_listings,  'Active Listings',    'var(--accent)'],
                ['fa-users',      $applicant_count,  'Total Applicants',   'var(--green)'],
            ];
            foreach ($stats as $s): ?>
            <div class="card" style="display:flex;align-items:center;gap:14px;">
                <div style="width:44px;height:44px;border-radius:10px;background:<?= $s[3] ?>22;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fa <?= $s[0] ?>" style="color:<?= $s[3] ?>;font-size:18px;"></i>
                </div>
                <div>
                    <div style="font-size:26px;font-weight:800;line-height:1;"><?= $s[1] ?></div>
                    <div style="font-size:13px;color:var(--text2);margin-top:3px;"><?= $s[2] ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Jobs table -->
        <div class="card">
            <h3 class="h3" style="margin-bottom:20px;">Your Job Listings</h3>

            <?php if ($total_listings === 0): ?>
                <div class="empty-state">
                    <div class="empty-icon">📋</div>
                    <h3>No listings yet</h3>
                    <p>Post your first job to start receiving applications.</p>
                    <a href="post-job.php" class="btn btn-primary">Post Your First Job</a>
                </div>
            <?php else: ?>
                <div style="overflow-x:auto;">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Job Title</th>
                                <th>Type</th>
                                <th>Location</th>
                                <th>Deadline</th>
                                <th>Applicants</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        $my_jobs->data_seek(0);
                        while ($job = $my_jobs->fetch_assoc()):
                            $days     = daysUntil($job['deadline']);
                            $closed   = ($days !== null && $days < 0);
                            $type_cls = jobTypeClass($job['job_type']);
                            $type_label = jobTypeLabel($job['job_type']);
                        ?>
                        <tr>
                            <td>
                                <a href="job-detail.php?id=<?= $job['id'] ?>"
                                   style="font-weight:600;color:var(--text);transition:color .2s;"
                                   onmouseover="this.style.color='var(--accent)'"
                                   onmouseout="this.style.color='var(--text)'">
                                    <?= e($job['title']) ?>
                                </a>
                            </td>
                            <td><span class="badge <?= $type_cls ?>"><?= e($type_label) ?></span></td>
                            <td style="color:var(--text2);"><?= e($job['location']) ?></td>
                            <td>
                                <?php if (!$job['deadline']): ?>
                                    <span style="color:var(--text3);font-size:13px;">No deadline</span>
                                <?php elseif ($closed): ?>
                                    <span class="badge badge-red">Closed</span>
                                <?php elseif ($days <= 5): ?>
                                    <span class="badge badge-amber"><?= niceDate($job['deadline']) ?></span>
                                <?php else: ?>
                                    <span style="font-size:13px;color:var(--text2);"><?= niceDate($job['deadline']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="view-applicants.php?id=<?= $job['id'] ?>"
                                   class="badge badge-green" style="text-decoration:none;cursor:pointer;">
                                    <i class="fa fa-users"></i> <?= $job['app_count'] ?>
                                </a>
                            </td>
                            <td>
                                <div style="display:flex;gap:6px;">
                                    <a href="view-applicants.php?id=<?= $job['id'] ?>" class="btn btn-ghost btn-sm">Applicants</a>
                                    <a href="delete-job.php?id=<?= $job['id'] ?>"
                                       onclick="return confirm('Delete this job listing and all its applications?')"
                                       class="btn btn-danger btn-sm">
                                        <i class="fa fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

    <!-- ═══════════════════════════════════════════
         SEEKER DASHBOARD
         ═══════════════════════════════════════════ -->
    <?php else:
        $apps = $conn->prepare(
            "SELECT j.id, j.title, j.location, j.job_type, a.applied_at, a.status
             FROM applications a JOIN jobs j ON a.job_id = j.id
             WHERE a.user_id = ? ORDER BY a.applied_at DESC"
        );
        $apps->bind_param('i', $user_id);
        $apps->execute();
        $my_apps = $apps->get_result();
        $total_apps = $my_apps->num_rows;
    ?>
        <!-- Stats -->
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:16px;margin-bottom:28px;">
            <div class="card" style="display:flex;align-items:center;gap:14px;">
                <div style="width:44px;height:44px;border-radius:10px;background:rgba(79,110,247,0.12);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fa fa-paper-plane" style="color:var(--accent);font-size:18px;"></i>
                </div>
                <div>
                    <div style="font-size:26px;font-weight:800;line-height:1;"><?= $total_apps ?></div>
                    <div style="font-size:13px;color:var(--text2);margin-top:3px;">Applications Sent</div>
                </div>
            </div>
        </div>

        <!-- Applications table -->
        <div class="card">
            <h3 class="h3" style="margin-bottom:20px;">Your Applications</h3>

            <?php if ($total_apps === 0): ?>
                <div class="empty-state">
                    <div class="empty-icon">📭</div>
                    <h3>No applications yet</h3>
                    <p>Start browsing jobs and apply — your applications will appear here.</p>
                    <a href="index.php" class="btn btn-primary">Browse Jobs</a>
                </div>
            <?php else: ?>
                <div style="overflow-x:auto;">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Job Title</th>
                                <th>Location</th>
                                <th>Type</th>
                                <th>Date Applied</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        $my_apps->data_seek(0);
                        while ($app = $my_apps->fetch_assoc()):
                            $status_map = [
                                'pending'     => ['badge-blue',   'Under Review'],
                                'reviewed'    => ['badge-purple', 'Reviewed'],
                                'shortlisted' => ['badge-green',  'Shortlisted 🎉'],
                                'rejected'    => ['badge-red',    'Not Selected'],
                            ];
                            [$sc, $sl] = $status_map[$app['status']] ?? ['badge-blue','Under Review'];
                            $type_cls = jobTypeClass($app['job_type']);
                            $type_label = jobTypeLabel($app['job_type']);
                        ?>
                        <tr>
                            <td>
                                <a href="job-detail.php?id=<?= $app['id'] ?>"
                                   style="font-weight:600;color:var(--text);"
                                   onmouseover="this.style.color='var(--accent)'"
                                   onmouseout="this.style.color='var(--text)'">
                                    <?= e($app['title']) ?>
                                </a>
                            </td>
                            <td style="color:var(--text2);"><?= e($app['location']) ?></td>
                            <td><span class="badge <?= $type_cls ?>"><?= e($type_label) ?></span></td>
                            <td style="color:var(--text2);font-size:13px;"><?= niceDate($app['applied_at']) ?></td>
                            <td><span class="badge <?= $sc ?>"><?= $sl ?></span></td>
                        </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</div>
</main>

<?php require 'footer.php'; ?>
