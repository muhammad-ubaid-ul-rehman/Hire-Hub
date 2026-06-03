<?php
require_once 'config.php';
$user    = requireRole('employer');
$user_id = (int)$user['id'];

$job_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($job_id <= 0) redirect('dashboard.php');

// Verify ownership
$chk = $conn->prepare("SELECT id, title FROM jobs WHERE id = ? AND employer_id = ?");
$chk->bind_param('ii', $job_id, $user_id);
$chk->execute();
$job = $chk->get_result()->fetch_assoc();
if (!$job) redirect('dashboard.php');

// Fetch applicants
$stmt = $conn->prepare(
    "SELECT a.*, COALESCE(a.status, 'pending') AS status, u.name AS seeker_name, u.email AS user_email, u.headline
     FROM applications a JOIN users u ON a.user_id = u.id
     WHERE a.job_id = ? ORDER BY a.applied_at DESC"
);
$stmt->bind_param('i', $job_id);
$stmt->execute();
$result = $stmt->get_result();

// Store applicants data for JavaScript
$applicants_data = [];
while ($row = $result->fetch_assoc()) {
    $applicants_data[] = $row;
}

// Reset result pointer for the while loop
$result->data_seek(0);

$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';
    $application_id = (int)($_POST['application_id'] ?? 0);

    if ($application_id > 0 && in_array($action, ['approve', 'reject', 'review'], true)) {
        if ($action === 'approve') {
            $status = 'shortlisted';
            $message = 'Applicant approved and shortlisted successfully.';
        } elseif ($action === 'reject') {
            $status = 'rejected';
            $message = 'Applicant has been marked as not approved.';
        } else {
            $status = 'reviewed';
            $message = 'Applicant has been moved to review status.';
        }
        $update = $conn->prepare('UPDATE applications SET status = ? WHERE id = ? AND job_id = ?');
        $update->bind_param('sii', $status, $application_id, $job_id);
        $update->execute();
        $_SESSION['flash'] = $message;

        redirect('view-applicants.php?id=' . $job_id);
    }
}

require 'header.php';
?>

