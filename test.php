<?php
require_once('../../../config.php');
require_login();

// Check database connection
global $DB;
$table_exists = $DB->get_manager()->table_exists('local_publictestlink');
echo "Table exists: " . ($table_exists ? 'YES' : 'NO') . "<br>";

// List all tables with publictestlink in name
$tables = $DB->get_tables();
foreach ($tables as $table) {
    if (strpos($table, 'publictestlink') !== false) {
        echo "Found table: $table<br>";
    }
}