<?php
/**
 * \file
 * \brief Set statuses for terms/new terms.
 * 
 * Call: post to inc/ajax_bulk_status_update.php
 */

require_once __DIR__ . '/session_utility.php';


function update_existing_words($status, $terms) {
    $ids = [];
    foreach ($terms as $t) {
        array_push($ids, $t['wid']);
    }
    $idlist = implode(', ', $ids);
    $sql = "UPDATE words
      SET WoStatus = {$status}
      WHERE WoID IN ({$idlist})";

    $count = runsql($sql, "");
    return (int) $count;
}

function update_new_words($status, $newterms) {

    // 1. Load all to temp table.
    $tmpLoad = "TMP_new_words_loading";
    $sql = "DROP TABLE IF EXISTS {$tmpLoad}";
    runsql($sql, "");

    // All of these fields are necessary
    // for the bulk insert.
    $sql = "CREATE TEMPORARY TABLE {$tmpLoad}
      ( WoTextLC varchar(250) )";
    runsql($sql, "");

    foreach ($newterms as $w) {
        $lc = mb_strtolower($w['text'], 'UTF-8');
        $sqllc = convert_string_to_sqlsyntax($lc);
        runsql("INSERT INTO {$tmpLoad} (WoTextLC) VALUES ({$sqllc})", "");
    }

    // 1b. Create final temp table with the unique terms
    // and all fields needed for the bulk insert.
    // 1. Load all to temp table.
    $temptbl = "TMP_new_words";
    $sql = "DROP TABLE IF EXISTS {$temptbl}";
    runsql($sql, "");

    $sql = "CREATE TEMPORARY TABLE {$temptbl} AS (
      SELECT t.WoTextLC as WoText, t.WoTextLC as WoTextLC,
             ${status} as WoStatus, NOW() AS WoStatusChanged
      FROM (SELECT DISTINCT WoTextLC from {$tmpLoad}) AS t
    )";
    runsql($sql, "");

    // 2. Bulk insert any new words.
    $sql = "SELECT StValue as value FROM settings
WHERE StKey = 'currentlanguage'";
    $lang = get_first_value($sql);

    $emptystring = convert_string_to_sqlsyntax('');
    $scorefields = make_score_random_insert_update('iv');
    $scorevals = make_score_random_insert_update('id');

    $sql = "INSERT INTO words (
WoLgID, WoTextLC, WoText, WoStatus, WoStatusChanged,
WoTranslation, WoSentence, WoRomanization,
{$scorefields})
SELECT
{$lang}, tt.WoTextLC, tt.WoText, tt.WoStatus, NOW(),
{$emptystring}, {$emptystring}, {$emptystring},
{$scorevals}
FROM {$temptbl} AS tt
WHERE WoTextLC NOT IN (
  SELECT WoTextLC FROM words
  WHERE WoLgID = {$lang} )
";
    $count = runsql($sql, "");

    // 3. Update all texts that have new terms.
    $sql = "UPDATE textitems2 AS ti2
JOIN words as w
ON (Ti2TextLC = w.WoTextLC AND ti2.Ti2LgID = w.WoLgID)
SET ti2.Ti2WoID = w.WoID
WHERE ti2.Ti2WoID = 0
  AND ti2.Ti2WordCount > 0
  AND ti2.Ti2TextLC IN (SELECT WoTextLC FROM {$temptbl})
  AND ti2.Ti2LgID = {$lang}
  AND w.WoLgID = {$lang}";
    runsql($sql, "");

    return (int) $count;
}

function do_updates() {
    $new_status = (int) $_POST['status'];
    $d = 'something';
    $terms = [];
    $newterms = [];
    if (isset($_POST['terms'])) {
        $terms = update_existing_words($new_status, $_POST['terms']);
    }
    if (isset($_POST['newterms'])) {
        $newterms = update_new_words($new_status, $_POST['newterms']);
    }

    $array = [
        "new_status" => $new_status,
        "terms" => $terms,
        "new" => $newterms
    ];
    return $array;
}


if (isset($_POST['status']) && (isset($_POST['terms']) || isset($_POST['newterms']))) {
    $data = do_updates();
    echo json_encode($data);
}
else {
    echo 'Missing POST params';
}


?>
