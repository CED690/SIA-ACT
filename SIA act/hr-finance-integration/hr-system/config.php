<?php
// XAMPP Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'hr_system');

// File paths for shared data
define('SHARED_DATA_DIR', __DIR__ . '/../shared-data/');
define('PAYROLL_EXPORT_FILE', SHARED_DATA_DIR . 'payroll_export.json');

// Create shared directory if it doesn't exist
if (!is_dir(SHARED_DATA_DIR)) {
    mkdir(SHARED_DATA_DIR, 0755, true);
}
?>