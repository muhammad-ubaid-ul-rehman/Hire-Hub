<?php
require_once 'config.php';
$tables = ['users', 'seeker_profiles', 'jobs', 'applications', 'saved_jobs', 'contact_messages'];
foreach ($tables as $t) {
    echo "TABLE {$t}\n";
    $r = $conn->query("SHOW CREATE TABLE {$t}");
    if (!$r) {
        echo 'ERROR: ' . $conn->error . "\n";
        continue;
    }
    $row = $r->fetch_assoc();
    echo $row['Create Table'] . "\n\n";
}
