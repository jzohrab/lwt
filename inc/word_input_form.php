<?php

require_once __DIR__ . '/database_connect.php';

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
  public $parent_id = 0;
  public $parent_text = '';
}


function exec_statement($stmt) {
  if (!$stmt) {
    throw new Exception($DBCONNECTION->error);
  }
  if (!$stmt->execute()) {
    throw new Exception($stmt->error);
  }
}


function set_parent($f) {
  if ($f->parent_id == 0) {
    return;
  }
  $sql = "INSERT INTO wordparents (WpWoID, WpParentWoID) VALUES (?, ?)";
  global $DBCONNECTION;
  $stmt = $DBCONNECTION->prepare($sql);
  $stmt->bind_param("ii", $f->wid, $f->parent_id);
  exec_statement($stmt);
}


/**
 * Insert a new word to the database, or throw exception.
 *
 * @param FormData $formdata
 *
 * @return int  New WoID inserted
 */
function save_new_formdata($f) {

  if ($f->parent_id == 0 && $f->parent_text != '') {
    $p = new FormData();
    $p->termlc = strtolower($f->parent_text);
    $p->term = $f->parent_text;
    $p->translation = $f->translation;
    $p->lang = $f->lang;
    $p->status = $f->status;

    $pid = save_new_formdata($p);

    $f->translation = '*';
    $f->parent_id = $pid;
  }

  // Yuck.
  $testfields = make_score_random_insert_update('iv');
  $testscores = make_score_random_insert_update('id');

  $sql = "INSERT INTO words
(
WoTextLC, WoText, WoTranslation, WoSentence, WoRomanization,
WoLgID, WoStatus,
WoStatusChanged, WoWordCount, {$testfields}
)
VALUES
(
?, ?, ?, ?, ?,
?, ?,
NOW(), 1, {$testscores}
)";

  global $DBCONNECTION;
  $stmt = $DBCONNECTION->prepare($sql);
  $stmt->bind_param("sssssii",
                    $f->termlc,
                    $f->term,
                    $f->translation,
                    $f->sentence,
                    $f->romanization,
                    $f->lang,
                    $f->status
                    );
  exec_statement($stmt);

  $f->wid = $stmt->insert_id;

  set_parent($f);

  return $f->wid;
}

/**
 * Update existing word.
 *
 * @param FormData $formdata
 *
 * @return int  Updated WoID
 */
function update_formdata($f) {

  // Yuck.
  $testfields = make_score_random_insert_update('u');

  $statusChanged = '';
  if ($f->status != $f->status_old) {
    $statusChanged = "WoStatusChanged = NOW(),";
  }

  $sql = "UPDATE words
SET {$statusChanged}
WoText = ?,
WoTextLC = ?,
WoTranslation = ?,
WoSentence = ?,
WoRomanization = ?,
WoStatus = ?,
{$testfields}
WHERE WoID = ?";
  
  global $DBCONNECTION;
  $stmt = $DBCONNECTION->prepare($sql);
  $stmt->bind_param("sssssii",
                    $f->term,
                    $f->termlc,
                    $f->translation,
                    $f->sentence,
                    $f->romanization,
                    $f->status,
                    $f->wid
                    );
  exec_statement($stmt);

  $parentsql = "DELETE FROM wordparents WHERE WpWoID = {$f->wid}";
  do_mysqli_query($parentsql);
  set_parent($f);

  return $f->wid;
}


/**
 * Print HTML form with FormData.
 */
function show_form($formdata, $title = "New Term:", $operation = "Save")
{
?>
<script type="text/javascript">
function set_parent_fields(event, ui) {
  $('#autocomplete_parent_text').val(ui.item.label);
  $('#autocomplete_parent_id').val(ui.item.value);
  return false;
}
  
function set_up_parent_autocomplete() {
  $("#autocomplete_parent_text").autocomplete({
    source: function(request, response) {
      $('#autocomplete_parent_id').val(0);
      $.ajax({
        url: 'inc/ajax_get_words_matching.php',
        type: 'POST',
        data: { word: request.term },
        dataType: 'json',
        success: function(data) {
          const arr = [];
          for (const [wordid, word] of Object.entries(data)) {
            const obj = { label: word, value: wordid };
            arr.push(obj);
          };
          response(arr, data);
        }
      })
    },
    select: set_parent_fields,
    focus: set_parent_fields,
    change: function(event,ui) {
      if (!ui.item) { $(this).val(''); }
    }
  });
}

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

  set_up_parent_autocomplete();
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
<input type="hidden" id="autocomplete_parent_id" name="WpParentWoID" value="<?php echo $formdata->parent_id; ?>" />

<table class="tab2" cellspacing="0" cellpadding="5">
  <tr title="Only change uppercase/lowercase!">
    <td class="td1 right"><b><?php echo $title; ?></b></td>
    <td class="td1">
      <input <?php echo $formdata->scrdir; ?> class="notempty checkoutsidebmp" data_info="New Term" type="text" name="WoText" id="wordfield" value="<?php echo tohtml($formdata->term); ?>" maxlength="250" size="35" />
      <img src="icn/status-busy.png" title="Field must not be empty" alt="Field must not be empty" />
    </td>
  <tr>
    <td class="td1 right">Parent term:</td>
    <td class="td1">
      <input <?php echo $formdata->scrdir; ?> data_info="parent" type="text" name="ParentText" id="autocomplete_parent_text" value="<?php echo tohtml($formdata->parent_text); ?>" maxlength="250" size="35" />
    </td>
  </tr>
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
    <td class="td1 right">Sentence:</td>
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


function cleanreq($s) {
  return trim(prepare_textdata($_REQUEST[$s]));
}


/**
 * Gets the data from posted shown_form
 */
function load_formdata_from_request(): FormData {
  $f = new FormData();

  $translation = repl_tab_nl(getreq("WoTranslation"));
  if ($translation == '' ) {
    $translation = '*';
  }

  $f->lang = $_REQUEST["WoLgID"];
  $f->wid = intval(getreq("WoID", 0));
  $f->term = cleanreq("WoText");
  $f->termlc = cleanreq("WoTextLC");
  $f->translation = $translation;
  $f->romanization = $_REQUEST["WoRomanization"];
  $f->sentence = repl_tab_nl($_REQUEST["WoSentence"]);
  $f->status = (int) $_REQUEST["WoStatus"];
  $f->status_old = $_REQUEST["WoOldStatus"];
  $f->parent_id = intval(getreq("WpParentWoID", 0));
  $f->parent_text = cleanreq("ParentText");

  // Not used during db updates:
  // $f->fromAnn = '';
  // $f->scrdir;
  // $f->tags;
  // $f->status_radiooptions;

  return $f;
}


?>
