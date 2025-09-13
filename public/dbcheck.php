<?php
$host='192.168.2.40'; $user='web'; $pass='#$PyKcm'; $db='denuncias';
$mysqli = @new mysqli($host, $user, $pass, $db, 3306);
if ($mysqli->connect_errno) { http_response_code(500); echo "CONNECT ERR: {$mysqli->connect_errno} - {$mysqli->connect_error}\n"; exit; }
$r=$mysqli->query('SELECT 1 as ok'); $row=$r?$r->fetch_assoc():null;
echo "OK DB=" . ($row['ok'] ?? 'no') . "\n";
