<?php
// candidate_profile.php — HireHub
// Shows full profile of a single approved seeker
// Schema: users (id, name, email, role, profile_pic, phone, location, bio, is_active, headline)
//         seeker_profiles (user_id, skills, experience_level, availability, ...)

session_start();
require_once 'config.php';

// ── VALIDATE ID ──────────────────────────────────────────────────────────
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: candidates.php');
    exit;
}

// ── FETCH CANDIDATE ──────────────────────────────────────────────────────
$sql = "SELECT
            u.id,
            u.name,
            u.email,
            u.phone,
            u.location,
            u.bio,
            u.headline,
            u.profile_pic,
            u.created_at,
            sp.job_title,
            sp.skills,
            sp.experience_years,
            sp.experience_level,
            sp.availability,
            sp.expected_salary,
            sp.linkedin,
            sp.portfolio,
            sp.education,
            sp.career_objective,
            sp.resume
        FROM users u
        INNER JOIN seeker_profiles sp ON sp.user_id = u.id
        WHERE u.id       = ?
          AND u.role      = 'seeker'
          AND u.is_active = 1
          AND sp.status  = 'approved'
        LIMIT 1";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die('Query error: ' . $conn->error);
}
$stmt->bind_param('i', $id);
$stmt->execute();
$c = $stmt->get_result()->fetch_assoc();
$stmt->close();

