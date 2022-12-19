<?php
/**
 * Migrates the LWT db defined in connect.inc.php.
 */

require_once __DIR__ . '/../../connect.inc.php';
require_once __DIR__ . '/mysql_migrator.php';

function apply_migrations($showlogging = false) {
    global $server, $dbname, $userid, $passwd;
    // echo "\nMigrating $dbname on $server\n\n";
    $dir = __DIR__ . '/../migrations';
    $repdir = __DIR__ . '/../migrations_repeatable';
    $migration = new MysqlMigrator($showlogging);
    $migration->exec("ALTER DATABASE `{$dbname}` CHARACTER SET utf8 COLLATE utf8_general_ci", $server, $dbname, $userid, $passwd);
    $migration->process($dir, $repdir, $server, $dbname, $userid, $passwd);
}
?>