<?php
// ── All PHP logic runs BEFORE any HTML output ──
require_once 'config.php';

// Already logged in? Go home
if (isLoggedIn()) redirect('index.php');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Rate limiting: max 5 attempts per 15 minutes
    $attempts = $_SESSION['login_attempts'] ?? 0;
    $last_attempt = $_SESSION['last_attempt'] ?? 0;
    $now = time();
    
    if ($attempts >= 5 && ($now - $last_attempt) < 900) { // 15 minutes
        $error = 'Too many login attempts. Please try again later.';
    } else {
        $email    = trim($_POST['email']    ?? '');
        $password = trim($_POST['password'] ?? '');

        if (!$email || !$password) {
            $error = 'Please fill in both fields.';
        } else {
            // Prepared statement — safe from SQL injection
            $stmt = $conn->prepare("SELECT id, name, password, role FROM users WHERE email = ? LIMIT 1");
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $res = $stmt->get_result();

            if ($res->num_rows === 1) {
                $row = $res->fetch_assoc();
                if (password_verify($password, $row['password'])) {
                    // Reset attempts on success
                    unset($_SESSION['login_attempts'], $_SESSION['last_attempt']);
                    session_regenerate_id(true); // prevent session fixation
                    $_SESSION['user_id'] = $row['id'];
                    logEvent("User " . ($row['email'] ?? $email) . " logged in successfully", 'INFO');
                    $_SESSION['flash']   = ['msg' => 'Welcome back, ' . $row['name'] . '! 👋', 'type' => 'success'];
                    redirect('index.php');
                } else {
                    $error = 'Incorrect password. Please try again.';
                    logEvent("Failed login attempt for email: $email", 'WARNING');
                    $_SESSION['login_attempts'] = $attempts + 1;
                    $_SESSION['last_attempt'] = $now;
                }
            } else {
                $error = 'No account found with that email address.';
                logEvent("Login attempt with non-existent email: $email", 'WARNING');
                $_SESSION['login_attempts'] = $attempts + 1;
                $_SESSION['last_attempt'] = $now;
            }
        }
    }
}

require 'header.php';
?>

<main class="page-body">
<div class="container" style="max-width:440px;">

    <!-- Back link -->
    <a href="index.php" style="display:inline-flex;align-items:center;gap:6px;color:var(--text2);font-size:14px;margin-bottom:32px;">
        <i class="fa fa-arrow-left"></i> Back to home
    </a>

    <div class="card">

        <div style="text-align:center; margin-bottom:28px;">
            <div style="width:52px;height:52px;border-radius:14px;background:linear-gradient(135deg,var(--accent),var(--purple));display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:22px;">
                👋
            </div>
            <h1 class="h3">Welcome back</h1>
            <p style="color:var(--text2);font-size:14px;margin-top:6px;">Sign in to your <?= SITE_NAME ?> account</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><i class="fa fa-circle-xmark"></i> <?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

            <div class="form-group">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control"
                       placeholder="you@example.com"
                       value="<?= e($_POST['email'] ?? '') ?>" required autofocus>
            </div>

            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control"
                       placeholder="Your password" required>
            </div>

            <button type="submit" class="btn btn-primary btn-lg btn-block" style="margin-top:8px;">
                <i class="fa fa-sign-in-alt"></i> Sign In
            </button>
        </form>

        <p style="text-align:center;margin-top:22px;color:var(--text2);font-size:14px;">
            Don't have an account?
            <a href="register.php" style="color:var(--accent);font-weight:600;">Create one free →</a>
        </p>

    </div>
</div>
</main>

<?php require 'footer.php'; ?>
