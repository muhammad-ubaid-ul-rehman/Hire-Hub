<?php
require_once 'config.php';

// ── Search & filter inputs ──
$search   = trim($_GET['search']   ?? '');
$location = trim($_GET['location'] ?? '');
$type     = trim($_GET['type']     ?? '');
$salary_min = trim($_GET['salary_min'] ?? '');
$experience = trim($_GET['experience'] ?? '');
$sort     = trim($_GET['sort']     ?? 'newest');

// ── Build query with prepared statement ──
$where  = "WHERE 1=1";
$params = [];
$types  = '';

if ($search !== '') {
    $like    = "%$search%";
    $where  .= " AND (j.title LIKE ? OR j.description LIKE ? OR j.requirements LIKE ? OR j.company LIKE ?)";
    $params  = array_merge($params, [$like, $like, $like, $like]);
    $types  .= 'ssss';
}
if ($location !== '') {
    $like    = "%$location%";
    $where  .= " AND j.location LIKE ?";
    $params[] = $like; $types .= 's';
}
if ($type !== '') {
    $where  .= " AND j.job_type = ?";
    $params[] = $type; $types .= 's';
}
if ($salary_min !== '') {
    $where  .= " AND j.salary_min >= ?";
    $params[] = (int)$salary_min; $types .= 'i';
}
if ($experience !== '') {
    $where  .= " AND j.experience_level = ?";
    $params[] = $experience; $types .= 's';
}

$orderBy = match($sort) {
    'oldest' => 'j.posted_at ASC',
    'salary_high' => 'CAST(REPLACE(REPLACE(j.salary, \'PKR\', \'\'), \',\', \'\') AS UNSIGNED) DESC',
    'salary_low' => 'CAST(REPLACE(REPLACE(j.salary, \'PKR\', \'\'), \',\', \'\') AS UNSIGNED) ASC',
    default => 'j.posted_at DESC'
};

$sql  = "SELECT j.*, u.name AS employer_name,
                (SELECT COUNT(*) FROM applications a WHERE a.job_id = j.id) AS app_count
         FROM jobs j
         JOIN users u ON j.employer_id = u.id
         $where AND j.status = 'active'
         ORDER BY $orderBy";

$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$result    = $stmt->get_result();
$job_count = $result->num_rows;

// ── Total live jobs (no filter) for hero ──
$total_jobs = $conn->query("SELECT COUNT(*) AS c FROM jobs WHERE status = 'active'")->fetch_assoc()['c'];
$is_search  = ($search !== '' || $location !== '' || $type !== '' || $salary_min !== '' || $experience !== '');

require 'header.php';
?>

<!-- ================================================================
     HERO SECTION
     ================================================================ -->
