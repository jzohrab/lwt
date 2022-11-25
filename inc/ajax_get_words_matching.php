<?php opcache_reset();
/**
 * \file
 * \brief get words matching starting letters.
 * 
 * Call: post to inc/ajax_get_words_matching.php
 */

require_once __DIR__ . '/session_utility.php';


function get_words_matching($word) {
    $sql = "SELECT StValue as value FROM settings
WHERE StKey = 'currentlanguage'";
    $lang = get_first_value($sql);

    $w = strtolower($word);
    $w = $w . '%';

    $sql = "SELECT WoID, WoTextLC FROM words
    WHERE WoLgID = {$lang} AND WoTextLC LIKE ? LIMIT 10";
    global $DBCONNECTION;
    $stmt = mysqli_prepare($DBCONNECTION, $sql);
    mysqli_stmt_bind_param($stmt, "s", $w);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    $result = array();
    while ($record = mysqli_fetch_assoc($res)) {
        $result[$record['WoID']] = $record['WoTextLC'];
    }
    mysqli_free_result($res);

    return $result;
}


$word = $_POST['word'];
if (isset($word)) {
    $data = get_words_matching($word);
    echo json_encode($data);
}
else {
    echo 'Missing POST param name=word';
}

?>
