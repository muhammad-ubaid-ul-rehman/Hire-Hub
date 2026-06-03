<?php
require_once 'config.php';

// Only employers can contact candidates
$user = getCurrentUser();
if (!$user) {
    redirect('login.php');
}
if ($user['role'] !== 'employer') {
    redirect('index.php');
}

// Get candidate ID from URL
$candidate_id = (int)($_GET['id'] ?? 0);
if ($candidate_id <= 0) {
    redirect('candidates.php');
}

// Get candidate details
$stmt = $conn->prepare("
    SELECT u.id, u.name, u.email, u.headline, u.location, sp.skills, sp.experience_level
    FROM users u
    INNER JOIN seeker_profiles sp ON sp.user_id = u.id
    WHERE u.id = ? AND u.role = 'seeker' AND u.is_active = 1 AND sp.status = 'approved'
    LIMIT 1
");
$stmt->bind_param('i', $candidate_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    redirect('candidates.php');
}

$candidate = $result->fetch_assoc();
$success = false;
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $message = trim($_POST['message'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    
    if (!$subject || !$message) {
        $error = 'Please fill in both subject and message.';
    } else {
        // Save message to database (we can store contact messages or create a new table)
        // For now, we'll create a simple message in the contact_messages table
        $insert = $conn->prepare(
            "INSERT INTO contact_messages (name, email, subject, message, sent_at)
             VALUES (?, ?, ?, ?, NOW())"
        );
        
        // Format message with employer info
        $full_message = "From: " . $user['name'] . " (" . $user['email'] . ")\n" .
                       "Employer: " . ($user['company_name'] ?? 'N/A') . "\n\n" .
                       $message . "\n\n" .
                       "---\nReply to: " . $user['email'];
        
        $insert->bind_param('ssss', $user['name'], $user['email'], $subject, $full_message);
        
        if ($insert->execute()) {
            $success = true;
            // Clear form
            $_POST = [];
        } else {
            $error = 'Failed to send message. Please try again.';
        }
    }
}

require 'header.php';
?>

<main class="page-body">
<div class="container" style="max-width:900px;">

    <!-- Header -->
    <div style="margin-bottom:32px;">
        <a href="candidates.php" style="display:inline-flex;align-items:center;gap:6px;color:var(--text2);font-size:14px;margin-bottom:16px;">
            <i class="fa fa-arrow-left"></i> Back to Talent
        </a>
        <h1 class="h3">Contact <?= e($candidate['name']) ?></h1>
    </div>

    <div style="display:grid;grid-template-columns:1fr 2fr;gap:28px;">

        <!-- Candidate Info Card -->
        <div class="card" style="height:fit-content;">
            <div style="text-align:center;margin-bottom:20px;">
                <div style="width:80px;height:80px;border-radius:12px;background:linear-gradient(135deg,var(--accent),var(--purple));display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:32px;color:white;font-weight:800;">
                    <?= initials($candidate['name']) ?>
                </div>
                <h2 class="h4"><?= e($candidate['name']) ?></h2>
                <p style="color:var(--accent);font-weight:600;margin:4px 0;">
                    <?= e($candidate['headline'] ?? 'Job Seeker') ?>
                </p>
            </div>

            <div style="border-top:1px solid var(--border);border-bottom:1px solid var(--border);padding:16px 0;margin:16px 0;">
                <?php if ($candidate['location']): ?>
                <p style="color:var(--text2);font-size:13px;margin-bottom:8px;">
                    <i class="fa fa-map-pin" style="color:var(--red);"></i> <?= e($candidate['location']) ?>
                </p>
                <?php endif; ?>
                
                <?php if ($candidate['experience_level']): ?>
                <p style="color:var(--text2);font-size:13px;margin-bottom:8px;">
                    <i class="fa fa-briefcase" style="color:var(--accent);"></i> 
                    <?= ucfirst($candidate['experience_level']) ?> Level
                </p>
                <?php endif; ?>
                
                <?php if ($candidate['skills']): ?>
                <p style="color:var(--text2);font-size:13px;">
                    <i class="fa fa-star" style="color:var(--yellow);"></i> 
                    <?php 
                    $skills = explode(',', $candidate['skills']);
                    echo e(implode(', ', array_slice(array_map('trim', $skills), 0, 3)));
                    ?>
                </p>
                <?php endif; ?>
            </div>

            <a href="candidate_profile.php?id=<?= $candidate['id'] ?>" class="btn btn-ghost btn-lg btn-block">
                <i class="fa fa-user"></i> View Full Profile
            </a>
        </div>

        <!-- Contact Form -->
        <div class="card">
            <?php if ($success): ?>
                <div class="alert alert-success" style="margin-bottom:20px;">
                    <i class="fa fa-check-circle"></i> Message sent successfully! The candidate will receive your inquiry.
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error" style="margin-bottom:20px;">
                    <i class="fa fa-circle-xmark"></i> <?= e($error) ?>
                </div>
            <?php endif; ?>

            <h3 class="h4" style="margin-bottom:20px;">Send a Message</h3>

            <form method="POST" action="contact_candidate.php?id=<?= $candidate['id'] ?>">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

                <div class="form-group">
                    <label class="form-label">Subject</label>
                    <input type="text" name="subject" class="form-control"
                           placeholder="e.g., Exciting Opportunity at Our Company"
                           value="<?= e($_POST['subject'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Message</label>
                    <textarea name="message" class="form-control" rows="8"
                              placeholder="Tell the candidate why you think they'd be a great fit..."
                              required><?= e($_POST['message'] ?? '') ?></textarea>
                    <p style="font-size:12px;color:var(--text3);margin-top:6px;">
                        Your contact information will be included in the message.
                    </p>
                </div>

                <button type="submit" class="btn btn-primary btn-lg btn-block">
                    <i class="fa fa-paper-plane"></i> Send Message
                </button>
            </form>

            <div style="margin-top:20px;padding:16px;background:rgba(79,110,247,0.1);border-radius:10px;border-left:3px solid var(--accent);">
                <p style="font-size:13px;color:var(--text2);margin:0;">
                    <strong>Tip:</strong> Be genuine and specific about why you think this candidate would be a good fit. Include details about the role and your company to get a faster response.
                </p>
            </div>
        </div>

    </div>

</div>
</main>

<?php require 'footer.php'; ?>
