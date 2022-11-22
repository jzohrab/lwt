<?php

/**
 * IMPORTANT!
 *
 * This file should be included at the top of any files that hit the
 * db.
 */

require_once __DIR__ . '/../inc/database_connect.php';

use PHPUnit\Framework\TestCase;

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

    public static function clean_db() {
        $tables = [
            "_lwtgeneral",
            "archivedtexts",
            "archtexttags",
            "feedlinks",
            "languages",
            "newsfeeds",
            "sentences",
            // "settings",  // Keep these????
            "tags",
            "tags2",
            "temptextitems",
            "tempwords",
            "textitems2",
            "texts",
            "texttags",
            "tts",
            "words",
            "wordparents",
            "wordtags"
        ];
        foreach ($tables as $t) {
            do_mysqli_query("truncate {$t}");
        }
    }

    /**
     * Data loaders.
     *
     * These might belong in an /api/db/ or similar.
     *
     * These are very hacky, not handling weird chars etc., and are
     * also very inefficient!  Will fix if tests get stupid slow.
     */

    public static function add_tags($tags) {
        foreach ($tags as $t) {
            $sql = "insert into tags (TgText, TgComment)
            values ('{$t}', '{$t} comment')";
            do_mysqli_query($sql);
        };
    }

    public static function add_texttags($tags) {
        foreach ($tags as $t) {
            $sql = "insert into tags2 (T2Text, T2Comment)
            values ('{$t}', '{$t} comment')";
            do_mysqli_query($sql);
        };
    }

    public static function assertTableContains($sql, $expected, $message = '') {
        $content = [];
        $res = do_mysqli_query($sql);
        while($row = mysqli_fetch_assoc($res)) {
            $content[] = implode('; ', $row);
        }
        mysqli_free_result($res);

        PHPUnit\Framework\Assert::assertEquals($expected, $content, $message);
    }
}

?>