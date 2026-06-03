<?php
require_once 'config.php';
$res = $conn->query('SHOW TABLES');
if (!$res) {
    echo 'ERROR: ' . $conn->error . "\n";
    exit(1);
}
while ($row = $res->fetch_row()) {
    echo $row[0] . "\n";
}
