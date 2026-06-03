SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `role` enum('seeker','employer','admin') DEFAULT 'seeker',
  `profile_pic` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `company_name` varchar(255) DEFAULT NULL,
  `company_location` varchar(255) DEFAULT NULL,
  `headline` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` (`name`, `email`, `password`, `role`, `phone`, `location`, `bio`, `is_active`, `company_name`, `company_location`, `headline`) VALUES
('hassan', 'this@this.com', '$2y$10$h0LdhRjtKYQmfYuw7is.J.x3GYcQplKl8xB1ONnHO4X42deGn7N0a', 'seeker', NULL, 'lahore', 'whatever you need', 1, NULL, NULL, 'web developer'),
('Hassan', 'employer@123.com', '$2y$10$2UxWk1IlOT9.0MJLdFRhX.wN9EqQwhVcSWBYKBkl8j2DFMf1RjO7W', 'employer', NULL, NULL, NULL, 1, NULL, NULL, NULL),
('applicant', 'applicant@gmail.com', '$2y$10$.EWqjKHwSHLao9zclybWOefn00zDxkMpUmPld0MGohyNhXYzMsIjS', 'seeker', NULL, NULL, NULL, 1, NULL, NULL, NULL),
('Admin User', 'ubaid@hirehub.com', '$2y$10$FXlQJy9oQJdXSJ.xGnEJ9e0FZH8lW.EBp6h8yXqaOZlnIzJ0KFTfm', 'admin', NULL, NULL, NULL, 1, NULL, NULL, 'System Administrator');

CREATE TABLE `categories` (
  `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` varchar(100) NOT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `job_count` int DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `categories` (`name`, `icon`, `job_count`) VALUES
('Technology', 'fas fa-laptop-code', 0),
('Marketing', 'fas fa-bullhorn', 0),
('Finance', 'fas fa-chart-line', 0),
('Healthcare', 'fas fa-heartbeat', 0),
('Education', 'fas fa-graduation-cap', 0),
('Engineering', 'fas fa-cogs', 0),
('Design', 'fas fa-paint-brush', 0),
('Sales', 'fas fa-handshake', 0),
('HR & Admin', 'fas fa-users', 0),
('Legal', 'fas fa-balance-scale', 0);

CREATE TABLE `applications` (
  `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `job_id` int NOT NULL,
  `user_id` int NOT NULL,
  `applied_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `full_name` varchar(255) NOT NULL,
  `fathers_name` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `skills` text DEFAULT NULL,
  `previous_work` text DEFAULT NULL,
  `resume_path` varchar(500) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `companies` (
  `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_id` int NOT NULL,
  `company_name` varchar(200) NOT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `industry` varchar(100) DEFAULT NULL,
  `company_size` enum('1-10','11-50','51-200','201-500','500+') DEFAULT NULL,
  `description` text DEFAULT NULL,
  `founded_year` year(4) DEFAULT NULL,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `contact_messages` (
  `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `subject` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `sent_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `contact_messages` (`name`, `email`, `subject`, `message`, `is_read`, `sent_at`) VALUES
('Muhammad Hassan Sharafat', 'hassansharafat66@gmail.com', 'hello', 'there is a problem with me can you help me', 1, '2026-05-11 16:07:37'),
('Muhammad Hassan Sharafat', 'hassansharafat66@gmail.com', 'hello', 'can you help me', 1, '2026-05-11 19:27:07');

CREATE TABLE `favorites` (
  `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `employer_id` int NOT NULL,
  `seeker_id` int NOT NULL,
  `saved_at` timestamp NOT NULL DEFAULT current_timestamp(),
  UNIQUE KEY `unique_save` (`employer_id`,`seeker_id`),
  FOREIGN KEY (`employer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`seeker_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `jobs` (
  `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `employer_id` int NOT NULL,
  `category_id` int DEFAULT NULL,
  `title` varchar(200) NOT NULL,
  `description` text NOT NULL,
  `requirements` text DEFAULT NULL,
  `benefits` text DEFAULT NULL,
  `job_type` enum('full-time','part-time','remote','contract','internship') DEFAULT 'full-time',
  `experience_level` enum('entry','mid','senior','executive') DEFAULT 'entry',
  `salary_min` decimal(10,2) DEFAULT NULL,
  `salary_max` decimal(10,2) DEFAULT NULL,
  `salary_currency` varchar(10) DEFAULT 'PKR',
  `location` varchar(150) DEFAULT NULL,
  `salary` varchar(255) DEFAULT NULL,
  `deadline` date DEFAULT NULL,
  `is_featured` tinyint(1) DEFAULT 0,
  `status` enum('active','closed','draft') DEFAULT 'active',
  `views` int DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `posted_at` date DEFAULT NULL,
  FOREIGN KEY (`employer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `job_alerts` (
  `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_id` int NOT NULL,
  `keywords` varchar(255) NOT NULL,
  `location` varchar(150),
  `job_type` enum('full-time','part-time','remote','contract','internship'),
  `frequency` enum('daily','weekly','instant') NOT NULL DEFAULT 'daily',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  UNIQUE KEY `unique_user` (`user_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `profile_views` (
  `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `seeker_id` int NOT NULL,
  `viewer_id` int DEFAULT NULL,
  `viewed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  FOREIGN KEY (`seeker_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`viewer_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `saved_jobs` (
  `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_id` int NOT NULL,
  `job_id` int NOT NULL,
  `saved_at` timestamp NOT NULL DEFAULT current_timestamp(),
  UNIQUE KEY `unique_save` (`user_id`,`job_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `seeker_profiles` (
  `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_id` int NOT NULL UNIQUE,
  `resume` varchar(255) DEFAULT NULL,
  `skills` text DEFAULT NULL,
  `education` text DEFAULT NULL,
  `experience` text DEFAULT NULL,
  `linkedin` varchar(255) DEFAULT NULL,
  `portfolio` varchar(255) DEFAULT NULL,
  `experience_years` int DEFAULT 0,
  `job_title` varchar(255) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `certifications` text DEFAULT NULL,
  `languages` varchar(255) DEFAULT NULL,
  `career_objective` text DEFAULT NULL,
  `availability` enum('full-time','part-time','remote','contract') DEFAULT 'full-time',
  `expected_salary` varchar(100) DEFAULT NULL,
  `experience_level` enum('entry','mid','senior','executive') DEFAULT 'entry',
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `seeker_profiles` (`user_id`, `resume`, `skills`, `education`, `experience`, `linkedin`, `portfolio`, `experience_years`, `job_title`, `gender`, `certifications`, `languages`, `career_objective`, `availability`, `expected_salary`, `experience_level`, `status`) VALUES
(1, NULL, 'PHP, JavaScript, MySQL', 'BS Computer Science', NULL, 'https://www.linkedin.com/', '0', 3, 'Web Developer', NULL, 'None', 'Urdu, English', 'Seeking web development role', 'full-time', '100000', 'mid', 'approved');

COMMIT;


/* =====================================================
   USERS (10 Seekers + 5 Employers)
   Password Pattern: Name@123
   ===================================================== */

INSERT INTO users (name,email,password,role,location,headline)
VALUES
('Ali Raza','ali.raza@gmail.com','Ali@123','seeker','Lahore','Flutter Developer'),
('Ahmed Khan','ahmed.khan@gmail.com','Ahmed@123','seeker','Karachi','Frontend Developer'),
('Hassan Ali','hassan.ali@gmail.com','Hassan@123','seeker','Islamabad','Backend Developer'),
('Usman Tariq','usman.tariq@gmail.com','Usman@123','seeker','Faisalabad','Software Engineer'),
('Bilal Ahmed','bilal.ahmed@gmail.com','Bilal@123','seeker','Multan','UI UX Designer'),
('Zain Malik','zain.malik@gmail.com','Zain@123','seeker','Sahiwal','Mobile App Developer'),
('Hamza Shah','hamza.shah@gmail.com','Hamza@123','seeker','Rawalpindi','Laravel Developer'),
('Awais Iqbal','awais.iqbal@gmail.com','Awais@123','seeker','Bahawalpur','Data Analyst'),
('Tayyab Hussain','tayyab.h@gmail.com','Tayyab@123','seeker','Gujranwala','DevOps Engineer'),
('Saad Sheikh','saad.sheikh@gmail.com','Saad@123','seeker','Peshawar','Web Developer'),

('Hassan Technologies','hr@hassantech.com','Hassan@123','employer','Lahore',NULL),
('PakSoft Solutions','jobs@paksoft.com','Paksoft@123','employer','Karachi',NULL),
('NextGen Systems','careers@nextgen.com','Nextgen@123','employer','Islamabad',NULL),
('TechVista Pvt Ltd','hr@techvista.com','Techvista@123','employer','Faisalabad',NULL),
('Digital Innovators','jobs@digitalinnovators.com','Digital@123','employer','Lahore',NULL);


/* =====================================================
   COMPANIES
   Employer IDs assumed 14-18
   ===================================================== */

INSERT INTO companies
(user_id,company_name,website,industry,company_size,description,founded_year)
VALUES
(14,'Hassan Technologies','https://hassantech.pk','Technology','51-200','Software House',2018),
(15,'PakSoft Solutions','https://paksoft.pk','Technology','11-50','IT Services',2019),
(16,'NextGen Systems','https://nextgen.pk','Technology','201-500','Enterprise Solutions',2015),
(17,'TechVista Pvt Ltd','https://techvista.pk','Technology','51-200','Software Development',2020),
(18,'Digital Innovators','https://digital.pk','Technology','11-50','Digital Agency',2021);


/* =====================================================
   SEEKER PROFILES
   User IDs assumed 4-13
   ===================================================== */

INSERT INTO seeker_profiles
(user_id,skills,education,experience_years,job_title,languages,expected_salary,status)
VALUES
(4,'Flutter,Dart,Firebase','BS Software Engineering',2,'Flutter Developer','Urdu,English','120000','approved'),
(5,'HTML,CSS,React','BS Computer Science',1,'Frontend Developer','Urdu,English','90000','approved'),
(6,'PHP,Laravel,MySQL','BS IT',3,'Backend Developer','Urdu,English','140000','approved'),
(7,'Java,Spring Boot','BS Software Engineering',2,'Software Engineer','Urdu,English','130000','approved'),
(8,'Figma,Adobe XD','BS Design',2,'UI UX Designer','Urdu,English','100000','approved'),
(9,'Flutter,Android','BS CS',1,'Mobile Developer','Urdu,English','110000','approved'),
(10,'Laravel,VueJS','BS IT',3,'Laravel Developer','Urdu,English','150000','approved'),
(11,'Python,Power BI','BS Data Science',2,'Data Analyst','Urdu,English','125000','approved'),
(12,'Docker,AWS,Linux','BS CS',3,'DevOps Engineer','Urdu,English','180000','approved'),
(13,'PHP,JavaScript','BS SE',2,'Web Developer','Urdu,English','115000','approved');


/* =====================================================
   CONTACT MESSAGES
   ===================================================== */

INSERT INTO contact_messages
(name,email,subject,message)
VALUES
('Ali Raza','ali.raza@gmail.com','Need Help','Unable to upload resume'),
('Ahmed Khan','ahmed.khan@gmail.com','Bug Report','Job page not loading'),
('Hamza Shah','hamza.shah@gmail.com','Account Issue','Cannot update profile'),
('Saad Sheikh','saad.sheikh@gmail.com','Feedback','Excellent platform');


/* =====================================================
   JOBS
   Employer IDs = 14-18
   Category IDs = 1-10
   ===================================================== */

INSERT INTO jobs
(employer_id,category_id,title,description,job_type,location,status,posted_at)
VALUES
(14,1,'Flutter Developer','Develop Flutter apps','full-time','Lahore','active','2026-06-03'),
(14,1,'Laravel Developer','Backend development','full-time','Lahore','active','2026-06-03'),
(14,1,'React Developer','Frontend development','full-time','Lahore','active','2026-06-03'),
(14,1,'UI UX Designer','Design interfaces','full-time','Lahore','active','2026-06-03'),
(14,1,'QA Engineer','Testing software','full-time','Lahore','active','2026-06-03'),

(15,1,'PHP Developer','PHP projects','full-time','Karachi','active','2026-06-02'),
(15,1,'Java Developer','Java backend systems','full-time','Karachi','active','2026-06-02'),
(15,1,'Python Developer','Python APIs','full-time','Karachi','active','2026-06-02'),
(15,1,'WordPress Developer','WP customization','full-time','Karachi','active','2026-06-02'),
(15,1,'Database Administrator','Manage databases','full-time','Karachi','active','2026-06-02'),

(16,1,'Backend Engineer','API Development','full-time','Islamabad','active','2026-06-01'),
(16,1,'Frontend Engineer','Web UI development','full-time','Islamabad','active','2026-06-01'),
(16,1,'DevOps Engineer','CI/CD pipelines','full-time','Islamabad','active','2026-06-01'),
(16,1,'Cloud Engineer','AWS deployment','full-time','Islamabad','active','2026-06-01'),
(16,1,'Software Architect','Architecture design','full-time','Islamabad','active','2026-06-01'),

(17,1,'Mobile Developer','Android apps','full-time','Faisalabad','active','2026-05-31'),
(17,1,'iOS Developer','iPhone apps','full-time','Faisalabad','active','2026-05-31'),
(17,1,'Full Stack Developer','Complete solutions','full-time','Faisalabad','active','2026-05-31'),
(17,1,'Graphic Designer','Creative design','full-time','Faisalabad','active','2026-05-31'),
(17,1,'SEO Specialist','SEO optimization','full-time','Faisalabad','active','2026-05-31'),

(18,1,'Content Writer','Technical writing','full-time','Lahore','active','2026-05-30'),
(18,1,'Digital Marketer','Marketing campaigns','full-time','Lahore','active','2026-05-30'),
(18,1,'Project Manager','Manage teams','full-time','Lahore','active','2026-05-30'),
(18,1,'Business Analyst','Business requirements','full-time','Lahore','active','2026-05-30'),
(18,1,'Data Analyst','Data insights','full-time','Lahore','active','2026-05-30'),

(14,1,'Junior Developer','Entry level role','full-time','Lahore','active','2026-05-29'),
(15,1,'Senior Developer','Senior role','full-time','Karachi','active','2026-05-29'),
(16,1,'Support Engineer','Technical support','full-time','Islamabad','active','2026-05-29'),
(17,1,'Network Engineer','Network management','full-time','Faisalabad','active','2026-05-29'),
(18,1,'Cyber Security Analyst','Security operations','full-time','Lahore','active','2026-05-29');


/* =====================================================
   APPLICATIONS
   Job IDs assumed 1-30
   User IDs 4-13
   ===================================================== */

INSERT INTO applications
(job_id,user_id,full_name,email,status)
VALUES
(1,4,'Ali Raza','ali.raza@gmail.com','pending'),
(2,5,'Ahmed Khan','ahmed.khan@gmail.com','pending'),
(3,6,'Hassan Ali','hassan.ali@gmail.com','accepted'),
(4,7,'Usman Tariq','usman.tariq@gmail.com','pending'),
(5,8,'Bilal Ahmed','bilal.ahmed@gmail.com','rejected'),
(6,9,'Zain Malik','zain.malik@gmail.com','pending'),
(7,10,'Hamza Shah','hamza.shah@gmail.com','accepted'),
(8,11,'Awais Iqbal','awais.iqbal@gmail.com','pending'),
(9,12,'Tayyab Hussain','tayyab.h@gmail.com','pending'),
(10,13,'Saad Sheikh','saad.sheikh@gmail.com','accepted');


/* =====================================================
   SAVED JOBS
   ===================================================== */

INSERT INTO saved_jobs (user_id,job_id)
VALUES
(4,1),
(4,2),
(5,3),
(6,4),
(7,5),
(8,6),
(9,7),
(10,8);


/* =====================================================
   FAVORITES
   Employers save seekers
   ===================================================== */

INSERT INTO favorites (employer_id,seeker_id)
VALUES
(14,4),
(14,6),
(15,5),
(16,7),
(17,8),
(18,10);


/* =====================================================
   PROFILE VIEWS
   ===================================================== */

INSERT INTO profile_views (seeker_id,viewer_id)
VALUES
(4,14),
(5,14),
(6,15),
(7,16),
(8,17),
(9,18),
(10,14),
(11,15);