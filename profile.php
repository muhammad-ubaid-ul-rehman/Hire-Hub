<?php
require_once 'config.php';
$user    = requireLogin();
$user_id = (int)$user['id'];

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $name     = trim($_POST['name']        ?? '');
    $email    = trim($_POST['email']       ?? '');
    $headline = trim($_POST['headline']    ?? '');
    $location = trim($_POST['location']    ?? '');
    $bio      = trim($_POST['bio']         ?? '');
    $new_pass = trim($_POST['new_password']?? '');

    if (!$name || !$email) {
        $error = 'Name and email are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $stmt = $conn->prepare(
            "UPDATE users SET name=?, email=?, headline=?, location=?, bio=? WHERE id=?"
        );
        $stmt->bind_param('sssssi', $name, $email, $headline, $location, $bio, $user_id);

        if ($stmt->execute()) {
            if ($new_pass !== '') {
                if (strlen($new_pass) < 6) {
                    $error = 'New password must be at least 6 characters.';
                } else {
                    $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
                    $ps = $conn->prepare("UPDATE users SET password=? WHERE id=?");
                    $ps->bind_param('si', $hashed, $user_id);
                    $ps->execute();
                }
            }
            if (!$error) {
                $success = 'Profile updated successfully!';
                $user = getCurrentUser(); // refresh
            }
        } else {
            $error = 'Update failed. Please try again.';
        }
    }
}

require 'header.php';
?>

<main class="page-body">
<div class="container" style="max-width:620px;">

    <!-- User header -->
    <div style="display:flex;align-items:center;gap:16px;margin-bottom:28px;">
        <div style="width:60px;height:60px;border-radius:16px;background:linear-gradient(135deg,var(--accent),var(--purple));display:flex;align-items:center;justify-content:center;font-size:24px;font-weight:800;color:#fff;flex-shrink:0;">
            <?= initials($user['name']) ?>
        </div>
        <div>
            <h1 class="h3"><?= e($user['name']) ?></h1>
            <p style="color:var(--text2);font-size:14px;margin-top:3px;">
                <?= ucfirst($user['role']) ?>
                <?php if ($user['headline']): ?> &mdash; <?= e($user['headline']) ?><?php endif; ?>
            </p>
        </div>
    </div>

    <!-- Edit form -->
    <div class="card" style="margin-bottom:20px;">
        <h3 class="h3" style="margin-bottom:20px;">Edit Profile</h3>

        <?php if ($success): ?>
            <div class="alert alert-success"><i class="fa fa-circle-check"></i> <?= e($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><i class="fa fa-circle-xmark"></i> <?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="profile.php">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div class="form-group">
                    <label class="form-label">Full Name <span style="color:var(--red);">*</span></label>
                    <input type="text" name="name" class="form-control" value="<?= e($user['name']) ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Email Address <span style="color:var(--red);">*</span></label>
                    <input type="email" name="email" class="form-control" value="<?= e($user['email']) ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Professional Headline</label>
                <input type="text" name="headline" class="form-control"
                       placeholder="e.g. Senior PHP Developer | Laravel Expert"
                       value="<?= e($user['headline'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label class="form-label">Location</label>
                <input type="text" name="location" class="form-control"
                       placeholder="e.g. Lahore, Pakistan"
                       value="<?= e($user['location'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label class="form-label">Bio / About</label>
                <textarea name="bio" class="form-control" rows="4"
                          placeholder="Tell employers about yourself..."><?= e($user['bio'] ?? '') ?></textarea>
            </div>

            <hr class="divider">
            <h4 style="font-size:15px;margin-bottom:16px;">Change Password</h4>

            <div class="form-group">
                <label class="form-label">New Password <span style="color:var(--text3);font-weight:400;">(leave blank to keep current)</span></label>
                <input type="password" name="new_password" class="form-control" placeholder="At least 6 characters">
            </div>

            <button type="submit" class="btn btn-primary btn-lg btn-block">
                <i class="fa fa-save"></i> Save Changes
            </button>
        </form>
    </div>

    <!-- Danger zone -->
    <div class="card" style="border-color:rgba(239,68,68,0.25);background:rgba(239,68,68,0.03);">
        <h4 style="color:var(--red);margin-bottom:8px;font-size:15px;">⚠️ Danger Zone</h4>
        <p style="color:var(--text2);font-size:14px;line-height:1.6;margin-bottom:16px;">
            Permanently delete your account and all associated data. This action is irreversible.
        </p>
        <a href="delete-account.php"
           onclick="return confirm('FINAL WARNING: Delete your account and ALL your data permanently?')"
           class="btn btn-danger">
            <i class="fa fa-trash-alt"></i> Delete My Account
        </a>
    </div>

</div>
</main>

<?php require 'footer.php'; ?>
