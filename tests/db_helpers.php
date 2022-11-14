<?php

/**
 * IMPORTANT!
 *
 * This file should be included at the top of any files that hit the
 * db.
 */

require_once __DIR__ . '/../inc/database_connect.php';

class DbHelpers {

    public static function ensure_using_test_db() {
        global $dbname;
        global $DBCONNECTION;
        $conn_db_name = get_first_value("SELECT DATABASE() AS value");

        $istestdbname = function($s) {
            return (strtolowersubstr($s, 0, 5) == 'test_');
        };
        foreach([$dbname, $conn_db_name] as $s) {
            $prefix = substr($s, 0, 5);
            if (strtolower($prefix) != 'test_') {
                $msg = "
*************************************************************
ERROR: Db name \"{$s}\" does not start with 'test_'

(Stopping tests to prevent data loss.)

Since database tests are destructive (delete/edit/change data),
you must use a dedicated test database when running tests.

1. Create a new database called 'test_<whatever_you_want>'
2. Update your connect.inc.php to use this new db
3. Run the tests.
*************************************************************
";
                echo $msg;
                die("Quitting");
            }
        }
    }

}

?>