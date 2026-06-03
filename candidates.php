<?php
// candidates.php — HireHub Talent Discovery
// FIXED using your exact job_portal schema:
//   TABLE: users
//   Columns: id, name, email, role, profile_pic, phone, location, bio, is_active, headline
//   FILTER:  role = 'seeker'  AND  is_active = 1
//   JOIN:    seeker_profiles (user_id) for skills, experience_level, availability

session_start();
require_once 'config.php';

$search       = isset($_GET['search'])       ? trim($_GET['search'])       : '';
$location     = isset($_GET['location'])     ? trim($_GET['location'])     : '';
$experience   = isset($_GET['experience'])   ? trim($_GET['experience'])   : '';
$availability = isset($_GET['availability']) ? trim($_GET['availability']) : '';

// ── KEY FIX ──────────────────────────────────────────────────────────────
// DUAL APPROVAL CHECK — both must be true to appear on candidates page:
//   1. users.is_active = 1                → account not disabled
//   2. seeker_profiles.status = 'approved' → admin approved the profile
// INNER JOIN so only seekers with a seeker_profiles row are returned.
// ─────────────────────────────────────────────────────────────────────────
$sql = "SELECT
            u.id,
            u.name,
            u.email,
            u.profile_pic,
            u.location,
            u.bio,
            u.headline,
            u.phone,
            u.created_at,
            sp.job_title,
            sp.skills,
            sp.experience_years,
            sp.experience_level,
            sp.availability,
            sp.expected_salary,
            sp.linkedin,
            sp.portfolio
        FROM users u
        INNER JOIN seeker_profiles sp ON sp.user_id = u.id
        WHERE u.role     = 'seeker'
          AND u.is_active = 1
          AND sp.status  = 'approved'";

$params = [];
$types  = '';

if (!empty($search)) {
    $sql .= " AND (u.name LIKE ? OR u.headline LIKE ? OR sp.skills LIKE ?)";
    $p = "%{$search}%";
    $params[] = $p; $params[] = $p; $params[] = $p;
    $types .= 'sss';
}
if (!empty($location)) {
    $sql .= " AND u.location LIKE ?";
    $params[] = "%{$location}%";
    $types .= 's';
}
if (!empty($experience)) {
    $sql .= " AND sp.experience_level = ?";
    $params[] = $experience;
    $types .= 's';
}
if (!empty($availability)) {
    $sql .= " AND sp.availability = ?";
    $params[] = $availability;
    $types .= 's';
}

$sql .= " ORDER BY u.id DESC";

$candidates = [];
$dbError    = '';
$stmt = $conn->prepare($sql);
if ($stmt) {
    if (!empty($params)) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $candidates = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $dbError = $conn->error;
}

// ── HELPERS (prefixed cd_ to avoid conflicts with config.php) ────────────
function cd_initials(string $name): string {
    $words = array_filter(explode(' ', trim($name)));
    return strtoupper(implode('', array_map(fn($w) => $w[0], array_slice($words, 0, 2))));
}
function cd_avatarBg(string $name): string {
    $p = ['#6366f1','#8b5cf6','#ec4899','#f59e0b','#10b981','#3b82f6','#ef4444','#14b8a6','#f97316'];
    return $p[abs(crc32($name)) % count($p)];
}
function cd_parseSkills(?string $raw): array {
    if (empty($raw)) return [];
    if (ltrim($raw)[0] === '[') return json_decode($raw, true) ?: [];
    return array_values(array_filter(array_map('trim', explode(',', $raw))));
}
function cd_availBadge(?string $av): array {
    $a = strtolower((string)$av);
    if (str_contains($a, 'open'))                                  return ['avail-open',    'Open to work'];
    if (str_contains($a, 'not') || str_contains($a, 'closed'))    return ['avail-closed',  'Not looking'];
    if (!empty($a))                                                return ['avail-limited', ucwords(str_replace('_',' ',$a))];
    return ['', ''];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>HireHub — Pakistan's Modern Job Platform</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --bg:#0d1117; --bg2:#161b22; --bgc:#1c2230;
  --ac:#6366f1; --acl:#818cf8; --ag:rgba(99,102,241,.14);
  --tp:#e6edf3; --tm:#7d8590; --td:#3d444d;
  --b:rgba(255,255,255,.07); --bh:rgba(99,102,241,.4);
  --r:14px; --rs:8px;
  --f:'Plus Jakarta Sans',system-ui,sans-serif;
}
body{font-family:var(--f);background:var(--bg);color:var(--tp);min-height:100vh}