<main class="page-body">
<div class="container">

    <?php if ($flash): ?>
        <div class="alert alert-success" style="margin-bottom:18px;">
            <i class="fa fa-circle-check"></i> <?= e($flash) ?>
        </div>
    <?php endif; ?>

    <a href="dashboard.php" style="display:inline-flex;align-items:center;gap:6px;color:var(--text2);font-size:14px;margin-bottom:24px;">
        <i class="fa fa-arrow-left"></i> Back to Dashboard
    </a>

    <div style="margin-bottom:28px;">
        <h1 class="h2">Applicants</h1>
        <p style="color:var(--text2);font-size:15px;margin-top:5px;">
            Job: <strong style="color:var(--text);"><?= e($job['title']) ?></strong>
            &ensp;·&ensp; <?= count($applicants_data) ?> candidate<?= count($applicants_data) !== 1 ? 's' : '' ?>
        </p>
    </div>

    <div class="card">
        <?php if (count($applicants_data) === 0): ?>
            <div class="empty-state">
                <div class="empty-icon">📭</div>
                <h3>No applicants yet</h3>
                <p>No one has applied for this position yet. Share the listing to attract candidates.</p>
            </div>
        <?php else: ?>
            <div style="overflow-x:auto;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Candidate</th>
                            <th>Contact</th>
                            <th>Skills</th>
                            <th>Resume</th>
                            <th>Applied</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php $i = 1; while ($a = $result->fetch_assoc()):
                        $status_map = [
                            'pending'     => ['badge-blue',   'Pending'],
                            'reviewed'    => ['badge-purple', 'In Review'],
                            'shortlisted' => ['badge-green',  'Approved'],
                            'rejected'    => ['badge-red',    'Not Approved'],
                        ];
                        [$sc, $sl] = $status_map[$a['status']] ?? ['badge-blue','Pending'];
                    ?>
                    <tr>
                        <td style="color:var(--text3);"><?= $i++ ?></td>
                        <td>
                            <div style="display:flex;align-items:center;gap:10px;">
                                <div style="width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,var(--accent),var(--purple));display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:#fff;flex-shrink:0;">
                                    <?= initials($a['full_name']) ?>
                                </div>
                                <div>
                                    <div style="font-weight:600;"><?= e($a['full_name']) ?></div>
                                    <?php if ($a['fathers_name']): ?>
                                        <div style="font-size:12px;color:var(--text2);">Father: <?= e($a['fathers_name']) ?></div>
                                    <?php endif; ?>
                                    <?php if ($a['headline']): ?>
                                        <div style="font-size:12px;color:var(--text2);"><?= e($a['headline']) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td>
                            <?php $display_email = $a['email'] ?: $a['user_email'] ?: $a['seeker_email'] ?? ''; ?>
                            <div style="font-size:14px;">
                                <?php if ($display_email): ?>
                                    <a href="mailto:<?= e($display_email) ?>" style="color:var(--accent);">
                                        <i class="fa fa-envelope"></i> <?= e($display_email) ?>
                                    </a>
                                <?php else: ?>
                                    <span style="color:var(--text3);font-size:13px;">No email</span>
                                <?php endif; ?>
                            </div>
                            <div style="font-size:12px;color:var(--text2);margin-top:2px;">
                                <?php if ($a['phone']): ?>
                                    <i class="fa fa-phone"></i> <?= e($a['phone']) ?>
                                <?php else: ?>
                                    <span style="color:var(--text3);font-size:13px;">No phone</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td style="max-width:200px;">
                            <div style="font-size:13px;color:var(--text2);line-height:1.4;">
                                <?= nl2br(e(substr($a['skills'], 0, 100))) ?>
                                <?php if (strlen($a['skills']) > 100): ?>...<?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <?php
                                $resumePath = $a['resume_path'] ?? '';
                                if ($resumePath && str_starts_with($resumePath, '/uploads/')) {
                                    $resumePath = '/job_portal' . $resumePath;
                                }
                                $resumeFile = $resumePath && str_starts_with($resumePath, '/')
                                    ? $_SERVER['DOCUMENT_ROOT'] . $resumePath
                                    : (__DIR__ . '/' . ltrim($resumePath, '/'));
                            ?>
                            <?php if ($resumePath && file_exists($resumeFile)): ?>
                                <a href="<?= e($resumePath) ?>" target="_blank" class="btn btn-sm btn-outline" style="font-size:12px;">
                                    <i class="fa fa-file"></i> View Resume
                                </a>
                            <?php else: ?>
                                <span style="color:var(--text3);font-size:12px;">No resume</span>
                            <?php endif; ?>
                            <br>
                            <button onclick="showDetails(<?= $a['id'] ?>)" class="btn btn-sm btn-link" style="font-size:12px;padding:2px;margin-top:4px;">
                                <i class="fa fa-eye"></i> View Details
                            </button>
                            <?php $showDecision = in_array($a['status'], ['pending', 'reviewed', ''], true); ?>
                            <?php if ($showDecision): ?>
                                <div style="display:flex;gap:6px;margin-top:8px;flex-wrap:wrap;">
                                    <form method="POST" action="view-applicants.php?id=<?= $job_id ?>" style="margin:0;">
                                        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <input type="hidden" name="application_id" value="<?= $a['id'] ?>">
                                        <button type="submit" class="btn btn-success btn-sm" style="font-size:12px;">Approve</button>
                                    </form>
                                    <form method="POST" action="view-applicants.php?id=<?= $job_id ?>" style="margin:0;">
                                        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <input type="hidden" name="application_id" value="<?= $a['id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm" style="font-size:12px;">Not Approve</button>
                                    </form>
                                    <?php if ($a['status'] === 'pending' || $a['status'] === ''): ?>
                                        <form method="POST" action="view-applicants.php?id=<?= $job_id ?>" style="margin:0;">
                                            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                            <input type="hidden" name="action" value="review">
                                            <input type="hidden" name="application_id" value="<?= $a['id'] ?>">
                                            <button type="submit" class="btn btn-purple btn-sm" style="font-size:12px;">Mark Review</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td style="color:var(--text2);font-size:13px;"><?= niceDate($a['applied_at']) ?></td>
                        <td><span class="badge <?= $sc ?>"><?= $sl ?></span></td>
                    </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