// 404 if not found / not approved
if (!$c) {
    header('HTTP/1.0 404 Not Found');
    ?><!DOCTYPE html>
    <html><head><title>Not Found</title>
    <style>body{font-family:sans-serif;background:#0d1117;color:#e6edf3;
    display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0}
    .box{text-align:center}.box h2{font-size:24px;margin-bottom:12px}
    .box p{color:#7d8590;margin-bottom:20px}
    .box a{color:#818cf8;text-decoration:none}</style></head>
    <body><div class="box">
    <h2>Candidate not found</h2>
    <p>This profile doesn't exist or hasn't been approved yet.</p>
    <a href="candidates.php">← Back to candidates</a>
    </div></body></html>
    <?php
    exit;
}

// ── HELPERS ──────────────────────────────────────────────────────────────
if (!function_exists('cp_initials')) {
    function cp_initials(string $name): string {
        $words = array_filter(explode(' ', trim($name)));
        return strtoupper(implode('', array_map(fn($w) => $w[0], array_slice($words, 0, 2))));
    }
}
if (!function_exists('cp_avatarBg')) {
    function cp_avatarBg(string $name): string {
        $p = ['#6366f1','#8b5cf6','#ec4899','#f59e0b','#10b981','#3b82f6','#ef4444','#14b8a6'];
        return $p[abs(crc32($name)) % count($p)];
    }
}
if (!function_exists('cp_parseSkills')) {
    function cp_parseSkills(?string $raw): array {
        if (empty($raw)) return [];
        if (ltrim($raw)[0] === '[') return json_decode($raw, true) ?: [];
        return array_values(array_filter(array_map('trim', explode(',', $raw))));
    }
}

$skills  = cp_parseSkills($c['skills'] ?? '');
$ini     = cp_initials($c['name'] ?? 'U');
$bg      = cp_avatarBg($c['name'] ?? '');
$pic     = !empty($c['profile_pic']) ? 'uploads/' . $c['profile_pic'] : '';
$hasPic  = $pic && file_exists($pic);

// headline: prefer users.headline, fall back to seeker_profiles.job_title
$displayTitle = !empty($c['headline']) ? $c['headline']
              : (!empty($c['job_title']) ? $c['job_title'] : 'Job Seeker');

$av      = strtolower($c['availability'] ?? '');
if (str_contains($av, 'open'))                                 { $avCls = 'avail-open';    $avLbl = 'Open to work'; }
elseif (str_contains($av, 'not') || str_contains($av, 'closed')) { $avCls = 'avail-closed';  $avLbl = 'Not looking';  }
elseif (!empty($av))                                           { $avCls = 'avail-limited'; $avLbl = ucwords(str_replace('_',' ',$av)); }
else                                                           { $avCls = '';              $avLbl = '';             }

$memberSince = !empty($c['created_at'])
    ? date('F Y', strtotime($c['created_at']))
    : '';

$isEmployer = isset($_SESSION['user_id']) &&
              isset($_SESSION['role']) &&
              $_SESSION['role'] === 'employer';
$isOwner    = isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] === $c['id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($c['name']) ?> — HireHub Profile</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --bg:#0d1117; --bg2:#161b22; --bgc:#1c2230; --bgc2:#222b3a;
  --ac:#6366f1; --acl:#818cf8; --ag:rgba(99,102,241,.13);
  --tp:#e6edf3; --tm:#7d8590; --td:#3d444d;
  --b:rgba(255,255,255,.07); --bh:rgba(99,102,241,.4);
  --r:14px; --rs:8px;
  --f:'Plus Jakarta Sans',system-ui,sans-serif;
}
body{font-family:var(--f);background:var(--bg);color:var(--tp);min-height:100vh}

/* NAV */
nav{position:sticky;top:0;z-index:100;background:rgba(13,17,23,.9);
    backdrop-filter:blur(20px);border-bottom:1px solid var(--b);
    padding:0 32px;display:flex;align-items:center;justify-content:space-between;height:64px}
.logo{display:flex;align-items:center;gap:10px;text-decoration:none}
.logo-icon{width:34px;height:34px;border-radius:9px;background:var(--ac);
           display:flex;align-items:center;justify-content:center;font-size:16px;font-weight:700;color:#fff}
.logo-text{font-size:18px;font-weight:700;color:var(--tp)}
.logo-text em{font-style:normal;color:var(--acl)}
.nl{display:flex;align-items:center;gap:4px}
.nl a{color:var(--tm);text-decoration:none;padding:6px 13px;border-radius:var(--rs);
      font-size:14px;font-weight:500;transition:all .2s}
.nl a:hover{color:var(--tp);background:var(--b)}
.na{display:flex;align-items:center;gap:10px}
.btn-o{border:1px solid var(--b);background:transparent;color:var(--tm);padding:7px 16px;
       border-radius:var(--rs);font-size:14px;font-weight:500;cursor:pointer;
       text-decoration:none;transition:all .2s;font-family:var(--f)}
.btn-o:hover{border-color:var(--bh);color:var(--tp)}
.btn-p{background:var(--ac);color:#fff;padding:7px 18px;border-radius:var(--rs);
       font-size:14px;font-weight:600;border:none;cursor:pointer;text-decoration:none;
       transition:background .2s;font-family:var(--f)}
.btn-p:hover{background:#5355d8}

/* BREADCRUMB */
.crumb{max-width:960px;margin:0 auto;padding:20px 32px 0;
       font-size:13px;color:var(--tm)}
.crumb a{color:var(--tm);text-decoration:none}
.crumb a:hover{color:var(--acl)}
.crumb span{margin:0 6px;opacity:.4}

/* LAYOUT */
.wrap{max-width:960px;margin:0 auto;padding:24px 32px 80px;
      display:grid;grid-template-columns:1fr 300px;gap:24px;align-items:start}

/* ── LEFT COLUMN ── */
/* HERO CARD */
.hero-card{background:var(--bgc);border:1px solid var(--b);border-radius:var(--r);
           padding:32px;display:flex;gap:24px;align-items:flex-start}
.av-wrap{flex-shrink:0}
.av{width:88px;height:88px;border-radius:18px;display:flex;align-items:center;
    justify-content:center;font-size:32px;font-weight:700;color:#fff;
    overflow:hidden;letter-spacing:-1px}
.av img{width:100%;height:100%;object-fit:cover;border-radius:18px}
.hero-info{flex:1;min-width:0}
.hero-name{font-size:24px;font-weight:700;margin-bottom:4px}
.hero-title{font-size:15px;color:var(--acl);font-weight:500;margin-bottom:14px}
.hero-meta{display:flex;flex-wrap:wrap;gap:10px;margin-bottom:16px}
.hmb{display:flex;align-items:center;gap:6px;font-size:13px;color:var(--tm)}
.hmb svg{width:13px;height:13px;flex-shrink:0}
.abadge{font-size:12px;font-weight:600;padding:4px 12px;border-radius:20px}
.avail-open   {background:rgba(16,185,129,.15);color:#34d399;border:1px solid rgba(16,185,129,.25)}
.avail-limited{background:rgba(245,158,11,.12); color:#fbbf24;border:1px solid rgba(245,158,11,.2)}
.avail-closed {background:rgba(239,68,68,.12);  color:#f87171;border:1px solid rgba(239,68,68,.2)}
.hero-actions{display:flex;gap:10px;flex-wrap:wrap}
.btn-contact{background:var(--ac);color:#fff;padding:9px 22px;border-radius:var(--rs);
             font-size:14px;font-weight:600;text-decoration:none;transition:background .2s;
             display:inline-flex;align-items:center;gap:7px}
.btn-contact:hover{background:#5355d8}
.btn-back{border:1px solid var(--b);color:var(--tm);padding:9px 18px;border-radius:var(--rs);
          font-size:14px;font-weight:500;text-decoration:none;transition:all .2s;
          display:inline-flex;align-items:center;gap:7px}
.btn-back:hover{border-color:var(--bh);color:var(--tp)}

/* SECTION CARDS */
.section{background:var(--bgc);border:1px solid var(--b);border-radius:var(--r);padding:26px}
.sec-title{font-size:13px;font-weight:600;color:var(--tm);text-transform:uppercase;
           letter-spacing:.07em;margin-bottom:16px;display:flex;align-items:center;gap:8px}
.sec-title::after{content:'';flex:1;height:1px;background:var(--b)}

/* BIO */
.bio-text{font-size:15px;color:var(--tm);line-height:1.8}

/* SKILLS */
.skills-grid{display:flex;flex-wrap:wrap;gap:8px}
.skill{font-size:13px;font-weight:600;background:var(--ag);color:var(--acl);
       border:1px solid rgba(99,102,241,.22);padding:6px 14px;border-radius:6px}

/* EMPTY SECTION */
.empty-sec{font-size:14px;color:var(--td);font-style:italic}

/* LEFT STACK */
.left-col{display:flex;flex-direction:column;gap:18px}

/* ── RIGHT COLUMN ── */
.right-col{display:flex;flex-direction:column;gap:18px}

/* INFO CARD */
.info-card{background:var(--bgc);border:1px solid var(--b);border-radius:var(--r);padding:22px}
.info-row{display:flex;flex-direction:column;gap:4px;padding:12px 0;
          border-bottom:1px solid var(--b)}
.info-row:first-child{padding-top:0}
.info-row:last-child{border-bottom:none;padding-bottom:0}
.info-label{font-size:11px;font-weight:600;color:var(--td);text-transform:uppercase;letter-spacing:.06em}
.info-value{font-size:14px;color:var(--tp);font-weight:500;word-break:break-all}
.info-value a{color:var(--acl);text-decoration:none}
.info-value a:hover{text-decoration:underline}

/* CONTACT CTA CARD */
.cta-card{background:linear-gradient(135deg,rgba(99,102,241,.18),rgba(139,92,246,.12));
          border:1px solid var(--bh);border-radius:var(--r);padding:24px;text-align:center}
.cta-card h4{font-size:16px;font-weight:700;margin-bottom:6px}
.cta-card p{font-size:13px;color:var(--tm);margin-bottom:18px;line-height:1.6}
.cta-card .btn-contact{width:100%;justify-content:center}
.cta-card .btn-login{display:block;margin-top:10px;font-size:13px;color:var(--acl);
                     text-decoration:none;text-align:center}
.cta-card .btn-login:hover{text-decoration:underline}

/* SHARE CARD */
.share-card{background:var(--bgc);border:1px solid var(--b);border-radius:var(--r);padding:20px}
.share-title{font-size:13px;font-weight:600;color:var(--tm);margin-bottom:12px}
.share-url{background:var(--bg2);border:1px solid var(--b);border-radius:var(--rs);
           padding:8px 12px;font-size:12px;color:var(--td);font-family:monospace;
           white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-bottom:10px}
.btn-copy{width:100%;background:var(--bg2);border:1px solid var(--b);border-radius:var(--rs);
          color:var(--tm);font-size:13px;font-weight:500;padding:8px;cursor:pointer;
          font-family:var(--f);transition:all .2s}
.btn-copy:hover{border-color:var(--bh);color:var(--tp)}
.btn-copy.copied{border-color:rgba(16,185,129,.4);color:#34d399}

@media(max-width:768px){
  nav,.crumb,.wrap{padding-left:16px;padding-right:16px}
  nav{padding:0 16px}
  .wrap{grid-template-columns:1fr;padding-top:16px}
  .right-col{order:-1}
  .hero-card{flex-direction:column;gap:16px}
  .av{width:72px;height:72px;font-size:26px;border-radius:14px}
}
</style>
</head>
<body>

<!-- NAV -->
<nav>
  <a href="index.php" class="logo">
    <div class="logo-icon">H</div>
    <span class="logo-text">Hire <em>Hub</em></span>
  </a>
  <div class="nl">
    <a href="index.php">🏠 Home</a>
    <a href="candidates.php">👥 Talent</a>
    <a href="contact.php">✉️ Contact</a>
  </div>
  <div class="na">
    <?php if (isset($_SESSION['user_id'])): ?>
      <a href="dashboard.php" class="btn-o">Dashboard</a>
      <a href="logout.php"    class="btn-p">Logout</a>
    <?php else: ?>
      <a href="login.php"    class="btn-o">Login</a>
      <a href="register.php" class="btn-p">Get Started</a>
    <?php endif; ?>
  </div>
</nav>

<!-- BREADCRUMB -->
<div class="crumb">
  <a href="candidates.php">Talent</a>
  <span>›</span>
  <?= htmlspecialchars($c['name']) ?>
</div>

<!-- MAIN LAYOUT -->
<div class="wrap">

  <!-- ── LEFT COLUMN ── -->
  <div class="left-col">

    <!-- HERO CARD -->
    <div class="hero-card">
      <div class="av-wrap">
        <div class="av" style="background:<?= $hasPic ? '#1c2230' : $bg ?>">
          <?php if ($hasPic): ?>
            <img src="<?= htmlspecialchars($pic) ?>"
                 alt="<?= htmlspecialchars($c['name']) ?>">
          <?php else: ?>
            <?= $ini ?>
          <?php endif; ?>
        </div>
      </div>
      <div class="hero-info">
        <div class="hero-name"><?= htmlspecialchars($c['name']) ?></div>
        <div class="hero-title">
          <?= htmlspecialchars($displayTitle) ?>
        </div>
        <div class="hero-meta">
          <?php if (!empty($c['location'])): ?>
          <span class="hmb">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/>
              <circle cx="12" cy="9" r="2.5"/>
            </svg>
            <?= htmlspecialchars($c['location']) ?>
          </span>
          <?php endif; ?>
          <?php if (!empty($c['experience_level'])): ?>
          <span class="hmb">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <rect x="2" y="7" width="20" height="14" rx="2"/>
              <path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/>
            </svg>
            <?= htmlspecialchars($c['experience_level']) ?>
          </span>
          <?php endif; ?>
          <?php if ($avCls): ?>
            <span class="abadge <?= $avCls ?>"><?= $avLbl ?></span>
          <?php endif; ?>
        </div>
        <div class="hero-actions">
          <?php if ($isOwner): ?>
            <a href="edit_profile.php" class="btn-contact">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
              </svg>
              Edit My Profile
            </a>
          <?php elseif ($isEmployer): ?>
            <a href="contact_candidate.php?id=<?= $c['id'] ?>" class="btn-contact">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                <polyline points="22,6 12,13 2,6"/>
              </svg>
              Contact Candidate
            </a>
          <?php else: ?>
            <a href="login.php?redirect=<?= urlencode('candidate_profile.php?id='.$c['id']) ?>"
               class="btn-contact">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                <polyline points="22,6 12,13 2,6"/>
              </svg>
              Contact Candidate
            </a>
          <?php endif; ?>
          <a href="candidates.php" class="btn-back">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <polyline points="15 18 9 12 15 6"/>
            </svg>
            All Candidates
          </a>
        </div>
      </div>
    </div>

    <!-- BIO -->
    <div class="section">
      <div class="sec-title">About</div>
      <?php if (!empty($c['bio'])): ?>
        <p class="bio-text"><?= nl2br(htmlspecialchars($c['bio'])) ?></p>
      <?php else: ?>
        <p class="empty-sec">No bio added yet.</p>
      <?php endif; ?>
    </div>

    <!-- SKILLS -->
    <div class="section">
      <div class="sec-title">Skills</div>
      <?php if (!empty($skills)): ?>
        <div class="skills-grid">
          <?php foreach ($skills as $sk): ?>
            <span class="skill"><?= htmlspecialchars(trim($sk)) ?></span>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <p class="empty-sec">No skills listed yet.</p>
      <?php endif; ?>
    </div>

    <!-- CAREER OBJECTIVE -->
    <?php if (!empty($c['career_objective'])): ?>
    <div class="section">
      <div class="sec-title">Career Objective</div>
      <p class="bio-text"><?= nl2br(htmlspecialchars($c['career_objective'])) ?></p>
    </div>
    <?php endif; ?>

    <!-- EDUCATION -->
    <?php if (!empty($c['education'])): ?>
    <div class="section">
      <div class="sec-title">Education</div>
      <p class="bio-text"><?= nl2br(htmlspecialchars($c['education'])) ?></p>
    </div>
    <?php endif; ?>

  </div><!-- /left-col -->

  <!-- ── RIGHT COLUMN ── -->
  <div class="right-col">

    <!-- CONTACT CTA -->
    <div class="cta-card">
      <h4>Interested in <?= htmlspecialchars(explode(' ', $c['name'])[0]) ?>?</h4>
      <p>Send a message to start the conversation and discuss opportunities.</p>
      <?php if ($isOwner): ?>
        <a href="edit_profile.php" class="btn-contact">Edit My Profile</a>
      <?php elseif ($isEmployer): ?>
        <a href="contact_candidate.php?id=<?= $c['id'] ?>" class="btn-contact">Send Message</a>
      <?php else: ?>
        <a href="login.php?redirect=<?= urlencode('candidate_profile.php?id='.$c['id']) ?>"
           class="btn-contact">Login to Contact</a>
        <a href="register.php" class="btn-login">Don't have an account? Sign up</a>
      <?php endif; ?>
    </div>

    <!-- PROFILE DETAILS -->
    <div class="info-card">
      <div class="sec-title" style="font-size:13px;font-weight:600;color:var(--tm);
           text-transform:uppercase;letter-spacing:.07em;margin-bottom:16px">
        Profile Details
      </div>

      <?php if (!empty($c['experience_level'])): ?>
      <div class="info-row">
        <span class="info-label">Experience</span>
        <span class="info-value"><?= htmlspecialchars($c['experience_level']) ?></span>
      </div>
      <?php endif; ?>

      <?php if (!empty($c['location'])): ?>
      <div class="info-row">
        <span class="info-label">Location</span>
        <span class="info-value"><?= htmlspecialchars($c['location']) ?></span>
      </div>
      <?php endif; ?>

      <?php if ($avLbl): ?>
      <div class="info-row">
        <span class="info-label">Availability</span>
        <span class="info-value">
          <span class="abadge <?= $avCls ?>" style="font-size:11px"><?= $avLbl ?></span>
        </span>
      </div>
      <?php endif; ?>

      <?php if ($isEmployer || $isOwner): ?>
        <?php if (!empty($c['email'])): ?>
        <div class="info-row">
          <span class="info-label">Email</span>
          <span class="info-value">
            <a href="mailto:<?= htmlspecialchars($c['email']) ?>">
              <?= htmlspecialchars($c['email']) ?>
            </a>
          </span>
        </div>
        <?php endif; ?>
        <?php if (!empty($c['phone'])): ?>
        <div class="info-row">
          <span class="info-label">Phone</span>
          <span class="info-value">
            <a href="tel:<?= htmlspecialchars($c['phone']) ?>">
              <?= htmlspecialchars($c['phone']) ?>
            </a>
          </span>
        </div>
        <?php endif; ?>
      <?php else: ?>
      <div class="info-row">
        <span class="info-label">Contact</span>
        <span class="info-value" style="color:var(--td);font-size:13px">
          Login as employer to view
        </span>
      </div>
      <?php endif; ?>

      <?php if ($memberSince): ?>
      <div class="info-row">
        <span class="info-label">Member since</span>
        <span class="info-value"><?= $memberSince ?></span>
      </div>
      <?php endif; ?>

      <?php if (!empty($c['expected_salary'])): ?>
      <div class="info-row">
        <span class="info-label">Expected Salary</span>
        <span class="info-value"><?= htmlspecialchars($c['expected_salary']) ?></span>
      </div>
      <?php endif; ?>

      <?php if (!empty($c['linkedin'])): ?>
      <div class="info-row">
        <span class="info-label">LinkedIn</span>
        <span class="info-value">
          <a href="<?= htmlspecialchars($c['linkedin']) ?>" target="_blank" rel="noopener">
            View Profile ↗
          </a>
        </span>
      </div>
      <?php endif; ?>

      <?php if (!empty($c['portfolio'])): ?>
      <div class="info-row">
        <span class="info-label">Portfolio</span>
        <span class="info-value">
          <a href="<?= htmlspecialchars($c['portfolio']) ?>" target="_blank" rel="noopener">
            View Portfolio ↗
          </a>
        </span>
      </div>
      <?php endif; ?>

      <?php if (($isEmployer || $isOwner) && !empty($c['resume_path'])): ?>
      <div class="info-row">
        <span class="info-label">Resume</span>
        <span class="info-value">
          <a href="uploads/<?= htmlspecialchars($c['resume_path']) ?>" target="_blank">
            Download CV ↓
          </a>
        </span>
      </div>
      <?php endif; ?>
    </div>

    <!-- SHARE CARD -->
    <div class="share-card">
      <div class="share-title">Share this profile</div>
      <div class="share-url" id="shareUrl">
        <?= htmlspecialchars((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']==='on'?'https':'http')
            .'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']) ?>
      </div>
      <button class="btn-copy" onclick="copyUrl(this)">Copy Link</button>
    </div>

  </div><!-- /right-col -->

</div><!-- /wrap -->

<script>
function copyUrl(btn) {
    const url = document.getElementById('shareUrl').textContent.trim();
    navigator.clipboard.writeText(url).then(() => {
        btn.textContent = '✓ Copied!';
        btn.classList.add('copied');
        setTimeout(() => {
            btn.textContent = 'Copy Link';
            btn.classList.remove('copied');
        }, 2000);
    });
}
</script>

</body>
</html>
