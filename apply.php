<?php
require_once 'config.php';

if (!isLoggedIn()) redirect('login.php');

$job_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user_id = (int)$_SESSION['user_id'];

if ($job_id <= 0) redirect('index.php');

// Verify job exists
$stmt = $conn->prepare("SELECT id, title FROM jobs WHERE id = ?");
$stmt->bind_param('i', $job_id);
$stmt->execute();
$job = $stmt->get_result()->fetch_assoc();
if (!$job) redirect('index.php');

// Check role
$user = getCurrentUser();
if ($user['role'] !== 'seeker') redirect('job-detail.php?id=' . $job_id);

// Check deadline
$dstmt = $conn->prepare("SELECT deadline FROM jobs WHERE id = ?");
$dstmt->bind_param('i', $job_id);
$dstmt->execute();
$row = $dstmt->get_result()->fetch_assoc();
if ($row['deadline'] && strtotime($row['deadline']) < time()) {
    $_SESSION['flash'] = ['msg' => 'This job listing has closed.', 'type' => 'error'];
    redirect('job-detail.php?id=' . $job_id);
}

// Check if already applied
$check_stmt = $conn->prepare("SELECT id FROM applications WHERE job_id = ? AND user_id = ?");
$check_stmt->bind_param('ii', $job_id, $user_id);
$check_stmt->execute();
if ($check_stmt->get_result()->num_rows > 0) {
    $_SESSION['flash'] = ['msg' => 'You have already applied for this position.', 'type' => 'info'];
    redirect('job-detail.php?id=' . $job_id);
}

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $full_name = trim($_POST['full_name'] ?? '');
    $fathers_name = trim($_POST['fathers_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $skills = trim($_POST['skills'] ?? '');
    $previous_work = trim($_POST['previous_work'] ?? '');

    // Validation
    if (!$full_name) {
        $error = 'Full name is required.';
    } elseif (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Valid email address is required.';
    } elseif (!$phone) {
        $error = 'Phone number is required.';
    } elseif (!$skills) {
        $error = 'Please describe your skills.';
    } elseif (!$previous_work) {
        $error = 'Please provide information about your previous work experience.';
    } else {
        // Handle file upload
        $resume_path = '';
        if (isset($_FILES['resume']) && $_FILES['resume']['error'] === UPLOAD_ERR_OK) {
            $upload_dir  = __DIR__ . '/uploads/resumes/';
            $upload_url  = '/job_portal/uploads/resumes/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $file_extension = strtolower(pathinfo($_FILES['resume']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['pdf', 'doc', 'docx'];

            if (!in_array($file_extension, $allowed_extensions)) {
                $error = 'Resume must be a PDF, DOC, or DOCX file.';
            } elseif ($_FILES['resume']['size'] > 5 * 1024 * 1024) { // 5MB limit
                $error = 'Resume file size must be less than 5MB.';
            } else {
                $new_filename = 'resume_' . $user_id . '_' . $job_id . '_' . time() . '.' . $file_extension;
                $resume_path = $upload_url . $new_filename;
                $resume_file = $upload_dir . $new_filename;

                if (move_uploaded_file($_FILES['resume']['tmp_name'], $resume_file)) {
                    // File uploaded successfully
                } else {
                    $error = 'Failed to upload resume. Please try again.';
                }
            }
        }

        if (!$error) {
            // Insert application
            $stmt = $conn->prepare(
                "INSERT INTO applications (job_id, user_id, applied_at, full_name, fathers_name, email, phone, skills, previous_work, resume_path, status)
                 VALUES (?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?, 'pending')"
            );
            $stmt->bind_param('iisssssss', $job_id, $user_id, $full_name, $fathers_name, $email, $phone, $skills, $previous_work, $resume_path);

            if ($stmt->execute()) {
                $success = true;
                $_SESSION['flash'] = ['msg' => 'Application submitted successfully! 🚀', 'type' => 'success'];
            } else {
                $error = 'Something went wrong. Please try again.';
                // Clean up uploaded file if insertion failed
                if (!empty($resume_file) && file_exists($resume_file)) {
                    unlink($resume_file);
                }
            }
        }
    }
}

if (!$success) {
    require 'header.php';
    ?>

    <main class="page-body">
    <div class="container" style="max-width:600px;">

        <a href="job-detail.php?id=<?= $job_id ?>" style="display:inline-flex;align-items:center;gap:6px;color:var(--text2);font-size:14px;margin-bottom:24px;">
            <i class="fa fa-arrow-left"></i> Back to Job Details
        </a>

        <div class="card">
            <div style="text-align:center;margin-bottom:28px;">
                <h1 class="h3">Apply for Job</h1>
                <p style="color:var(--text2);font-size:14px;margin-top:6px;">
                    <strong><?= e($job['title']) ?></strong>
                </p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error"><i class="fa fa-circle-xmark"></i> <?= e($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="apply.php?id=<?= $job_id ?>" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

                <div class="form-group">
                    <label class="form-label">Full Name <span style="color:var(--red);">*</span></label>
                    <input type="text" name="full_name" class="form-control"
                           value="<?= e($_POST['full_name'] ?? $user['name'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Father's Name</label>
                    <input type="text" name="fathers_name" class="form-control"
                           value="<?= e($_POST['fathers_name'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Email Address <span style="color:var(--red);">*</span></label>
                    <input type="email" name="email" class="form-control"
                           value="<?= e($_POST['email'] ?? $user['email'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Phone Number <span style="color:var(--red);">*</span></label>
                    <input type="tel" name="phone" class="form-control"
                           value="<?= e($_POST['phone'] ?? $user['phone'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Resume/CV <span style="color:var(--red);">*</span></label>
                    <input type="file" name="resume" class="form-control" accept=".pdf,.doc,.docx" required>
                    <small style="color:var(--text3);font-size:12px;">Accepted formats: PDF, DOC, DOCX (Max 5MB)</small>
                </div>

                <div class="form-group">
                    <label class="form-label">Skills & Technologies <span style="color:var(--red);">*</span></label>
                    <textarea name="skills" class="form-control" rows="3" placeholder="e.g., PHP, JavaScript, MySQL, HTML/CSS, etc." required><?= e($_POST['skills'] ?? '') ?></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Previous Work Experience <span style="color:var(--red);">*</span></label>
                    <textarea name="previous_work" class="form-control" rows="4" placeholder="Briefly describe your previous work experience, projects, and achievements..." required><?= e($_POST['previous_work'] ?? '') ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary btn-lg btn-block" style="margin-top:16px;">
                    <i class="fa fa-paper-plane"></i> Submit Application
                </button>
            </form>
        </div>
    </div>
    </main>

    <?php
    require 'footer.php';
} else {
    redirect('job-detail.php?id=' . $job_id);
}
?>
