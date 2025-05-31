<?php
error_reporting(E_ALL);
ini_set('display_errors', 'On');

session_start();
header("Content-Type: application/json; charset=utf-8");

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

echo json_encode([
    'csrf_token' => $_SESSION['csrf_token']
]);
exit;
