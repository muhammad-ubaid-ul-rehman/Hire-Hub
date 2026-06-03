<?php
require_once 'config.php';

if (isLoggedIn()) redirect('index.php');

$error = '';
$post  = $_POST; // keep form values for re-fill

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($post['name']     ?? '');
    $email    = trim($post['email']    ?? '');
    $password = trim($post['password'] ?? '');
    $role     = $post['role'] ?? 'seeker';

    // Basic validation
    if (!$name || !$email || !$password) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $error = 'Password must contain at least one uppercase letter.';
    } elseif (!preg_match('/[a-z]/', $password)) {
        $error = 'Password must contain at least one lowercase letter.';
    } elseif (!preg_match('/[0-9]/', $password)) {
        $error = 'Password must contain at least one number.';
    } elseif (!in_array($role, ['seeker', 'employer'])) {
        $error = 'Please choose a valid role.';
    } else {
        // Check duplicate email
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $error = 'This email is already registered. Try logging in.';
        } else {
            // Insert
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt2  = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt2->bind_param('ssss', $name, $email, $hashed, $role);
            if ($stmt2->execute()) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $conn->insert_id;
                $_SESSION['flash']   = ['msg' => 'Welcome to ' . SITE_NAME . ', ' . $name . '! 🎉', 'type' => 'success'];
                redirect('index.php');
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}

require 'header.php';
?>

<main class="page-body">
<div class="container" style="max-width:460px;">

    <a href="index.php" style="display:inline-flex;align-items:center;gap:6px;color:var(--text2);font-size:14px;margin-bottom:32px;">
        <i class="fa fa-arrow-left"></i> Back to home
    </a>

    <div class="card">
        <div style="text-align:center; margin-bottom:28px;">
            <div style="width:52px;height:52px;border-radius:14px;background:linear-gradient(135deg,var(--accent),var(--purple));display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:22px;">
                🚀
            </div>
            <h1 class="h3">Create your account</h1>
            <p style="color:var(--text2);font-size:14px;margin-top:6px;">Join <?= SITE_NAME ?> — find or post jobs in Pakistan</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><i class="fa fa-circle-xmark"></i> <?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="register.php">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

            <div class="form-group">
                <label class="form-label">Full Name</label>
                <input type="text" name="name" class="form-control"
                       placeholder="Your full name"
                       value="<?= e($post['name'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control"
                       placeholder="you@example.com"
                       value="<?= e($post['email'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control"
                       placeholder="At least 6 characters" required>
            </div>

            <div class="form-group">
                <label class="form-label">I am a...</label>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:4px;">
                    <?php
                    $roles = [
                        'seeker'   => ['icon' => '🔍', 'label' => 'Job Seeker', 'sub' => 'Looking for work'],
                        'employer' => ['icon' => '🏢', 'label' => 'Employer',   'sub' => 'Hiring talent'],
                    ];
                    foreach ($roles as $val => $r):
                        $sel = ($post['role'] ?? 'seeker') === $val;
                    ?>
                    <label style="cursor:pointer;">
                        <input type="radio" name="role" value="<?= $val ?>" <?= $sel ? 'checked' : '' ?> style="display:none;" class="role-radio">
                        <div class="role-card" style="border:2px solid <?= $sel ? 'var(--accent)' : 'var(--border2)' ?>;border-radius:12px;padding:16px;text-align:center;transition:all 0.2s;">
                            <div style="font-size:24px;margin-bottom:6px;"><?= $r['icon'] ?></div>
                            <div style="font-size:14px;font-weight:700;"><?= $r['label'] ?></div>
                            <div style="font-size:12px;color:var(--text2);"><?= $r['sub'] ?></div>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-lg btn-block" style="margin-top:8px;">
                <i class="fa fa-user-plus"></i> Create Account
            </button>
        </form>

        <p style="text-align:center;margin-top:22px;color:var(--text2);font-size:14px;">
            Already have an account?
            <a href="login.php" style="color:var(--accent);font-weight:600;">Sign in →</a>
        </p>
    </div>
</div>
</main>

<script>
// Role card visual toggle
document.querySelectorAll('.role-radio').forEach(r => {
    r.addEventListener('change', () => {
        document.querySelectorAll('.role-card').forEach(c => c.style.borderColor = 'var(--border2)');
        r.closest('label').querySelector('.role-card').style.borderColor = 'var(--accent)';
    });
});
</script>

<?php require 'footer.php'; ?>
