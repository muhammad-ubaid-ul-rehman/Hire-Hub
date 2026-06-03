<?php
require_once 'config.php';
$user = getCurrentUser();
$role = $user['role'] ?? null;

$seeker_id = (int)($_GET['id'] ?? 0);
if ($seeker_id <= 0) {
    redirect('candidates.php');
}

// Allow owners and admins to view all seeker profiles; others only approved seekers.
$where = 'u.id = ? AND u.role = "seeker"';
$params = [$seeker_id];
$types = 'i';
if (!isAdmin() && $user['id'] !== $seeker_id) {
    $where .= ' AND sp.status = "approved"';
}

$sql = "SELECT u.*, sp.*, 
               IFNULL(pv.view_count, 0) AS view_count,
               IFNULL(f.saved_by_current, 0) AS saved_by_current
        FROM users u
        JOIN seeker_profiles sp ON sp.user_id = u.id
        LEFT JOIN (
            SELECT seeker_id, COUNT(*) AS view_count
            FROM profile_views GROUP BY seeker_id
        ) pv ON pv.seeker_id = u.id
        LEFT JOIN (
            SELECT seeker_id, COUNT(*) AS saved_by_current
            FROM favorites WHERE employer_id = ? GROUP BY seeker_id
        ) f ON f.seeker_id = u.id
        WHERE $where
        LIMIT 1";

$stmt = $conn->prepare($sql);
if ($user && $user['role'] === 'employer') {
    $stmt->bind_param('ii', $user['id'], $seeker_id);
} else {
    $dummy = 0;
    $stmt->bind_param('ii', $dummy, $seeker_id);
}
$stmt->execute();
$profile = $stmt->get_result()->fetch_assoc();
if (!$profile) {
    require 'header.php';
    echo '<main class="page-body"><div class="container"><div class="card empty-state"><div class="empty-icon">⚠️</div><h3>Profile not available</h3><p>The requested profile cannot be viewed at this time.</p><a href="candidates.php" class="btn btn-primary">Browse Talent</a></div></div></main>'; 
    require 'footer.php';
    exit;
}

// Record view after selecting profile
$viewer_id = $user['id'] ?? null;
if (!$viewer_id || $viewer_id !== $seeker_id) {
    $track = $conn->prepare('INSERT INTO profile_views (seeker_id, viewer_id) VALUES (?, ?)');
    $viewer = $viewer_id ?: null;
    $track->bind_param('ii', $seeker_id, $viewer);
    $track->execute();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $role === 'employer') {
    verifyCsrf();
    if ($_POST['action'] === 'toggle_save') {
        $exists = $conn->prepare('SELECT id FROM favorites WHERE employer_id = ? AND seeker_id = ?');
        $exists->bind_param('ii', $user['id'], $seeker_id);
        $exists->execute();
        $res = $exists->get_result();
        if ($res->num_rows > 0) {
            $del = $conn->prepare('DELETE FROM favorites WHERE employer_id = ? AND seeker_id = ?');
            $del->bind_param('ii', $user['id'], $seeker_id);
            $del->execute();
            $_SESSION['flash'] = ['msg' => 'Candidate removed from favorites.', 'type' => 'info'];
        } else {
            $ins = $conn->prepare('INSERT INTO favorites (employer_id, seeker_id) VALUES (?, ?)');
            $ins->bind_param('ii', $user['id'], $seeker_id);
            $ins->execute();
            $_SESSION['flash'] = ['msg' => 'Candidate saved to favorites.', 'type' => 'success'];
        }
        redirect('candidate.php?id=' . $seeker_id);
    }
}

function prettySkills(string $skills): array {
    return array_filter(array_map('trim', explode(',', $skills)));
}

require 'header.php';
?>

