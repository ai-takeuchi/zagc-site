<?php
// cp '.env.example.php' '.env.php'
// Then edit .env.php and set the appropriate values.
//
// This file is used when running `script/run_build.sh` locally,
// and is also used by the API on the public server.
// Please upload this file to the public server manually.
//
// Be careful not to expose sensitive information.

// === Cockpit CMS Configuration ===
define('COCKPIT_URL', 'https://your-cockpit-url.com');
define('COCKPIT_TOKEN', 'your_cockpit_token_here');
define('COCKPIT_SPACE', 'your_space_name');  // Leave empty if not used
define('COCKPIT_ITEMS_API_PATH', 'api/content/items');
define('COCKPIT_ITEMS', 'info,blog');
define('COCKPIT_ASSETS_API_PATH', 'api/public/getAssets');

// === Deployment Settings ===
define('DEPLOY_URL', '/'); // If the site is deployed under a subpath, include it here (e.g., https://example.com/path)
// define('DEPLOY_URL', 'https://your-live-site.com'); // Uncomment and set this if deploying to a live site with a path

// === FTP Settings (for deployment) ===
define('FTP_HOST', 'ftp.example.com');
define('FTP_PORT', '21');
define('FTP_HOST_PATH', '');
define('FTP_USER', 'your_ftp_username');
define('FTP_PASSWORD', 'your_ftp_password');
define('FTP_REMOTE_DIR', '/htdocs/');

// Used by the deploy API
define('GITHUB_TOKEN', '');
define('YOUR_GITHUB_USERNAME', '');
define('YOUR_REPO_NAME', '');
define('WORKFLOW_FILE', 'deploy.yml');
define('BRANCH_NAME', 'main');

// Web Contents
// Inquiry page API recipient
define('MAILTO', '');
