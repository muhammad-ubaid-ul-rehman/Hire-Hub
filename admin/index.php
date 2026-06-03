<?php
ob_start();
require_once '../config.php';
requireAdminAccess();
$user = getCurrentUser();

$tab = $_GET['tab'] ?? 'jobs';
$valid_tabs = ['jobs', 'applications', 'users', 'messages', 'profiles'];
if (!in_array($tab, $valid_tabs, true)) {
    $tab = 'jobs';
}

$q = trim($_GET['q'] ?? '');
$searchTerm = '%' . $q . '%';

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';
    $return_tab = $_POST['return_tab'] ?? 'jobs';

    if ($action === 'delete_job') {
        $job_id = (int)($_POST['job_id'] ?? 0);
        if ($job_id > 0) {
            $delApps = $conn->prepare('DELETE FROM applications WHERE job_id = ?');
            $delApps->bind_param('i', $job_id);
            $delApps->execute();

            $delJob = $conn->prepare('DELETE FROM jobs WHERE id = ?');
            $delJob->bind_param('i', $job_id);
            $delJob->execute();

            $_SESSION['flash'] = ['msg' => 'Job and related applications deleted successfully.', 'type' => 'success'];
        }
    }

    if ($action === 'delete_application') {
        $app_id = (int)($_POST['application_id'] ?? 0);
        if ($app_id > 0) {
            $delApp = $conn->prepare('DELETE FROM applications WHERE id = ?');
            $delApp->bind_param('i', $app_id);
            $delApp->execute();
            $_SESSION['flash'] = ['msg' => 'Application deleted successfully.', 'type' => 'success'];
        }
    }

    if ($action === 'change_status') {
        $app_id = (int)($_POST['application_id'] ?? 0);
        $status = $_POST['status'] ?? 'pending';
        $valid_status = ['pending', 'reviewed', 'shortlisted', 'rejected'];
        if ($app_id > 0 && in_array($status, $valid_status, true)) {
            $upd = $conn->prepare('UPDATE applications SET status = ? WHERE id = ?');
            $upd->bind_param('si', $status, $app_id);
            $upd->execute();
            $_SESSION['flash'] = ['msg' => 'Application status updated.', 'type' => 'success'];
        }
    }

    if ($action === 'delete_user') {
        $delete_user_id = (int)($_POST['user_id'] ?? 0);
        if ($delete_user_id > 0 && $delete_user_id !== (int)$user['id']) {
            // Delete all applications by the user
            $delUserApps = $conn->prepare('DELETE FROM applications WHERE user_id = ?');
            $delUserApps->bind_param('i', $delete_user_id);
            $delUserApps->execute();

            // Delete any jobs posted by the user and related applications
            $jobIds = [];
            $getJobs = $conn->prepare('SELECT id FROM jobs WHERE employer_id = ?');
            $getJobs->bind_param('i', $delete_user_id);
            $getJobs->execute();
            $resultJobs = $getJobs->get_result();
            while ($row = $resultJobs->fetch_assoc()) {
                $jobIds[] = (int)$row['id'];
            }
            foreach ($jobIds as $jid) {
                $delCampaignApps = $conn->prepare('DELETE FROM applications WHERE job_id = ?');
                $delCampaignApps->bind_param('i', $jid);
                $delCampaignApps->execute();
            }
            $delJobs = $conn->prepare('DELETE FROM jobs WHERE employer_id = ?');
            $delJobs->bind_param('i', $delete_user_id);
            $delJobs->execute();

            $delUser = $conn->prepare('DELETE FROM users WHERE id = ?');
            $delUser->bind_param('i', $delete_user_id);
            $delUser->execute();

            $_SESSION['flash'] = ['msg' => 'User and all related content removed.', 'type' => 'success'];
        } else {
            $_SESSION['flash'] = ['msg' => 'You cannot delete your own admin account here.', 'type' => 'error'];
        }
    }

    if ($action === 'delete_message') {
        $message_id = (int)($_POST['message_id'] ?? 0);
        if ($message_id > 0) {
            $delMsg = $conn->prepare('DELETE FROM contact_messages WHERE id = ?');
            $delMsg->bind_param('i', $message_id);
            $delMsg->execute();
            $_SESSION['flash'] = ['msg' => 'Message deleted successfully.', 'type' => 'success'];
        }
    }

    if ($action === 'toggle_message_read') {
        $message_id = (int)($_POST['message_id'] ?? 0);
        $new_state = (int)($_POST['is_read'] ?? 0);
        if ($message_id > 0) {
            $updMsg = $conn->prepare('UPDATE contact_messages SET is_read = ? WHERE id = ?');
            $updMsg->bind_param('ii', $new_state, $message_id);
            $updMsg->execute();
            $_SESSION['flash'] = ['msg' => $new_state ? 'Message marked as read.' : 'Message marked as unread.', 'type' => 'success'];
        }
    }

    if ($action === 'approve_profile') {
        $profile_id = (int)($_POST['profile_id'] ?? 0);
        if ($profile_id > 0) {
            $upd = $conn->prepare('UPDATE seeker_profiles SET status = "approved" WHERE id = ?');
            $upd->bind_param('i', $profile_id);
            $upd->execute();
            $_SESSION['flash'] = ['msg' => 'Seeker profile approved and is now publicly visible.', 'type' => 'success'];
        }
    }

    if ($action === 'reject_profile') {
        $profile_id = (int)($_POST['profile_id'] ?? 0);
        if ($profile_id > 0) {
            $upd = $conn->prepare('UPDATE seeker_profiles SET status = "rejected" WHERE id = ?');
            $upd->bind_param('i', $profile_id);
            $upd->execute();
            $_SESSION['flash'] = ['msg' => 'Seeker profile rejected.', 'type' => 'info'];
        }
    }

    if ($action === 'activate_jobs') {
        $job_ids = $_POST['job_ids'] ?? [];
        $job_ids = array_map('intval', array_filter($job_ids));
        
        if (!empty($job_ids)) {
            $placeholders = implode(',', array_fill(0, count($job_ids), '?'));
            $stmt = $conn->prepare("UPDATE jobs SET status = 'active' WHERE id IN ($placeholders)");
            $stmt->bind_param(str_repeat('i', count($job_ids)), ...$job_ids);
            if ($stmt->execute()) {
                $count = count($job_ids);
                $_SESSION['flash'] = ['msg' => "✓ {$count} job(s) activated successfully!", 'type' => 'success'];
            } else {
                $_SESSION['flash'] = ['msg' => 'Error activating jobs.', 'type' => 'error'];
            }
        }
    }

    if ($action === 'activate_all_jobs') {
        $result = $conn->query("UPDATE jobs SET status = 'active' WHERE status != 'active'");
        $count = $conn->affected_rows;
        if ($count > 0) {
            $_SESSION['flash'] = ['msg' => "✓ All {$count} inactive job(s) activated!", 'type' => 'success'];
        } else {
            $_SESSION['flash'] = ['msg' => 'All jobs are already active.', 'type' => 'info'];
        }
    }
    redirect('index.php?tab=' . urlencode($return_tab));
}

