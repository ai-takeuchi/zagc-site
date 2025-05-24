<?php
require_once __DIR__."/../.env.php";

// 設定
$githubToken = GITHUB_TOKEN;
$owner = YOUR_GITHUB_USERNAME;
$repo = YOUR_REPO_NAME;
$workflow = WORKFLOW_FILE;  // ワークフローのファイル名
$ref = BRANCH_NAME;         // 実行したいブランチ

// 安全対策
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

// GitHub API エンドポイント
$url = "https://api.github.com/repos/{$owner}/{$repo}/actions/workflows/{$workflow}/dispatches";

// データ
$payload = json_encode(['ref' => $ref]);

// GitHub API呼び出し
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: token {$githubToken}",
    "Accept: application/vnd.github+json",
    "User-Agent: PHP-Deploy-Client"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// レスポンス
if ($httpCode === 204) {
    echo json_encode(['status' => 'ok', 'message' => 'Deploy triggered']);
} else {
    http_response_code($httpCode);
    echo json_encode(['status' => 'error', 'message' => 'GitHub API failed', 'response_code' => $httpCode]);
}
