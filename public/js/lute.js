/* Lute js.  Moving things over as needed from existing js files. */

/** 
 * Prepare the interaction events with the text.
 * 
 * @since 2.0.3-fork
 */
function prepareTextInteractions(textid) {
  $('.word').on('click', word_clicked);
  $('.word').mousedown(select_started);
  $('.word').mouseup(select_ended);
  /*
    $(document).on('keydown', keydown_event_do_text_text);
    $('#thetext').hoverIntent(
        {
            over: word_hover_over, 
            out: word_hover_out, 
            interval: 150, 
            selector:".wsty,.mwsty"
        }
    );
*/
}


function word_clicked(e) {
  if (e.shiftKey) {
    add_shift_clicked_element($(this));
    return;
  }
  else {
    clear_shift_clicked_elements();
  }

  $('span.kwordmarked').removeClass('kwordmarked');
  $(this).addClass('kwordmarked');

  let av = (s) => $(this).attr(s);
  const url = 'edit_word.php?tid=' + av('tid') + '&ord=' + av('data_order') + '&wid=' + av('data_wid');
  alert(url);
  // TODO: showRightFrames
  
  return false;
}

var selection_start_el = null;

function select_started(e) {
  selection_start_el = $(this);
  console.log('started with ' + $(this).html());
}

function select_ended(e) {
  console.log('ended with ' + $(this).html());
  if (selection_start_el.attr('id') == $(this).attr('id')) {
    return;
  }

  if (selection_start_el.attr('seid') != $(this).attr('seid')) {
    alert("Selections cannot span sentences.");
    return;
  }

  const startord = parseInt(selection_start_el.attr('data_order'));
  const endord = parseInt($(this).attr('data_order'));
  const selected = $("span.word").filter(function() {
    const ord = $(this).attr("data_order");
    return ord >= startord && ord <= endord;
  });
  const text = selected.toArray().map((el) => $(el).text()).join(' ');
  console.log(text);

  if (text.length > 250) {
    alert(`Selections can be max length 250 chars ("${text}" is ${text.length} chars)`);
    return;
  }

  let av = (s) => selection_start_el.attr(s);
  const url = `edit_word.php?tid=${av('tid')}&ord=${av('data_order')}&wid=&text=${encodeURIComponent(text)}`;
  alert(url);
  // TODO: showRightFrames
}
