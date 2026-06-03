<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';

// Get parameters
$search = trim($_GET['search'] ?? '');
$location = trim($_GET['location'] ?? '');
$type = trim($_GET['type'] ?? '');
$limit = min((int)($_GET['limit'] ?? 10), 50);
$offset = (int)($_GET['offset'] ?? 0);

// Build query
$where = "WHERE 1=1";
$params = [];
$types = '';

if ($search !== '') {
    $like = "%$search%";
    $where .= " AND (j.title LIKE ? OR j.description LIKE ? OR j.requirements LIKE ?)";
    $params = array_merge($params, [$like, $like, $like]);
    $types .= 'sss';
}
if ($location !== '') {
    $like = "%$location%";
    $where .= " AND j.location LIKE ?";
    $params[] = $like; $types .= 's';
}
if ($type !== '') {
    $where .= " AND j.job_type = ?";
    $params[] = $type; $types .= 's';
}

$sql = "SELECT j.id, j.title, j.location, j.job_type, j.salary, j.posted_at, 
               u.name AS employer_name,
               (SELECT COUNT(*) FROM applications a WHERE a.job_id = j.id) AS applications
        FROM jobs j
        JOIN users u ON j.employer_id = u.id
        $where
        ORDER BY j.posted_at DESC
        LIMIT ? OFFSET ?";

$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$jobs = [];
while ($job = $result->fetch_assoc()) {
    $jobs[] = [
        'id' => $job['id'],
        'title' => $job['title'],
        'location' => $job['location'],
        'job_type' => $job['job_type'],
        'salary' => $job['salary'],
        'employer' => $job['employer_name'],
        'applications' => $job['applications'],
        'posted_at' => $job['posted_at']
    ];
}

echo json_encode([
    'success' => true,
    'jobs' => $jobs,
    'count' => count($jobs)
]);
?>