<section style="background: radial-gradient(ellipse 90% 60% at 50% -10%, rgba(79,110,247,0.18) 0%, transparent 70%); padding: 80px 0 60px;">
    <div class="container" style="text-align:center;">

        <?php if ($total_jobs > 0): ?>
        <div style="display:inline-flex;align-items:center;gap:8px;background:rgba(79,110,247,0.1);border:1px solid rgba(79,110,247,0.25);border-radius:20px;padding:6px 16px;font-size:13px;font-weight:600;color:var(--accent);margin-bottom:24px;">
            <span style="width:7px;height:7px;border-radius:50%;background:var(--green);display:inline-block;animation:pulse 2s infinite;"></span>
            <?= $total_jobs ?> live <?= $total_jobs === 1 ? 'opportunity' : 'opportunities' ?> right now
        </div>
        <?php endif; ?>

        <h1 class="h1" style="margin-bottom:16px; color: #fff;">
            Find Your Next<br>
            <span style="background:linear-gradient(135deg,#fff,#e2e8f0);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;">
                Career in Pakistan
            </span>
        </h1>
        <p style="color:var(--text2);font-size:18px;max-width:500px;margin:0 auto 40px;line-height:1.7;">
            Real jobs from real employers. No fluff, no spam — just opportunities.
        </p>

        <!-- Search bar -->
        <form method="GET" action="index.php" style="max-width:900px;margin:0 auto;">
            <div style="display:flex;align-items:center;background:var(--card);border:1px solid var(--border2);border-radius:16px;padding:8px 8px 8px 20px;gap:8px;flex-wrap:wrap;">
                <i class="fa fa-search" style="color:var(--text3);flex-shrink:0;"></i>
                <input type="text" name="search" placeholder="Job title, skills, company..."
                       value="<?= e($search) ?>"
                       style="flex:2;min-width:160px;background:transparent;border:none;color:var(--text);font-size:15px;outline:none;padding:6px 0;">

                <div style="width:1px;height:22px;background:var(--border);flex-shrink:0;"></div>

                <i class="fa fa-location-dot" style="color:var(--text3);flex-shrink:0;"></i>
                <input type="text" name="location" placeholder="City (e.g. Lahore)"
                       value="<?= e($location) ?>"
                       style="flex:1;min-width:120px;background:transparent;border:none;color:var(--text);font-size:15px;outline:none;padding:6px 0;">

                <div style="width:1px;height:22px;background:var(--border);flex-shrink:0;"></div>

                <select name="type"
                        style="background:transparent;border:none;color:<?= $type ? 'var(--text)' : 'var(--text3)' ?>;font-size:14px;outline:none;padding:6px 4px;flex-shrink:0;">
                    <option value="">Any Type</option>
                    <?php foreach (['full-time'=>'Full-Time','part-time'=>'Part-Time','remote'=>'Remote','contract'=>'Contract','internship'=>'Internship'] as $val => $label): ?>
                        <option value="<?= $val ?>" <?= $type === $val ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>

                <div style="width:1px;height:22px;background:var(--border);flex-shrink:0;"></div>

                <input type="number" name="salary_min" placeholder="Min Salary (PKR)"
                       value="<?= e($salary_min) ?>"
                       style="flex:1;min-width:100px;background:transparent;border:none;color:var(--text);font-size:14px;outline:none;padding:6px 4px;">

                <div style="width:1px;height:22px;background:var(--border);flex-shrink:0;"></div>

                <select name="experience"
                        style="background:transparent;border:none;color:<?= $experience ? 'var(--text)' : 'var(--text3)' ?>;font-size:14px;outline:none;padding:6px 4px;flex-shrink:0;">
                    <option value="">Experience</option>
                    <?php foreach (['entry'=>'Entry Level','mid'=>'Mid Level','senior'=>'Senior Level','executive'=>'Executive'] as $val => $label): ?>
                        <option value="<?= $val ?>" <?= $experience === $val ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>

                <button type="submit" class="btn btn-primary" style="border-radius:10px;padding:12px 24px;flex-shrink:0;">
                    Search
                </button>
            </div>
        </form>

        <!-- Advanced filters -->
        <div style="margin-top:16px;text-align:center;">
            <details style="display:inline-block;">
                <summary style="cursor:pointer;color:var(--text2);font-size:14px;padding:8px 16px;border-radius:8px;border:1px solid var(--border);background:var(--bg);">
                    <i class="fa fa-sliders"></i> Advanced Filters
                </summary>
                <div style="position:absolute;margin-top:8px;background:var(--card);border:1px solid var(--border);border-radius:12px;padding:20px;min-width:300px;z-index:100;">
                    <form method="GET" action="index.php" style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                        <input type="hidden" name="search" value="<?= e($search) ?>">
                        <input type="hidden" name="location" value="<?= e($location) ?>">
                        <input type="hidden" name="type" value="<?= e($type) ?>">
                        <input type="hidden" name="salary_min" value="<?= e($salary_min) ?>">
                        <input type="hidden" name="experience" value="<?= e($experience) ?>">
                        
                        <div class="form-group" style="grid-column:span 2;">
                            <label class="form-label">Sort By</label>
                            <select name="sort" class="form-control">
                                <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest First</option>
                                <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Oldest First</option>
                                <option value="salary_high" <?= $sort === 'salary_high' ? 'selected' : '' ?>>Highest Salary</option>
                                <option value="salary_low" <?= $sort === 'salary_low' ? 'selected' : '' ?>>Lowest Salary</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-primary" style="grid-column:span 2;">Apply Filters</button>
                    </form>
                </div>
            </details>
        </div>

    </div>
</section>

<!-- ================================================================
     JOB LISTINGS
     ================================================================ -->
