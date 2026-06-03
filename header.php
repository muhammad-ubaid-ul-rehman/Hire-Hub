<?php
// header.php — Outputs navbar only. Logic lives in each page file.
require_once 'config.php';
$_currentUser = getCurrentUser(); // available as $_currentUser in all pages
// Route-aware active link helper for navbar
$__currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= SITE_NAME ?> — <?= SITE_TAGLINE ?></title>
    <meta name="description" content="<?= SITE_NAME ?> — Find top jobs in Pakistan. Real employers, real opportunities.">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
    /* ================================================================
       DESIGN SYSTEM — HireHub
       ================================================================ */
    :root {
        --bg:          #04050d;
        --bg2:         #0c1222;
        --surface:     #111827;
        --card:        rgba(15,24,40,0.95);
        --card2:       rgba(22,34,54,0.95);
        --border:      rgba(255,255,255,0.08);
        --border2:     rgba(255,255,255,0.14);

        --accent:      #4F6EF7;
        --accent-dark: #3a56d4;
        --accent-glow: rgba(79,110,247,0.25);

        --green:       #10B981;
        --green-bg:    rgba(16,185,129,0.12);
        --amber:       #F59E0B;
        --amber-bg:    rgba(245,158,11,0.12);
        --red:         #EF4444;
        --red-bg:      rgba(239,68,68,0.12);
        --purple:      #8B5CF6;
        --purple-bg:   rgba(139,92,246,0.12);

        --text:        #F1F5F9;
        --text2:       #94A3B8;
        --text3:       #475569;

        --radius:      12px;
        --radius-lg:   16px;
        --shadow:      0 4px 24px rgba(0,0,0,0.4);
        --shadow-lg:   0 8px 40px rgba(0,0,0,0.5);

        --transition:  all 0.2s ease;
    }

    /* ── Reset ── */
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    html { scroll-behavior: smooth; }
    body {
        font-family: 'Inter', system-ui, sans-serif;
        background: radial-gradient(circle at 20% 10%, rgba(79,110,247,0.18), transparent 18%),
                    radial-gradient(circle at 80% 20%, rgba(139,92,246,0.12), transparent 16%),
                    radial-gradient(circle at 80% 85%, rgba(16,185,129,0.08), transparent 22%),
                    linear-gradient(180deg, #05070f 0%, #070b14 45%, #0a1130 100%);
        color: var(--text);
        line-height: 1.6;
        min-height: 100vh;
        -webkit-font-smoothing: antialiased;
        background-attachment: fixed;
        position: relative;
        overflow-x: hidden;
    }
    body::before {
        content: '';
        position: fixed;
        inset: 0;
        background: radial-gradient(circle at 40% 8%, rgba(79,110,247,0.16), transparent 20%),
                    radial-gradient(circle at 15% 75%, rgba(139,92,246,0.08), transparent 15%),
                    linear-gradient(180deg, transparent 0%, rgba(255,255,255,0.02) 50%, transparent 100%);
        pointer-events: none;
        opacity: 1;
        z-index: -1;
    }
    a { text-decoration: none; color: inherit; }
    img { max-width: 100%; display: block; }
    button { font-family: inherit; cursor: pointer; }
    input, select, textarea { font-family: inherit; }

    /* ── Layout ── */
    .container { max-width: 1200px; margin: 0 auto; padding: 0 24px; }
    .container-sm { max-width: 700px; margin: 0 auto; padding: 0 24px; }

    /* ── Typography ── */
    .h1 { font-size: clamp(28px,4vw,52px); font-weight: 900; line-height: 1.1; letter-spacing: -1.5px; }
    .h2 { font-size: clamp(22px,3vw,36px); font-weight: 800; letter-spacing: -0.5px; }
    .h3 { font-size: 20px; font-weight: 700; }
    .label { font-size: 12px; font-weight: 600; letter-spacing: 0.08em; text-transform: uppercase; color: var(--text2); }

    /* ── Buttons ── */
    .btn {
        display: inline-flex; align-items: center; justify-content: center;
        gap: 8px; padding: 11px 22px;
        border-radius: var(--radius); border: none;
        font-size: 14px; font-weight: 600;
        transition: var(--transition); cursor: pointer;
        white-space: nowrap;
    }
    .btn-primary  { background: var(--accent); color: #fff; }
    .btn-primary:hover  { background: var(--accent-dark); transform: translateY(-1px); box-shadow: 0 4px 20px var(--accent-glow); }
    .btn-ghost    { background: rgba(255,255,255,0.06); color: var(--text); border: 1px solid var(--border2); }
    .btn-ghost:hover    { background: rgba(255,255,255,0.1); }
    .btn-danger   { background: var(--red-bg); color: var(--red); border: 1px solid rgba(239,68,68,0.3); }
    .btn-danger:hover   { background: rgba(239,68,68,0.2); }
    .btn-success  { background: var(--green-bg); color: var(--green); border: 1px solid rgba(16,185,129,0.3); }
    .btn-purple   { background: var(--purple-bg); color: var(--purple); border: 1px solid rgba(139,92,246,0.3); }
    .btn-purple:hover { background: rgba(139,92,246,0.18); }
    .btn-sm       { padding: 7px 14px; font-size: 13px; border-radius: 8px; }
    .btn-lg       { padding: 14px 32px; font-size: 16px; border-radius: 14px; }
    .btn-block    { width: 100%; }

    /* ── Cards ── */
    .card {
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: var(--radius-lg);
        padding: 28px;
        box-shadow: 0 20px 60px rgba(0,0,0,0.25);
    }
    .card-hover { transition: var(--transition); }
    .card-hover:hover { border-color: rgba(79,110,247,0.4); transform: translateY(-3px); box-shadow: var(--shadow); }

    /* ── Form elements ── */
    .form-group { margin-bottom: 20px; }
    .form-label {
        display: block; margin-bottom: 7px;
        font-size: 13px; font-weight: 600; color: var(--text2);
    }
    .form-control {
        width: 100%; padding: 12px 16px;
        background: var(--bg2); border: 1px solid var(--border2);
        color: var(--text); border-radius: var(--radius);
        font-size: 15px; outline: none;
        transition: border-color 0.2s, box-shadow 0.2s;
    }
    .form-control:focus {
        border-color: var(--accent);
        box-shadow: 0 0 0 3px var(--accent-glow);
    }
    .form-control::placeholder { color: var(--text3); }
    textarea.form-control { resize: vertical; min-height: 120px; }
    select.form-control { background: var(--bg2); }

    /* ── Alerts ── */
    .alert { padding: 13px 18px; border-radius: var(--radius); font-size: 14px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
    .alert-error   { background: var(--red-bg);    border: 1px solid rgba(239,68,68,0.3);    color: var(--red); }
    .alert-success { background: var(--green-bg);  border: 1px solid rgba(16,185,129,0.3);   color: var(--green); }
    .alert-info    { background: var(--purple-bg); border: 1px solid rgba(139,92,246,0.3);   color: var(--purple); }

    /* ── Badges / Tags ── */
    .badge {
        display: inline-flex; align-items: center; gap: 5px;
        padding: 3px 10px; border-radius: 20px;
        font-size: 12px; font-weight: 600;
    }
    .badge-green  { background: var(--green-bg);  color: var(--green); }
    .badge-amber  { background: var(--amber-bg);  color: var(--amber); }
    .badge-red    { background: var(--red-bg);    color: var(--red); }
    .badge-blue   { background: rgba(79,110,247,0.12); color: var(--accent); }
    .badge-purple { background: var(--purple-bg); color: var(--purple); }

    /* ── Tables ── */
    .table { width: 100%; border-collapse: collapse; }
    .table th { padding: 12px 16px; text-align: left; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text2); border-bottom: 1px solid var(--border); }
    .table td { padding: 16px; border-bottom: 1px solid var(--border); font-size: 14px; vertical-align: middle; }
    .table tbody tr:hover { background: rgba(255,255,255,0.02); }
    .table tbody tr:last-child td { border-bottom: none; }

    /* ── Empty state ── */
    .empty-state { text-align: center; padding: 60px 20px; }
    .empty-state .empty-icon { font-size: 52px; margin-bottom: 16px; opacity: 0.6; }
    .empty-state h3 { font-size: 20px; font-weight: 700; margin-bottom: 8px; }
    .empty-state p { color: var(--text2); margin-bottom: 24px; line-height: 1.7; }

    /* ── Divider ── */
    .divider { border: none; border-top: 1px solid var(--border); margin: 24px 0; }

    /* ── Toast ── */
    #toast-container { position: fixed; bottom: 24px; right: 24px; z-index: 9999; display: flex; flex-direction: column; gap: 10px; }
    .toast {
        display: flex; align-items: center; gap: 12px;
        background: var(--card2); border: 1px solid var(--border2);
        padding: 14px 18px; border-radius: var(--radius);
        box-shadow: var(--shadow-lg); font-size: 14px; font-weight: 500;
        max-width: 340px; animation: slideUp 0.3s ease;
    }
    .toast-success { border-left: 3px solid var(--green); }
    .toast-error   { border-left: 3px solid var(--red); }
    .toast-info    { border-left: 3px solid var(--accent); }
    @keyframes slideUp { from { opacity:0; transform:translateY(12px); } to { opacity:1; transform:translateY(0); } }

    /* ================================================================
       NAVBAR
       ================================================================ */
    .navbar {
        background: rgba(6,9,18,0.92);
        backdrop-filter: blur(24px);
        -webkit-backdrop-filter: blur(24px);
        border-bottom: 1px solid rgba(255,255,255,0.08);
        box-shadow: 0 20px 50px rgba(0,0,0,0.25);
        position: sticky; top: 0; z-index: 500;
        height: 64px;
    }
    .secret-admin-trigger {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        background: rgba(255,255,255,0.08);
        border: 1px solid rgba(255,255,255,0.12);
        color: var(--text2);
        font-size: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-left: 10px;
        transition: background 0.2s ease, color 0.2s ease;
        cursor: pointer;
    }
    .secret-admin-trigger:hover {
        background: rgba(255,255,255,0.16);
        color: var(--text);
    }
    .navbar-inner {
        height: 64px; display: flex;
        align-items: center; justify-content: space-between;
        max-width: 1200px; margin: 0 auto; padding: 0 24px;
    }
    .nav-logo {
        font-size: 20px; font-weight: 900; color: #fff;
        letter-spacing: -0.5px; display: flex; align-items: center; gap: 8px;
    }
    .nav-logo .logo-icon {
        width: 32px; height: 32px; border-radius: 8px;
        background: linear-gradient(135deg, var(--accent), var(--purple));
        display: flex; align-items: center; justify-content: center;
        font-size: 16px; color: #fff;
    }
    .nav-logo span { color: var(--accent); }

    .nav-links { display: flex; align-items: center; gap: 2px; }
    .nav-link {
        padding: 7px 14px; border-radius: 8px;
        font-size: 14px; font-weight: 500; color: var(--text2);
        transition: var(--transition);
    }
    .nav-link:hover, .nav-link.active { color: var(--text); background: rgba(255,255,255,0.06); }

    /* User menu */
    .user-menu { position: relative; }
    .user-trigger {
        display: flex; align-items: center; gap: 9px;
        background: rgba(255,255,255,0.05);
        border: 1px solid var(--border2);
        padding: 5px 14px 5px 6px;
        border-radius: 40px; cursor: pointer;
        font-size: 14px; font-weight: 500;
        color: var(--text); transition: var(--transition);
    }
    .user-trigger:hover { background: rgba(255,255,255,0.09); }
    .nav-avatar {
        width: 30px; height: 30px; border-radius: 50%;
        background: linear-gradient(135deg, var(--accent), var(--purple));
        display: flex; align-items: center; justify-content: center;
        font-size: 12px; font-weight: 700; color: #fff; flex-shrink: 0;
    }
    .user-dropdown {
        position: absolute; top: calc(100% + 10px); right: 0;
        min-width: 200px; background: var(--card);
        border: 1px solid var(--border2); border-radius: var(--radius-lg);
        padding: 8px; display: none; flex-direction: column;
        box-shadow: var(--shadow-lg);
        animation: fadeDown 0.15s ease;
    }
    .user-menu:hover .user-dropdown { display: flex; }
    @keyframes fadeDown { from { opacity:0; transform:translateY(-6px); } to { opacity:1; transform:translateY(0); } }

    /* ================================================================
       Premium Micro-interactions (Safe)
       ================================================================ */
    @media (prefers-reduced-motion: reduce) {
        * { scroll-behavior: auto !important; }
        .reveal, .reveal * { animation: none !important; transition: none !important; }
    }

    .reveal {
        opacity: 0;
        transform: translateY(10px);
        transition: opacity 600ms ease, transform 600ms ease;
        will-change: opacity, transform;
    }
    .reveal.is-visible {
        opacity: 1;
        transform: translateY(0);
    }

    .btn:focus-visible,
    .nav-link:focus-visible,
    a:focus-visible,
    button:focus-visible,
    input:focus-visible,
    select:focus-visible,
    textarea:focus-visible {
        outline: none;
        box-shadow: 0 0 0 3px var(--accent-glow);
    }

    /* Reusable button variants (some pages reference these) */
    .btn-outline {
        background: rgba(255,255,255,0.05);
        color: var(--text);
        border: 1px solid var(--border2);
    }
    .btn-outline:hover { background: rgba(255,255,255,0.10); transform: translateY(-1px); box-shadow: 0 8px 24px rgba(0,0,0,0.35); }

    /* Status badge helpers (used in dashboard/admin) */
    .badge-green  { background: var(--green-bg); color: var(--green); }
    .badge-blue   { background: rgba(79,110,247,0.12); color: var(--accent); }
    .badge-purple { background: var(--purple-bg); color: var(--purple); }
    .badge-red    { background: var(--red-bg); color: var(--red); }

    .btn-success { background: var(--green-bg); color: var(--green); border: 1px solid rgba(16,185,129,0.3); }
    .btn-success:hover { background: rgba(16,185,129,0.16); transform: translateY(-1px); box-shadow: 0 4px 20px rgba(16,185,129,0.18); }

    .btn-danger { background: var(--red-bg); color: var(--red); border: 1px solid rgba(239,68,68,0.3); }
    .btn-danger:hover { background: rgba(239,68,68,0.20); transform: translateY(-1px); box-shadow: 0 4px 20px rgba(239,68,68,0.15); }

    .btn-primary:hover { transform: translateY(-1px); }

    .card-hover {
        transition: var(--transition);
    }

    .dd-item.danger:hover { background: rgba(239,68,68,0.18); }

    .dd-item i { width: 16px; text-align: center; font-size: 13px; }

    .dd-item:hover { transform: translateY(-1px); }

    .dd-item { transform: translateZ(0); }

    .dd-header { padding: 10px 12px 12px; border-bottom: 1px solid var(--border); margin-bottom: 4px; }
    .dd-name { font-size: 14px; font-weight: 700; color: var(--text); }
    .dd-role { font-size: 12px; color: var(--text2); margin-top: 1px; }

    .dd-item {
        display: flex; align-items: center; gap: 10px;
        padding: 9px 12px; border-radius: 8px;
        font-size: 14px; color: var(--text2);
        transition: var(--transition); cursor: pointer;
    }
    .dd-item:hover { background: rgba(255,255,255,0.06); color: var(--text); }
    .dd-item.danger { color: var(--red); }
    .dd-item.danger:hover { background: var(--red-bg); }
    .dd-item i { width: 16px; text-align: center; font-size: 13px; }

    /* ── Job type colors ── */
    .type-full    { background: rgba(79,110,247,0.12); color: var(--accent); }
    .type-part    { background: var(--amber-bg);       color: var(--amber); }
    .type-remote  { background: var(--green-bg);       color: var(--green); }
    .type-contract{ background: var(--purple-bg);      color: var(--purple); }
    .type-intern  { background: rgba(236,72,153,0.12); color: #ec4899; }

    /* ── Page padding ── */
    .page-body { position: relative; padding: 48px 0 80px; }
    .page-body::before {
        content: '';
        position: absolute;
        inset: 0;
        background: radial-gradient(circle at top center, rgba(79,110,247,0.06), transparent 28%);
        pointer-events: none;
        z-index: 0;
    }

    /* ── Footer ── */
    .footer {
        background: var(--surface); border-top: 1px solid var(--border);
        padding: 36px 0 24px; margin-top: 80px;
    }
    .footer-row { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; padding-bottom: 20px; border-bottom: 1px solid var(--border); margin-bottom: 20px; }
    .footer-links { display: flex; gap: 20px; flex-wrap: wrap; }
    .footer-links a { font-size: 14px; color: var(--text2); transition: color 0.2s; }
    .footer-links a:hover { color: var(--accent); }
    .footer-copy { font-size: 13px; color: var(--text3); }

    /* ── Responsive ── */
    @media (max-width: 768px) {
        .container { padding: 0 16px; }
        .navbar-inner { padding: 0 16px; }
        .card { padding: 20px; }
        .nav-link { display: none; }
        .nav-link.nav-mobile-show { display: flex; }
    }
    </style>
</head>
<body>

<!-- ── Navbar ── -->
<header class="navbar">
    <div class="navbar-inner">

        <!-- Logo -->
        <a href="index.php" class="nav-logo">
            <div class="logo-icon"><i class="fa-solid fa-briefcase"></i></div>
            Hire<span>Hub</span>
        </a>

        <!-- Nav Links -->
        <nav class="nav-links">
            <a href="index.php"
               class="nav-link <?= $__currentPage === 'index.php' ? 'active' : '' ?>">
                <i class="fa fa-home"></i> Home
            </a>
            <a href="candidates.php"
               class="nav-link <?= $__currentPage === 'candidates.php' ? 'active' : '' ?>">
                <i class="fa fa-users"></i> Talent
            </a>
            <a href="contact.php"
               class="nav-link <?= $__currentPage === 'contact.php' ? 'active' : '' ?>">
                <i class="fa fa-envelope"></i> Contact
            </a>
            <button id="secret-admin-trigger" class="secret-admin-trigger" type="button" aria-label="Secret admin access">
                <i class="fa fa-lock"></i>
            </button>

            <?php if ($_currentUser): ?>

                <?php if ($_currentUser['role'] === 'admin'): ?>
                    <a href="admin/index.php" class="nav-link <?= $__currentPage === 'admin/index.php' ? 'active' : '' ?>">
                        <i class="fa fa-shield-halved"></i> Admin
                    </a>
                <?php endif; ?>

                <!-- User menu dropdown -->
                <div class="user-menu" style="margin-left:8px;">
                    <div class="user-trigger">
                        <div class="nav-avatar">
                            <?= initials($_currentUser['name']) ?>
                        </div>
                        <?= e(explode(' ', $_currentUser['name'])[0]) ?>
                        <i class="fa fa-chevron-down" style="font-size:10px; color:var(--text3);"></i>
                    </div>
                    <div class="user-dropdown">
                        <div class="dd-header">
                            <div class="dd-name"><?= e($_currentUser['name']) ?></div>
                            <div class="dd-role"><?= ucfirst($_currentUser['role']) ?></div>
                        </div>
                        <a href="dashboard.php" class="dd-item <?= $__currentPage === 'dashboard.php' ? 'active' : '' ?>"><i class="fa fa-th-large"></i> Dashboard</a>
                        <?php if ($_currentUser['role'] === 'seeker'): ?>
                            <a href="seeker-profile.php" class="dd-item"><i class="fa fa-briefcase"></i> Professional Profile</a>
                            <a href="favorites.php" class="dd-item<?= $__currentPage === 'favorites.php' ? ' active' : '' ?>"><i class="fa fa-heart"></i> Saved Jobs</a>
                            <a href="job-alerts.php" class="dd-item<?= $__currentPage === 'job-alerts.php' ? ' active' : '' ?>"><i class="fa fa-bell"></i> Job Alerts</a>
                        <?php endif; ?>
                        <a href="profile.php"   class="dd-item"><i class="fa fa-user-pen"></i> Profile</a>
                        <hr class="divider" style="margin:6px 0;">
                        <a href="logout.php"    class="dd-item danger"><i class="fa fa-right-from-bracket"></i> Logout</a>
                    </div>
                </div>

            <?php else: ?>
                <a href="login.php"    class="btn btn-ghost btn-sm" style="margin-left:6px;">Login</a>
                <a href="register.php" class="btn btn-primary btn-sm" style="margin-left:4px;">Get Started</a>
            <?php endif; ?>
        </nav>

    </div>
</header>

<script>
(function() {
    const trigger = document.getElementById('secret-admin-trigger');
    if (!trigger) return;

    let clickCount = 0;
    let clickTimer = null;

    function resetClicks() {
        clickCount = 0;
        if (clickTimer) {
            clearTimeout(clickTimer);
            clickTimer = null;
        }
    }

    function getSecretUrl() {
        const segments = window.location.pathname.split('/');
        const last = segments[segments.length - 1];
        if (!last || last.endsWith('.php')) {
            segments.pop();
        }
        if (segments[segments.length - 1] === 'admin') {
            segments.pop();
        }
        const base = segments.join('/') || '';
        return base + '/admin/secret.php';
    }

    trigger.addEventListener('click', function(event) {
        event.preventDefault();
        clickCount += 1;
        if (clickTimer) clearTimeout(clickTimer);
        clickTimer = setTimeout(resetClicks, 1200);

        if (clickCount === 3) {
            resetClicks();
            const url = getSecretUrl();
            window.location.href = url;
        }
    });
})();
</script>

<!-- Toast container -->
<div id="toast-container"></div>

<script>
// Premium, lightweight reveal-on-scroll (safe: visual only)
// Run after full page load to ensure all cards are rendered
window.addEventListener('load', function() {
    const reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (reduce) return;

    const nodes = document.querySelectorAll('.reveal');
    if (!nodes || nodes.length === 0) return;

    const io = new IntersectionObserver((entries) => {
        for (const e of entries) {
            if (e.isIntersecting) {
                e.target.classList.add('is-visible');
                io.unobserve(e.target);
            }
        }
    }, { threshold: 0.12 });

    nodes.forEach(n => io.observe(n));
});
</script>


<?php
// Show flash message as toast (set via $_SESSION['flash'])
if (!empty($_SESSION['flash'])):
    $f = $_SESSION['flash'];
    unset($_SESSION['flash']);
?>
<script>
document.addEventListener('DOMContentLoaded', () => {
    showToast(<?= json_encode($f['msg']) ?>, <?= json_encode($f['type'] ?? 'info') ?>);
});
</script>
<?php endif; ?>

<script>
// ── Toast system ──
function showToast(message, type = 'info', duration = 4000) {
    const icons = { success: 'fa-circle-check', error: 'fa-circle-xmark', info: 'fa-circle-info' };
    const colors = { success: '#10B981', error: '#EF4444', info: '#4F6EF7' };
    const container = document.getElementById('toast-container');
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `<i class="fa ${icons[type]||icons.info}" style="color:${colors[type]};font-size:16px;"></i> ${message}
        <button onclick="this.parentElement.remove()" style="margin-left:auto;background:none;border:none;color:var(--text3);font-size:18px;line-height:1;padding:0 0 0 8px;">×</button>`;
    container.appendChild(toast);
    setTimeout(() => { toast.style.opacity='0'; toast.style.transition='opacity 0.3s'; setTimeout(()=>toast.remove(),300); }, duration);
}

// ── Flash from PHP ──
function flash(msg, type) { sessionStorage.setItem('hh_flash_msg', msg); sessionStorage.setItem('hh_flash_type', type); }
window.addEventListener('load', () => {
    const m = sessionStorage.getItem('hh_flash_msg');
    if (m) { showToast(m, sessionStorage.getItem('hh_flash_type')||'info'); sessionStorage.removeItem('hh_flash_msg'); sessionStorage.removeItem('hh_flash_type'); }
});
</script>
