<?php
require_once 'config.php';

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name']    ?? '');
    $email   = trim($_POST['email']   ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (!$name || !$email || !$subject || !$message) {
        $error = 'Please fill in all fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $stmt = $conn->prepare(
            "INSERT INTO contact_messages (name, email, subject, message, sent_at) VALUES (?, ?, ?, ?, NOW())"
        );
        $stmt->bind_param('ssss', $name, $email, $subject, $message);
        if ($stmt->execute()) {
            $success = "Thank you, <strong>$name</strong>! Your message has been received. We'll reply to <strong>$email</strong> within 24 hours.";
        } else {
            $error = 'Could not send your message. Please try again.';
        }
    }
}

require 'header.php';
?>

<main class="page-body">
<div class="container">

    <div style="text-align:center;margin-bottom:48px;">
        <p class="label" style="margin-bottom:10px;">Get in Touch</p>
        <h1 class="h2" style="margin-bottom:12px;">Contact Us</h1>
        <p style="color:var(--text2);font-size:16px;max-width:440px;margin:0 auto;line-height:1.7;">
            Have a question, feedback, or issue? We'd love to hear from you.
        </p>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1.6fr;gap:28px;align-items:start;max-width:900px;margin:0 auto;" class="contact-grid">

        <!-- Info cards -->
        <div style="display:flex;flex-direction:column;gap:14px;">
            <?php
            $cards = [
                ['fa-envelope',    'var(--accent)',  'Email',        SITE_EMAIL],
                ['fa-phone',       'var(--green)',   'Phone',        '+92 300 000 0000'],
                ['fa-location-dot','var(--amber)',   'Office',       'Lahore, Pakistan'],
                ['fa-clock',       'var(--purple)',  'Working Hours','Mon–Fri, 9am–6pm PKT'],
            ];
            foreach ($cards as $c): ?>
            <div class="card" style="display:flex;align-items:center;gap:14px;padding:18px 20px;">
                <div style="width:40px;height:40px;border-radius:10px;background:<?= $c[1] ?>22;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fa <?= $c[0] ?>" style="color:<?= $c[1] ?>;"></i>
                </div>
                <div>
                    <div style="font-size:12px;color:var(--text2);font-weight:600;text-transform:uppercase;letter-spacing:.05em;"><?= $c[2] ?></div>
                    <div style="font-size:14px;font-weight:600;margin-top:2px;"><?= $c[3] ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Form -->
        <div class="card">
            <h3 class="h3" style="margin-bottom:22px;">Send a Message</h3>

            <?php if ($success): ?>
                <div class="alert alert-success"><i class="fa fa-circle-check"></i> <?= $success ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error"><i class="fa fa-circle-xmark"></i> <?= e($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="contact.php">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                    <div class="form-group">
                        <label class="form-label">Your Name</label>
                        <input type="text" name="name" class="form-control"
                               placeholder="Full name"
                               value="<?= e($_POST['name'] ?? ($_currentUser['name'] ?? '')) ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control"
                               placeholder="you@example.com"
                               value="<?= e($_POST['email'] ?? ($_currentUser['email'] ?? '')) ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Subject</label>
                    <input type="text" name="subject" class="form-control"
                           placeholder="How can we help?"
                           value="<?= e($_POST['subject'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Message</label>
                    <textarea name="message" class="form-control" rows="6"
                              placeholder="Describe your question or issue..."
                              required><?= e($_POST['message'] ?? '') ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary btn-lg btn-block">
                    <i class="fa fa-paper-plane"></i> Send Message
                </button>
            </form>
        </div>

    </div>
</div>
</main>

<style>
@media (max-width:768px) { .contact-grid { grid-template-columns:1fr !important; } }
</style>

<?php require 'footer.php'; ?>
