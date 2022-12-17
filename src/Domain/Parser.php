<?php

namespace App\Domain;

use App\Entity\Text;

require_once __DIR__ . '/../../connect.inc.php';


class Parser {

    /** PUBLIC **/
    
    public static function parse(Text $text) {
        $p = new Parser();
        $p->parseText($text);
    }

    public function __construct()
    {
        global $userid, $passwd, $server, $dbname; // From connect.inc.php
        $conn = @mysqli_connect($server, $userid, $passwd, $dbname);
        @mysqli_query($conn, "SET SESSION sql_mode = ''");
        $this->conn = $conn;
    }

    /** PRIVATE **/

    private function exec_sql($sql, $params = null) {
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new Exception($this->conn->error);
        }
        if ($params) {
            $stmt->bind_param(...$params);
        }
        if (!$stmt->execute()) {
            throw new \Exception($stmt->error);
        }
        // return $stmt->get_result();
    }
 
    private function parseText(Text $text) {

        $id = $text->getID();
        $cleanup = [
            "DELETE FROM sentences WHERE SeTxID = $id",
            "DELETE FROM textitems2 WHERE Ti2TxID = $id"
        ];
        foreach ($cleanup as $sql)
            $this->exec_sql($sql);

        return;
        
    $wl = array();
    $wl_max = 0;
    $mw_sql = '';
    $sql = "SELECT LgRightToLeft FROM languages WHERE LgID=$lid";
    $res = do_mysqli_query($sql);
    $record = mysqli_fetch_assoc($res);
    // Just checking if LgID exists with ID should be enough
    if ($record == false) {
        my_die("Language data not found: $sql"); 
    }
    $rtlScript = $record['LgRightToLeft'];
    mysqli_free_result($res);

    if ($id == -2) {
        /*
        Replacement code not created yet 

        trigger_error(
            "Using splitCheckText with \$id == -2 is deprectad and won't work in 
            LWT 3.0.0. Use format_text instead.", 
            E_USER_WARNING
        );*/
        return prepare_text_parsing($text, -2, $lid);
    }
    prepare_text_parsing($text, $id, $lid);

    // Check text
    if ($id == -1) {
        check_text_valid($lid);
    }

    if ($id > 0) {
        import_temptextitems($id, $lid);
    }
    // Check text
    if ($id == -1) {
        check_text($sql, (bool)$rtlScript, $wl);
    }
    do_mysqli_query("TRUNCATE TABLE temptextitems");

        // TODO
    }
}