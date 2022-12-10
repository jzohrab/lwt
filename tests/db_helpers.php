<?php

/**
 * IMPORTANT!
 *
 * This file should be included at the top of any files that hit the
 * db.
 */

require_once __DIR__ . '/../inc/database_connect.php';

/**
 * LWT users create a connect.inc.php file with db settings, so just
 * use that to create the db connection for symfony.
 * Gah this is brutal.  Can't be helped while transitioning.
 */
require_once __DIR__ . '/../connect.inc.php';
global $userid, $passwd, $server, $dbname;
$DATABASE_URL = "mysql://{$userid}:{$passwd}@{$server}/{$dbname}?serverVersion=8&charset=utf8";
$_ENV['DATABASE_URL'] = $DATABASE_URL;
$_SERVER['DATABASE_URL'] = $DATABASE_URL;


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

        $alters = [
            "sentences",
            "tags",
            "textitems2",
            "texts",
            "words"
        ];
        foreach ($alters as $t) {
            do_mysqli_query("ALTER TABLE {$t} AUTO_INCREMENT = 1");
        }
    }

    public static function load_language_spanish() {
        $url = "http://something.com/###";
        $sql = "INSERT INTO `languages` (`LgID`, `LgName`, `LgDict1URI`, `LgDict2URI`, `LgGoogleTranslateURI`, `LgExportTemplate`, `LgTextSize`, `LgCharacterSubstitutions`, `LgRegexpSplitSentences`, `LgExceptionsSplitSentences`, `LgRegexpWordCharacters`, `LgRemoveSpaces`, `LgSplitEachChar`, `LgRightToLeft`) VALUES (1,'Spanish','{$url}','{$url}','{$url}','\$y\\t\$t\\n',150,'´=\'|`=\'|’=\'|‘=\'|...=…|..=‥','.!?:;','Mr.|Dr.|[A-Z].|Vd.|Vds.','a-zA-ZÀ-ÖØ-öø-ȳáéíóúÁÉÍÓÚñÑ',0,0,0)";
        do_mysqli_query($sql);
    }

    /**
     * Data loaders.
     *
     * These might belong in an /api/db/ or similar.
     *
     * These are very hacky, not handling weird chars etc., and are
     * also very inefficient!  Will fix if tests get stupid slow.
     */

    public static function exec_statement($stmt) {
        if (!$stmt) {
            throw new Exception($DBCONNECTION->error);
        }
        if (!$stmt->execute()) {
            throw new Exception($stmt->error);
        }
    }

    public static function add_text($text, $langid, $title = 'testing') {
        global $DBCONNECTION;
        $sql = "INSERT INTO texts (TxLgID, TxTitle, TxText) VALUES (?, ?, ?)";
        $stmt = $DBCONNECTION->prepare($sql);
        $stmt->bind_param("iss", $langid, $title, $text);
        DbHelpers::exec_statement($stmt);
        return $stmt->insert_id;
    }

    // This just hacks directly into the table, it doesn't update textitems2 etc.
    public static function add_word($WoLgID, $WoText, $WoTextLC, $WoStatus, $WoWordCount) {
        global $DBCONNECTION;
        $sql = "insert into words (WoLgID, WoText, WoTextLC, WoStatus, WoWordCount) values (?, ?, ?, ?, ?);";
        $stmt = $DBCONNECTION->prepare($sql);
        $stmt->bind_param("issii", $WoLgID, $WoText, $WoTextLC, $WoStatus, $WoWordCount);
        DbHelpers::exec_statement($stmt);
        return $stmt->insert_id;
    }

    public static function add_tags($tags) {
        $ids = [];
        global $DBCONNECTION;
        foreach ($tags as $t) {
            $sql = "insert into tags (TgText, TgComment)
            values ('{$t}', '{$t} comment')";
            $stmt = $DBCONNECTION->prepare($sql);
            DbHelpers::exec_statement($stmt);
            $ids[] = $stmt->insert_id;
        };
        return $ids;
    }

    public static function add_texttags($tags) {
        $ids = [];
        global $DBCONNECTION;
        foreach ($tags as $t) {
            $sql = "insert into tags2 (T2Text, T2Comment)
            values ('{$t}', '{$t} comment')";
            $stmt = $DBCONNECTION->prepare($sql);
            DbHelpers::exec_statement($stmt);
            $ids[] = $stmt->insert_id;
        };
        return $ids;
    }


    /**
     * Checks.
     */

    public static function assertTableContains($sql, $expected, $message = '') {
        $content = [];
        $res = do_mysqli_query($sql);
        while($row = mysqli_fetch_assoc($res)) {
            $content[] = implode('; ', $row);
        }
        mysqli_free_result($res);

        PHPUnit\Framework\Assert::assertEquals($expected, $content, $message);
    }

    /**
     * Sample calls:
     * DbHelpers::assertRecordcountEquals('select * from x where id=2', 1, 'single record');
     * DbHelpers::assertRecordcountEquals('x', 19, 'all records in table x');
     */
    public static function assertRecordcountEquals($sql, $expected, $message = '') {
        if (stripos($sql, 'select') === false) {
            $sql = "select * from {$sql}";
        }
        $c = get_first_value("select count(*) as value from ({$sql}) src");

        if ($c != $expected) {
            $content = [];
            $res = do_mysqli_query($sql);
            while($row = mysqli_fetch_assoc($res)) {
                $content[] = implode('; ', $row);
            }
            mysqli_free_result($res);
            $content = implode("\n", $content);
            $message = "{$message} ... got data:\n\n{$content}\n";
        }
        PHPUnit\Framework\Assert::assertEquals($expected, $c, $message);
    }

}

?>