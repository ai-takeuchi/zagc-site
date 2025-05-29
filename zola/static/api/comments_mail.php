<?php
require_once __DIR__."/../.env.php";

// JSON response
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
$pagePath = trim($input["page-path"] ?? "");
$pageTitle = trim($input["page-title"] ?? "");
$pageDate = trim($input["page-date"] ?? "");

if (!$name || !$email || !$message || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid input"]);
    exit;
}

// メールの内容
$to = MAILTO;  // 受信先を自分のメールに変更
$subject = "Comments from: {$name}";
// $pageDate = trim($input["page-date"] ?? "");
$datetime = date("Y-m-d H:i:s"); // 現在日時

$body = <<<EOT
page-title: {$input["page-title"]}
page-path: {$input["page-path"]}
page-date: {$pageDate}
Datetime: {$datetime}
Name: {$name}
Email: {$email}
Comments:
{$message}
EOT;

$headers = "From: {$email}\r\n";

// メール送信
if (mail($to, $subject, $body, $headers)) {
    echo json_encode(["status" => "ok"]);
} else {
    http_response_code(500);
    echo json_encode(["error" => "Failed to send email"]);
}
