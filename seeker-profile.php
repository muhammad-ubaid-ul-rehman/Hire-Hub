<?php
require_once 'config.php';
$user = requireRole('seeker');
$user_id = (int)$user['id'];

$conn->query("CREATE TABLE IF NOT EXISTS seeker_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    job_title VARCHAR(255),
    skills TEXT,
    experience_years INT,
    experience_level VARCHAR(50),
    education TEXT,
    certifications TEXT,
    languages VARCHAR(255),
    career_objective TEXT,
    availability VARCHAR(50),
    expected_salary VARCHAR(100),
    linkedin VARCHAR(255),
    portfolio VARCHAR(255),
    resume VARCHAR(255),
    status ENUM('pending','approved','rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB");

$error = '';
$success = '';

$profile = $conn->prepare("SELECT * FROM seeker_profiles WHERE user_id = ?");
$profile->bind_param('i', $user_id);
$profile->execute();
$seeker = $profile->get_result()->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $job_title = trim($_POST['job_title'] ?? '');
    $skills = trim($_POST['skills'] ?? '');
    $experience = (int)($_POST['experience'] ?? 0);
    $experience_level = $_POST['experience_level'] ?? 'entry';
    $education = trim($_POST['education'] ?? '');
    $certifications = trim($_POST['certifications'] ?? '');
    $languages = trim($_POST['languages'] ?? '');
    $career_objective = trim($_POST['career_objective'] ?? '');
    $availability = $_POST['availability'] ?? 'full-time';
    $expected_salary = trim($_POST['expected_salary'] ?? '');
    $linkedin = trim($_POST['linkedin'] ?? '');
    $portfolio = trim($_POST['portfolio'] ?? '');

    if ($job_title === '') {
        $error = 'Job title is required.';
    } else {
        $stmt = $conn->prepare(
            "INSERT INTO seeker_profiles (user_id, job_title, skills, experience_years, experience_level, education, certifications, languages, career_objective, availability, expected_salary, linkedin, portfolio)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                job_title=?, skills=?, experience_years=?, experience_level=?,
                education=?, certifications=?, languages=?, career_objective=?,
                availability=?, expected_salary=?, linkedin=?, portfolio=?"
        );
        $stmt->bind_param(
            'isissssssssssissssssss',
            $user_id, $job_title, $skills, $experience, $experience_level,
            $education, $certifications, $languages, $career_objective,
            $availability, $expected_salary, $linkedin, $portfolio,
            $job_title, $skills, $experience, $experience_level,
            $education, $certifications, $languages, $career_objective,
            $availability, $expected_salary, $linkedin, $portfolio
        );
        if ($stmt->execute()) {
            $success = 'Profile updated successfully!';
            $profile = $conn->prepare("SELECT * FROM seeker_profiles WHERE user_id = ?");
            $profile->bind_param('i', $user_id);
            $profile->execute();
            $seeker = $profile->get_result()->fetch_assoc();
        } else {
            $error = 'Update failed. Please try again.';
        }
    }
}

require 'header.php';
?>

<main class="page-body">
<div class="container" style="max-width:900px;">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:16px;margin-bottom:28px;">
        <div>
            <h1 class="h3">Professional Profile</h1>
            <p style="color:var(--text2);font-size:14px;margin-top:4px;">
                Build a complete profile to attract employers and get discovered in talent searches.
            </p>
        </div>
        <a href="candidate.php?id=<?= $user_id ?>" class="btn btn-ghost">
            <i class="fa fa-eye"></i> View Public Profile
        </a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><i class="fa fa-circle-xmark"></i> <?= e($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><i class="fa fa-circle-check"></i> <?= e($success) ?></div>
    <?php endif; ?>

    <form method="POST" action="seeker-profile.php">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

        <!-- Personal Info (from users table) -->
        <div class="card" style="margin-bottom:24px;">
            <h3 class="h3" style="margin-bottom:18px;">Personal Information</h3>
            <div style="display:grid;grid-template-columns:2fr 1fr 1fr;gap:16px;">
                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input type="text" class="form-control" value="<?= e($user['name']) ?>" disabled>
                </div>
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" value="<?= e($user['email']) ?>" disabled>
                </div>
                <div class="form-group">
                    <label class="form-label">Phone</label>
                    <input type="tel" class="form-control" value="<?= e($user['phone'] ?? '') ?>" disabled>
                </div>
            </div>
            <div style="display:grid;grid-template-columns:2fr 1fr;gap:16px;">
                <div class="form-group">
                    <label class="form-label">Location</label>
                    <input type="text" class="form-control" value="<?= e($user['location'] ?? '') ?>" disabled>
                </div>
                <div class="form-group">
                    <label class="form-label">Professional Headline</label>
                    <input type="text" class="form-control" value="<?= e($user['headline'] ?? '') ?>" disabled style="opacity:0.66;">
                </div>
            </div>
            <p style="color:var(--text2);font-size:13px;margin-top:14px;">
                <i class="fa fa-info-circle" style="color:var(--text3);"></i> Update personal details in your <a href="profile.php" style="color:var(--accent);">account settings</a>
            </p>
        </div>

        <!-- Professional Info -->
        <div class="card" style="margin-bottom:24px;">
            <h3 class="h3" style="margin-bottom:18px;">Professional Information</h3>
            
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div class="form-group">
                    <label class="form-label">Desired Job Title <span style="color:var(--red);">*</span></label>
                    <input type="text" name="job_title" class="form-control" placeholder="e.g. Senior PHP Developer" 
                           value="<?= e($seeker['job_title'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Experience Level</label>
                    <select name="experience_level" class="form-control">
                        <?php foreach (['entry'=>'Entry Level','mid'=>'Mid Level','senior'=>'Senior','executive'=>'Executive'] as $val => $label): ?>
                            <option value="<?= $val ?>" <?= ($seeker['experience_level'] ?? 'entry') === $val ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div class="form-group">
                    <label class="form-label">Years of Experience</label>
                    <input type="number" name="experience" class="form-control" min="0" max="70" 
                           placeholder="0" value="<?= e($seeker['experience_years'] ?? 0) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Availability</label>
                    <select name="availability" class="form-control">
                        <?php foreach (['full-time'=>'Full-Time','part-time'=>'Part-Time','remote'=>'Remote','contract'=>'Contract'] as $val => $label): ?>
                            <option value="<?= $val ?>" <?= ($seeker['availability'] ?? 'full-time') === $val ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div class="form-group">
                    <label class="form-label">Expected Salary</label>
                    <input type="text" name="expected_salary" class="form-control" placeholder="e.g. 100,000 - 150,000 PKR p.a." 
                           value="<?= e($seeker['expected_salary'] ?? '') ?>">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Professional Skills <small style="font-weight:400;color:var(--text2);">(comma separated)</small></label>
                <textarea name="skills" class="form-control" rows="3" placeholder="PHP, Laravel, MySQL, REST API, Docker, Git..."><?= e($seeker['skills'] ?? '') ?></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Career Objective / Summary</label>
                <textarea name="career_objective" class="form-control" rows="4" 
                          placeholder="Tell employers about your career goals and what you're looking for..."><?= e($seeker['career_objective'] ?? '') ?></textarea>
            </div>
        </div>

        <!-- Education & Certifications -->
        <div class="card" style="margin-bottom:24px;">
            <h3 class="h3" style="margin-bottom:18px;">Education & Qualifications</h3>

            <div class="form-group">
                <label class="form-label">Education History</label>
                <textarea name="education" class="form-control" rows="4" 
                          placeholder="Degree Name from University (Year)&#10;Example: BS Computer Science from FAST-NUCES (2021)"><?= e($seeker['education'] ?? '') ?></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Certifications</label>
                <textarea name="certifications" class="form-control" rows="3" 
                          placeholder="Certification Name from Organization&#10;Example: AWS Solutions Architect from Amazon (2023)"><?= e($seeker['certifications'] ?? '') ?></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Languages</label>
                <input type="text" name="languages" class="form-control" placeholder="English (Fluent), Urdu (Native), Arabic (Basic)" 
                       value="<?= e($seeker['languages'] ?? '') ?>">
            </div>
        </div>

        <!-- Links & Attachments -->
        <div class="card" style="margin-bottom:24px;">
            <h3 class="h3" style="margin-bottom:18px;">Online Presence</h3>

            <div class="form-group">
                <label class="form-label">LinkedIn Profile</label>
                <input type="url" name="linkedin" class="form-control" placeholder="https://linkedin.com/in/yourname" 
                       value="<?= e($seeker['linkedin'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label class="form-label">Portfolio Website</label>
                <input type="url" name="portfolio" class="form-control" placeholder="https://yourportfolio.com" 
                       value="<?= e($seeker['portfolio'] ?? '') ?>">
            </div>

            <p style="color:var(--text2);font-size:13px;">
                <i class="fa fa-info-circle" style="color:var(--text3);"></i> You can upload your resume in the <a href="profile.php" style="color:var(--accent);">account settings</a>
            </p>
        </div>

        <!-- Submit -->
        <div style="display:flex;gap:12px;flex-wrap:wrap;">
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="fa fa-save"></i> Save Professional Profile
            </button>
            <a href="candidate.php?id=<?= $user_id ?>" class="btn btn-ghost btn-lg">View Public Profile</a>
        </div>
    </form>

</div>
</main>

<?php require 'footer.php'; ?>
