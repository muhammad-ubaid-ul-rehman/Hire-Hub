<?php
// ============================================================
// config.php — Core configuration. Include ONCE at top of each file.
// ============================================================

// Start session safely (won't error if already started)
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 86400,   // 1 day
        'path'     => '/',
        'secure'   => false,   // set true if using HTTPS
        'httponly' => true,    // JS cannot access cookie
        'samesite' => 'Strict'
    ]);
    session_start();
}

// ── Site identity (change here to rebrand everywhere) ──
define('SITE_NAME',    'HireHub');
define('SITE_TAGLINE', 'Pakistan\'s Modern Job Platform');
define('SITE_EMAIL',   'support@hirehub.pk');
define('SITE_VERSION', '2.0');

// ── Database credentials ──
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'job_portal');

// ── Connect ──
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die('<p style="font-family:sans-serif;color:red;padding:40px;">
        Database connection failed. Make sure XAMPP MySQL is running.<br>
        Error: ' . $conn->connect_error . '
    </p>');
}
$conn->set_charset('utf8mb4');

// ============================================================
// HELPER FUNCTIONS
// ============================================================

/** Check if user is logged in */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

/** Get full current user row from DB */
function getCurrentUser(): ?array {
    global $conn;
    if (!isLoggedIn()) return null;
    $id   = (int)$_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $res  = $stmt->get_result();
    return ($res->num_rows > 0) ? $res->fetch_assoc() : null;
}

/** Redirect and stop execution */
function redirect(string $url): void {
    header("Location: $url");
    exit();
}

/** Require login — redirect to login page if not logged in */
function requireLogin(): array {
    if (!isLoggedIn()) redirect('login.php');
    $user = getCurrentUser();
    if (!$user) redirect('login.php');
    return $user;
}

/** Require a specific role */
function requireRole(string $role): array {
    $user = requireLogin();
    if ($user['role'] !== $role) redirect('index.php');
    return $user;
}

/** Check if current user is admin */
function isAdmin(): bool {
    $user = getCurrentUser();
    return $user !== null && $user['role'] === 'admin';
}

/** Check secret admin access granted via hidden password */
function hasSecretAdminAccess(): bool {
    return isAdmin() || !empty($_SESSION['secret_admin']);
}

/** Require admin access or secret unlock */
function requireAdminAccess(): void {
    if (!hasSecretAdminAccess()) {
        redirect('secret.php');
    }
}

/** Clean output to prevent XSS */
function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/** Generate initials avatar letters from a name */
function initials(string $name): string {
    $parts = explode(' ', trim($name));
    $init  = strtoupper(substr($parts[0], 0, 1));
    if (count($parts) > 1) $init .= strtoupper(substr(end($parts), 0, 1));
    return $init;
}

/** Format date nicely */
function niceDate(string $date): string {
    return date('M d, Y', strtotime($date));
}

/** Format date and time nicely */
function niceDateTime(string $date): string {
    return date('M d, Y \a\t h:i A', strtotime($date));
}

/** Days until a deadline */
function daysUntil(?string $date): ?int {
    if (!$date) return null;
    return (int)ceil((strtotime($date) - time()) / 86400);
}

/** Job type display label */
function jobTypeLabel(string $jobType): string {
    return [
        'full-time' => 'Full-Time',
        'part-time' => 'Part-Time',
        'remote'    => 'Remote',
        'contract'  => 'Contract',
        'internship'=> 'Internship',
    ][$jobType] ?? ucwords(str_replace(['-', '_'], ' ', $jobType));
}

/** Job type badge class */
function jobTypeClass(string $jobType): string {
    return [
        'full-time' => 'type-full',
        'part-time' => 'type-part',
        'remote'    => 'type-remote',
        'contract'  => 'type-contract',
        'internship'=> 'type-intern',
    ][$jobType] ?? 'type-full';
}

/** Experience level label */
function experienceLabel(string $level): string {
    return [
        'entry'     => 'Entry Level',
        'mid'       => 'Mid Level',
        'senior'    => 'Senior Level',
        'executive' => 'Executive',
    ][$level] ?? ucwords(str_replace(['-', '_'], ' ', $level));
}

/** CSRF token — generate once per session */
function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Validate CSRF token from POST */
function verifyCsrf(): void {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        die('CSRF validation failed. Please go back and try again.');
    }
}

/** Log errors and important events */
function logEvent(string $message, string $level = 'INFO'): void {
    $logFile = __DIR__ . '/logs/app.log';
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) mkdir($logDir, 0755, true);
    
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] [$level] $message" . PHP_EOL;
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

/** Handle exceptions and errors */
function handleError($errno, $errstr, $errfile, $errline) {
    $message = "Error [$errno]: $errstr in $errfile on line $errline";
    logEvent($message, 'ERROR');
    
    $_SESSION['error_details'] = [
        'message' => $errstr,
        'type' => 'Error',
        'file' => $errfile,
        'line' => $errline,
        'errno' => $errno,
        'trace' => debug_backtrace()
    ];
    
    header('Location: error.php');
    exit;
}

/** Handle uncaught exceptions */
function handleException($exception) {
    $message = 'Uncaught exception: ' . $exception->getMessage() . ' in ' . $exception->getFile() . ':' . $exception->getLine();
    logEvent($message, 'EXCEPTION');
    
    $_SESSION['error_details'] = [
        'message' => $exception->getMessage(),
        'type' => 'Exception',
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
        'code' => $exception->getCode(),
        'trace' => $exception->getTrace()
    ];
    
    header('Location: error.php');
    exit;
}

set_error_handler('handleError');
set_exception_handler('handleException');
