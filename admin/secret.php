<?php
require_once '../config.php';

$flash = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = trim($_POST['password'] ?? '');
    if ($password === 'this') {
        $_SESSION['secret_admin'] = true;
        redirect('index.php');
    }
    $flash = 'Incorrect password. Please try again.';
}

require '../header.php';
?>

<main class="page-body">
<div class="container" style="max-width:520px;">
    <div style="margin-bottom:28px;">
        <h1 class="h3">Admin Access</h1>
        <p style="color:var(--text2);font-size:14px;">Enter the secret admin password to unlock the admin panel.</p>
    </div>

    <div class="card">
        <?php if ($flash): ?>
            <div class="alert alert-error"><i class="fa fa-circle-xmark"></i> <?= e($flash) ?></div>
        <?php endif; ?>

        <form method="POST" action="secret.php">
            <div class="form-group">
                <label class="form-label">Admin Password</label>
                <input type="password" name="password" class="form-control" placeholder="Enter your secret password" required autofocus>
            </div>
            <button type="submit" class="btn btn-primary btn-lg btn-block" style="margin-top:12px;">
                <i class="fa fa-unlock-keyhole"></i> Unlock Admin Panel
            </button>
        </form>

        <p style="color:var(--text2);font-size:13px;margin-top:18px;">Tip: trigger this screen by clicking the hidden admin spot in the header three times.</p>
    </div>
</div>
</main>

<?php require '../footer.php'; ?>
