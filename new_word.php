<?php

/**************************************************************
Call: new_word.php?...
            ... text=[textid]&lang=[langid] ... new term input  
            ... op=Save ... do the insert
New word, created while reading or testing
 ***************************************************************/

require_once 'inc/session_utility.php';
require_once 'inc/simterms.php';

// INSERT

class FormData
{
  public $fromAnn = '';
  public $lang;
  public $wid = 0;
  public $term;
  public $termlc;
  public $scrdir;
  public $translation = '';
  public $tags;
  public $romanization = '';
  public $sentence = '';
  public $status = 1;
  public $status_old = 1;
  public $status_radiooptions;
}


function show_form($formdata, $title = "New Term:", $operation = "Save")
{
?>
<script type="text/javascript">
$(document).ready(ask_before_exiting);
$(window).on('beforeunload',function() {
  setTimeout(function() {window.parent.frames['ru'].location.href = 'empty.html';}, 0);
});
</script>

<form name="wordform" class="validate" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
<input type="hidden" name="WoID" id="langfield" value="<?php echo $formdata->wid; ?>" />
<input type="hidden" name="WoLgID" id="langfield" value="<?php echo $formdata->lang; ?>" />
<input type="hidden" name="fromAnn" value="<?php echo $formdata->fromAnn; ?>" />
<input type="hidden" name="WoOldStatus" value="<?php echo $formdata->status_old; ?>" />
<input type="hidden" name="WoTextLC" value="<?php echo tohtml($formdata->termlc); ?>" />
<input type="hidden" name="tid" value="<?php echo getreq('tid'); ?>" />
<input type="hidden" name="ord" value="<?php echo getreq('ord'); ?>" />

<table class="tab2" cellspacing="0" cellpadding="5">
  <tr title="Only change uppercase/lowercase!">
    <td class="td1 right"><b><?php echo $title; ?></b></td>
    <td class="td1">
      <input <?php echo $formdata->scrdir; ?> class="notempty checkoutsidebmp" data_info="New Term" type="text" name="WoText" id="wordfield" value="<?php echo tohtml($formdata->term); ?>" maxlength="250" size="35" />
      <img src="icn/status-busy.png" title="Field must not be empty" alt="Field must not be empty" />
    </td>
  </tr>
  <?php print_similar_terms_tabrow(); ?>
  <tr>
    <td class="td1 right">Translation:</td>
    <td class="td1">
      <textarea name="WoTranslation" class="setfocus textarea-noreturn checklength checkoutsidebmp" data_maxlength="500" data_info="Translation" cols="35" rows="3"><?php echo tohtml($formdata->translation); ?></textarea>
    </td>
  </tr>
  <tr>
    <td class="td1 right">Tags:</td>
    <td class="td1">
      <?php echo getWordTags($formdata->wid); ?>
    </td>
  </tr>
  <tr>
    <td class="td1 right">Romaniz.:</td>
    <td class="td1">
      <input type="text" class="checkoutsidebmp" data_info="Romanization" name="WoRomanization" value="<?php echo tohtml($formdata->romanization); ?>" maxlength="100" size="35" />
    </td>
  </tr>
  <tr>
    <td class="td1 right">Sentence<br />Term in {...}:</td>
    <td class="td1">
      <textarea <?php echo $formdata->scrdir; ?> name="WoSentence" class="textarea-noreturn checklength checkoutsidebmp" data_maxlength="1000" data_info="Sentence" cols="35" rows="3"><?php echo tohtml($formdata->sentence); ?></textarea>
    </td>
  </tr>
  <tr>
    <td class="td1 right">Status:</td>
    <td class="td1">
       <?php echo get_wordstatus_radiooptions($formdata->status); ?>
    </td>
  </tr>
  <tr>
    <td class="td1 right" colspan="2">
       <?php echo createDictLinksInEditWin($formdata->lang, $formdata->term, 'document.forms[0].WoSentence', isset($_GET['nodict'])?0:1); ?>
 &nbsp; &nbsp; &nbsp; <input type="submit" name="op" value="<?php echo $operation; ?>" />
    </td>
  </tr>
</table>

</form>

<div id="exsent">
  <span class="click" onclick="do_ajax_show_sentences(<?php echo $formdata->lang; ?>, <?php echo prepare_textdata_js($formdata->termlc) . ', ' . prepare_textdata_js("document.forms['wordform'].WoSentence") . ', 0'; ?>);">
  <img src="icn/sticky-notes-stack.png" title="Show Sentences" alt="Show Sentences" /> Show Sentences</span>
</div>

     <?php
}


