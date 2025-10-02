<?php
require_once __DIR__ . '/../../config.php';

// 1. Secure this action
if (!is_admin()) {
    die('Permission Denied.');
}

// 2. Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 3. Security: Validate CSRF token
    validate_csrf_token();

    // 4. Define flag file path
    $flag_file = __DIR__ . '/../../maintenance.flag';

    // 5. Toggle the file's existence
    if (file_exists($flag_file)) {
        // Maintenance is on, turn it off by deleting the file
        unlink($flag_file);
    } else {
        // Maintenance is off, turn it on by creating the file
        touch($flag_file);
    }

    // 6. Redirect back to the admin dashboard
    redirect('index.php');

} else {
    // Redirect if accessed directly
    redirect('index.php');
}