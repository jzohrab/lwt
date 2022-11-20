<?php

/**
 * Input form for words.
 */


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

// Set focus to correct field.
$(window).on('load', function() {
  const wordfield = $('#wordfield');
  const transfield = $('#translationfield');
  if (wordfield.val()) {
    transfield.focus();
  }
  else {
    wordfield.focus();
  }
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
      <textarea name="WoTranslation" id="translationfield" class="textarea-noreturn checklength checkoutsidebmp" data_maxlength="500" data_info="Translation" cols="35" rows="3"><?php echo tohtml($formdata->translation); ?></textarea>
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

?>