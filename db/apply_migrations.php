<?php
/**
 * Migrates the LWT db defined in connect.inc.php.
 */

require __DIR__ . '/../connect.inc.php';
require __DIR__ . '/lib/mysql_migrator.php';

$dir = __DIR__ . '/migrations';

echo "Migrating $dbname ($server).\n";

$migration = new MysqlMigrator();
$migration->process($dir, $server, $dbname, $userid, $passwd);
?>