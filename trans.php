<?php

/**
 * \file
 * \brief Get a translation from Web Dictionary
 * 
 * Call trans.php?x=1&t=[textid]&i=[textpos]
 *      GTr translates sentence in Text t, Pos i
 * 
 * @package Lwt
 * @author  LWT Project <lwt-project@hotmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/html/trans_8php.html
 * @since   1.0.3
 */

require_once 'inc/session_utility.php';

$i = $_REQUEST["i"];
$t = $_REQUEST["t"];


function get_trans_url($trans) {
    if(substr($trans, 0, 1) == '*') {
        $trans = substr($trans, 1);
    }
    if (substr($trans, 0, 7) == 'ggl.php') {
        $trans = str_replace('?', '?sent=1&', $trans);
    }
    return $trans;
}

$sql = "select SeText, LgGoogleTranslateURI 
from languages, sentences, textitems2 
where Ti2SeID=SeID and Ti2LgID=LgID 
and Ti2TxID = $t and Ti2Order = $i";
$res = do_mysqli_query($sql);
$record = mysqli_fetch_assoc($res);
mysqli_free_result($res);

if (! $record) {
    my_die("No results: $sql");
}

$trans = isset($record['LgGoogleTranslateURI']) ? $record['LgGoogleTranslateURI'] : "";
$trans = get_trans_url($trans);

if ($trans != '') {
    header("Location: " . createTheDictLink($trans, $record['SeText']));
}

exit();

?>
