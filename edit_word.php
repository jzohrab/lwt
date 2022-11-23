<?php

/**
 * \file
 * \brief Create or edit single word
 * 
 * Call: edit_word.php?....
 *  ... op=Save ... do insert new
 *  ... op=Change ... do update
 *  ... fromAnn=recno ... calling from impr. annotation editing
 *  ... tid=[textid]&ord=[textpos]&wid= ... new word  
 *  ... tid=[textid]&ord=[textpos]&wid=[wordid] ... edit word 
 * 
 * @since  1.0.3
 * @author LWT Project <lwt-project@hotmail.com>
 */

require_once 'inc/session_utility.php';
require_once 'inc/simterms.php';
require_once 'inc/word_input_form.php';


/**
 * Insert a new word to the database
 * 
 * @param FormData $fd
 * 
 * @return array{0: int, 1: string} Word id, and then an insertion message 
 */
function insert_new_word($fd)
{
    $wid = 0;
    $message = '';
    try {
        $wid = save_new_formdata($fd);
        $message = "Term saved";
    }
    catch (Exception $e) {
        $message = $e->getMessage();
    }

    return array($wid, $message);
}

/**
 * Edit an existing term.
 * 
 * @param FormData $fd
 * 
 * @return array{0: string, 1: string} Word id, and then an insertion message 
 */
function edit_term($fd)
{
    $wid = 0;
    $message = '';
    try {
        $wid = update_formdata($fd);
        $message = "Updated";
    }
    catch (Exception $e) {
        $message = $e->getMessage();
    }

    return array($wid, $message);
}

/**
 * Use this function if the lowercase version of the word does not correspond.
 * It will echo an error message.
 *
 * @param string $textlc The lowercase version of the word we want.
 */
function lowercase_term_not_equal($textlc): void
{
    $message = 
    'Error: Term in lowercase must be exactly = "' . 
    $textlc . '", please go back and correct this!'; 
    echo error_message_with_hide($message, 0);
}

/**
 * Echoes a JavaScript element, that will edit terms diplay
 */
function change_term_display($wid, $translation, $hex): void
{
    ?>
<script type="text/javascript">
    //<![CDATA[
    var context = window.parent.document.getElementById('frame-l');
    var contexth = window.parent.document.getElementById('frame-h');
    var woid = <?php echo prepare_textdata_js($wid); ?>;
    var status = <?php echo prepare_textdata_js($_REQUEST["WoStatus"]); ?>;
    var trans = <?php echo prepare_textdata_js($translation . getWordTagList($wid, ' ', 1, 0)); ?>;
    var roman = <?php echo prepare_textdata_js($_REQUEST["WoRomanization"]); ?>;
    var title;
    if (window.parent.document.getElementById('frame-l').JQ_TOOLTIP) {
        title = '';
    } else {
        title = make_tooltip(
            <?php echo prepare_textdata_js($_REQUEST["WoText"]); ?>, trans, roman, status
        );
    }
    <?php
    if ($_REQUEST['op'] == 'Save') {
        ?>
        $('.TERM<?php echo $hex; ?>', context)
        .removeClass('status0')
        .addClass('word' + woid + ' ' + 'status' + status)
        .attr('data_trans', trans)
        .attr('data_rom', roman)
        .attr('data_status', status)
        .attr('title', title)
        .attr('data_wid', woid);
        <?php
    } else {
        ?>
        $('.word' + woid, context)
        .removeClass('status<?php echo $_REQUEST['WoOldStatus']; ?>')
        .addClass('status' + status)
        .attr('data_trans', trans)
        .attr('data_rom', roman)
        .attr('data_status', status)
        .attr('title', title);
        <?php
    }
    ?>
    $('#learnstatus', contexth).html('<?php echo addslashes(texttodocount2($_REQUEST['tid'])); ?>');

    cleanupRightFrames();
    //]]>
</script>
    <?php
}


/**
 * Add new term or edit existing one, display result.
 */
function handle_save_or_update(): void
{
    $titlestart = "Edit Term: ";
    if ($_REQUEST['op'] == 'Save') {
      $titlestart = "New Term: ";
    }

    $fd = load_formdata_from_request();

    $titletext = $titlestart . tohtml($fd->termlc);
    pagestart_nobody($titletext);
    echo '<h4><span class="bigger">' . $titletext . '</span></h4>';

    if (mb_strtolower($fd->term, 'UTF-8') != $fd->termlc) {
        lowercase_term_not_equal($fd->termlc);
        pageend();
        exit();
    }

    $wid = 0;
    $message = '';
    if ($_REQUEST['op'] == 'Save') {
        [ $wid, $message ] = insert_new_word($fd);
    }
    else {
        [ $wid, $message ] = edit_term($fd);
    }

    echo '<p>OK: ' . tohtml($message) . '</p>';

    $fa = getreq("fromAnn"); // from-recno or empty
    if ($fa !== '') {
        $textlc_js = prepare_textdata_js($fd->textlc);
        echo "<script>window.opener.do_ajax_edit_impr_text({$fa}, {$textlc_js});</script>";
    } else {
        $hex = strToClassName(prepare_textdata($_REQUEST["WoTextLC"]));
        change_term_display($wid, $fd->translation, $hex);
    }

    pageend();
}


