<?php

require_once __DIR__ . '/../../../inc/session_utility.php';

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
        
    }


    static function unarchive($textid) {

    }

}

?>