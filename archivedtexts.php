<?php

/**
 * List archived texts.  Currently un-archiving is not supported.
 */

require_once 'inc/session_utility.php';
require_once 'inc/database_connect.php';

/**
 * Just list the archived texts.
 * 
 * @return void
 */
function archived_texts_do_page()
{
  pagestart('Archived texts', true);

?>
<table class="sortable tab1" cellspacing="0" cellpadding="5">
<thead class="test_class_to_delete">
    <tr>
        <th class="th1 clickable" style="width: 20%;">Language</th>
        <th class="th1 clickable">Title</th>
    </tr>
</thead>
<tbody>

<?php

  $sql = "select LgName, TxTitle
from texts
inner join languages on LgID = TxLgID
where TxArchived is true";
  $res = do_mysqli_query($sql);
    // MARK ACTIONS

  while ($r = mysqli_fetch_assoc($res)) {
    $l = $r['LgName'];
    $t = $r['TxTitle'];
    $row = "<tr><td>{$l}</td><td>{$t}</td></tr>";
    echo $row;
  }

  mysqli_free_result($res);
?>

</tbody>

<?php

    pageend();
}

archived_texts_do_page();
?>