/* ── NAV ── */
nav{position:sticky;top:0;z-index:100;background:rgba(13,17,23,.9);
    backdrop-filter:blur(20px);border-bottom:1px solid var(--b);
    padding:0 32px;display:flex;align-items:center;justify-content:space-between;height:64px}
.logo{display:flex;align-items:center;gap:10px;text-decoration:none}
.logo-icon{width:34px;height:34px;border-radius:9px;background:var(--ac);
           display:flex;align-items:center;justify-content:center;
           font-size:16px;font-weight:700;color:#fff}
.logo-text{font-size:18px;font-weight:700;color:var(--tp)}
.logo-text em{font-style:normal;color:var(--acl)}
.nl{display:flex;align-items:center;gap:4px}
.nl a{color:var(--tm);text-decoration:none;padding:6px 13px;border-radius:var(--rs);
      font-size:14px;font-weight:500;transition:all .2s}
.nl a:hover,.nl a.on{color:var(--tp);background:var(--b)}
.na{display:flex;align-items:center;gap:10px}
.btn-o{border:1px solid var(--b);background:transparent;color:var(--tm);padding:7px 16px;
       border-radius:var(--rs);font-size:14px;font-weight:500;cursor:pointer;
       text-decoration:none;transition:all .2s;font-family:var(--f)}
.btn-o:hover{border-color:var(--bh);color:var(--tp)}
.btn-p{background:var(--ac);color:#fff;padding:7px 18px;border-radius:var(--rs);
       font-size:14px;font-weight:600;border:none;cursor:pointer;text-decoration:none;
       transition:background .2s;font-family:var(--f)}
.btn-p:hover{background:#5355d8}

/* ── HERO ── */
.hero{padding:56px 32px 32px;max-width:1200px;margin:0 auto}
.hero h1{font-size:30px;font-weight:700;margin-bottom:6px}
.hero p{color:var(--tm);font-size:15px}

/* ── FILTER ── */
.fw{max-width:1200px;margin:0 auto;padding:0 32px 32px}
.fb{background:var(--bg2);border:1px solid var(--b);border-radius:var(--r);
    padding:22px;display:grid;
    grid-template-columns:1fr .7fr .55fr .55fr auto;gap:12px;align-items:end}
.ff label{display:block;font-size:11px;font-weight:600;color:var(--tm);
          text-transform:uppercase;letter-spacing:.06em;margin-bottom:7px}
.ff input,.ff select{width:100%;background:var(--bg);border:1px solid var(--b);
    border-radius:var(--rs);color:var(--tp);font-family:var(--f);font-size:14px;
    padding:9px 13px;outline:none;transition:border-color .2s;
    -webkit-appearance:none;appearance:none}
.ff input::placeholder{color:var(--td)}
.ff input:focus,.ff select:focus{border-color:var(--ac)}
.sw{position:relative}.sw::after{content:'▾';position:absolute;right:11px;
    top:50%;transform:translateY(-50%);color:var(--tm);pointer-events:none;font-size:11px}
.btn-f{background:var(--ac);color:#fff;border:none;border-radius:var(--rs);
       padding:9px 22px;font-size:14px;font-weight:600;cursor:pointer;
       font-family:var(--f);transition:background .2s;white-space:nowrap}
.btn-f:hover{background:#5355d8}

/* ── RESULTS BAR ── */
.rb{max-width:1200px;margin:0 auto;padding:0 32px 16px;
    display:flex;align-items:center;justify-content:space-between}
.rc{font-size:13px;color:var(--tm)}
.rc strong{color:var(--tp)}
.clear-link{font-size:13px;color:var(--acl);text-decoration:none}
.clear-link:hover{text-decoration:underline}

/* ── GRID ── */
.grid{max-width:1200px;margin:0 auto;padding:0 32px 80px;
      display:grid;grid-template-columns:repeat(auto-fill,minmax(295px,1fr));gap:18px}

/* ── CARD ── */
.card{background:var(--bgc);border:1px solid var(--b);border-radius:var(--r);
      padding:22px;display:flex;flex-direction:column;gap:14px;
      transition:border-color .25s,transform .2s,box-shadow .25s}
.card:hover{border-color:var(--bh);transform:translateY(-2px);
            box-shadow:0 8px 28px rgba(0,0,0,.35),0 0 0 1px var(--bh)}

.ctop{display:flex;align-items:flex-start;gap:13px}
.av{width:50px;height:50px;border-radius:11px;display:flex;align-items:center;
    justify-content:center;font-size:17px;font-weight:700;color:#fff;
    flex-shrink:0;overflow:hidden;letter-spacing:-.5px}
.av img{width:100%;height:100%;object-fit:cover;border-radius:11px}
.ci{flex:1;min-width:0}
.cn{font-size:15px;font-weight:700;color:var(--tp);
    white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.ct{font-size:12px;color:var(--acl);margin-top:2px;
    white-space:nowrap;overflow:hidden;text-overflow:ellipsis;font-weight:500}

.abadge{font-size:11px;font-weight:600;padding:3px 9px;border-radius:20px;
        margin-left:auto;flex-shrink:0;white-space:nowrap;line-height:1.6}
.avail-open   {background:rgba(16,185,129,.15);color:#34d399;border:1px solid rgba(16,185,129,.25)}
.avail-limited{background:rgba(245,158,11,.12); color:#fbbf24;border:1px solid rgba(245,158,11,.2)}
.avail-closed {background:rgba(239,68,68,.12);  color:#f87171;border:1px solid rgba(239,68,68,.2)}

.cmeta{display:flex;flex-wrap:wrap;gap:7px}
.mb{display:flex;align-items:center;gap:5px;font-size:12px;color:var(--tm);
    background:var(--bg2);padding:4px 10px;border-radius:20px;border:1px solid var(--b)}
.mb svg{width:11px;height:11px;flex-shrink:0}

.cbio{font-size:13px;color:var(--tm);line-height:1.65;
      display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}

.stags{display:flex;flex-wrap:wrap;gap:6px}
.stag{font-size:11px;font-weight:600;background:var(--ag);color:var(--acl);
      border:1px solid rgba(99,102,241,.2);padding:3px 9px;border-radius:5px}
.smore{font-size:11px;color:var(--td);padding:3px 4px;align-self:center}

.cfoot{border-top:1px solid var(--b);padding-top:13px;display:flex;gap:8px;margin-top:auto}
.bv{flex:1;text-align:center;padding:8px;border:1px solid var(--b);
    border-radius:var(--rs);color:var(--tm);font-size:13px;font-weight:500;
    text-decoration:none;transition:all .2s}
.bv:hover{border-color:var(--ac);color:var(--acl);background:var(--ag)}
.bc{flex:1;text-align:center;padding:8px;background:var(--ac);
    border-radius:var(--rs);color:#fff;font-size:13px;font-weight:600;
    text-decoration:none;transition:background .2s}
.bc:hover{background:#5355d8}

/* ── EMPTY & ERROR ── */
.empty{grid-column:1/-1;text-align:center;padding:70px 20px}
.eico{width:68px;height:68px;border-radius:16px;background:var(--bg2);
      border:1px solid var(--b);display:flex;align-items:center;
      justify-content:center;margin:0 auto 18px;font-size:26px}
.empty h3{font-size:18px;margin-bottom:8px}
.empty p{color:var(--tm);font-size:14px;max-width:380px;margin:0 auto;line-height:1.75}
.empty code{background:var(--bg2);padding:2px 6px;border-radius:4px;
            font-size:12px;color:var(--acl);border:1px solid var(--b)}

.err{max-width:1200px;margin:0 auto 16px;padding:0 32px}
.err-box{background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.25);
         border-radius:8px;padding:12px 16px;font-size:13px;color:#f87171;
         font-family:monospace;line-height:1.7}

@media(max-width:768px){
  nav,.hero,.fw,.rb,.grid{padding-left:16px;padding-right:16px}
  .fb{grid-template-columns:1fr}
  .grid{grid-template-columns:1fr}
  nav{padding:0 16px}
}
</style>
</head>
<body>

<!-- ── NAV ─────────────────────────────────── -->
<nav>
  <a href="index.php" class="logo">
    <div class="logo-icon">H</div>
    <span class="logo-text">Hire <em>Hub</em></span>
  </a>
  <div class="nl">
    <a href="index.php">🏠 Home</a>
    <a href="candidates.php" class="on">👥 Talent</a>
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

<!-- ── HERO ─────────────────────────────────── -->
<div class="hero">
  <h1>Candidate Discovery</h1>
  <p>Browse approved professional profiles and connect with top talent in real time.</p>
</div>

<!-- ── FILTERS ───────────────────────────────── -->
<div class="fw">
  <form method="GET" action="candidates.php">
    <div class="fb">
      <div class="ff">
        <label>Search candidates</label>
        <input type="text" name="search"
               placeholder="Job title, skills, location, keywords"
               value="<?= htmlspecialchars($search) ?>">
      </div>
      <div class="ff">
        <label>Location</label>
        <input type="text" name="location"
               placeholder="City or region"
               value="<?= htmlspecialchars($location) ?>">
      </div>
      <div class="ff">
        <label>Experience</label>
        <div class="sw">
          <select name="experience">
            <option value="">Any level</option>
            <?php foreach (['Entry','Mid','Senior','Executive'] as $lvl): ?>
            <option value="<?= $lvl ?>" <?= $experience===$lvl?'selected':'' ?>><?= $lvl ?> level</option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="ff">
        <label>Availability</label>
        <div class="sw">
          <select name="availability">
            <option value="">Any</option>
            <option value="open"        <?= $availability==='open'       ?'selected':'' ?>>Open to work</option>
            <option value="limited"     <?= $availability==='limited'    ?'selected':'' ?>>Limited</option>
            <option value="not_looking" <?= $availability==='not_looking'?'selected':'' ?>>Not looking</option>
          </select>
        </div>
      </div>
      <button type="submit" class="btn-f">Filter</button>
    </div>
  </form>
</div>

<!-- ── DB ERROR ──────────────────────────────── -->
<?php if ($dbError): ?>
<div class="err">
  <div class="err-box">
    <strong>Database error:</strong> <?= htmlspecialchars($dbError) ?><br>
    <small>
      If "seeker_profiles" doesn't exist yet, remove the <code>LEFT JOIN</code> line
      and the <code>sp.*</code> columns from the SELECT in <code>candidates.php</code>.
    </small>
  </div>
</div>
<?php endif; ?>

<!-- ── RESULTS COUNT ─────────────────────────── -->
<div class="rb">
  <span class="rc">
    Showing <strong><?= count($candidates) ?></strong>
    approved candidate<?= count($candidates)!==1?'s':'' ?>
    <?= !empty($search) ? ' for "<strong>'.htmlspecialchars($search).'</strong>"' : '' ?>
  </span>
  <?php if (!empty($search)||!empty($location)||!empty($experience)||!empty($availability)): ?>
    <a href="candidates.php" class="clear-link">Clear filters</a>
  <?php endif; ?>
</div>

<!-- ── CANDIDATE CARDS ───────────────────────── -->
<div class="grid">

<?php if (empty($candidates)): ?>
  <div class="empty">
    <div class="eico">🔍</div>
    <h3><?= (!empty($search)||!empty($location)||!empty($experience)||!empty($availability)) ? 'No results found' : 'No approved candidates yet' ?></h3>
    <p>
      <?php if (!empty($search)||!empty($location)||!empty($experience)||!empty($availability)): ?>
        Try wider search terms or <a href="candidates.php" style="color:var(--acl)">clear all filters</a>.
      <?php else: ?>
        Candidates need <code>role = 'seeker'</code> and <code>is_active = 1</code>
        in the <code>users</code> table.<br>Approve them from the admin panel to show here.
      <?php endif; ?>
    </p>
  </div>

<?php else: ?>
  <?php foreach ($candidates as $c):
    $skills = cd_parseSkills($c['skills'] ?? '');
    $ini    = cd_initials($c['name'] ?? 'U');
    $bg     = cd_avatarBg($c['name'] ?? '');

    // Profile picture — adjust folder path to match your uploads directory
    $pic    = !empty($c['profile_pic']) ? 'uploads/'.$c['profile_pic'] : '';
    $hasPic = $pic && file_exists($pic);

    [$avCls, $avLbl] = cd_availBadge($c['availability'] ?? '');
  ?>
  <div class="card">

    <!-- Top: avatar + name + availability -->
    <div class="ctop">
      <div class="av" style="background:<?= $hasPic ? '#1c2230' : $bg ?>">
        <?php if ($hasPic): ?>
          <img src="<?= htmlspecialchars($pic) ?>"
               alt="<?= htmlspecialchars($c['name']) ?>">
        <?php else: ?>
          <?= $ini ?>
        <?php endif; ?>
      </div>
      <div class="ci">
        <div class="cn"><?= htmlspecialchars($c['name'] ?? 'Unknown') ?></div>
        <div class="ct"><?= htmlspecialchars(!empty($c['headline']) ? $c['headline'] : 'Job Seeker') ?></div>
      </div>
      <?php if ($avCls): ?>
        <span class="abadge <?= $avCls ?>"><?= $avLbl ?></span>
      <?php endif; ?>
    </div>

    <!-- Location + Experience badges -->
    <?php if (!empty($c['location']) || !empty($c['experience_level'])): ?>
    <div class="cmeta">
      <?php if (!empty($c['location'])): ?>
      <span class="mb">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/>
          <circle cx="12" cy="9" r="2.5"/>
        </svg>
        <?= htmlspecialchars($c['location']) ?>
      </span>
      <?php endif; ?>
      <?php if (!empty($c['experience_level'])): ?>
      <span class="mb">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <rect x="2" y="7" width="20" height="14" rx="2"/>
          <path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/>
        </svg>
        <?= htmlspecialchars($c['experience_level']) ?>
      </span>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Bio snippet -->
    <?php if (!empty($c['bio'])): ?>
      <div class="cbio"><?= htmlspecialchars($c['bio']) ?></div>
    <?php endif; ?>

    <!-- Skill tags -->
    <?php if (!empty($skills)): ?>
    <div class="stags">
      <?php foreach (array_slice($skills, 0, 4) as $sk): ?>
        <span class="stag"><?= htmlspecialchars(trim($sk)) ?></span>
      <?php endforeach; ?>
      <?php if (count($skills) > 4): ?>
        <span class="smore">+<?= count($skills)-4 ?> more</span>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Actions -->
    <div class="cfoot">
      <a href="candidate_profile.php?id=<?= (int)$c['id'] ?>" class="bv">
        View Profile
      </a>
      <?php
        $isEmployer = isset($_SESSION['user_id']) &&
                      isset($_SESSION['role']) &&
                      $_SESSION['role'] === 'employer';
      ?>
      <?php if ($isEmployer): ?>
        <a href="contact_candidate.php?id=<?= (int)$c['id'] ?>" class="bc">Contact</a>
      <?php else: ?>
        <a href="login.php?redirect=<?= urlencode('candidates.php') ?>" class="bc">Contact</a>
      <?php endif; ?>
    </div>

  </div>
  <?php endforeach; ?>
<?php endif; ?>

</div><!-- /grid -->

</body>
</html>
