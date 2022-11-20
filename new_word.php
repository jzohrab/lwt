<?php

/**************************************************************
Call: new_word.php?...
            ... text=[textid]&lang=[langid] ... new term input  
            ... op=Save ... do the insert
New word, created while reading or testing
 ***************************************************************/

require_once 'inc/session_utility.php';
require_once 'inc/simterms.php';
require_once 'inc/word_input_form.php';

// INSERT
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