<main class="page-body" style="padding-top:0;">
<div class="container">




    <!-- Section header -->
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;flex-wrap:wrap;gap:12px;">
        <div>
            <h2 class="h3"><?= $is_search ? 'Search Results' : 'Latest Openings' ?></h2>
            <p style="color:var(--text2);font-size:14px;margin-top:3px;">
                <?= $job_count ?> job<?= $job_count !== 1 ? 's' : '' ?> found
            </p>
        </div>
        <?php if ($is_search): ?>
            <a href="index.php" style="color:var(--accent);font-size:14px;font-weight:500;">
                <i class="fa fa-times-circle"></i> Clear all filters
            </a>
        <?php endif; ?>
    </div>

    <!-- Jobs grid -->
    <?php if ($job_count > 0): ?>

        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:20px;">
        <?php while ($job = $result->fetch_assoc()):
            // Deadline
            $days      = daysUntil($job['deadline']);
            $is_closed = ($days !== null && $days < 0);
            $type_class = jobTypeClass($job['job_type']);
            $type_label = jobTypeLabel($job['job_type']);
        ?>
        <article class="card card-hover reveal" style="<?= $is_closed ? 'opacity:0.55;' : '' ?>">


            <!-- Top row -->
            <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:14px;">
                <div style="width:44px;height:44px;border-radius:10px;background:rgba(79,110,247,0.12);display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0;">
                    💼
                </div>
                <div style="display:flex;gap:6px;flex-wrap:wrap;justify-content:flex-end;">
                    <?php if (isLoggedIn() && $_currentUser['role'] === 'seeker'): ?>
                        <?php
                        $fav_stmt = $conn->prepare("SELECT id FROM saved_jobs WHERE user_id = ? AND job_id = ?");
                        $fav_stmt->bind_param('ii', $_currentUser['id'], $job['id']);
                        $fav_stmt->execute();
                        $is_favorited = $fav_stmt->get_result()->num_rows > 0;
                        ?>
                        <form method="POST" action="favorite.php" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                            <input type="hidden" name="job_id" value="<?= $job['id'] ?>">
                            <input type="hidden" name="action" value="<?= $is_favorited ? 'remove' : 'add' ?>">
                            <button type="submit" class="badge" style="background:<?= $is_favorited ? 'var(--red-bg)' : 'var(--green-bg)' ?>;color:<?= $is_favorited ? 'var(--red)' : 'var(--green)' ?>;border:none;cursor:pointer;padding:4px 8px;">
                                <i class="fa fa-heart"></i> <?= $is_favorited ? 'Saved' : 'Save' ?>
                            </button>
                        </form>
                    <?php endif; ?>
                    <span class="badge <?= $type_class ?>"><?= e($type_label) ?></span>
                    <?php if ($is_closed): ?>
                        <span class="badge badge-red">Closed</span>
                    <?php elseif ($days !== null && $days <= 5): ?>
                        <span class="badge badge-amber"><i class="fa fa-clock"></i> <?= $days ?>d left</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Title & company -->
            <h3 style="font-size:16px;font-weight:700;margin-bottom:5px;color:var(--text);">
                <?= e($job['title']) ?>
            </h3>
            <p style="font-size:13px;color:var(--text2);margin-bottom:12px;">
                <i class="fa fa-building"></i> <?= e($job['employer_name']) ?>
                &ensp;·&ensp;
                <i class="fa fa-location-dot"></i> <?= e($job['location']) ?>
            </p>

            <!-- Salary -->
            <?php if ($job['salary']): ?>
                <p style="color:var(--green);font-size:15px;font-weight:700;margin-bottom:14px;">
                    <i class="fa fa-money-bill-wave"></i> <?= e($job['salary']) ?>
                </p>
            <?php endif; ?>

            <!-- Description snippet -->
            <p style="color:var(--text2);font-size:13px;line-height:1.6;margin-bottom:18px;display:-webkit-box;-webkit-line-clamp:2;line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                <?= e(strip_tags($job['description'])) ?>
            </p>

            <!-- Footer -->
            <div style="display:flex;justify-content:space-between;align-items:center;margin-top:auto;">
                <span style="font-size:12px;color:var(--text3);">
                    <i class="fa fa-users"></i> <?= $job['app_count'] ?> applied
                    &ensp;·&ensp;
                    <?= niceDate($job['posted_at']) ?>
                </span>
                <?php if (!$is_closed): ?>
                    <a href="job-detail.php?id=<?= $job['id'] ?>" class="btn btn-primary btn-sm">
                        View <i class="fa fa-arrow-right"></i>
                    </a>
                <?php else: ?>
                    <span class="badge badge-red">Closed</span>
                <?php endif; ?>
            </div>
        </article>
        <?php endwhile; ?>
        </div>

    <?php elseif ($is_search): ?>
        <!-- Search returned nothing -->
        <div class="card reveal">
            <div class="empty-state">
                <div class="empty-icon">🔍</div>
                <h3>No results found</h3>
                <p>No jobs match "<?= e($search ?: $location) ?>". Try different keywords or broaden your search.</p>
                <a href="index.php" class="btn btn-primary">View all jobs</a>
            </div>
        </div>

    <?php else: ?>
        <!-- No jobs in DB at all -->
        <div class="card reveal">
            <div class="empty-state">
                <div class="empty-icon">📭</div>
                <h3>No jobs posted yet</h3>
                <p>
                    There are no active job listings right now.<br>
                    Create a free account and we'll notify you the moment new jobs go live!
                </p>
                <?php if (!isLoggedIn()): ?>
                    <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
                        <a href="register.php" class="btn btn-primary btn-lg">
                            <i class="fa fa-user-plus"></i> Create Free Account
                        </a>
                        <a href="login.php" class="btn btn-ghost btn-lg">Sign In</a>
                    </div>
                <?php else: ?>
                    <p style="color:var(--green);font-size:14px;font-weight:600;">
                        <i class="fa fa-check-circle"></i>
                        You're logged in as <?= e($_currentUser['email']) ?>.
                        We'll notify you when jobs are posted.
                    </p>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

</div>
</main>

<style>
@keyframes pulse { 0%,100%{opacity:1;} 50%{opacity:0.4;} }
</style>

<?php require 'footer.php'; ?>