function get_term_and_lang($wid)
{
  $sql = "SELECT WoText AS t, WoLgID AS lid FROM words WHERE WoID = {$wid}";
    if ($wid == '') {
        $tid = $_REQUEST["tid"];
        $ord = $_REQUEST["ord"];
        $sql =  "SELECT Ti2Text AS t, Ti2LgID AS lid
        FROM textitems2 
        WHERE Ti2TxID = {$tid} AND Ti2WordCount = 1 AND Ti2Order = {$ord};";
    }
    $res = do_mysqli_query($sql);
    $record = mysqli_fetch_assoc($res);
    mysqli_free_result($res);
    if ($record) {
      $term = $record['t'];
      $lang = $record['lid'];
    } else {
      my_die("Cannot access Term and Language in edit_word.php");
    }
    return [ $term, $lang ];
}


function get_sentence_for_termlc($termlc) {
  $tid = $_REQUEST['tid'];
  $ord = $_REQUEST['ord'];
  $sql = "select Ti2SeID as value from textitems2
  where Ti2TxID = {$tid} and Ti2WordCount = 1 and Ti2Order = {$ord}";
  $seid = get_first_value($sql);
  $sent = getSentence($seid, $termlc, (int) getSettingWithDefault('set-term-sentence-count'));
  return repl_tab_nl($sent[1]);
}


function augment_formdata_for_updates($wid, &$formdata)
{
    $sql = "SELECT words.*,
ifnull(pw.WoID, 0) as ParentWoID,
ifnull(pw.WoTextLC, '') as ParentWoTextLC
FROM words
LEFT OUTER JOIN wordparents on wordparents.WpWoID = words.WoID
LEFT OUTER JOIN words AS pw on pw.WoID = wordparents.WpParentWoID
where words.WoID = {$wid}";
    $res = do_mysqli_query($sql);
    $record = mysqli_fetch_assoc($res);
    mysqli_free_result($res);
    if (! $record) {
      my_die("No matching record for {$wid} ??");
    }

    $status = $record['WoStatus'];
    if ($formdata->fromAnn == '' && $status >= 98) {
        $status = 1;
    }
    $sentence = repl_tab_nl($record['WoSentence']);
    if ($sentence == '' && isset($_REQUEST['tid']) && isset($_REQUEST['ord'])) {
        $sentence = get_sentence_for_termlc($formdata->termlc);
    }
    $transl = repl_tab_nl($record['WoTranslation']);
    if($transl == '*') {
        $transl='';
    }

    $formdata->wid = $wid;
    $formdata->translation = $transl;
    $formdata->tags = getWordTags($wid);
    $formdata->sentence = $sentence;
    $formdata->romanization = $record['WoRomanization'];
    $formdata->status = $status;
    $formdata->status_old = $record['WoStatus'];
    $formdata->parent_id = $record['ParentWoID'];
    $formdata->parent_text = $record['ParentWoTextLC'];
}


function handle_display_form() {
    // FORM
    // edit_word.php?tid=..&ord=..&wid=..

    $wid = getreq('wid');
    [ $term, $lang ] = get_term_and_lang($wid);
    $termlc = mb_strtolower($term, 'UTF-8');
    $scrdir = getScriptDirectionTag($lang);

    if ($wid == '') {
        $wid = get_first_value(
            "SELECT WoID AS value FROM words 
            WHERE WoLgID = " . $lang . " AND WoTextLC = " . convert_string_to_sqlsyntax($termlc)
        );
    }

    $formdata = new FormData();
    $formdata->fromAnn = getreq("fromAnn");
    $formdata->lang = $lang;
    $formdata->term = $term;
    $formdata->termlc = $termlc;
    $formdata->scrdir = $scrdir;

    $new = (isset($wid) == false);

    $titletext = ($new ? "New Term" : "Edit Term") . ": " . tohtml($term);
    pagestart_nobody($titletext);

    if ($new) {
        $formdata->tags = getWordTags(0);
        $formdata->sentence = get_sentence_for_termlc($termlc);
        $formdata->status = 1;
        $formdata->status_old = 1;

        show_form($formdata, "New Term", "Save");
        
    } else {
        augment_formdata_for_updates($wid, $formdata);
        show_form($formdata, "Edit Term", "Change");
    }

    pageend();
}


if (isset($_REQUEST['op'])) {
  handle_save_or_update();
} else {
  handle_display_form();
}


?>
