<?php
/**
 * Migrates the LWT db defined in connect.inc.php.
 */
require_once __DIR__ . '/lib/apply_migrations.php';
echo "Migrating $dbname ($server).\n";
apply_migrations();
?>