<?php

require_once __DIR__ . '/../../../inc/session_utility.php';
require_once __DIR__ . '/../../../inc/database_connect.php';

class LwtTextDatabase {


    static function delete($textid) {
        $statements = [
            "delete from textitems2 where Ti2TxID = {$textid}",
            "delete from sentences where SeTxID = {$textid}", 
            "delete from texts where TxID = {$textid}"
        ];
        foreach ($statements as $sql) {
            runsql($sql, "");
        }
    }


    static function archive($textid) {
        $statements = [
            "delete from textitems2 where Ti2TxID = {$textid}",
            "delete from sentences where SeTxID = {$textid}", 
            "update texts set TxArchived = true where TxID = {$textid}"
        ];
        foreach ($statements as $sql) {
            runsql($sql, "");
        }
    }


    static function unarchive($textid) {
        $where = " where TxID = {$textid}";
        $sql = "update texts set TxArchived = false $where";
        runsql($sql, "");
        $text = get_first_value("select TxText as value from texts $where");
        $lid = (int) get_first_value("select TxLgID as value from texts $where");
        splitCheckText($text, $lid, $textid);
    }

}

?>