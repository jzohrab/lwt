<?php


/***
Manage archived texts

Call: edit_archivedtexts.php?....
      ... markaction=[opcode] ... do actions on marked texts
      ... del=[textid] ... do delete
      ... unarch=[textid] ... do unarchive
      ... op=Change ... do update
      ... chg=[textid] ... display edit screen 
      ... filterlang=[langid] ... language filter 
      ... sort=[sortcode] ... sort 
      ... page=[pageno] ... page  
      ... query=[titlefilter] ... title filter   
 */

require_once 'inc/session_utility.php';


function get_multiplearchivedtextactions_selectoptions(): string 
{
    $r = "<option value=\"\" selected=\"selected\">[Choose...]</option>";
    $r .= "<option value=\"unarch\">Unarchive Marked Texts</option>";
    $r .= "<option value=\"del\">Delete Marked Texts</option>";
    return $r;
}

// -------------------------------------------------------------


$currentlang = validateLang(processDBParam("filterlang", 'currentlanguage', '', 0));
$currentsort = processDBParam("sort", 'currentarchivesort', '1', 1);

$currentpage = processSessParam("page", "currentarchivepage", '1', 1);
$currentquery = processSessParam("query", "currentarchivequery", '', 0);
$currentquerymode = processSessParam(
    "query_mode", "currentarchivequerymode", 'title,text', 0
);
$currentregexmode = getSettingWithDefault("set-regex-mode");

$currenttag1 = validateTextTag(
    processSessParam("tag1", "currenttexttag1", '', 0), 
    $currentlang
);
$currenttag2 = validateTextTag(
    processSessParam("tag2", "currenttexttag2", '', 0), 
    $currentlang
);
$currenttag12 = processSessParam("tag12", "currenttexttag12", '', 0);


$wh_lang = ($currentlang != '') ? (' and TxLgID=' . $currentlang) : '';
$wh_query = $currentregexmode . 'LIKE ' .  
convert_string_to_sqlsyntax(
    ($currentregexmode == '') ? 
    str_replace("*", "%", mb_strtolower($currentquery, 'UTF-8')) : 
    $currentquery
);
switch ($currentquerymode) {
case 'title,text':
    $wh_query=' and (TxTitle ' . $wh_query . ' or TxText ' . $wh_query . ')';
    break;
case 'title':
    $wh_query=' and (TxTitle ' . $wh_query . ')';
    break;
case 'text':
    $wh_query=' and (TxText ' . $wh_query . ')';
    break;
}
if ($currentquery!=='') {
    if ($currentregexmode!=='') {
        if (@mysqli_query(
            $GLOBALS["DBCONNECTION"], 
            'select "test" rlike ' . convert_string_to_sqlsyntax($currentquery)
            ) === false) {
            $currentquery='';
            $wh_query = '';
            unset($_SESSION['currentwordquery']);
            if(isset($_REQUEST['query'])) { 
                echo '<p id="hide3" style="color:red;text-align:center;">' + 
                '+++ Warning: Invalid Search +++</p>'; 
            }
        }
    }
} else { 
    $wh_query = ''; 
}

$wh_tag1 = null;
$wh_tag2 = null;
if ($currenttag1 == '' && $currenttag2 == '') {
    $wh_tag = ''; 
} else {
    if ($currenttag1 != '') {
        if ($currenttag1 == -1) {
            $wh_tag1 = "group_concat(TtT2ID) IS NULL"; 
        } else {
            $wh_tag1 = "concat('/',group_concat(TtT2ID separator '/'),'/') like '%/" . 
            $currenttag1 . "/%'"; 
        }
    } 
    if ($currenttag2 != '') {
        if ($currenttag2 == -1) {
            $wh_tag2 = "group_concat(TtT2ID) IS NULL"; 
        } else {
            $wh_tag2 = "concat('/',group_concat(TtT2ID separator '/'),'/') like '%/" . 
            $currenttag2 . "/%'"; 
        }
    } 
    if ($currenttag1 != '' && $currenttag2 == '') {    
        $wh_tag = " having (" . $wh_tag1 . ') '; 
    } elseif ($currenttag2 != '' && $currenttag1 == '') {    
        $wh_tag = " having (" . $wh_tag2 . ') ';
    } else {
        $wh_tag = " having ((" . $wh_tag1 . ($currenttag12 ? ') AND (' : ') OR (') . 
        $wh_tag2 . ')) '; 
    }
}

$no_pagestart = 
    (getreq('markaction') == 'deltag');
if (!$no_pagestart) {
    pagestart('My ' . getLanguage($currentlang) . ' Text Archive', true);
}

$message = '';

// MARK ACTIONS

