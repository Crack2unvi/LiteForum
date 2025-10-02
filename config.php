<?php
// config.php

// --- Error Reporting ---
// Report all errors during development.
// In a production environment, this should be set to 0.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// --- Timezone ---
// Set the default timezone for all date/time functions.
date_default_timezone_set('Europe/Istanbul');

// --- Database ---
// Define the absolute path to the SQLite database file.
define('DB_FILE', __DIR__ . '/forum.sqlite');

// --- Site Settings ---
// The name of the forum.
define('SITE_NAME', 'LiteForum');

// --- Session ---
// Start the session to handle user logins.
// Use secure session settings.
session_start([
    'cookie_httponly' => true,
    'cookie_secure' => false, // Set to true if using HTTPS
    'cookie_samesite' => 'Lax'
]);

// --- Global Functions ---
// A good place to include the functions file.
require_once __DIR__ . '/functions.php';

// --- Database Connection ---
// Function to get the PDO connection.
// This prevents creating a new connection on every include.
function get_pdo() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO('sqlite:' . DB_FILE);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            // In a real app, you'd log this error and show a generic error page.
            die('Database connection failed: ' . $e->getMessage());
        }
    }
    return $pdo;
}
