<?php
/**
 * Migrates the LWT db defined in connect.inc.php.
 */

require_once __DIR__ . '/../../connect.inc.php';
require_once __DIR__ . '/mysql_migrator.php';

function apply_migrations($showlogging = false) {
    global $server, $dbname, $userid, $passwd;
    $dir = __DIR__ . '/../migrations';
    $repdir = __DIR__ . '/../migrations_repeatable';
    $migration = new MysqlMigrator($showlogging);
    $migration->process($dir, $repdir, $server, $dbname, $userid, $passwd);
}
?>