<?php

$query = $_SERVER['QUERY_STRING'] ?? '';
$target = 'public/login.php' . ($query !== '' ? '?' . $query : '');

header('Location: ' . $target);
exit;
