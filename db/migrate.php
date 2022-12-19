<?php
/**
 * Migrates the LWT db defined in connect.inc.php.
 */
require_once __DIR__ . '/lib/apply_migrations.php';
echo "\nMigrating $dbname on $server.\n";
apply_migrations(true);
?>