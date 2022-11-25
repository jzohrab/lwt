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


function update_reading_highlights_for_terms_with_hex($hex) {
    ?>
    <script type="text/javascript">
    //<![CDATA[
    if($('.TERM<?php echo $hex; ?>', context).length) {
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
}


function save_form() {
        $fd = load_formdata_from_request();
        $wid = 0;
        $message = '';
        try {
          $wid = save_new_formdata($fd);
          $message = "Term saved";
        }
        catch (Exception $e) {
          $message = $e->getMessage();
        }

        $titletext = "New Term: " . tohtml($fd->termlc);
        pagestart_nobody($titletext);
        echo '<h4><span class="bigger">' . $titletext . '</span></h4>';

        if (strpos($message, 'uplicate entry') == 1) {
            $message = 'Error: <b>Duplicate entry for <i>' . $fd->termlc . '</i></b><br /><br /><input type="button" value="&lt;&lt; Back" onclick="history.back();" />';
        }

        ?>

   <p><?php echo $message; ?></p>

        <?php
        if (substr($message, 0, 5) != 'Error') {
?>
<script type="text/javascript">
    //<![CDATA[
    var context = window.parent.document;
    var woid = <?php echo prepare_textdata_js($wid); ?>;
    var status = <?php echo prepare_textdata_js($fd->status); ?>;
    var trans = <?php echo prepare_textdata_js($fd->translation . getWordTagList($wid, ' ', 1, 0)); ?>;
    var roman = <?php echo prepare_textdata_js($fd->romanization); ?>;
    var title = window.parent.JQ_TOOLTIP?'':make_tooltip(<?php echo prepare_textdata_js($fd->term); ?>,trans,roman,status);
    //]]>
</script>

<?php
            $len = get_first_value('select WoWordCount as value from words where WoID = ' . $wid);
            if ($len > 1) {
                insertExpressions($fd->termlc, $_REQUEST["WoLgID"], $wid, $len, 0);
            } else if ($len == 1) {
                $hex = strToClassName(prepare_textdata($fd->termlc));
                update_reading_highlights_for_terms_with_hex($hex);
                flush();
            }
?>
<script type="text/javascript">
    window.parent.getElementById('frame-l').focus();
    window.parent.setTimeout('cClick()', 100);
</script>
<?php
        } // (substr($message,0,5) != 'Error')

    }


/* MAIN ****************************/

if (getreq('op') == 'Save') {
  save_form();
}
else {
    // Show form
    $lang = (int)getreq('lang');
    $scrdir = getScriptDirectionTag($lang);

    $formdata = new FormData();
    $formdata->lang = $lang;
    $formdata->scrdir = $scrdir;

    pagestart_nobody('');
    show_form($formdata);
}

pageend();

?>
