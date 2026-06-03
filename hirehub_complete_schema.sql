-- ============================================================
--  HireHub Job Portal — COMPLETE DATABASE SCHEMA
--  Version: 2.0 (Migration Ready)
--  Generated: 2026-05-19
--
--  FIXES vs original schema:
--    1. users: added `headline` column (seen in live DB screenshot)
--    2. users: added `company_name`, `company_location` columns
--             (seen in live DB — cols 12 & 13 in phpMyAdmin)
--    3. seeker_profiles: approval uses BOTH
--         seeker_profiles.status = 'approved'  (admin panel sets this)
--         users.is_active = 1                  (account is active)
--       candidates.php now correctly checks BOTH conditions
--    4. All ALTER TABLE guards so running on an existing DB is safe
--    5. Seed data preserved & extended
-- ============================================================

-- ============================================================
-- 0. CREATE & SELECT DATABASE
-- ============================================================
CREATE DATABASE IF NOT EXISTS job_portal
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE job_portal;

-- ============================================================
-- 1. USERS
--    Core accounts table for seekers, employers and admins.
--    FIX: added headline, company_name, company_location
--         which exist in the live DB but were missing from schema
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id               INT           AUTO_INCREMENT PRIMARY KEY,
    name             VARCHAR(255)  NOT NULL,
    email            VARCHAR(255)  NOT NULL UNIQUE,
    password         VARCHAR(255)  NOT NULL,
    role             ENUM('seeker','employer','admin') NOT NULL DEFAULT 'seeker',
    phone            VARCHAR(50)   DEFAULT NULL,
    location         VARCHAR(150)  DEFAULT NULL,
    bio              TEXT          DEFAULT NULL,
    profile_pic      VARCHAR(500)  DEFAULT NULL,
    headline         VARCHAR(255)  DEFAULT NULL,          -- e.g. "PHP Developer at TechCorp"
    company_name     VARCHAR(255)  DEFAULT NULL,          -- quick-ref for employer accounts
    company_location VARCHAR(255)  DEFAULT NULL,          -- quick-ref for employer accounts
    is_active        TINYINT(1)    NOT NULL DEFAULT 1,    -- 0 = disabled, 1 = active
    created_at       TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email  (email),
    INDEX idx_role   (role),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Safe ALTER: add missing columns if upgrading an existing DB
ALTER TABLE users
    ADD COLUMN IF NOT EXISTS headline         VARCHAR(255) DEFAULT NULL AFTER profile_pic,
    ADD COLUMN IF NOT EXISTS company_name     VARCHAR(255) DEFAULT NULL AFTER headline,
    ADD COLUMN IF NOT EXISTS company_location VARCHAR(255) DEFAULT NULL AFTER company_name;

