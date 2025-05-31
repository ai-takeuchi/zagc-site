<?php
// セッション開始
session_start();
header("Content-Type: application/json; charset=utf-8");

// JSON の入力を受け取る
$input = json_decode(file_get_contents("php://input"), true);

$email = $input["email"] ?? "";
$password = $input["password"] ?? "";

// 入力チェック（サンプルなので簡単に）
if (empty($email) || empty($password)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "メールアドレスとパスワードを入力してください"]);
    exit;
}

// ここで DB 認証をする（例としてダミーデータ）
$dummy_user = [
    "email" => "test@example.com",
    "password" => "password", // 本来はハッシュ化してDB保存する
    "name" => "テスト太郎"
];

if ($email === $dummy_user["email"] && $password === $dummy_user["password"]) {
    // 認証成功 → セッションに情報を保存
    $_SESSION["customer_logged_in"] = true;
    $_SESSION["customer_email"] = $dummy_user["email"];
    $_SESSION["customer_name"] = $dummy_user["name"];

    echo json_encode(["success" => true, "message" => "ログイン成功", "name" => $dummy_user["name"]]);
} else {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "認証失敗"]);
}
