<?php
require_once 'config.php';
$queries = [
    "ALTER TABLE seeker_profiles
        ADD COLUMN job_title VARCHAR(255) DEFAULT NULL,
        ADD COLUMN date_of_birth DATE DEFAULT NULL,
        ADD COLUMN gender ENUM('male','female','other') DEFAULT NULL,
        ADD COLUMN certifications TEXT DEFAULT NULL,
        ADD COLUMN languages VARCHAR(255) DEFAULT NULL,
        ADD COLUMN career_objective TEXT DEFAULT NULL,
        ADD COLUMN availability ENUM('full-time','part-time','remote','contract') DEFAULT 'full-time',
        ADD COLUMN expected_salary VARCHAR(100) DEFAULT NULL,
        ADD COLUMN experience_level ENUM('entry','mid','senior','executive') DEFAULT 'entry',
        ADD COLUMN status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending';",
    "CREATE TABLE IF NOT EXISTS favorites (
        id INT(11) NOT NULL AUTO_INCREMENT,
        employer_id INT(11) NOT NULL,
        seeker_id INT(11) NOT NULL,
        saved_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
        PRIMARY KEY (id),
        UNIQUE KEY unique_save (employer_id, seeker_id),
        KEY fk_fav_employer (employer_id),
        KEY fk_fav_seeker (seeker_id),
        CONSTRAINT fk_fav_employer FOREIGN KEY (employer_id) REFERENCES users(id) ON DELETE CASCADE,
        CONSTRAINT fk_fav_seeker FOREIGN KEY (seeker_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
    "CREATE TABLE IF NOT EXISTS profile_views (
        id INT(11) NOT NULL AUTO_INCREMENT,
        seeker_id INT(11) NOT NULL,
        viewer_id INT(11) DEFAULT NULL,
        viewed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
        PRIMARY KEY (id),
        KEY fk_view_seeker (seeker_id),
        KEY fk_view_viewer (viewer_id),
        CONSTRAINT fk_view_seeker FOREIGN KEY (seeker_id) REFERENCES users(id) ON DELETE CASCADE,
        CONSTRAINT fk_view_viewer FOREIGN KEY (viewer_id) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;"
];
foreach ($queries as $query) {
    if (!$conn->query($query)) {
        echo "ERROR: " . $conn->error . "\n";
    } else {
        echo "OK: " . strtok(trim($query), '\n') . "\n";
    }
}
