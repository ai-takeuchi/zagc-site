<?php
/**
 * GitHub Actions の最新IPを取得し、.ftpaccess の該当部分を書き換える例
 */

/*
# BEGIN GITHUB ACTIONS IPs
Allow from 192.30.252.0/22
Allow from 185.199.108.0/22
...
# END GITHUB ACTIONS IPs
*/

$apiUrl = "https://api.github.com/meta";
$ftpaccessPath = __DIR__ . "/.ftpaccess";  // 書き換える .ftpaccess のパス

// 1️⃣ GitHub APIからIPレンジ取得
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERAGENT, "PHP-cURL");  // User-Agent は必須
$response = curl_exec($ch);

if ($response === false) {
    die("cURL error: " . curl_error($ch));
}

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    die("GitHub API failed. HTTP Code: $httpCode");
}

// 2️⃣ JSONをパースして actions のIPレンジを取得
$data = json_decode($response, true);
if (!isset($data["actions"]) || !is_array($data["actions"])) {
    die("Invalid API response: actions not found");
}
$actionsIPs = $data["actions"];

// 3️⃣ .ftpaccess の内容を読み込む
$ftpaccessContent = file_get_contents($ftpaccessPath);
if ($ftpaccessContent === false) {
    die("Failed to read .ftpaccess");
}

// 4️⃣ Allow from セクションを書き換える例
// .ftpaccess に以下のようなマーカーを入れておくのがおすすめ
// # BEGIN GITHUB ACTIONS IPs
// ...（ここを書き換える）...
// # END GITHUB ACTIONS IPs

$newContent = preg_replace_callback(
    '#(# BEGIN GITHUB ACTIONS IPs)(.*?)(# END GITHUB ACTIONS IPs)#s',
    function ($matches) use ($actionsIPs) {
        // FTPアクセス制御用の「Allow from」形式にする
        $allowLines = array_map(fn($ip) => "Allow from $ip", $actionsIPs);
        $replacement = $matches[1] . "\n" . implode("\n", $allowLines) . "\n" . $matches[3];
        return $replacement;
    },
    $ftpaccessContent
);

if ($newContent === null) {
    die("Regex error while replacing");
}

// 5️⃣ .ftpaccess を上書き
$result = file_put_contents($ftpaccessPath, $newContent);
if ($result === false) {
    die("Failed to write .ftpaccess");
}

echo "✅ .ftpaccess updated successfully\n";
