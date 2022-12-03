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
 *  ... tid=[textid]&ord=[textpos]&wid=&txt=[text] ... new multi-word term (overrides text at tid and ord)
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
    var oldstatus = <?= getreq("WoOldStatus", 0) ?>;
    var trans = <?php echo prepare_textdata_js($translation . getWordTagList($wid, ' ', 1, 0)); ?>;
    var roman = <?php echo prepare_textdata_js($_REQUEST["WoRomanization"]); ?>;
    var tooltiptitle;
    if (window.parent.document.getElementById('frame-l').JQ_TOOLTIP) {
        tooltiptitle = '';
    } else {
        tooltiptitle = make_tooltip(
            <?php echo prepare_textdata_js($_REQUEST["WoText"]); ?>, trans, roman, status
        );
    }

    var selector = '.word' + woid;
    <?php if ($_REQUEST['op'] == 'Save') { ?>
        selector = '.TERM<?= $hex ?>';
    <?php } ?>

    var termelement = $(selector, context);

    if (woid != 0 && woid != '') {
        termelement
            .addClass('word' + woid)
            .attr('data_wid', woid);
    }

    termelement
        .removeClass('status' + oldstatus)
        .addClass('status' + status)
        .attr('data_trans', trans)
        .attr('data_rom', roman)
        .attr('data_status', status)
        .attr('title', tooltiptitle);

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


function handle_display_form() {
    // FORM
    // edit_word.php?tid=..&ord=..&wid=..&txt=..
    $wid = getreq('wid', 0);
    $tid = getreq('tid', 0);
    $ord = getreq('ord', 0);
    $txt = getreq('txt', '');
    $formdata = load_formdata_from_db($wid, $tid, $ord, $txt);
    $formdata->autofocus = getreq('autofocus', 'true');

    pagestart_nobody("Term: " . tohtml($formdata->term));
    show_form($formdata);
    pageend();
}


if (isset($_REQUEST['op'])) {
  handle_save_or_update();
} else {
  handle_display_form();
}


?>
