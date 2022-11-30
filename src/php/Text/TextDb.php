<?php

namespace LWT\Text\Database;

require_once __DIR__ . '/../../../inc/session_utility.php';


function delete_text_id($textid) {
    $statements = [
        "delete from textitems2 where Ti2TxID = {$textid}",
        "delete from sentences where SeTxID = {$textid}", 
        "delete from texts where TxID = {$textid}"
    ];
    foreach ($statements as $sql) {
        runsql($sql, "");
    }
}


function unarchive($textid) {

}

?>