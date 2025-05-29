<?php
require_once __DIR__."/../.env.php";

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["error" => "Method Not Allowed"]);
    exit;
}

$input = json_decode(file_get_contents("php://input"), true);

$name = trim($input["name"] ?? "");
$email = trim($input["email"] ?? "");
$message = trim($input["message"] ?? "");
$pageTitle = trim($input["page-title"] ?? "");
$pagePath = trim($input["page-path"] ?? "");
$pageDate = trim($input["page-date"] ?? "");
$datetime = date("Y-m-d H:i:s");

if (!$name || !$email || !$message || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid input"]);
    exit;
}

// Cockpit API に送信
$url = COCKPIT_URL."/api/collections/save/comments";
$apiKey = COCKPIT_API_KEY;

$data = [
    "data" => [
        "name" => $name,
        "email" => $email,
        "message" => $message,
        "page_title" => $pageTitle,
        "page_path" => $pagePath,
        "page_date" => $pageDate,
        "datetime" => $datetime
    ]
];

$options = [
    "http" => [
        "method"  => "POST",
        "header"  => "Content-Type: application/json\r\nCockpit-Token: {$apiKey}\r\n",
        "content" => json_encode($data)
    ]
];
$context  = stream_context_create($options);
$result = file_get_contents($url, false, $context);

if ($result) {
    echo json_encode(["status" => "ok"]);
} else {
    http_response_code(500);
    echo json_encode(["error" => "Failed to save comment"]);
}
