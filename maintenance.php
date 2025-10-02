<?php
// Command-line script to toggle maintenance mode.
// Usage: php maintenance.php [on|off|status]

$flag_file = __DIR__ . '/maintenance.flag';

// Check if running from CLI
if (php_sapi_name() !== 'cli') {
    die('This script can only be run from the command line.');
}

if ($argc < 2) {
    echo "Usage: php maintenance.php [on|off|status]\n";
    exit(1);
}

$command = strtolower($argv[1]);

switch ($command) {
    case 'on':
        if (!file_exists($flag_file)) {
            touch($flag_file);
            echo "Maintenance mode has been ENABLED.\n";
        } else {
            echo "Maintenance mode is already ON.\n";
        }
        break;

    case 'off':
        if (file_exists($flag_file)) {
            unlink($flag_file);
            echo "Maintenance mode has been DISABLED.\n";
        } else {
            echo "Maintenance mode is already OFF.\n";
        }
        break;

    case 'status':
        if (file_exists($flag_file)) {
            echo "Maintenance mode is currently ON.\n";
        } else {
            echo "Maintenance mode is currently OFF.\n";
        }
        break;

    default:
        echo "Invalid command. Usage: php maintenance.php [on|off|status]\n";
        exit(1);
}

exit(0);