if (isset($_REQUEST['op'])) {
    
    if ($_REQUEST['op'] == 'Save') {

        $text = trim(prepare_textdata($_REQUEST["WoText"]));
        $textlc = mb_strtolower($text, 'UTF-8');
        $translation_raw = repl_tab_nl(getreq("WoTranslation"));
        if ($translation_raw == '' ) { 
            $translation = '*'; 
        }
        else { 
            $translation = $translation_raw; 
        }
    
        $titletext = "New Term: " . tohtml($textlc);
        pagestart_nobody($titletext);
        echo '<h4><span class="bigger">' . $titletext . '</span></h4>';
    
        $message = runsql(
            'insert into words (WoLgID, WoTextLC, WoText, ' .
            'WoStatus, WoTranslation, WoSentence, WoRomanization, WoStatusChanged,' .  make_score_random_insert_update('iv') . ') values( ' . 
            $_REQUEST["WoLgID"] . ', ' .
            convert_string_to_sqlsyntax($textlc) . ', ' .
            convert_string_to_sqlsyntax($text) . ', ' .
            $_REQUEST["WoStatus"] . ', ' .
            convert_string_to_sqlsyntax($translation) . ', ' .
            convert_string_to_sqlsyntax(repl_tab_nl($_REQUEST["WoSentence"])) . ', ' .
            convert_string_to_sqlsyntax($_REQUEST["WoRomanization"]) . ', NOW(), ' .  
            make_score_random_insert_update('id') . ')', "Term saved", $sqlerrdie = false
        );

        if (substr($message, 0, 22) == 'Error: Duplicate entry') {
            $message = 'Error: <b>Duplicate entry for <i>' . $textlc . '</i></b><br /><br /><input type="button" value="&lt;&lt; Back" onclick="history.back();" />';
        }
        
        $wid = get_last_key();

        saveWordTags($wid);
        init_word_count();
        //        $showAll = getSettingZeroOrOne('showallwords',1);
        ?>

   <p><?php echo $message; ?></p>

        <?php
        if (substr($message, 0, 5) != 'Error') {?>
<script type="text/javascript">
    //<![CDATA[
    var context = window.parent.document;
    var woid = <?php echo prepare_textdata_js($wid); ?>;
    var status = <?php echo prepare_textdata_js($_REQUEST["WoStatus"]); ?>;
    var trans = <?php echo prepare_textdata_js($translation . getWordTagList($wid, ' ', 1, 0)); ?>;
    var roman = <?php echo prepare_textdata_js($_REQUEST["WoRomanization"]); ?>;
    var title = window.parent.JQ_TOOLTIP?'':make_tooltip(<?php echo prepare_textdata_js($_REQUEST["WoText"]); ?>,trans,roman,status);
    //]]>
</script>
            <?php
            $len = get_first_value('select WoWordCount as value from words where WoID = ' . $wid);
            if ($len > 1) {
                insertExpressions($textlc, $_REQUEST["WoLgID"], $wid, $len, 0);
            } else if ($len == 1) {
                $hex = strToClassName(prepare_textdata($textlc));
                do_mysqli_query(
                    'UPDATE textitems2 SET Ti2WoID = ' . $wid . ' 
                    WHERE Ti2LgID = ' . $_REQUEST["WoLgID"] . ' AND Ti2TextLC = ' . convert_string_to_sqlsyntax_notrim_nonull($textlc)
                );
                ?>
<script type="text/javascript">
    //<![CDATA[
    if($('.TERM<?php echo $hex; ?>', context).length){
        $('.TERM<?php echo $hex; ?>', context)
        .removeClass('status0')
        .addClass('word' + woid + ' ' + 'status' + status)
        .attr('data_trans',trans)
        .attr('data_rom',roman)
        .attr('data_status',status)
        .attr('data_wid',woid)
        .attr('title',title);
        $('#learnstatus', context).html('<?php echo addslashes(texttodocount2($_REQUEST['tid'])); ?>');
    }
    //]]>
</script>
                <?php
                flush();
            } ?>
<script type="text/javascript">
    window.parent.getElementById('frame-l').focus();
    window.parent.setTimeout('cClick()', 100);
</script>
            <?php
        } // (substr($message,0,5) != 'Error')

    } // $_REQUEST['op'] == 'Save'

} // if (isset($_REQUEST['op']))


else {
    // Show form
    $lang = (int)getreq('lang');
    $scrdir = getScriptDirectionTag($lang);

    $formdata = new FormData();
    $formdata->lang = $lang;
    $formdata->scrdir = $scrdir;

    pagestart_nobody('');
    show_form($formdata, "New Term:", "Save");

}

pageend();

?>