// Load counts and data
$summary = $conn->query('SELECT
    (SELECT COUNT(*) FROM jobs) AS total_jobs,
    (SELECT COUNT(*) FROM applications) AS total_applications,
    (SELECT COUNT(*) FROM users) AS total_users,
    (SELECT COUNT(*) FROM users WHERE role = "seeker") AS total_seekers,
    (SELECT COUNT(*) FROM users WHERE role = "employer") AS total_employers,
    (SELECT COUNT(*) FROM contact_messages WHERE is_read = 0) AS unread_messages,
    (SELECT COUNT(*) FROM seeker_profiles WHERE status = "pending") AS pending_profiles,
    (SELECT COUNT(*) FROM seeker_profiles WHERE status = "approved") AS approved_profiles
')->fetch_assoc();

$jobs_sql =
    'SELECT j.*, u.name AS employer_name, u.email AS employer_email,
        (SELECT COUNT(*) FROM applications a WHERE a.job_id = j.id) AS app_count
     FROM jobs j
     JOIN users u ON j.employer_id = u.id';
if ($q) {
    $jobs_sql .= ' WHERE (j.title LIKE ? OR j.location LIKE ? OR u.name LIKE ?)';
}
$jobs_sql .= ' ORDER BY j.posted_at DESC';
$jobs = $conn->prepare($jobs_sql);
if ($q) {
    $jobs->bind_param('sss', $searchTerm, $searchTerm, $searchTerm);
}
$jobs->execute();
$jobs_result = $jobs->get_result();

$applications_sql =
    'SELECT a.*, u.name AS seeker_name, a.email AS seeker_email, a.phone AS seeker_phone,
        j.title AS job_title, j.id AS job_id
     FROM applications a
     JOIN users u ON a.user_id = u.id
     JOIN jobs j ON a.job_id = j.id';
if ($q) {
    $applications_sql .= ' WHERE (u.name LIKE ? OR a.full_name LIKE ? OR j.title LIKE ? OR a.email LIKE ? OR a.status LIKE ?)';
}
$applications_sql .= ' ORDER BY a.applied_at DESC';
$applications = $conn->prepare($applications_sql);
if ($q) {
    $applications->bind_param('sssss', $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm);
}
$applications->execute();
$applications_result = $applications->get_result();

$users_sql =
    'SELECT u.*,
        (SELECT COUNT(*) FROM jobs WHERE employer_id = u.id) AS job_count,
        (SELECT COUNT(*) FROM applications WHERE user_id = u.id) AS application_count
     FROM users u';
if ($q) {
    $users_sql .= ' WHERE (u.name LIKE ? OR u.email LIKE ? OR u.role LIKE ?)';
}
$users_sql .= ' ORDER BY u.id DESC';
$users = $conn->prepare($users_sql);
if ($q) {
    $users->bind_param('sss', $searchTerm, $searchTerm, $searchTerm);
}
$users->execute();
$users_result = $users->get_result();

$messages_sql = 'SELECT * FROM contact_messages';
if ($q) {
    $messages_sql .= ' WHERE (name LIKE ? OR email LIKE ? OR subject LIKE ? OR message LIKE ?)';
}
$messages_sql .= ' ORDER BY sent_at DESC';
$messages = $conn->prepare($messages_sql);
if ($q) {
    $messages->bind_param('ssss', $searchTerm, $searchTerm, $searchTerm, $searchTerm);
}
$messages->execute();
$messages_result = $messages->get_result();

$profiles_sql =
    'SELECT sp.*, u.name, u.email, u.location,
        (SELECT COUNT(*) FROM profile_views pv WHERE pv.seeker_id = u.id) AS view_count
     FROM seeker_profiles sp
     JOIN users u ON sp.user_id = u.id';
if ($q) {
    $profiles_sql .= ' WHERE (u.name LIKE ? OR sp.job_title LIKE ? OR sp.skills LIKE ? OR sp.career_objective LIKE ?)';
}
$profiles_sql .= ' ORDER BY sp.status DESC, sp.id DESC';
$profiles = $conn->prepare($profiles_sql);
if ($q) {
    $profiles->bind_param('ssss', $searchTerm, $searchTerm, $searchTerm, $searchTerm);
}
$profiles->execute();
$profiles_result = $profiles->get_result();

require '../header.php';
?>

<main class="page-body">
<div class="container">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:16px;margin-bottom:28px;">
        <div>
            <h1 class="h3">Admin Panel</h1>
            <p style="color:var(--text2);font-size:14px;margin-top:4px;">
                Manage jobs, applications, users, and advanced platform requests.
            </p>
        </div>
        <a href="../dashboard.php" class="btn btn-outline">
            <i class="fa fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] === 'error' ? 'error' : ($flash['type'] === 'success' ? 'success' : 'info') ?>">
            <i class="fa fa-<?= $flash['type'] === 'error' ? 'circle-xmark' : ($flash['type'] === 'success' ? 'circle-check' : 'info-circle') ?>"></i>
            <?= e($flash['msg']) ?>
        </div>
    <?php endif; ?>

    <div style="display:grid;grid-template-columns:repeat(5,minmax(160px,1fr));gap:16px;margin-bottom:24px;">
        <div class="card" style="border-left:4px solid var(--accent);">
            <div style="font-size:12px;color:var(--text3);text-transform:uppercase;letter-spacing:0.08em;margin-bottom:6px;">Total Jobs</div>
            <div style="font-size:28px;font-weight:800;"><?= e($summary['total_jobs']) ?></div>
        </div>
        <div class="card" style="border-left:4px solid var(--green);">
            <div style="font-size:12px;color:var(--text3);text-transform:uppercase;letter-spacing:0.08em;margin-bottom:6px;">Applications</div>
            <div style="font-size:28px;font-weight:800;"><?= e($summary['total_applications']) ?></div>
        </div>
        <div class="card" style="border-left:4px solid var(--purple);">
            <div style="font-size:12px;color:var(--text3);text-transform:uppercase;letter-spacing:0.08em;margin-bottom:6px;">Employers</div>
            <div style="font-size:28px;font-weight:800;"><?= e($summary['total_employers']) ?></div>
        </div>
        <div class="card" style="border-left:4px solid var(--blue);">
            <div style="font-size:12px;color:var(--text3);text-transform:uppercase;letter-spacing:0.08em;margin-bottom:6px;">Seekers</div>
            <div style="font-size:28px;font-weight:800;"><?= e($summary['total_seekers']) ?></div>
        </div>
        <div class="card" style="border-left:4px solid var(--amber);">
            <div style="font-size:12px;color:var(--text3);text-transform:uppercase;letter-spacing:0.08em;margin-bottom:6px;">Pending Profiles</div>
            <div style="font-size:28px;font-weight:800;color:var(--amber);"><?= e($summary['pending_profiles']) ?></div>
        </div>
    </div>

    <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:18px;">
        <?php foreach ($valid_tabs as $value): ?>
            <a href="?tab=<?= $value ?><?= $q ? '&q=' . urlencode($q) : '' ?>" class="nav-link <?= $tab === $value ? 'active' : '' ?>" style="padding:10px 14px;">
                <?= ucfirst($value) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <form method="get" style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;margin-bottom:24px;">
        <input type="hidden" name="tab" value="<?= e($tab) ?>">
        <input type="text" name="q" class="form-control" placeholder="Search <?= ucfirst($tab) ?>..." value="<?= e($q) ?>" style="min-width:260px;">
        <button type="submit" class="btn btn-primary btn-sm">Search</button>
        <?php if ($q): ?>
            <a href="index.php?tab=<?= e($tab) ?>" class="btn btn-ghost btn-sm">Clear</a>
        <?php endif; ?>
    </form>

    <?php if ($tab === 'jobs'): ?>
        <div class="card">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
                <h3 class="h3" style="margin:0;">All Job Listings</h3>
                <div style="display:flex;gap:10px;flex-wrap:wrap;">
                    <button type="button" class="btn btn-primary btn-sm" onclick="activateSelectedJobs()" id="activateBtn" style="display:none;">
                        <i class="fa fa-check-circle"></i> Activate Selected
                    </button>
                    <form method="POST" action="index.php?tab=jobs" style="display:inline;">
                        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                        <input type="hidden" name="action" value="activate_all_jobs">
                        <input type="hidden" name="return_tab" value="jobs">
                        <button type="submit" class="btn btn-success btn-sm">
                            <i class="fa fa-bolt"></i> Activate All Inactive
                        </button>
                    </form>
                </div>
            </div>
            
            <?php if ($jobs_result->num_rows === 0): ?>
                <div class="empty-state">
                    <div class="empty-icon">📦</div>
                    <h3>No job listings available</h3>
                    <p>There are currently no jobs posted on the platform.</p>
                </div>
            <?php else: ?>
                <form method="POST" action="index.php?tab=jobs" id="jobsForm">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <input type="hidden" name="action" value="activate_jobs">
                    <input type="hidden" name="return_tab" value="jobs">
                    
                    <div style="overflow-x:auto;">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th style="width:40px;"><input type="checkbox" id="selectAllJobs" onchange="toggleAllJobCheckboxes()"></th>
                                    <th>Job</th>
                                    <th>Employer</th>
                                    <th>Location</th>
                                    <th>Status</th>
                                    <th>Applicants</th>
                                    <th>Posted</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php $jobs_result->data_seek(0); while ($job = $jobs_result->fetch_assoc()): ?>
                                <tr>
                                    <td><input type="checkbox" name="job_ids[]" value="<?= $job['id'] ?>" class="jobCheckbox" onchange="updateActivateBtn()"></td>
                                    <td style="min-width:200px;">
                                        <a href="../job-detail.php?id=<?= $job['id'] ?>" style="font-weight:600;color:var(--text);">
                                            <?= e($job['title']) ?>
                                        </a>
                                    </td>
                                    <td style="color:var(--text2);min-width:180px;">
                                        <?= e($job['employer_name']) ?><br>
                                        <a href="mailto:<?= e($job['employer_email']) ?>" style="color:var(--accent);font-size:13px;"><?= e($job['employer_email']) ?></a>
                                    </td>
                                    <td><?= e($job['location']) ?></td>
                                    <td>
                                        <span class="badge <?= $job['status'] === 'active' ? 'badge-green' : 'badge-amber' ?>">
                                            <?= ucfirst($job['status']) ?>
                                        </span>
                                    </td>
                                    <td><span class="badge badge-blue"><?= e($job['app_count']) ?></span></td>
                                    <td style="color:var(--text2);font-size:13px;"><?= niceDate($job['posted_at']) ?></td>
                                    <td>
                                        <div style="display:flex;gap:6px;flex-wrap:wrap;">
                                            <a href="../job-detail.php?id=<?= $job['id'] ?>" class="btn btn-ghost btn-sm">View</a>
                                            <form method="POST" action="index.php?tab=jobs" style="display:inline;" onsubmit="return confirm('Delete this job and all its applications?');">
                                                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                                <input type="hidden" name="action" value="delete_job">
                                                <input type="hidden" name="job_id" value="<?= $job['id'] ?>">
                                                <input type="hidden" name="return_tab" value="jobs">
                                                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    
        <script>
            function toggleAllJobCheckboxes() {
                const selectAll = document.getElementById('selectAllJobs');
                const checkboxes = document.querySelectorAll('.jobCheckbox');
                checkboxes.forEach(cb => cb.checked = selectAll.checked);
                updateActivateBtn();
            }
            
            function updateActivateBtn() {
                const checked = document.querySelectorAll('.jobCheckbox:checked');
                const btn = document.getElementById('activateBtn');
                btn.style.display = checked.length > 0 ? 'inline-flex' : 'none';
            }
            
            function activateSelectedJobs() {
                const checked = document.querySelectorAll('.jobCheckbox:checked');
                if (checked.length === 0) {
                    alert('Please select at least one job.');
                    return;
                }
                if (confirm('Activate ' + checked.length + ' selected job(s)?')) {
                    document.getElementById('jobsForm').submit();
                }
            }
        </script>
    <?php elseif ($tab === 'applications'): ?>
        <div class="card">
            <h3 class="h3" style="margin-bottom:20px;">All Applications</h3>
            <?php if ($applications_result->num_rows === 0): ?>
                <div class="empty-state">
                    <div class="empty-icon">📩</div>
                    <h3>No applications yet</h3>
                    <p>No one has applied yet. Check back later.</p>
                </div>
            <?php else: ?>
                <div style="overflow-x:auto;">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Candidate</th>
                                <th>Job</th>
                                <th>Contact</th>
                                <th>Status</th>
                                <th>Applied</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php while ($app = $applications_result->fetch_assoc()): ?>
                            <tr>
                                <td style="min-width:220px;">
                                    <strong><?= e($app['seeker_name']) ?></strong><br>
                                    <?= e($app['full_name']) ? e($app['full_name']) : '' ?>
                                </td>
                                <td style="min-width:200px;">
                                    <a href="../job-detail.php?id=<?= $app['job_id'] ?>" style="font-weight:600;color:var(--text);">
                                        <?= e($app['job_title']) ?>
                                    </a>
                                </td>
                                <td style="min-width:180px;">
                                    <a href="mailto:<?= e($app['seeker_email']) ?>" style="color:var(--accent);font-size:13px;">
                                        <?= e($app['seeker_email']) ?>
                                    </a>
                                    <div style="font-size:13px;color:var(--text2);margin-top:4px;">
                                        <?= $app['phone'] ? e($app['phone']) : '<span style="color:var(--text3);font-size:12px;">No phone</span>' ?>
                                    </div>
                                </td>
                                <td style="min-width:180px;">
                                    <form method="POST" style="display:grid;gap:6px;">
                                        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                        <input type="hidden" name="action" value="change_status">
                                        <input type="hidden" name="application_id" value="<?= $app['id'] ?>">
                                        <input type="hidden" name="return_tab" value="applications">
                                        <select name="status" class="form-control" style="min-width:140px;">
                                            <?php foreach (['pending'=>'Pending','reviewed'=>'Reviewed','shortlisted'=>'Shortlisted','rejected'=>'Rejected'] as $val => $label): ?>
                                                <option value="<?= $val ?>" <?= $app['status'] === $val ? 'selected' : '' ?>><?= $label ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" class="btn btn-sm btn-primary">Update</button>
                                    </form>
                                </td>
                                <td><?= niceDate($app['applied_at']) ?></td>
                                <td>
                                    <div style="display:flex;gap:6px;flex-wrap:wrap;">
                                        <?php
                                            $resumeUrl = $app['resume_path'] ?? '';
                                            if ($resumeUrl && str_starts_with($resumeUrl, '/uploads/')) {
                                                $resumeUrl = '/job_portal' . $resumeUrl;
                                            }
                                        ?>
                                        <?php if ($resumeUrl): ?>
                                            <a href="<?= e($resumeUrl) ?>" target="_blank" class="btn btn-sm btn-outline">Resume</a>
                                        <?php endif; ?>
                                        <form method="POST" action="index.php?tab=applications" style="display:inline;" onsubmit="return confirm('Delete this application permanently?');">
                                            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                            <input type="hidden" name="action" value="delete_application">
                                            <input type="hidden" name="application_id" value="<?= $app['id'] ?>">
                                            <input type="hidden" name="return_tab" value="applications">
                                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    <?php elseif ($tab === 'users'): ?>
        <div class="card">
            <h3 class="h3" style="margin-bottom:20px;">User Management</h3>
            <?php if ($users_result->num_rows === 0): ?>
                <div class="empty-state">
                    <div class="empty-icon">👥</div>
                    <h3>No users found</h3>
                </div>
            <?php else: ?>
                <div style="overflow-x:auto;">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Jobs</th>
                                <th>Applications</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php while ($u = $users_result->fetch_assoc()): ?>
                            <tr>
                                <td><?= e($u['name']) ?></td>
                                <td><a href="mailto:<?= e($u['email']) ?>" style="color:var(--accent);"><?= e($u['email']) ?></a></td>
                                <td><?= ucfirst(e($u['role'])) ?></td>
                                <td><?= e($u['job_count']) ?></td>
                                <td><?= e($u['application_count']) ?></td>
                                <td>
                                    <?php if ((int)$u['id'] !== (int)$user['id']): ?>
                                        <form method="POST" action="index.php?tab=users" style="display:inline;" onsubmit="return confirm('Delete this user and all related content?');">
                                            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                            <input type="hidden" name="action" value="delete_user">
                                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                            <input type="hidden" name="return_tab" value="users">
                                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                        </form>
                                    <?php else: ?>
                                        <span style="color:var(--text3);font-size:13px;">Current admin</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    <?php elseif ($tab === 'messages'): ?>
        <div class="card">
            <h3 class="h3" style="margin-bottom:20px;">Contact Messages</h3>
            <?php if ($messages_result->num_rows === 0): ?>
                <div class="empty-state">
                    <div class="empty-icon">📬</div>
                    <h3>No messages yet</h3>
                    <p>No one has contacted you yet.</p>
                </div>
            <?php else: ?>
                <div style="overflow-x:auto;">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Subject</th>
                                <th>Message</th>
                                <th>Date / Time</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php while ($msg = $messages_result->fetch_assoc()): ?>
                            <tr>
                                <td style="font-weight:600;min-width:170px;">
                                    <?= e($msg['name']) ?>
                                    <?php if ($msg['is_read']): ?>
                                        <span class="badge badge-green" style="margin-left:6px;">Read</span>
                                    <?php else: ?>
                                        <span class="badge badge-red" style="margin-left:6px;">Unread</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="mailto:<?= e($msg['email']) ?>" style="color:var(--accent);">
                                        <?= e($msg['email']) ?>
                                    </a>
                                </td>
                                <td style="max-width:200px;"><?= e($msg['subject']) ?></td>
                                <td style="max-width:300px;word-wrap:break-word;"><?= nl2br(e(substr($msg['message'], 0, 200))) ?><?php if (strlen($msg['message']) > 200): ?>...<?php endif; ?></td>
                                <td style="font-size:13px;color:var(--text2);">
                                    <?= isset($msg['sent_at']) && $msg['sent_at'] ? niceDate($msg['sent_at']) : '—' ?>
                                </td>
                                <td>
                                    <form method="POST" action="index.php?tab=messages" style="display:inline;" onsubmit="return confirm('Delete this message permanently?');">
                                        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                        <input type="hidden" name="action" value="delete_message">
                                        <input type="hidden" name="message_id" value="<?= $msg['id'] ?>">
                                        <input type="hidden" name="return_tab" value="messages">
                                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                    </form>
                                    <form method="POST" action="index.php?tab=messages" style="display:inline;margin-left:6px;" onsubmit="return true;">
                                        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                        <input type="hidden" name="action" value="toggle_message_read">
                                        <input type="hidden" name="message_id" value="<?= $msg['id'] ?>">
                                        <input type="hidden" name="is_read" value="<?= $msg['is_read'] ? 0 : 1 ?>">
                                        <input type="hidden" name="return_tab" value="messages">
                                        <button type="submit" class="btn btn-sm <?= $msg['is_read'] ? 'btn-ghost' : 'btn-success' ?>">
                                            <?= $msg['is_read'] ? 'Mark Unread' : 'Mark Read' ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    <?php elseif ($tab === 'profiles'): ?>
        <div class="card">
            <h3 class="h3" style="margin-bottom:20px;">Candidate Profiles</h3>
            <?php if ($profiles_result->num_rows === 0): ?>
                <div class="empty-state">
                    <div class="empty-icon">👤</div>
                    <h3>No seeker profiles</h3>
                    <p>No job seeker profiles have been created yet.</p>
                </div>
            <?php else: ?>
                <div style="overflow-x:auto;">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Seeker</th>
                                <th>Job Title</th>
                                <th>Location</th>
                                <th>Experience</th>
                                <th>Status</th>
                                <th>Views</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php while ($prof = $profiles_result->fetch_assoc()): ?>
                            <tr>
                                <td style="font-weight:600;"><?= e($prof['name']) ?><br><span style="color:var(--text2);font-size:13px;"><?= e($prof['email']) ?></span></td>
                                <td><?= e($prof['job_title'] ?? 'Not specified') ?></td>
                                <td><?= e($prof['location'] ?? 'Not specified') ?></td>
                                <td><?= e($prof['experience_years']) ?> yrs</td>
                                <td><span class="badge <?= $prof['status'] === 'approved' ? 'badge-green' : ($prof['status'] === 'rejected' ? 'badge-red' : 'badge-blue') ?>"><?= ucfirst($prof['status']) ?></span></td>
                                <td><?= e($prof['view_count']) ?></td>
                                <td>
                                    <div style="display:flex;gap:6px;flex-wrap:wrap;">
                                        <a href="../candidate.php?id=<?= e($prof['user_id']) ?>" class="btn btn-ghost btn-sm">View</a>
                                        <?php if ($prof['status'] !== 'approved'): ?>
                                            <form method="POST" action="index.php?tab=profiles" style="display:inline;">
                                                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                                <input type="hidden" name="action" value="approve_profile">
                                                <input type="hidden" name="profile_id" value="<?= $prof['id'] ?>">
                                                <input type="hidden" name="return_tab" value="profiles">
                                                <button type="submit" class="btn btn-success btn-sm">Approve</button>
                                            </form>
                                        <?php endif; ?>
                                        <?php if ($prof['status'] !== 'rejected'): ?>
                                            <form method="POST" action="index.php?tab=profiles" style="display:inline;">
                                                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                                <input type="hidden" name="action" value="reject_profile">
                                                <input type="hidden" name="profile_id" value="<?= $prof['id'] ?>">
                                                <input type="hidden" name="return_tab" value="profiles">
                                                <button type="submit" class="btn btn-danger btn-sm">Reject</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
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

<?php require '../footer.php'; ?>
<?php ob_end_flush(); ?>
