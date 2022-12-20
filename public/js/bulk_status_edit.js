/**
 * On the reading pane, the user can shift-click words to add them to
 * a list of words to be "bulk edited."  When done, the list is
 * processed via an ajax call.
 */


/**
 * The list of shift-clicked words.
 */
let shift_clicked_elements = [];

/**
 * Deactivate the bulk update button when the list is empty.
 */
function deactivate_bulk_update_button() {
  const btn = $('#bulkStatusUpdateBtn');
  if (btn.length) {
    btn.prop('disabled', (shift_clicked_elements.length == 0));
  }
}


/**
 * Add/remove a word to the list of clicked words.
 */
function add_shift_clicked_element(el) {
  // If element is already in the array, remove it; otherwise, it's a
  // new element.
  const elid = el.attr('id');
  // console.log(`clicked el: ${elid}`);

  const existing_index = shift_clicked_elements.findIndex(e => e.attr('id') == elid);
  if (existing_index != -1) {
    // console.log('... already in list, removing');
    el.removeClass('shiftClicked');
    shift_clicked_elements.splice(existing_index, 1);
  }
  else {
    // console.log('... not in list, adding');
    el.addClass('shiftClicked');
    shift_clicked_elements.push(el);
  }

  deactivate_bulk_update_button();
}

/**
 * Remove words from the list and update the UI.
 */
function clear_shift_clicked_elements() {
  // console.log(`clearing current list`);
  shift_clicked_elements.forEach(el => el.removeClass('shiftClicked'));
  shift_clicked_elements = [];
  deactivate_bulk_update_button();
}


/**
 * Show form for bulk status setting.
 */
function open_bulk_status_popup() {
  n = shift_clicked_elements.length;
  if (n == 0) {
    console.log('odd ... Should never reach open_bulk_status_popup');
    return;
  }

  function make_status_link(newstat) {
    const t = getStatusName(newstat);
    const abbr = getStatusAbbr(newstat);
    return `<span class="click"
      onclick="set_status_for_shift_clicked_elements(${newstat})"
      title="${t}">[${abbr}]</span>`;
  }

  status_links = [1, 2, 3, 4, 5, 99, 98].
    map(make_status_link).
    join('&nbsp;');

  const overlib_body = `<p>Click the new status:</p>
<div>
${status_links}
</div>
<br />`;
  return overlib(overlib_body, CAPTION, `Status update (${n} terms)`);
}


/**
 * Update all shift-clicked terms to the given status.
 */
function set_status_for_shift_clicked_elements(newStatus) {

  // Not bothering to confirm for now.
  /*
  const answer = confirm(`${shift_clicked_elements.length} terms will be affect. Continue?`);
  if (! answer) {
    console.log('quitting status update');
    return;
  }
  */

  const wids = shift_clicked_elements.
        filter(e => e.attr('data_wid')).
        map((e) => ({'wid': e.attr('data_wid'), 'text': e.text()}));
  const unknowns = shift_clicked_elements.
        filter(e => !e.attr('data_wid')).
        map((e) => ({ 'pos': e.attr('data_pos'), 'ord': e.attr('data_order'), 'text': e.text() }));
  // console.log(`wids: ${JSON.stringify(wids)}`);
  // console.log(`unknowns: ${JSON.stringify(unknowns)}`);

  $.ajax({
    url: 'inc/ajax_bulk_status_update.php',
    type: 'post',
    data: { status: newStatus, terms: wids, newterms: unknowns },
    dataType: 'JSON',
    success: function(response) {
      // console.log('success, ajax_bulk_status_update');
      update_ui_for_bulk_status_changes(newStatus);
      cClick();
      clear_shift_clicked_elements();
    },
    error: function(response, status, err) {
      const msg = {
        response: response,
        status: status,
        error: err
      };
      console.log(`failed: ${JSON.stringify(msg, null, 2)}`);
    }
  });

}

/**
 * post update ajax call, fix the UI.
 */
function update_ui_for_bulk_status_changes(newStatus) {
  const newClass = `status${newStatus}`;
  shift_clicked_elements.forEach(function (e) {
    ltext = e.text().toLowerCase();
    // console.log(`updating words with text = ${ltext}`);
    matches = $('span.word').toArray().filter(el => $(el).text().toLowerCase() == ltext);
    matches.forEach(function (m) {
      $(m).removeClass('status98 status99 status1 status2 status3 status4 status5 shiftClicked')
        .addClass(newClass)
        .attr('data_status',`${newStatus}`);
    });
  });
}