$id = null;
if (isset($_REQUEST['markaction'])) {
    $markaction = $_REQUEST['markaction'];
    $actiondata = getreq('data');
    $message = "Multiple Actions: 0";
    if (isset($_REQUEST['marked'])) {
        if (is_array($_REQUEST['marked'])) {
            $l = count($_REQUEST['marked']);
            if ($l > 0) {
                $list = "(" . $_REQUEST['marked'][0];
                for ($i=1; $i<$l; $i++) { 
                    $list .= "," . $_REQUEST['marked'][$i]; 
                }
                $list .= ")";
                
                if ($markaction == 'del') {
                    // handle deleting texts.
                    // TODO
                } elseif ($markaction == 'unarch') {
                    $count = 0;
                    // handle unarchiving for $list.
                    // TODO
                    $message = 'Unarchived Text(s): ' . $count;
                } 
                                                
            }
        }
    }
}  // end markactions


    // DISPLAY

    echo error_message_with_hide($message, 0);

    $sql = 'select count(*) as value from texts where TxArchived is true';
    $recno = (int)get_first_value($sql);

    $maxperpage = (int)getSettingWithDefault('set-archivedtexts-per-page');

    $pages = $recno == 0 ? 0 : intval(($recno-1) / $maxperpage) + 1;
    
    if ($currentpage < 1) { 
        $currentpage = 1; 
    }
    if ($currentpage > $pages) { 
        $currentpage = $pages; 
    }
    $limit = 'LIMIT ' . (($currentpage-1) * $maxperpage) . ',' . $maxperpage;

    $sorts = array('TxTitle','TxID desc','TxID');
    $lsorts = count($sorts);
    if ($currentsort < 1) { 
        $currentsort = 1; 
    }
    if ($currentsort > $lsorts) { 
        $currentsort = $lsorts; 
    }
    
    ?>


<form name="form1" action="#" onsubmit="document.form1.querybutton.click(); return false;">
<table class="tab1" cellspacing="0" cellpadding="5">
    <tr>
        <th class="th1" colspan="4">Filter <img src="icn/funnel.png" title="Filter" alt="Filter" />&nbsp;
            <input type="button" value="Reset All" onclick="resetAll('edit_archivedtexts.php');" />
        </th>
    </tr>
    <tr>
        <td class="td1 center" colspan="2">
            Language:
            <select name="filterlang" onchange="{setLang(document.form1.filterlang,'edit_archivedtexts.php');}">
                <?php echo get_languages_selectoptions($currentlang, '[Filter off]'); ?>
            </select>
        </td>
        <td class="td1 center" colspan="2">
            <select name="query_mode" onchange="{val=document.form1.query.value;mode=document.form1.query_mode.value; location.href='edit_archivedtexts.php?page=1&amp;query=' + val + '&amp;query_mode=' + mode;}">
                <option value="title,text"<?php 
                if($currentquerymode=="title,text") { 
                    echo ' selected="selected"'; 
                } ?>>Title &amp; Text</option>
                <option disabled="disabled">------------</option>
                <option value="title"<?php 
                if($currentquerymode=="title") { 
                    echo ' selected="selected"'; 
                } ?>>Title</option>
                <option value="text"<?php 
                if($currentquerymode=="text") { 
                    echo ' selected="selected"'; 
                } ?>>Text</option>
            </select>
            <?php
            if($currentregexmode=='') { 
                echo '<span style="vertical-align: middle"> (Wildc.=*): </span>'; 
            }
            elseif($currentregexmode=='r') { 
                echo '<span style="vertical-align: middle"> RegEx Mode: </span>';
            } else { 
                echo '<span style="vertical-align: middle"> RegEx(CS) Mode: </span>'; 
            }?>
            <input type="text" name="query" value="<?php echo tohtml($currentquery); ?>" maxlength="50" size="15" />&nbsp;
            <input type="button" name="querybutton" value="Filter" onclick="{val=document.form1.query.value;val=encodeURIComponent(val); location.href='edit_archivedtexts.php?page=1&amp;query=' + val;}" />&nbsp;
            <input type="button" value="Clear" onclick="{location.href='edit_archivedtexts.php?page=1&amp;query=';}" />
        </td>
    </tr>
    <tr>
        <td class="td1 center" colspan="2" nowrap="nowrap">
            Tag #1:
            <select name="tag1" onchange="{val=document.form1.tag1.options[document.form1.tag1.selectedIndex].value; location.href='archivedtexts.php?page=1&amp;tag1=' + val;}"><?php echo get_texttag_selectoptions($currenttag1, $currentlang); ?></select>
        </td>
        <td class="td1 center" nowrap="nowrap">
            Tag #1 .. 
            <select name="tag12" onchange="{val=document.form1.tag12.options[document.form1.tag12.selectedIndex].value; location.href='archivedtexts.php?page=1&amp;tag12=' + val;}"><?php echo get_andor_selectoptions($currenttag12); ?></select> .. Tag #2
        </td>
        <td class="td1 center" nowrap="nowrap">
            Tag #2:
            <select name="tag2" onchange="{val=document.form1.tag2.options[document.form1.tag2.selectedIndex].value; location.href='archivedtexts.php?page=1&amp;tag2=' + val;}"><?php echo get_texttag_selectoptions($currenttag2, $currentlang); ?></select>
        </td>
    </tr>
        <?php if($recno > 0) { ?>
    <tr>
    <th class="th1" colspan="2" nowrap="nowrap">
            <?php echo $recno; ?> Text<?php echo ($recno==1?'':'s'); ?>
    </th>
    <th class="th1" colspan="1" nowrap="nowrap">
            <?php makePager($currentpage, $pages, 'edit_archivedtexts.php', 'form1'); ?>
    </th>
    <th class="th1" nowrap="nowrap">
    Sort Order:
    <select name="sort" onchange="{val=document.form1.sort.options[document.form1.sort.selectedIndex].value; location.href='edit_archivedtexts.php?page=1&amp;sort=' + val;}"><?php echo get_textssort_selectoptions($currentsort); ?></select>
    </th></tr>
            <?php 
        } ?>
