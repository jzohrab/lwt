<?php
/**
 * Migrates the LWT db defined in connect.inc.php.
 */

require __DIR__ . '/../../connect.inc.php';
require __DIR__ . '/mysql_migrator.php';

function apply_migrations() {
    global $server, $dbname, $userid, $passwd;
    $dir = __DIR__ . '/../migrations';
    $migration = new MysqlMigrator();
    $migration->process($dir, $server, $dbname, $userid, $passwd);
}
?>