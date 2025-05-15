<?php
// Cockpit CMS Version 2.11.3 - Custom Public API Endpoint
// File: cockpit/config/api/public/getAssets.php
// To retrieve assets for a specific space, specify the space name using the URL query parameter, e.g., space=space-name

// Bootstrap Cockpit core
require_once __DIR__ . "/../../../bootstrap.php";

// Ensure the current user has permission to access assets
if (!Cockpit::instance()->helper('auth')->hasaccess('cockpit', 'assets')) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$app = Cockpit::instance();

// Manually load the Assets module bootstrap (not auto-loaded in public API)
require_once __DIR__ . '/../../../modules/Assets/bootstrap.php';

$spaceName = null;

// 1. First, attempt to retrieve the space name from the $_GET superglobal
if (isset($_GET['space'])) {
    $spaceName = $_GET['space'];
}

// 2. If the space name cannot be retrieved from $_GET or is empty, extract it using a regular expression from $_SERVER['REQUEST_URI']
if (empty($spaceName) && isset($_SERVER['REQUEST_URI'])) {
    $requestUri = $_SERVER['REQUEST_URI'];
    // Regular expression pattern: Capture the string that follows "?space=" or "&space=" and precedes an "&" character
    $pattern = '/[?&]space=([^&]+)/';

    if (preg_match($pattern, $requestUri, $matches)) {
        $spaceName = $matches[1];
    }
}

// Get the space instance
if ($spaceName) {
    $spaceInstance = $app->helper('spaces')->space($spaceName);

    if (!$spaceInstance) {
        echo json_encode(['error' => 'Space not found']);
        exit;
    }

    // Get the Assets module instance for the specific space
    $assetsModule = $spaceInstance->module('assets');
    if (!$assetsModule) {
        echo json_encode(['error' => "Assets module not found for space {$spaceName}"]);
        exit;
    }
} else {
    // Default assets module instance (if no space is defined)
    $assetsModule = $app->module('assets');
}

// Check if the assets module is loaded
if (!$assetsModule) {
    echo json_encode(['error' => "assets module not loaded"]);
    exit;
}

// Fetch assets sorted by creation date (newest first)
$options = ['sort' => ['created' => -1]];
$assets = $assetsModule->assets($options);

// Return the assets as a JSON response
header('Content-Type: application/json');

// Return an error if no assets were found
if (!$assets) {
    echo json_encode(['error' => 'No assets found']);
} else {
    echo json_encode($assets);
}
exit;
