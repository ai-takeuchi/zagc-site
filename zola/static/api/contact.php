<?php
require_once __DIR__."/../.env.php";

// JSON 形式で受信
header("Content-Type: application/json");

// CORS 許可（必要に応じて調整）
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["error" => "Method Not Allowed"]);
    exit;
}

// JSONデータを受信してデコード
$input = json_decode(file_get_contents("php://input"), true);

// 簡易バリデーション
$name = trim($input["name"] ?? "");
$email = trim($input["email"] ?? "");
$message = trim($input["message"] ?? "");

if (!$name || !$email || !$message || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid input"]);
    exit;
}

// メールの内容
$to = MAILTO;  // 受信先を自分のメールに変更
$datetime = date("Y-m-d H:i:s"); // 現在日時
$subject = "【お問い合わせ】{$name} 様より";
$body = <<<EOT
Datetime: {$datetime}
お名前: {$name}
メール: {$email}
メッセージ:
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