</div>
</main>

<!-- Application Details Modal -->
<div id="detailsModal" class="modal" style="display:none;">
    <div class="modal-content" style="max-width:600px;">
        <div class="modal-header">
            <h3>Application Details</h3>
            <button onclick="closeModal()" class="modal-close">&times;</button>
        </div>
        <div id="modalBody" class="modal-body">
            <!-- Content will be loaded here -->
        </div>
    </div>
</div>

<style>
.modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
}
.modal-content {
    background: white;
    border-radius: 8px;
    width: 90%;
    max-height: 80vh;
    overflow-y: auto;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
}
.modal-header {
    padding: 20px;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.modal-header h3 {
    margin: 0;
    color: var(--text);
}
.modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: var(--text2);
}
.modal-body {
    padding: 20px;
}
.detail-row {
    margin-bottom: 16px;
}
.detail-label {
    font-weight: 600;
    color: var(--text);
    margin-bottom: 4px;
}
.detail-value {
    color: var(--text2);
    line-height: 1.5;
}
</style>

<script>
const secretCsrfToken = <?= json_encode(csrfToken()) ?>;

function showDetails(appId) {
    // Find the application data
    const applicants = <?php echo json_encode($applicants_data); ?>;
    const applicant = applicants.find(a => a.id == appId);

    if (applicant) {
        const status = applicant.status || 'pending';
        const skills = applicant.skills || 'No skills provided';
        const previousWork = applicant.previous_work || 'No previous work experience details provided';
        const modalBody = document.getElementById('modalBody');
        modalBody.innerHTML = `
            <div class="detail-row">
                <div class="detail-label">Full Name</div>
                <div class="detail-value">${applicant.full_name}</div>
            </div>
            ${applicant.fathers_name ? `
            <div class="detail-row">
                <div class="detail-label">Father's Name</div>
                <div class="detail-value">${applicant.fathers_name}</div>
            </div>` : ''}
            <div class="detail-row">
                <div class="detail-label">Email</div>
                <div class="detail-value">
                    ${applicant.email || applicant.user_email || applicant.seeker_email ? `<a href="mailto:${applicant.email || applicant.user_email || applicant.seeker_email}">${applicant.email || applicant.user_email || applicant.seeker_email}</a>` : 'No email provided'}
                </div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Phone</div>
                <div class="detail-value">${applicant.phone ? applicant.phone : 'No phone provided'}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Skills & Technologies</div>
                <div class="detail-value">${skills.replace(/\n/g, '<br>')}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Previous Work Experience</div>
                <div class="detail-value">${previousWork.replace(/\n/g, '<br>')}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Applied On</div>
                <div class="detail-value">${new Date(applicant.applied_at).toLocaleDateString()}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Status</div>
                <div class="detail-value">${status === 'pending' ? 'Pending' : status === 'reviewed' ? 'In Review' : status === 'shortlisted' ? 'Approved' : status === 'rejected' ? 'Not Approved' : 'Pending'}</div>
            </div>
            ${status === 'pending' || status === 'reviewed' ? `
            <form method="POST" action="view-applicants.php?id=${applicant.job_id}" style="display:flex;gap:10px;flex-wrap:wrap;margin-top:20px;">
                <input type="hidden" name="csrf_token" value="${secretCsrfToken}">
                <input type="hidden" name="application_id" value="${applicant.id}">
                <button type="submit" name="action" value="approve" class="btn btn-success">Approve</button>
                <button type="submit" name="action" value="reject" class="btn btn-danger">Not Approve</button>
                ${status === 'pending' ? '<button type="submit" name="action" value="review" class="btn btn-purple">Mark Review</button>' : ''}
            </form>` : ''}
        `;
        document.getElementById('detailsModal').style.display = 'flex';
    }
}

function closeModal() {
    document.getElementById('detailsModal').style.display = 'none';
}

// Close modal when clicking outside
document.getElementById('detailsModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});
</script>

<?php require 'footer.php'; ?>