-- ============================================================
-- 2. SEEKER PROFILES
--    Extended info for job-seeker accounts.
--    KEY: status column controls admin approval:
--         'pending'  → awaiting admin review
--         'approved' → visible on candidates page
--         'rejected' → hidden, user notified
--    Both seeker_profiles.status = 'approved' AND
--    users.is_active = 1 must be true to appear publicly.
-- ============================================================
CREATE TABLE IF NOT EXISTS seeker_profiles (
    id               INT           AUTO_INCREMENT PRIMARY KEY,
    user_id          INT           NOT NULL UNIQUE,
    job_title        VARCHAR(255)  DEFAULT NULL,
    skills           TEXT          DEFAULT NULL,          -- comma-separated or JSON array
    experience_years INT           DEFAULT 0,
    education        TEXT          DEFAULT NULL,
    resume_path      VARCHAR(500)  DEFAULT NULL,
    career_objective TEXT          DEFAULT NULL,
    availability     ENUM('full-time','part-time','remote','contract') DEFAULT 'full-time',
    expected_salary  VARCHAR(100)  DEFAULT NULL,
    experience_level ENUM('entry','mid','senior','executive') DEFAULT 'entry',
    linkedin         VARCHAR(255)  DEFAULT NULL,
    portfolio        VARCHAR(255)  DEFAULT NULL,
    status           ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    created_at       TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_status  (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 3. COMPANIES
--    Company profile linked to an employer user account.
-- ============================================================
CREATE TABLE IF NOT EXISTS companies (
    id           INT           AUTO_INCREMENT PRIMARY KEY,
    user_id      INT           NOT NULL UNIQUE,
    company_name VARCHAR(255)  NOT NULL,
    logo         VARCHAR(500)  DEFAULT NULL,
    website      VARCHAR(255)  DEFAULT NULL,
    industry     VARCHAR(150)  DEFAULT NULL,
    company_size ENUM('1-10','11-50','51-200','201-500','500+') DEFAULT '1-10',
    description  TEXT          DEFAULT NULL,
    founded_year INT           DEFAULT NULL,
    created_at   TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 4. CATEGORIES
--    Job categories (Technology, Marketing, etc.)
-- ============================================================
CREATE TABLE IF NOT EXISTS categories (
    id   INT          AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    icon VARCHAR(100) DEFAULT 'fas fa-briefcase'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 5. JOBS
--    Job postings created by employer accounts.
-- ============================================================
CREATE TABLE IF NOT EXISTS jobs (
    id               INT            AUTO_INCREMENT PRIMARY KEY,
    employer_id      INT            NOT NULL,
    category_id      INT            DEFAULT NULL,
    title            VARCHAR(255)   NOT NULL,
    location         VARCHAR(150)   NOT NULL,
    job_type         ENUM('full-time','part-time','remote','contract','internship')
                                    NOT NULL DEFAULT 'full-time',
    salary           VARCHAR(100)   DEFAULT NULL,
    salary_min       DECIMAL(10,2)  DEFAULT 0,
    salary_max       DECIMAL(10,2)  DEFAULT 0,
    description      TEXT           NOT NULL,
    requirements     TEXT           DEFAULT NULL,
    benefits         TEXT           DEFAULT NULL,
    experience_level ENUM('entry','mid','senior','executive') DEFAULT 'entry',
    deadline         DATE           DEFAULT NULL,
    is_featured      TINYINT(1)     DEFAULT 0,
    status           ENUM('active','closed','draft') DEFAULT 'active',
    views            INT            DEFAULT 0,
    posted_at        TIMESTAMP      DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employer_id)  REFERENCES users(id)       ON DELETE CASCADE,
    FOREIGN KEY (category_id)  REFERENCES categories(id)  ON DELETE SET NULL,
    INDEX idx_employer_id (employer_id),
    INDEX idx_category_id (category_id),
    INDEX idx_job_type    (job_type),
    INDEX idx_location    (location),
    INDEX idx_posted_at   (posted_at),
    INDEX idx_status      (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 6. APPLICATIONS
--    Job applications submitted by seekers.
-- ============================================================
CREATE TABLE IF NOT EXISTS applications (
    id            INT          AUTO_INCREMENT PRIMARY KEY,
    job_id        INT          NOT NULL,
    user_id       INT          NOT NULL,
    applied_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    full_name     VARCHAR(255) NOT NULL,
    fathers_name  VARCHAR(255) DEFAULT NULL,
    email         VARCHAR(255) NOT NULL,
    phone         VARCHAR(50)  NOT NULL,
    skills        TEXT         DEFAULT NULL,
    previous_work TEXT         DEFAULT NULL,
    cover_letter  TEXT         DEFAULT NULL,
    resume_path   VARCHAR(500) DEFAULT NULL,
    status        ENUM('pending','reviewed','shortlisted','rejected','hired')
                               NOT NULL DEFAULT 'pending',
    FOREIGN KEY (job_id)  REFERENCES jobs(id)  ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_application (job_id, user_id),
    INDEX idx_job_id  (job_id),
    INDEX idx_user_id (user_id),
    INDEX idx_status  (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 7. SAVED JOBS
--    Jobs bookmarked by job seekers.
-- ============================================================
CREATE TABLE IF NOT EXISTS saved_jobs (
    id       INT       AUTO_INCREMENT PRIMARY KEY,
    user_id  INT       NOT NULL,
    job_id   INT       NOT NULL,
    saved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (job_id)  REFERENCES jobs(id)  ON DELETE CASCADE,
    UNIQUE KEY unique_save (user_id, job_id),
    INDEX idx_user_id (user_id),
    INDEX idx_job_id  (job_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 8. CONTACT MESSAGES
--    Messages submitted through the public contact form.
-- ============================================================
CREATE TABLE IF NOT EXISTS contact_messages (
    id      INT          AUTO_INCREMENT PRIMARY KEY,
    name    VARCHAR(255) NOT NULL,
    email   VARCHAR(255) NOT NULL,
    subject VARCHAR(255) DEFAULT NULL,
    message TEXT         NOT NULL,
    is_read TINYINT(1)   NOT NULL DEFAULT 0,
    sent_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_is_read (is_read),
    INDEX idx_sent_at (sent_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 9. JOB ALERTS
--    Saved search alerts for seekers.
-- ============================================================
CREATE TABLE IF NOT EXISTS job_alerts (
    id         INT          AUTO_INCREMENT PRIMARY KEY,
    user_id    INT          NOT NULL,
    keywords   VARCHAR(255) NOT NULL,
    location   VARCHAR(150) DEFAULT NULL,
    job_type   ENUM('full-time','part-time','remote','contract','internship') DEFAULT NULL,
    is_active  TINYINT(1)   NOT NULL DEFAULT 1,
    created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id  (user_id),
    INDEX idx_keywords (keywords),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 10. FAVORITES
--     Employers can save/favourite candidate profiles.
-- ============================================================
CREATE TABLE IF NOT EXISTS favorites (
    id          INT       AUTO_INCREMENT PRIMARY KEY,
    employer_id INT       NOT NULL,
    seeker_id   INT       NOT NULL,
    saved_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (seeker_id)   REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_favorite (employer_id, seeker_id),
    INDEX idx_employer_id (employer_id),
    INDEX idx_seeker_id   (seeker_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 11. PROFILE VIEWS
--     Tracks who viewed which seeker profile and when.
-- ============================================================
CREATE TABLE IF NOT EXISTS profile_views (
    id        INT       AUTO_INCREMENT PRIMARY KEY,
    seeker_id INT       NOT NULL,
    viewer_id INT       DEFAULT NULL,           -- NULL = guest/anonymous
    viewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (seeker_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (viewer_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_seeker_id (seeker_id),
    INDEX idx_viewer_id (viewer_id),
    INDEX idx_viewed_at (viewed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- ============================================================
--  SEED DATA
-- ============================================================
-- ============================================================

-- ============================================================
-- SEED: 10 Job Categories
-- ============================================================
INSERT IGNORE INTO categories (name, icon) VALUES
    ('Technology',      'fas fa-laptop-code'),
    ('Marketing',       'fas fa-bullhorn'),
    ('Finance',         'fas fa-chart-line'),
    ('Healthcare',      'fas fa-heartbeat'),
    ('Education',       'fas fa-graduation-cap'),
    ('Engineering',     'fas fa-cogs'),
    ('Design',          'fas fa-paint-brush'),
    ('Sales',           'fas fa-handshake'),
    ('HR & Admin',      'fas fa-users'),
    ('Legal',           'fas fa-balance-scale');

-- ============================================================
-- SEED: Admin User
--   email:    admin@hirehub.com
--   password: admin123
-- ============================================================
INSERT IGNORE INTO users
    (name, email, password, role, is_active)
VALUES
    (
        'Admin',
        'admin@hirehub.com',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        'admin',
        1
    );

-- ============================================================
-- SEED: Demo Employer
--   email:    employer@demo.com
--   password: admin123
-- ============================================================
INSERT IGNORE INTO users
    (name, email, password, role, location, headline, company_name, company_location, is_active)
VALUES
    (
        'TechCorp HR',
        'employer@demo.com',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        'employer',
        'Lahore',
        'HR Manager at TechCorp Solutions',
        'TechCorp Solutions',
        'Lahore, Pakistan',
        1
    );

INSERT IGNORE INTO companies
    (user_id, company_name, industry, company_size, description, founded_year)
SELECT
    id,
    'TechCorp Solutions',
    'Technology',
    '51-200',
    'Leading software house in Pakistan building world-class digital products.',
    2015
FROM users
WHERE email = 'employer@demo.com'
LIMIT 1;

-- ============================================================
-- SEED: Demo Seeker #1 — Ali Khan (approved)
--   email:    seeker@demo.com
--   password: admin123
-- ============================================================
INSERT IGNORE INTO users
    (name, email, password, role, location, headline, is_active)
VALUES
    (
        'Ali Khan',
        'seeker@demo.com',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        'seeker',
        'Lahore',
        'PHP Developer | Laravel | MySQL',
        1
    );

INSERT IGNORE INTO seeker_profiles
    (user_id, job_title, skills, experience_years, education,
     career_objective, availability, expected_salary, experience_level, status)
SELECT
    id,
    'PHP Developer',
    'PHP, Laravel, JavaScript, MySQL, REST APIs, Git',
    2,
    'Bachelor of Computer Science — University of Punjab (2022)',
    'Looking for a challenging role in web development where I can grow and contribute.',
    'full-time',
    'PKR 60,000 – 80,000',
    'mid',
    'approved'          -- ← APPROVED: will appear on candidates page
FROM users
WHERE email = 'seeker@demo.com'
LIMIT 1;

-- ============================================================
-- SEED: Demo Seeker #2 — Sara Ahmed (approved)
--   email:    sara@demo.com
--   password: admin123
-- ============================================================
INSERT IGNORE INTO users
    (name, email, password, role, location, headline, is_active)
VALUES
    (
        'Sara Ahmed',
        'sara@demo.com',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        'seeker',
        'Karachi',
        'UI/UX Designer | Figma | Adobe XD',
        1
    );

INSERT IGNORE INTO seeker_profiles
    (user_id, job_title, skills, experience_years, education,
     career_objective, availability, expected_salary, experience_level, status)
SELECT
    id,
    'UI/UX Designer',
    'Figma, Adobe XD, Illustrator, Photoshop, Prototyping, User Research',
    3,
    'Bachelor of Design — NCA Lahore (2021)',
    'Passionate about creating beautiful and intuitive digital experiences.',
    'full-time',
    'PKR 70,000 – 100,000',
    'mid',
    'approved'
FROM users
WHERE email = 'sara@demo.com'
LIMIT 1;

-- ============================================================
-- SEED: Demo Seeker #3 — Usman Raza (approved)
--   email:    usman@demo.com
--   password: admin123
-- ============================================================
INSERT IGNORE INTO users
    (name, email, password, role, location, headline, is_active)
VALUES
    (
        'Usman Raza',
        'usman@demo.com',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        'seeker',
        'Islamabad',
        'React Developer | TypeScript | Node.js',
        1
    );

INSERT IGNORE INTO seeker_profiles
    (user_id, job_title, skills, experience_years, education,
     career_objective, availability, expected_salary, experience_level, status)
SELECT
    id,
    'Frontend Developer',
    'React.js, TypeScript, Node.js, Tailwind CSS, REST APIs, Git, Firebase',
    4,
    'Bachelor of Software Engineering — FAST NUCES (2020)',
    'Seeking a senior frontend role to build scalable modern web applications.',
    'remote',
    'PKR 100,000 – 140,000',
    'senior',
    'approved'
FROM users
WHERE email = 'usman@demo.com'
LIMIT 1;

-- ============================================================
-- SEED: Demo Seeker #4 — Ayesha Malik (pending — NOT visible)
--   email:    ayesha@demo.com
--   password: admin123
-- ============================================================
INSERT IGNORE INTO users
    (name, email, password, role, location, headline, is_active)
VALUES
    (
        'Ayesha Malik',
        'ayesha@demo.com',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        'seeker',
        'Lahore',
        'Digital Marketing Specialist',
        1
    );

INSERT IGNORE INTO seeker_profiles
    (user_id, job_title, skills, experience_years, education,
     career_objective, availability, expected_salary, experience_level, status)
SELECT
    id,
    'Digital Marketing Specialist',
    'SEO, Google Ads, Facebook Ads, Content Writing, Canva, Analytics',
    2,
    'MBA Marketing — IBA Karachi (2023)',
    'Looking to join a growth-focused company to drive digital marketing strategy.',
    'full-time',
    'PKR 55,000 – 75,000',
    'mid',
    'pending'           -- ← PENDING: will NOT appear until admin approves
FROM users
WHERE email = 'ayesha@demo.com'
LIMIT 1;

-- ============================================================
-- SEED: 6 Demo Jobs
-- ============================================================
INSERT IGNORE INTO jobs
    (employer_id, category_id, title, location, job_type,
     salary, salary_min, salary_max, description, requirements,
     benefits, experience_level, deadline, is_featured, status)
SELECT
    u.id, 1,
    'Senior PHP Developer', 'Lahore', 'full-time',
    'PKR 80,000 – 120,000', 80000, 120000,
    'We are looking for an experienced PHP Developer to build and maintain scalable web applications using Laravel.',
    '3+ years PHP/Laravel, MySQL proficiency, REST API design, Version control with Git',
    'Competitive salary, Health insurance, Remote work options, Learning budget',
    'senior', DATE_ADD(NOW(), INTERVAL 30 DAY), 1, 'active'
FROM users u WHERE u.email = 'employer@demo.com' LIMIT 1;

INSERT IGNORE INTO jobs
    (employer_id, category_id, title, location, job_type,
     salary, salary_min, salary_max, description, requirements,
     benefits, experience_level, deadline, is_featured, status)
SELECT
    u.id, 7,
    'UI/UX Designer', 'Karachi', 'full-time',
    'PKR 50,000 – 80,000', 50000, 80000,
    'Creative UI/UX Designer needed to craft beautiful digital experiences for our suite of products.',
    '2+ years UI/UX, Figma or Adobe XD, Portfolio required, Basic HTML/CSS is a plus',
    'Flexible hours, Creative freedom, MacBook provided, Annual bonus',
    'mid', DATE_ADD(NOW(), INTERVAL 25 DAY), 1, 'active'
FROM users u WHERE u.email = 'employer@demo.com' LIMIT 1;

INSERT IGNORE INTO jobs
    (employer_id, category_id, title, location, job_type,
     salary, salary_min, salary_max, description, requirements,
     benefits, experience_level, deadline, is_featured, status)
SELECT
    u.id, 1,
    'React Frontend Developer', 'Remote', 'remote',
    'PKR 60,000 – 90,000', 60000, 90000,
    'Join our frontend team to build modern, responsive interfaces using React.js and TypeScript.',
    '2+ years React.js, TypeScript, REST API integration, Git proficiency',
    'Remote-first, Flexible hours, Learning allowance, Team retreats',
    'mid', DATE_ADD(NOW(), INTERVAL 20 DAY), 0, 'active'
FROM users u WHERE u.email = 'employer@demo.com' LIMIT 1;

INSERT IGNORE INTO jobs
    (employer_id, category_id, title, location, job_type,
     salary, salary_min, salary_max, description, requirements,
     benefits, experience_level, deadline, is_featured, status)
SELECT
    u.id, 2,
    'Digital Marketing Manager', 'Islamabad', 'full-time',
    'PKR 70,000 – 100,000', 70000, 100000,
    'Drive our digital marketing strategy across SEO, SEM, social media, and content marketing.',
    '3+ years digital marketing, Google Ads certified, SEO/SEM proficiency, Content strategy',
    'Performance bonus, Flexible schedule, Training budget',
    'senior', DATE_ADD(NOW(), INTERVAL 15 DAY), 0, 'active'
FROM users u WHERE u.email = 'employer@demo.com' LIMIT 1;

INSERT IGNORE INTO jobs
    (employer_id, category_id, title, location, job_type,
     salary, salary_min, salary_max, description, requirements,
     benefits, experience_level, deadline, is_featured, status)
SELECT
    u.id, 1,
    'Junior Web Developer', 'Lahore', 'internship',
    'PKR 25,000 – 35,000', 25000, 35000,
    'Great opportunity for freshers to kick-start their career in web development with expert mentors.',
    'Basic HTML, CSS, JavaScript, Willingness to learn, Good communication',
    'Mentorship program, Training provided, Growth opportunities',
    'entry', DATE_ADD(NOW(), INTERVAL 45 DAY), 0, 'active'
FROM users u WHERE u.email = 'employer@demo.com' LIMIT 1;

INSERT IGNORE INTO jobs
    (employer_id, category_id, title, location, job_type,
     salary, salary_min, salary_max, description, requirements,
     benefits, experience_level, deadline, is_featured, status)
SELECT
    u.id, 3,
    'Financial Analyst', 'Karachi', 'full-time',
    'PKR 60,000 – 90,000', 60000, 90000,
    'Analyze financial data, prepare reports and support strategic decision-making for our leadership team.',
    'Finance/Accounting degree, Advanced Excel, CFA preferred, 2+ years experience',
    'Competitive pay, Health benefits, Career growth',
    'mid', DATE_ADD(NOW(), INTERVAL 30 DAY), 0, 'active'
FROM users u WHERE u.email = 'employer@demo.com' LIMIT 1;


-- ============================================================
-- ============================================================
--  REFERENCE: CORRECT candidates.php QUERY
--  Use this exact SQL in candidates.php to show only profiles
--  that have been approved by admin through the admin panel.
--
--  BOTH conditions required:
--    1. seeker_profiles.status = 'approved'  (admin clicked Approve)
--    2. users.is_active = 1                  (account not disabled)
-- ============================================================
-- ============================================================

/*
SELECT
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
    sp.resume_path,
    sp.education,
    sp.career_objective
FROM users u
INNER JOIN seeker_profiles sp ON sp.user_id = u.id
WHERE u.role     = 'seeker'
  AND u.is_active = 1
  AND sp.status  = 'approved'
ORDER BY u.id DESC;
*/


-- ============================================================
-- ============================================================
--  DEMO ACCOUNT SUMMARY
-- ============================================================
-- ============================================================
/*
  Role      | Email                | Password  | Visible on /candidates?
  ----------+----------------------+-----------+------------------------
  Admin     | admin@hirehub.com    | admin123  | n/a
  Employer  | employer@demo.com    | admin123  | n/a
  Seeker    | seeker@demo.com      | admin123  | YES (approved)
  Seeker    | sara@demo.com        | admin123  | YES (approved)
  Seeker    | usman@demo.com       | admin123  | YES (approved)
  Seeker    | ayesha@demo.com      | admin123  | NO  (pending)
*/


-- ============================================================
-- ============================================================
--  TABLE RELATIONSHIPS
-- ============================================================
-- ============================================================
/*
  users (id)
  ├── seeker_profiles  (user_id)      One-to-One
  ├── companies        (user_id)      One-to-One
  ├── jobs             (employer_id)  One-to-Many
  ├── applications     (user_id)      One-to-Many
  ├── saved_jobs       (user_id)      One-to-Many
  ├── job_alerts       (user_id)      One-to-Many
  ├── favorites        (employer_id)  One-to-Many
  ├── favorites        (seeker_id)    One-to-Many
  └── profile_views    (seeker_id, viewer_id)  One-to-Many

  jobs (id)
  ├── applications  (job_id)      One-to-Many
  ├── saved_jobs    (job_id)      One-to-Many
  └── categories    (category_id) Many-to-One
*/

-- ============================================================
-- END OF SCHEMA
-- ============================================================
