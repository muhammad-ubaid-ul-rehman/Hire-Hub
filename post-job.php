<?php
require_once 'config.php';

// Only employers can post jobs
$user = requireRole('employer');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $title    = trim($_POST['title']        ?? '');
    $location = trim($_POST['location']     ?? '');
    $job_type = trim($_POST['job_type']     ?? 'full-time');
    $salary   = trim($_POST['salary']       ?? '');
    $desc     = trim($_POST['description']  ?? '');
    $req      = trim($_POST['requirements'] ?? '');
    $deadline = trim($_POST['deadline']     ?? '');
    $emp_id   = (int)$_SESSION['user_id'];

    $valid_types = ['full-time','part-time','remote','contract','internship'];

    if (!$title || !$location || !$desc) {
        $error = 'Title, location and description are required.';
    } elseif (!in_array($job_type, $valid_types)) {
        $error = 'Invalid job type selected.';
    } else {
        $safe_deadline = ($deadline && strtotime($deadline)) ? $deadline : null;

        $stmt = $conn->prepare(
            "INSERT INTO jobs (employer_id, title, location, job_type, salary, description, requirements, deadline, status, posted_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())"
        );
        $stmt->bind_param('isssssss', $emp_id, $title, $location, $job_type, $salary, $desc, $req, $safe_deadline);

        if ($stmt->execute()) {
            $_SESSION['flash'] = ['msg' => 'Job posted successfully! It\'s now live. 🎉', 'type' => 'success'];
            redirect('dashboard.php');
        } else {
            $error = 'Something went wrong. Please try again.';
        }
    }
}

require 'header.php';
?>

<main class="page-body">
<div class="container" style="max-width:700px;">

    <a href="dashboard.php" style="display:inline-flex;align-items:center;gap:6px;color:var(--text2);font-size:14px;margin-bottom:24px;">
        <i class="fa fa-arrow-left"></i> Back to Dashboard
    </a>

    <div style="margin-bottom:28px;">
        <h1 class="h2">Post a New Job</h1>
        <p style="color:var(--text2);font-size:15px;margin-top:6px;">Fill in the details below to publish your listing.</p>
    </div>

    <div class="card">

        <?php if ($error): ?>
            <div class="alert alert-error"><i class="fa fa-circle-xmark"></i> <?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="post-job.php">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

            <div class="form-group">
                <label class="form-label">Job Title <span style="color:var(--red);">*</span></label>
                <input type="text" name="title" class="form-control"
                       placeholder="e.g. Senior React Developer"
                       value="<?= e($_POST['title'] ?? '') ?>" required>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div class="form-group">
                    <label class="form-label">City / Location <span style="color:var(--red);">*</span></label>
                    <input type="text" name="location" class="form-control"
                           placeholder="e.g. Lahore, Remote"
                           value="<?= e($_POST['location'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Job Type</label>
                    <select name="job_type" class="form-control">
                        <?php foreach (['full-time'=>'Full-Time','part-time'=>'Part-Time','remote'=>'Remote','contract'=>'Contract','internship'=>'Internship'] as $val => $label): ?>
                            <option value="<?= $val ?>" <?= ($_POST['job_type'] ?? 'full-time') === $val ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div class="form-group">
                    <label class="form-label">Salary Range</label>
                    <input type="text" name="salary" class="form-control"
                           placeholder="e.g. 80k – 120k PKR"
                           value="<?= e($_POST['salary'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">
                        Application Deadline
                        <span style="color:var(--text3);font-weight:400;">(optional)</span>
                    </label>
                    <input type="date" name="deadline" class="form-control"
                           min="<?= date('Y-m-d') ?>"
                           value="<?= e($_POST['deadline'] ?? '') ?>">
                    <p style="font-size:12px;color:var(--text3);margin-top:5px;">
                        After this date the listing shows as "Closed".
                    </p>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Job Description <span style="color:var(--red);">*</span></label>
                <textarea name="description" class="form-control" rows="7"
                          placeholder="Describe the role, responsibilities, day-to-day tasks..." required><?= e($_POST['description'] ?? '') ?></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">
                    Requirements
                    <span style="color:var(--text3);font-weight:400;">(optional)</span>
                </label>
                <textarea name="requirements" class="form-control" rows="5"
                          placeholder="List qualifications, skills, experience needed..."><?= e($_POST['requirements'] ?? '') ?></textarea>
            </div>

            <div style="display:flex;gap:12px;margin-top:8px;">
                <button type="submit" class="btn btn-primary btn-lg" style="flex:2;">
                    <i class="fa fa-paper-plane"></i> Publish Job
                </button>
                <a href="dashboard.php" class="btn btn-ghost btn-lg" style="flex:1;text-align:center;">
                    Cancel
                </a>
            </div>
        </form>

    </div>
</div>
</main>

<?php require 'footer.php'; ?>
