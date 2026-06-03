<?php
require_once 'config.php';
$res = $conn->query('SHOW COLUMNS FROM contact_messages');
if (!$res) {
    echo 'ERROR: ' . $conn->error . "\n";
    exit(1);
}
while ($r = $res->fetch_assoc()) {
    echo $r['Field'] . ' ' . $r['Type'] . "\n";
}