<main class="page-body">
<div class="container">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:18px;margin-bottom:26px;">
        <div>
            <p class="label">Candidate Profile</p>
            <h1 class="h3"><?= e($profile['name']) ?></h1>
            <p style="color:var(--text2);font-size:14px;margin-top:6px;max-width:760px;">
                <?= e($profile['headline'] ?: $profile['job_title'] ?: 'Professional job seeker with a detailed candidate profile.') ?>
            </p>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
            <a href="candidates.php" class="btn btn-ghost"><i class="fa fa-arrow-left"></i> Back to Talent</a>
            <?php if ($role === 'employer'): ?>
                <form method="POST" action="candidate.php?id=<?= $seeker_id ?>" style="display:inline;">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <input type="hidden" name="action" value="toggle_save">
                    <button type="submit" class="btn <?= $profile['saved_by_current'] ? 'btn-purple' : 'btn-primary' ?>">
                        <i class="fa fa-bookmark"></i>
                        <?= $profile['saved_by_current'] ? 'Saved' : 'Save Candidate' ?>
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <div class="card" style="padding:0;overflow:hidden;">
        <div style="background:linear-gradient(135deg,rgba(79,110,247,0.22),rgba(139,92,246,0.18));padding:30px;">
            <div style="display:flex;align-items:center;gap:20px;flex-wrap:wrap;">
                <div style="width:92px;height:92px;border-radius:24px;overflow:hidden;background:var(--bg2);display:flex;align-items:center;justify-content:center;font-size:32px;font-weight:800;color:#fff;">
                    <?= $profile['profile_pic'] ? '<img src="' . e($profile['profile_pic']) . '" alt="Profile picture" style="width:100%;height:100%;object-fit:cover;">' : initials($profile['name']) ?>
                </div>
                <div style="flex:1;min-width:220px;">
                    <p class="label">Professional Summary</p>
                    <h2 class="h2" style="margin-top:6px;"><?= e($profile['job_title'] ?: $profile['headline'] ?: 'Professional Candidate') ?></h2>
                    <p style="color:var(--text2);font-size:14px;line-height:1.8;margin-top:10px;max-width:720px;">
                        <?= e($profile['career_objective'] ?: $profile['bio'] ?: 'No summary added yet.') ?>
                    </p>
                </div>
                <div style="text-align:right;min-width:160px;">
                    <div style="font-size:12px;color:var(--text3);text-transform:uppercase;letter-spacing:0.08em;margin-bottom:8px;">Profile views</div>
                    <div style="font-size:32px;font-weight:800;color:var(--accent);"><?= e($profile['view_count']) ?></div>
                </div>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:2fr 1fr;gap:1px;background:rgba(255,255,255,0.04);">
            <section style="background:var(--card);padding:28px;">
                <div style="display:flex;gap:18px;flex-wrap:wrap;margin-bottom:24px;">
                    <div style="flex:1;min-width:180px;">
                        <h4 class="label">Current Location</h4>
                        <p style="color:var(--text);font-weight:700;margin-top:8px;"><?= e($profile['location'] ?: 'Not specified') ?></p>
                    </div>
                    <div style="flex:1;min-width:180px;">
                        <h4 class="label">Experience Level</h4>
                        <p style="color:var(--text);font-weight:700;margin-top:8px;"><?= e(ucfirst($profile['experience_level'])) ?> / <?= e($profile['experience_years']) ?> years</p>
                    </div>
                    <div style="flex:1;min-width:180px;">
                        <h4 class="label">Availability</h4>
                        <p style="color:var(--text);font-weight:700;margin-top:8px;"><?= e(ucfirst(str_replace('-', ' ', $profile['availability']))) ?></p>
                    </div>
                    <div style="flex:1;min-width:180px;">
                        <h4 class="label">Expected Salary</h4>
                        <p style="color:var(--text);font-weight:700;margin-top:8px;"><?= e($profile['expected_salary'] ?: 'Negotiable') ?></p>
                    </div>
                </div>

                <div style="margin-bottom:24px;">
                    <h4 class="label">Professional Skills</h4>
                    <div style="display:flex;flex-wrap:wrap;gap:10px;margin-top:12px;">
                        <?php foreach (prettySkills($profile['skills'] ?? '') as $skill): ?>
                            <span class="badge badge-blue"><?= e($skill) ?></span>
                        <?php endforeach; ?>
                        <?php if (empty(trim($profile['skills'] ?? ''))): ?>
                            <span style="color:var(--text2);font-size:14px;">No skills added yet.</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                    <div>
                        <h4 class="label">Education</h4>
                        <p style="color:var(--text);margin-top:10px;white-space:pre-wrap;line-height:1.7;"><?= e($profile['education'] ?: 'Not available') ?></p>
                    </div>
                    <div>
                        <h4 class="label">Certifications</h4>
                        <p style="color:var(--text);margin-top:10px;white-space:pre-wrap;line-height:1.7;"><?= e($profile['certifications'] ?: 'Not available') ?></p>
                    </div>
                </div>

                <div style="margin-top:24px;">
                    <h4 class="label">Career Objective</h4>
                    <p style="color:var(--text);margin-top:10px;line-height:1.8;white-space:pre-wrap;"><?= e($profile['career_objective'] ?: 'No career objective provided yet.') ?></p>
                </div>
            </section>

            <aside style="background:var(--surface);padding:28px;display:flex;flex-direction:column;gap:18px;min-height:320px;">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;">
                    <div>
                        <h4 class="label">Contact</h4>
                        <p style="color:var(--text);margin-top:8px;"><?= e($profile['email']) ?></p>
                    </div>
                    <div style="font-size:12px;color:var(--text3);padding:8px 12px;border-radius:999px;background:rgba(255,255,255,0.04);">Profile</div>
                </div>

                <div>
                    <h4 class="label">Language Skills</h4>
                    <p style="color:var(--text);margin-top:10px;"><?= e($profile['languages'] ?: 'No languages listed') ?></p>
                </div>

                <div style="display:grid;gap:10px;">
                    <?php if ($profile['linkedin']): ?>
                        <a href="<?= e($profile['linkedin']) ?>" target="_blank" class="btn btn-ghost">LinkedIn Profile</a>
                    <?php endif; ?>
                    <?php if ($profile['portfolio']): ?>
                        <a href="<?= e($profile['portfolio']) ?>" target="_blank" class="btn btn-ghost">Portfolio</a>
                    <?php endif; ?>
                    <?php if ($profile['resume']): ?>
                        <a href="<?= e($profile['resume']) ?>" target="_blank" class="btn btn-primary">Download Resume</a>
                    <?php endif; ?>
                </div>

                <div style="background:rgba(255,255,255,0.05);border:1px solid var(--border2);border-radius:16px;padding:18px;">
                    <h4 class="label">Profile status</h4>
                    <p style="margin-top:10px;color:var(--text);font-weight:700;"><?= ucfirst($profile['status']) ?></p>
                </div>
            </aside>
        </div>
    </div>
</div>
</main>

<?php require 'footer.php'; ?>
