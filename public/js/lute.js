/* Lute js.  Moving things over as needed from existing js files. */

/** 
 * Prepare the interaction events with the text.
 * 
 * @since 2.0.3-fork
 */
function prepareTextInteractions(textid) {
  $('.word').on('click', word_clicked);
  $('.mword').on('click', word_clicked);

  /*
    $('#thetext').on('selectstart','span',false).on(
        'mousedown','.wsty',
        {annotation: ANNOTATIONS_MODE}, 
        mword_drag_n_drop_select);
    $('#thetext').on('click', '.mword', mword_click_event_do_text_text);
    $('.word').on('dblclick', word_dblclick_event_do_text_text);
    $('#thetext').on('dblclick', '.mword', word_dblclick_event_do_text_text);
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