</table>
</form>

    <?php
    if ($recno==0) {
        ?>
<p>No archived texts found.</p>
        <?php
    } else {
        ?>
<form name="form2" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
<input type="hidden" name="data" value="" />
<table class="tab1" cellspacing="0" cellpadding="5">
    <tr>
        <th class="th1" colspan="2">
            Multi Actions 
            <img src="icn/lightning.png" title="Multi Actions" alt="Multi Actions" />
        </th>
    </tr>
    <tr>
        <td class="td1 center">
            <input type="button" value="Mark All" onclick="selectToggle(true,'form2');" />
            <input type="button" value="Mark None" onclick="selectToggle(false,'form2');" />
        </td>
        <td class="td1 center">
            Marked Texts:&nbsp; 
            <select name="markaction" id="markaction" disabled="disabled" onchange="multiActionGo(document.form2, document.form2.markaction);"><?php echo get_multiplearchivedtextactions_selectoptions(); ?></select>
        </td>
    </tr>
</table>

<table class="sortable tab1" cellspacing="0" cellpadding="5">
    <tr>
        <th class="th1 sorttable_nosort">Mark</th>
        <?php if ($currentlang == '') { 
            echo '<th class="th1 clickable">Lang.</th>'; 
        } ?>
        <th class="th1 clickable">
            Title [Tags] / Audio:&nbsp;
            <img src="<?php print_file_path('icn/speaker-volume.png'); ?>" title="With Audio" alt="With Audio" />, Src.Link:&nbsp;
            <img src="<?php print_file_path('icn/chain.png'); ?>" title="Source Link available" alt="Source Link available" />, Ann.Text:&nbsp;
            <img src="icn/tick.png" title="Annotated Text available" alt="Annotated Text available" />
        </th>
    </tr>
    <?php

        $sql = "SELECT TxID, TxTitle, LgName, TxAudioURI, TxSourceURI, 
        length(TxAnnotatedText) as annotlen, 
        IF(
            COUNT(T2Text)=0, 
            '', 
            CONCAT(
                '[',group_concat(DISTINCT T2Text ORDER BY T2Text separator ', '),']'
            )
        ) AS taglist
        from
        texts
        inner join languages on LgID = TxLgID
        left JOIN texttags ON TxID = TtTxID
        left join tags2 on T2ID = TtT2ID
        where TxArchived = true
        $wh_lang$wh_query 
        group by TxID $wh_tag 
        order by {$sorts[$currentsort-1]} 
        $limit";

        $res = do_mysqli_query($sql);
        while ($record = mysqli_fetch_assoc($res)) {
            echo '<tr>
            <td class="td1 center">
            <a name="rec' . $record['TxID'] . '">
            <input name="marked[]" class="markcheck" type="checkbox" value="' . 
            $record['TxID'] . '" ' . checkTest($record['TxID'], 'marked') . 
            ' /></a></td>';
            echo '<td class="td1 center">' . tohtml($record['TxTitle']) . 
            ' <span class="smallgray2">' . tohtml($record['taglist']) . '</span> &nbsp;';
            if (isset($record['TxAudioURI'])) {
                echo '<img src="' . get_file_path('icn/speaker-volume.png') . 
                '" title="With Audio" alt="With Audio" />';
            } else {
                echo '';
            }
            if (isset($record['TxSourceURI'])) {
                echo ' <a href="' . $record['TxSourceURI'] . '" target="_blank">
                <img src="'.get_file_path('icn/chain.png') . 
                '" title="Link to Text Source" alt="Link to Text Source" /></a>';
            }
            if ($record['annotlen']) {
                echo ' <img src="icn/tick.png" title="Annotated Text available" ' . 
                'alt="Annotated Text available" />';
            } 
            echo '</td>';
            echo '</tr>';
        }
        mysqli_free_result($res);
        ?>
</table>

<?php if($pages > 1) { ?>
<table class="tab1" cellspacing="0" cellpadding="5">
    <tr>
        <th class="th1" nowrap="nowrap">
            <?php echo $recno; ?> Text<?php echo ($recno==1?'':'s'); ?>
        </th>
        <th class="th1" nowrap="nowrap">
            <?php makePager($currentpage, $pages, 'archivedtexts.php', 'form2'); ?>
        </th>
    </tr>
</table>
</form>
<?php 
} 

}



pageend();

?>
