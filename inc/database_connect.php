<?php

/**
 * \file
 * \brief Connects to the database and check its state.
 * 
 * @author https://github.com/HugoFara/ HugoFara
 */

require_once __DIR__ . "/kernel_utility.php";
require __DIR__ . "/../connect.inc.php";
require_once __DIR__ . "/db_restore.php";
require_once __DIR__ . "/../db/lib/apply_migrations.php";

/**
 * Do a SQL query to the database. 
 * It is a wrapper for mysqli_query function.
 *
 * @param string $sql Query using SQL syntax
 *
 * @global mysqli $DBCONNECTION Connection to the database
 *
 * @return mysqli_result|true
 */
function do_mysqli_query($sql)
{
    global $DBCONNECTION;
    $res = mysqli_query($DBCONNECTION, $sql);
    if ($res != false) {
        return $res;
    }
    echo '</select></p></div>
    <div style="padding: 1em; color:red; font-size:120%; background-color:#CEECF5;">' .
    '<p><b>Fatal Error in SQL Query:</b> ' . 
    tohtml($sql) . 
    '</p>' . 
    '<p><b>Error Code &amp; Message:</b> [' . 
    mysqli_errno($DBCONNECTION) . 
    '] ' . 
    tohtml(mysqli_error($DBCONNECTION)) . 
    "</p></div><hr /><pre>Backtrace:\n\n";
    debug_print_backtrace();
    echo '</pre><hr />';
    die('</body></html>');
}

/**
 * Run a SQL query, you can specify its behavior and error message.
 *
 * @param string $sql       MySQL query
 * @param string $m         Success phrase to prepend to the number of affected rows
 * @param bool   $sqlerrdie To die on errors (default = TRUE)
 *
 * @return string Error message if failure, or the number of affected rows
 */
function runsql($sql, $m, $sqlerrdie = true): string 
{
    if ($sqlerrdie) {
        $res = do_mysqli_query($sql); 
    } else {
        $res = mysqli_query($GLOBALS['DBCONNECTION'], $sql); 
    }        
    if ($res == false) {
        $message = "Error: " . mysqli_error($GLOBALS['DBCONNECTION']);
    } else {
        $num = mysqli_affected_rows($GLOBALS['DBCONNECTION']);
        $message = ($m == '') ? (string)$num : $m . ": " . $num;
    }
    return $message;
}


/**
 * Return the record "value" in the first line of the database if found.
 *
 * @param string $sql MySQL query
 * 
 * @return float|int|string|null Any returned value from the database.
 * 
 * @since 2.5.4-fork Officially return numeric types.
 */
function get_first_value($sql) 
{
    $res = do_mysqli_query($sql);        
    $record = mysqli_fetch_assoc($res);
    if ($record) { 
        $d = $record["value"]; 
    } else {
        $d = null; 
    }
    mysqli_free_result($res);
    return $d;
}


/**
 * Replace Windows line return ("\r\n") by Linux ones ("\n").
 * 
 * @param string $s Input string
 * 
 * @return string Adapted string. 
 */
function prepare_textdata($s): string 
{
    return str_replace("\r\n", "\n", $s);
}

// -------------------------------------------------------------

function prepare_textdata_js($s): string 
{
    $s = convert_string_to_sqlsyntax($s);
    if ($s == "NULL") { 
        return "''"; 
    }
    return str_replace("''", "\\'", $s);
}


/**
 * Prepares a string to be properly recognized as a string by SQL.
 *
 * @param string $data Input string
 *
 * @return string Properly escaped and trimmed string. "NULL" if the input string is empty.
 */
function convert_string_to_sqlsyntax($data): string 
{
    $result = "NULL";
    $data = trim(prepare_textdata($data));
    if ($data != "") { 
        $result = "'" . 
        mysqli_real_escape_string($GLOBALS['DBCONNECTION'], $data) . 
        "'"; 
    }
    return $result;
}

/**
 * Prepares a string to be properly recognized as a string by SQL.
 *
 * @param string $data Input string
 *
 * @return string Properly escaped and trimmed string
 */
function convert_string_to_sqlsyntax_nonull($data): string 
{
    $data = trim(prepare_textdata($data));
    return  "'" . mysqli_real_escape_string($GLOBALS['DBCONNECTION'], $data) . "'";
}

/**
 * Prepares a string to be properly recognized as a string by SQL.
 *
 * @param string $data Input string
 *
 * @return string Properly escaped string
 */
function convert_string_to_sqlsyntax_notrim_nonull($data): string 
{
    return "'" . 
    mysqli_real_escape_string($GLOBALS['DBCONNECTION'], prepare_textdata($data)) . 
    "'";
}

// -------------------------------------------------------------

function convert_regexp_to_sqlsyntax($input): string 
{
    $output = preg_replace_callback(
        "/\\\\x\{([\da-z]+)\}/ui", 
        function ($a) {
            $num = $a[1];
            $dec = hexdec($num);
            return "&#$dec;";
        }, 
        preg_replace(
            array('/\\\\(?![-xtfrnvup])/u','/(?<=[[^])[\\\\]-/u'), 
            array('','-'), 
            $input
        )
    );
    return convert_string_to_sqlsyntax_nonull(
        html_entity_decode($output, ENT_NOQUOTES, 'UTF-8')
    );
}

/**
 * Validate a language ID
 *
 * @param string $currentlang Language ID to validate
 *
 * @return string '' if the language is not valid, $currentlang otherwise
 */
function validateLang($currentlang): string 
{
    if ($currentlang == '') {
        return '';
    }
    $sql_string = 'SELECT count(LgID) AS value 
    FROM languages 
    WHERE LgID=' . $currentlang;
    if (get_first_value($sql_string) == 0) {  
        return ''; 
    } 
    return $currentlang;
}

/**
 * Validate a text ID
 *
 * @param string $currenttext Text ID to validate
 *
 * @global string '' if the text is not valid, $currenttext otherwise
 */
function validateText($currenttext): string 
{
    if ($currenttext == '') {
        return '';
    }
    $sql_string = 'SELECT count(TxID) AS value 
    FROM texts WHERE TxID=' . 
    $currenttext;
    if (get_first_value($sql_string) == 0) {  
        return ''; 
    }
    return $currenttext;
}

// -------------------------------------------------------------

function validateTag($currenttag,$currentlang) 
{
    if ($currenttag != '' && $currenttag != -1) {
        $sql = "SELECT (
            " . $currenttag . " IN (
                SELECT TgID 
                FROM words, tags, wordtags 
                WHERE TgID = WtTgID AND WtWoID = WoID" . 
                ($currentlang != '' ? " AND WoLgID = " . $currentlang : '') .
                " group by TgID order by TgText
            )
        ) AS value";
        /*if ($currentlang == '') {
            $sql = "select (" . $currenttag . " in (select TgID from words, tags, wordtags where TgID = WtTgID and WtWoID = WoID group by TgID order by TgText)) as value"; 
        }
        else {
            $sql = "select (" . $currenttag . " in (select TgID from words, tags, wordtags where TgID = WtTgID and WtWoID = WoID and WoLgID = " . $currentlang . " group by TgID order by TgText)) as value"; 
        }*/
        $r = get_first_value($sql);
        if ($r == 0) { 
            $currenttag = ''; 
        } 
    }
    return $currenttag;
}

// -------------------------------------------------------------

function validateTextTag($currenttag,$currentlang) 
{
    if ($currenttag != '' && $currenttag != -1) {
        if ($currentlang == '') {
            $sql = "select (
                " . $currenttag . " in (
                    select T2ID 
                    from texts, 
                    tags2, 
                    texttags 
                    where T2ID = TtT2ID and TtTxID = TxID 
                    group by T2ID 
                    order by T2Text
                )
            ) as value"; 
        }
        else {
            $sql = "select (
                " . $currenttag . " in (
                    select T2ID 
                    from texts, 
                    tags2, 
                    texttags 
                    where T2ID = TtT2ID and TtTxID = TxID and TxLgID = " . $currentlang . " 
                    group by T2ID order by T2Text
                )
            ) as value"; 
        }
        $r = get_first_value($sql);
        if ($r == 0 ) { 
            $currenttag = ''; 
        } 
    }
    return $currenttag;
}

/** 
 * Convert a setting to 0 or 1
 *
 * @param string     $key The input value
 * @param string|int $dft Default value to use, should be convertible to string
 * 
 * @return int
 * 
 * @psalm-return 0|1
 */
function getSettingZeroOrOne($key, $dft): int
{
    $r = getSetting($key);
    $r = ($r == '' ? $dft : (((int)$r !== 0) ? 1 : 0));
    return (int)$r;
}

/**
 * Get a setting from the database. It can also check for its validity.
 * 
 * @param  string $key Setting key. If $key is 'currentlanguage' or 
 *                     'currenttext', we validate language/text.
 * @return string $val Value in the database if found, or an empty string
 */
function getSetting($key) 
{
    $val = get_first_value(
        'SELECT StValue AS value 
        FROM settings 
        WHERE StKey = ' . convert_string_to_sqlsyntax($key)
    );
    if (isset($val)) {
        $val = trim($val);
        if ($key == 'currentlanguage' ) { 
            $val = validateLang($val); 
        }
        if ($key == 'currenttext' ) { 
            $val = validateText($val); 
        }
        return $val;
    }
    else { 
        return ''; 
    }
}

/**
 * Get the settings value for a specific key. Return a default value when possible
 * 
 * @param string $key Settings key
 * 
 * @return string Requested setting, or default value, or ''
 */
function getSettingWithDefault($key) 
{
    $dft = get_setting_data();
    $val = get_first_value(
        'SELECT StValue AS value
         FROM settings
         WHERE StKey = ' . convert_string_to_sqlsyntax($key)
    );
    if (isset($val) && $val != '') {
        return trim($val); 
    }
    if (isset($dft[$key])) { 
        return $dft[$key]['dft']; 
    }
    return '';
    
}

/**
 * Save the setting identified by a key with a specific value.
 * 
 * @param string $k Setting key
 * @param mixed  $v Setting value, will get converted to string
 * 
 * @return string Error or success message
 */
function saveSetting($k, $v) 
{
    $dft = get_setting_data();
    if (!isset($v)) {
        return ''; 
    }
    if ($v === '') {
        return '';
    }
    runsql(
        'DELETE FROM settings 
        WHERE StKey = ' . convert_string_to_sqlsyntax($k), 
        ''
    );
    if (isset($dft[$k]) && $dft[$k]['num']) {
        $v = (int)$v;
        if ($v < $dft[$k]['min']) { 
            $v = $dft[$k]['dft']; 
        }
        if ($v > $dft[$k]['max']) { 
            $v = $dft[$k]['dft']; 
        }
    }
    $dum = runsql(
        'INSERT INTO settings (StKey, StValue) values(' .
        convert_string_to_sqlsyntax($k) . ', ' . 
        convert_string_to_sqlsyntax($v) . ')', 
        ''
    );
    return $dum;
}


// -------------------------------------------------------------

/**
 * Optimize the database.
 *
 * @global string $trbpref Table prefix
 */
function optimizedb(): void 
{
    $sql = 
    "SHOW TABLE STATUS 
    WHERE Engine IN ('MyISAM','Aria') AND (
        (Data_free / Data_length > 0.1 AND Data_free > 102400) OR Data_free > 1048576
    ) AND Name NOT LIKE '\_%'";
    $res = do_mysqli_query($sql);
    while($row = mysqli_fetch_assoc($res)) {
        runsql('OPTIMIZE TABLE ' . $row['Name'], '');
    }
    mysqli_free_result($res);
}

/**
 * Update the word count for Japanese language (using MeCab only).
 * 
 * @param int $japid Japanese language ID
 * 
 * @return void
 */
function update_japanese_word_count($japid)
{
    // STEP 1: write the useful info to a file
    $db_to_mecab = tempnam(sys_get_temp_dir(), "db_to_mecab");
    $mecab_args = ' -F %m%t\\t -U %m%t\\t -E \\n ';
    $mecab = get_mecab_path($mecab_args);

    $sql = "SELECT WoID, WoTextLC FROM words 
    WHERE WoLgID = $japid AND WoWordCount = 0";
    $res = do_mysqli_query($sql);
    $fp = fopen($db_to_mecab, 'w');
    while ($record = mysqli_fetch_assoc($res)) {
        echo $record['WoID'] . "\t" . $record['WoTextLC'] . "\n";
        fwrite($fp, $record['WoID'] . "\t" . $record['WoTextLC'] . "\n");
    }
    mysqli_free_result($res);
    fclose($fp);

    // STEP 2: process the data with MeCab and refine the output
    $handle = popen($mecab . $db_to_mecab, "r");
    if (feof($handle)) {
        pclose($handle);
        unlink($db_to_mecab);
        return;
    }
    $sql = "INSERT INTO mecab (MID, MWordCount) values";
    $values = array();
    while (!feof($handle)) {
        $row = fgets($handle, 1024);
        $arr = explode("4\t", $row, 2);
        if (!empty($arr[1])) {
            $cnt = substr_count(
                preg_replace('$[^267]\t$u', '', $arr[1]), 
                "\t"
            );
            if (empty($cnt)) {
                $cnt = 1;
            }
            $values[] = "(" . convert_string_to_sqlsyntax($arr[0]) . ", $cnt)";
        }
    }
    pclose($handle);
    if (empty($values)) {
        // Nothing to update, quit
        return;
    }
    $sql .= join(",", $values);


    // STEP 3: edit the database
    do_mysqli_query(
        "CREATE TEMPORARY TABLE mecab ( 
            MID mediumint(8) unsigned NOT NULL, 
            MWordCount tinyint(3) unsigned NOT NULL, 
            PRIMARY KEY (MID)
        ) CHARSET=utf8"
    );
    
    do_mysqli_query($sql);
    do_mysqli_query(
        "UPDATE words 
        JOIN mecab ON MID = WoID 
        SET WoWordCount = MWordCount"
    );
    do_mysqli_query("DROP TABLE mecab");

    unlink($db_to_mecab);
}

/**
 * Initiate the number of words in terms for all languages,
 * or for specific $wid if set.
 * 
 * Only terms with a word count set to 0 are changed.
 * 
 * @return void
 */
function init_word_count($wid = 0): void 
{
    $sqlarr = array();
    $i = 0;
    $min = 0;
    /**
     * @var string|null ID for the Japanese language using MeCab
     */
    $japid = get_first_value(
        "SELECT group_concat(LgID) value 
        FROM languages 
        WHERE UPPER(LgRegexpWordCharacters)='MECAB'"
    );

    if ($japid) {
        update_japanese_word_count((int)$japid);
    }

    $whereWoID = '';
    if ($wid != 0) {
        $whereWoID = " AND WoID = {$wid}";
    }
    $sql = "SELECT WoID, WoTextLC, LgRegexpWordCharacters, LgSplitEachChar 
    FROM words, languages 
    WHERE WoWordCount = 0 AND WoLgID = LgID {$whereWoID}
    ORDER BY WoID";
    $result = do_mysqli_query($sql);
    while ($rec = mysqli_fetch_assoc($result)){
        if ($rec['LgSplitEachChar']) {
            $textlc = preg_replace('/([^\s])/u', "$1 ", $rec['WoTextLC']);
        } else {
            $textlc = $rec['WoTextLC'];
        }
        $sqlarr[]= ' WHEN ' . $rec['WoID'] . ' 
        THEN ' . preg_match_all(
            '/([' . $rec['LgRegexpWordCharacters'] . ']+)/u', $textlc, $ma
        );
        if (++$i % 1000 == 0) {
            $max = $rec['WoID'];
            $sqltext = "UPDATE  words 
            SET WoWordCount = CASE WoID" . implode(' ', $sqlarr) . "
            END 
            WHERE WoWordCount=0 AND WoID BETWEEN $min AND $max";
            do_mysqli_query($sqltext);
            $min = $max;
            $sqlarr = array();
        }
    }
    mysqli_free_result($result);
    if (!empty($sqlarr)) {
        $sqltext = "UPDATE  words 
        SET WoWordCount = CASE WoID" . implode(' ', $sqlarr) . ' 
        END where WoWordCount=0';
        do_mysqli_query($sqltext);
    }
}


/**
 * Parse a Japanese text using MeCab and add it to the database.
 *
 * @param string $text Text to parse.
 * @param int    $id   Text ID. If $id = -1 print results, 
 *                     if $id = -2 return splitted texts
 *
 * @return null|string[] Splitted sentence if $id = -2
 *
 * @since 2.5.1-fork Works even if LOAD DATA LOCAL INFILE operator is disabled.
 *
 * @psalm-return non-empty-list<string>|null
 */
function parse_japanese_text($text, $id): ?array
{
    $text = preg_replace('/[ \t]+/u', ' ', $text);
    $text = trim($text);
    if ($id == -1) {
        echo '<div id="check_text" style="margin-right:50px;">
        <h4>Text</h4>
        <p>' . str_replace("\n", "<br /><br />", tohtml($text)). '</p>'; 
    } else if ($id == -2) {
        $text = preg_replace("/[\n]+/u", "\n¶", $text);
        return explode("\n", $text);
    }

    $file_name = tempnam(sys_get_temp_dir(), "tmpti");
    // We use the format "word  num num" for all nodes
    $mecab_args = " -F %m\\t%t\\t%h\\n -U %m\\t%t\\t%h\\n -E EOS\\t3\\t7\\n";
    $mecab_args .= " -o $file_name ";
    $mecab = get_mecab_path($mecab_args);
    
    // WARNING: \n is converted to PHP_EOL here!
    $handle = popen($mecab, 'w');
    fwrite($handle, $text);
    pclose($handle);

    runsql(
        "CREATE TEMPORARY TABLE IF NOT EXISTS temptextitems2 (
            TiCount smallint(5) unsigned NOT NULL,
            TiSeID mediumint(8) unsigned NOT NULL,
            TiOrder smallint(5) unsigned NOT NULL,
            TiWordCount tinyint(3) unsigned NOT NULL,
            TiText varchar(250) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL
        ) DEFAULT CHARSET=utf8", 
        ''
    );
    // It is faster to write to a file and let SQL do its magic, but may run into
    // security restrictions
    if (get_first_value("SELECT @@GLOBAL.local_infile as value")) {
        do_mysqli_query(
            "SET @order:=0, @term_type:=0,@sid=0, @count:=0,@last_term_type:=0;"
        );
        
        $sql = 
        "LOAD DATA LOCAL INFILE " . convert_string_to_sqlsyntax($file_name) . "
        INTO TABLE temptextitems2
        FIELDS TERMINATED BY '\\t' 
        LINES TERMINATED BY '" . PHP_EOL . "' 
        (@term, @node_type, @third)
        SET 
        TiSeID = IF(@term_type=2 OR (@term='EOS' AND @third='7'), @sid:=@sid+1,@sid),
        TiCount = (@count:= @count + CHAR_LENGTH(@term)) + 1 - CHAR_LENGTH(@term),
        TiOrder = IF(
            CASE
                WHEN @third = '7' THEN IF(
                    @term = 'EOS',
                    (@term_type := 2) AND (@term := '¶'), 
                    @g := 2
                ) 
                WHEN LOCATE(@node_type, '267') THEN @term_type:=0 
                ELSE @term_type:=1 
            END IS NULL,
            NULL,
            @order := @order + IF((@last_term_type=1) AND (@term_type=1), 0, 1) 
            + IF((@last_term_type=0) AND (@term_type=0), 1, 0) 
        ), 
        TiText = @term,
        TiWordCount =
        CASE 
            WHEN (@last_term_type:=@term_type) IS NULL THEN NULL
            WHEN @term_type=0 THEN 1
            ELSE 0 
        END";
        do_mysqli_query($sql);
        do_mysqli_query("DELETE FROM temptextitems2 WHERE TiOrder=@order");
    } else {
        $handle = fopen($file_name, 'r');
        $mecabed = fread($handle, filesize($file_name));
        fclose($handle);
        $values = array();
        $order = 0;
        $sid = 1;
        if ($id > 0) {
            $sid = 0;
        }
        $term_type = 0;
        $count = 0;
        $row = array(0, 0, 0, "", 0);
        foreach (explode(PHP_EOL, $mecabed) as $line) {
            list($term, $node_type, $third) = explode(mb_chr(9), $line);
            if ($term_type == 2 || $term == 'EOS' && $third == '7') {
                $sid += 1;
            }
            $row[0] = $sid; // TiSeID
            $row[1] = $count + 1; // TiCount
            $count += mb_strlen($term);
            $last_term_type = $term_type;
            if ($third == '7') {
                if ($term == 'EOS') {
                    $term = '¶';
                }
                $term_type = 2;
            } else if (str_contains('267', $node_type)) {
                $term_type = 0;
            } else {
                $term_type = 1;
            }
            $order += (($term_type == 0) && ($last_term_type == 0)) + 
            !(($term_type == 1) && ($last_term_type == 1));
            $row[2] = $order; // TiOrder
            $row[3] = convert_string_to_sqlsyntax_notrim_nonull($term); // TiText
            $row[4] = $term_type == 0 ? 1 : 0; // TiWordCount
            $values[] = "(" . implode(",", $row) . ")";
        }
        do_mysqli_query(
            "INSERT INTO temptextitems2 (
                TiSeID, TiCount, TiOrder, TiText, TiWordCount
            ) VALUES " . implode(',', $values)
        );
        // Delete elements TiOrder=@order
        do_mysqli_query("DELETE FROM temptextitems2 WHERE TiOrder=$order");
    }
    do_mysqli_query(
        "INSERT INTO temptextitems (
            TiCount, TiSeID, TiOrder, TiWordCount, TiText
        ) 
        SELECT MIN(TiCount) s, TiSeID, TiOrder, TiWordCount, 
        group_concat(TiText ORDER BY TiCount SEPARATOR '')
        FROM temptextitems2
        GROUP BY TiOrder"
    );
    do_mysqli_query("DROP TABLE temptextitems2");
    unlink($file_name);
    return null;
}

/**
 * Parse a text using the default tools. It is a not-japanese text.
 *
 * @param string $text Text to parse
 * @param int    $id   Text ID. If $id == -2, only split the text.
 * @param int    $lid  Language ID.
 *
 * @return null|string[] If $id == -2 return a splitted version of the text.
 *
 * @since 2.5.1-fork Works even if LOAD DATA LOCAL INFILE operator is disabled.
 *
 * @psalm-return non-empty-list<string>|null
 */
function parse_standard_text($text, $id, $lid): ?array
{
    $sql = "SELECT * FROM languages WHERE LgID=$lid";
    $res = do_mysqli_query($sql);
    $record = mysqli_fetch_assoc($res);
    $removeSpaces = $record['LgRemoveSpaces'];
    $splitSentence = $record['LgRegexpSplitSentences'];
    $noSentenceEnd = $record['LgExceptionsSplitSentences'];
    $termchar = $record['LgRegexpWordCharacters'];
    $rtlScript = $record['LgRightToLeft'];
    // Split text paragraphs using " ¶" symbol 
    $text = str_replace("\n", " ¶", $text);
    $text = trim($text);
    if ($record['LgSplitEachChar']) {
        $text = preg_replace('/([^\s])/u', "$1\t", $text);
    }
    $text = preg_replace('/\s+/u', ' ', $text);
    if ($id == -1) { 
        echo "<div id=\"check_text\" style=\"margin-right:50px;\">
        <h4>Text</h4>
        <p " .  ($rtlScript ? 'dir="rtl"' : '') . ">" . 
        str_replace("¶", "<br /><br />", tohtml($text)) . 
        "</p>"; 
    }
    // "\r" => Sentence delimiter, "\t" and "\n" => Word delimiter
    $text = preg_replace_callback(
        "/(\S+)\s*((\.+)|([$splitSentence]))([]'`\"”)‘’‹›“„«»』」]*)(?=(\s*)(\S+|$))/u",
        // Arrow functions got introduced in PHP 7.4 
        //fn ($matches) => find_latin_sentence_end($matches, $noSentenceEnd)
        function ($matches) use ($noSentenceEnd) {
            return find_latin_sentence_end($matches, $noSentenceEnd); 
        },
        $text
    );
    // Paragraph delimiters become a combination of ¶ and carriage return \r
    $text = str_replace(array("¶"," ¶"), array("¶\r","\r¶"), $text);
    $text = preg_replace(
        array(
            '/([^' . $termchar . '])/u',
            '/\n([' . $splitSentence . '][\'`"”)\]‘’‹›“„«»』」]*)\n\t/u',
            '/([0-9])[\n]([:.,])[\n]([0-9])/u'
        ), 
        array("\n$1\n", "$1", "$1$2$3"), 
        $text
    );
    if ($id == -2) {
        $text = remove_spaces(
            str_replace(
                array("\r\r","\t","\n"), array("\r","",""), $text
            ), 
            $removeSpaces
        );
        return explode("\r", $text);
    }

    
    $text = trim(
        preg_replace(
            array(
                "/\r(?=[]'`\"”)‘’‹›“„«»』」 ]*\r)/u",
                '/[\n]+\r/u',
                '/\r([^\n])/u',
                "/\n[.](?![]'`\"”)‘’‹›“„«»』」]*\r)/u",
                "/(\n|^)(?=.?[$termchar][^\n]*\n)/u"
            ), 
            array(
                "",
                "\r",
                "\r\n$1",
                ".\n",
                "\n1\t"
            ), 
            str_replace(array("\t","\n\n"), array("\n",""), $text)
        )
    );
    $text = remove_spaces(
        preg_replace("/(\n|^)(?!1\t)/u", "\n0\t", $text), $removeSpaces
    );
    // It is faster to write to a file and let SQL do its magic, but may run into
    // security restrictions
    if (get_first_value("SELECT @@GLOBAL.local_infile as value")) {
        $file_name = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "tmpti.txt";
        $fp = fopen($file_name, 'w');
        fwrite($fp, $text);
        fclose($fp);
        do_mysqli_query(
            "SET @order=0, @sid=0, @count=0;"
        );
        $sql = "LOAD DATA LOCAL INFILE " . convert_string_to_sqlsyntax($file_name) . "
        INTO TABLE temptextitems 
        FIELDS TERMINATED BY '\\t' LINES TERMINATED BY '\\n' (@word_count, @term)
        SET 
            TiSeID = @sid, 
            TiCount = (@count:=@count+CHAR_LENGTH(@term))+1-CHAR_LENGTH(@term),
            TiOrder = IF(
                @term LIKE '%\\r',
                CASE 
                    WHEN (@term:=REPLACE(@term,'\\r','')) IS NULL THEN NULL 
                    WHEN (@sid:=@sid+1) IS NULL THEN NULL 
                    WHEN @count:= 0 IS NULL THEN NULL 
                    ELSE @order := @order+1 
                END, 
                @order := @order+1
            ), 
            TiText = @term,
            TiWordCount = @word_count";

        do_mysqli_query($sql);
        mysqli_free_result($res);
        unlink($file_name);
    } else {
        throw new Exception("SELECT @@GLOBAL.local_infile must be 1, check your mysql configuration.");

        // TODO - this entire function should be rewritten perhaps ... lots of magic in here.

        $values = array();
        $order = 0;
        $sid = 1;
        if ($id > 0) {
            $sid = 0;
        }
        $count = 0;
        $row = array(0, 0, 0, "", 0);
        foreach (explode("\n", $text) as $line) {
            list($word_count, $term) = explode("\t", $line);
            $row[0] = $sid; // TiSeID
            $row[1] = $count + 1; // TiCount
            $count += mb_strlen($term);
            if (str_ends_with($term, "\r")) {
                $term = str_replace("\r", '', $term);
                $sid++;
                $count = 0;
            }
            $row[2] = ++$order; // TiOrder
            $row[3] = convert_string_to_sqlsyntax_notrim_nonull($term); // TiText
            $row[4] = $word_count; // TiWordCount
            $values[] = "(" . implode(",", $row) . ")";
        }
        do_mysqli_query(
            "INSERT INTO temptextitems (
                TiSeID, TiCount, TiOrder, TiText, TiWordCount
            ) VALUES " . implode(',', $values)
        );
    }
    return null;
}


/**
 * Pre-parse the input text before a definitive parsing by a specialized parser.
 *
 * @param string $text Text to parse
 * @param int    $id   Text ID
 * @param int    $lid  Language ID
 *
 * @return null|string[] If $id = -2 return a splitted version of the text
 *
 * @psalm-return non-empty-list<string>|null
 */
function prepare_text_parsing($text, $id, $lid): ?array
{
    $sql = "SELECT * FROM languages WHERE LgID=" . $lid;
    $res = do_mysqli_query($sql);
    $record = mysqli_fetch_assoc($res);
    $termchar = $record['LgRegexpWordCharacters'];
    $replace = explode("|", $record['LgCharacterSubstitutions']);
    mysqli_free_result($res);
    $text = prepare_textdata($text);
    //if(is_callable('normalizer_normalize')) $s = normalizer_normalize($s);

    do_mysqli_query('TRUNCATE TABLE temptextitems');

    // because of sentence special characters
    $text = str_replace(array('}','{'), array(']','['), $text);    
    foreach ($replace as $value) {
        $fromto = explode("=", trim($value));
        if (count($fromto) >= 2) {
            $text = str_replace(trim($fromto[0]), trim($fromto[1]), $text);
        }
    }

    if ('MECAB' == strtoupper(trim($termchar))) {
        return parse_japanese_text($text, $id);
    } 
    return parse_standard_text($text, $id, $lid);
}

/**
 * Echo the sentences in a text. Prepare JS data for words and word count.
 * 
 * @return void
 */
function check_text_valid($lid)
{
    $wo = $nw = array();
    $res = do_mysqli_query(
        'SELECT GROUP_CONCAT(TiText order by TiOrder SEPARATOR "") 
        Sent FROM temptextitems group by TiSeID'
    );
    echo '<h4>Sentences</h4><ol>';
    while($record = mysqli_fetch_assoc($res)){
        echo "<li>" . tohtml($record['Sent']) . "</li>";
    }
    mysqli_free_result($res);
    echo '</ol>';
    $res = do_mysqli_query(
        'SELECT count(`TiOrder`) cnt, if(0=TiWordCount,0,1) as len, 
        LOWER(TiText) as word, WoTranslation 
        FROM temptextitems 
        LEFT JOIN words ON lower(TiText)=WoTextLC AND WoLgID=' . $lid . ' 
        GROUP BY lower(TiText)'
    );
    while ($record = mysqli_fetch_assoc($res)) {
        if ($record['len']==1) {
            $wo[]= array(
                tohtml($record['word']),
                $record['cnt'],
                tohtml($record['WoTranslation'])
            );
        } else{
            $nw[] = array(tohtml($record['word']), tohtml($record['cnt']));
        }
    }
    mysqli_free_result($res);
    echo '<script type="text/javascript">
    WORDS = ', json_encode($wo), ';
    NOWORDS = ', json_encode($nw), ';
    </script>';
}

/** INSERTING EXPRESSIONS *********************************/


function strToHex($string): string
{
    $hex='';
    for ($i=0; $i < strlen($string); $i++)
    {
        $h = dechex(ord($string[$i]));
        if (strlen($h) == 1 ) { 
            $hex .= "0" . $h; 
        }
        else {
            $hex .= $h; 
        }
    }
    return strtoupper($hex);
}


/**
 * Escapes everything to "HEXxx" but not 0-9, a-z, A-Z, and unicode >= (hex 00A5, dec 165)
 *
 * @param string $string String to escape
 */
function strToClassName($string): string
{
    $length = mb_strlen($string, 'UTF-8');
    $r = '';
    for ($i=0; $i < $length; $i++)
    {
        $c = mb_substr($string, $i, 1, 'UTF-8');
        $o = ord($c);
        if (($o < 48)  
            || ($o > 57 && $o < 65)  
            || ($o > 90 && $o < 97)  
            || ($o > 122 && $o < 165)
        ) {
            $r .= 'HEX' . strToHex($c); 
        } else { 
            $r .= $c; 
        }
    }
    return $r;
}


/**
 * Insert an expression to the database using MeCab.
 *
 * @param string $text   Text to insert
 * @param string $lid    Language ID
 * @param string $wid    Word ID
 * @param int    $mode   If equal to 0, add data in the output
 *
 * @return array{0: string[], 1: string[]} Append text and values to insert to 
 *                                         the database
 *
 * @since 2.5.0-fork Function added.
 *
 * @psalm-return array{0: array<int, string>, 1: list<string>}
 */
function insert_expression_from_mecab($text, $lid, $wid, $len, $sentenceIDRange): array
{
    $db_to_mecab = tempnam(sys_get_temp_dir(), "db_to_mecab");
    $mecab_args = " -F %m\\t%t\\t%h\\n -U %m\\t%t\\t%h\\n -E EOS\\t3\\t7\\n ";

    $mecab = get_mecab_path($mecab_args);

    $whereSeIDRange = '';
    if (! is_null($sentenceIDRange)) {
        [ $lower, $upper ] = $sentenceIDRange;
        $whereSeIDRange = "(SeID >= {$lower} AND SeID <= {$upper}) AND";
    }

    $sql = "SELECT SeID, SeTxID, SeFirstPos, SeText FROM sentences 
    WHERE {$whereSeIDRange} SeText LIKE " . convert_string_to_sqlsyntax_notrim_nonull("%$text%");
    $res = do_mysqli_query($sql);


    $appendtext = array();
    $sqlarray = array();
    // For each sentence in database containing $text
    while ($record = mysqli_fetch_assoc($res)) {
        $sent = trim($record['SeText']);
        $fp = fopen($db_to_mecab, 'w');
        fwrite($fp, $sent . "\n");
        fclose($fp);

        $handle = popen($mecab . $db_to_mecab, "r");
        $word_counter = 0;
        // For each word in sentence
        while (!feof($handle)) {
            $row = fgets($handle, 16132);
            $arr = explode("\t", $row, 4);
            // Not a word (punctuation)
            if (empty($arr[0]) || $arr[0] == "EOS" 
                || strpos("2 6 7", $arr[1]) === false
            ) {
                continue;
            }
            // A word in sentence but not in selected multi-word
            if (mb_strpos($text, $arr[0]) === false) {
                $word_counter++;
                continue;
            }
            $seek = 0;
            // For each occurence of multi-word in sentence 
            while (
                $seek < mb_strlen($sent) 
                && ($seek = mb_strpos($sent, $text, $seek)) !== false
            ) {
                $sent = mb_substr($sent, $seek);
                $pos = $word_counter * 2 + (int) $record['SeFirstPos'];
                // Ti2WoID,Ti2LgID,Ti2TxID,Ti2SeID,Ti2Order,Ti2WordCount,Ti2Text
                $sqlarray[] = "($wid, $lid, {$record['SeTxID']}, {$record['SeID']}, 
                $pos, $len, " . convert_string_to_sqlsyntax_notrim_nonull($text) . ")";
                if (getSettingZeroOrOne('showallwords', 1)) {
                    $appendtext[$pos] = '&nbsp;' . $len . '&nbsp';
                } else { 
                    $appendtext[$pos] = $text;
                }
                $seek++;
            }
            $word_counter++;
        }
        pclose($handle);
    }
    mysqli_free_result($res);
    unlink($db_to_mecab);

    return array($appendtext, $sqlarray);
}


// Ref https://stackoverflow.com/questions/1725227/preg-match-and-utf-8-in-php
// Leaving the "echo" comments in, in case more future debugging needed.

/**
 * Returns array of matches in same format as preg_match or preg_match_all
 * @param bool   $matchAll If true, execute preg_match_all, otherwise preg_match
 * @param string $pattern  The pattern to search for, as a string.
 * @param string $subject  The input string.
 * @param int    $offset   The place from which to start the search (in bytes).
 * @return array
 */
function pregMatchCapture($matchAll, $pattern, $subject, $offset = 0)
{
    if ($offset != 0) { $offset = strlen(mb_substr($subject, 0, $offset)); }

    $matchInfo = array();
    $method    = 'preg_match';
    $flag      = PREG_OFFSET_CAPTURE;
    if ($matchAll) {
        $method .= '_all';
    }

    // echo "pattern: $pattern ; subject: $subject \n";
    $n = $method($pattern, $subject, $matchInfo, $flag, $offset);
    // echo "matchinfo:\n";
    // var_dump($matchInfo);
    $result = array();
    if ($n !== 0 && !empty($matchInfo)) {
        if (!$matchAll) {
            $matchInfo = array($matchInfo);
        }
        foreach ($matchInfo as $matches) {
            $positions = array();
            foreach ($matches as $match) {
                $matchedText   = $match[0];
                $matchedLength = $match[1];
                $positions[]   = array(
                    $matchedText,
                    mb_strlen(mb_strcut($subject, 0, $matchedLength))
                );
            }
            $result[] = $positions;
        }
        if (!$matchAll) {
            $result = $result[0];
        }
    }
    // echo "Returning:\n";
    // var_dump($result);
    return $result;
}


/**
 * Insert an expression without using a tool like MeCab.
 *
 * @param string $textlc Text to insert in lower case
 * @param string $lid    Language ID
 * @param string $wid    Word ID
 * @param mixed  $mode   Unnused
 * @param array  $sentenceIDRange
 *
 * @return array{string[], string[]} Append text, sentence id
 *
 * @since 2.5.2-fork Fixed multi-words insertion for languages using no space
 *
 * @psalm-return array{0: array<int, mixed|string>, 1: list<string>}
 */
function insert_standard_expression($textlc, $lid, $wid, $len, $sentenceIDRange): array
{

    // DEBUGGING HELPER FOR FUTURE, because this code is brutal and
    // needs to be completely replaced, but I need to understand it
    // first.
    // Change $problemterm to the term that's not getting handled
    // correctly.  e.g.,
    // $problemterm = mb_strtolower('de refilón');
    $problemterm = mb_strtolower('PROBLEM_TERM');
    $logme = function($s) {};
    if ($textlc == $problemterm) {
        $logme = function($s) { echo "{$s}\n"; };
        $logme("\n\n================");
        $r = implode(', ', $sentenceIDRange);
        $logme("Starting search for $textlc, lid = $lid, wid = $wid, len = $len, range = {$r}");
    }

    $appendtext = array();
    $sqlarr = array();
    $res = do_mysqli_query("SELECT * FROM languages WHERE LgID=$lid");
    $record = mysqli_fetch_assoc($res);
    $removeSpaces = $record["LgRemoveSpaces"];
    $splitEachChar = $record['LgSplitEachChar'];
    $termchar = $record['LgRegexpWordCharacters'];
    mysqli_free_result($res);

    $whereSeIDRange = '';
    if (! is_null($sentenceIDRange)) {
        [ $lower, $upper ] = $sentenceIDRange;
        $whereSeIDRange = "(SeID >= {$lower} AND SeID <= {$upper}) AND";
    }
    if ($removeSpaces == 1 && $splitEachChar == 0) {
        $sql = "SELECT 
        group_concat(Ti2Text ORDER BY Ti2Order SEPARATOR ' ') AS SeText, SeID, 
        SeTxID, SeFirstPos 
        FROM textitems2, sentences 
        WHERE {$whereSeIDRange} SeID=Ti2SeID AND SeLgID = $lid AND Ti2LgID = $lid 
        AND SeText LIKE " . convert_string_to_sqlsyntax_notrim_nonull("%$textlc%") . " 
        AND Ti2WordCount < 2 
        GROUP BY SeID";
    } else {
        $sql = "SELECT * FROM sentences 
        WHERE {$whereSeIDRange} SeLgID = $lid AND SeText LIKE " . 
        convert_string_to_sqlsyntax_notrim_nonull("%$textlc%");
    }
    $logme($sql);

    $wis = $textlc;
    $res = do_mysqli_query($sql);
    $notermchar = "/[^$termchar]({$textlc})[^$termchar]/ui";
    // For each sentence in the language containing the query
    $matches = null;
    while ($record = mysqli_fetch_assoc($res)){
        $string = ' ' . $record['SeText'] . ' ';
        $logme('"' . $string . '"');
        if ($splitEachChar) {
            $string = preg_replace('/([^\s])/u', "$1 ", $string);
        } else if ($removeSpaces == 1) {
            $ma = pregMatchCapture(
                false,
                '/(?<=[ ])(' . preg_replace('/(.)/ui', "$1[ ]*", $textlc) . 
                ')(?=[ ])/ui', 
                $string
            );
            if (!empty($ma[1])) {
                $textlc = trim($ma[1]);
                $notermchar = "/[^$termchar]({$textlc})[^$termchar]/ui";
            }
        }
        $last_pos = mb_strripos($string, $textlc, 0, 'UTF-8');
        $logme("last_pos = $last_pos, notermchar = $notermchar");

        // For each occurence of query in sentence
        while ($last_pos !== false) {
            $logme("searching string = ' $string '");
            $matches = null;
            $matches = pregMatchCapture(false, $notermchar, " $string ", $last_pos - 1);
            if (count($matches) == 0) {
                $logme("preg_match returned no matches?");
            }
            else {
                $c = count($matches);
                $logme("big pregmatch = $c");
            }

            if ($splitEachChar || $removeSpaces || count($matches) > 0) {
                // Number of terms before group
                $beforesubstr = mb_substr($string, 0, $last_pos, 'UTF-8');
                $logme("Checking count of terms in: $beforesubstr");
                $before = pregMatchCapture(true, "/([$termchar]+)/u", $beforesubstr);

                // Note pregMatchCapture returns a few arrays, we want
                // the first one.  (I confess I don't grok what's
                // happening here, but inspecting a var_dump of the
                // returned data led me to this.  jz)
                $cnt = count($before[0]);
                $pos = 2 * $cnt + (int) $record['SeFirstPos'];
                $logme("Got count = $cnt, pos = $pos");
                // $txt = $textlc;

                $txt = $matches[1][0];
                if ($txt != $textlc) {
                    $txt = $splitEachChar ? $wis : $matches[1][0]; 
                }

                $insert = convert_string_to_sqlsyntax_notrim_nonull($txt);
                $entry = "($wid, $lid, {$record['SeTxID']}, {$record['SeID']}, $pos, $len, $insert)";
                $sqlarr[] = $entry;
                $logme("-----------------\nadded entry: $entry \n-----------------");
                
                if (getSettingZeroOrOne('showallwords', 1)) {
                    $appendtext[$pos] = "&nbsp;$len&nbsp";
                } else { 
                    $appendtext[$pos] = $splitEachChar || $removeSpaces ? $wis : $txt;
                }
            }
            // Cut the sentence to before the right-most term starts
            $string = mb_substr($string, 0, $last_pos, 'UTF-8');
            $last_pos = mb_strripos($string, $textlc, 0, 'UTF-8');
            $logme("string is now: $string");
            $logme("last_pos is now: $last_pos");
        }
    }
    mysqli_free_result($res);

    $logme("final sqlarr:" . implode('; ', $sqlarr));
    $logme("ENDING SEARCH FOR $textlc");
    $logme("================");

    return array($appendtext, $sqlarr);
}


/**
 * Prepare a JavaScript dialog to insert a new expression.
 * 
 * @param string   $hex        Lowercase text, formatted version of the text.
 * @param string[] $appendtext Text to append
 * @param int      $wid        Term ID
 * @param int      $len        Words count.
 * 
 * @return void
 * 
 */
function new_expression_interactable2($hex, $appendtext, $wid, $len): void 
{
    $showAll = (bool)getSettingZeroOrOne('showallwords', 1) ? "m" : "";
    
    $sql = "SELECT * FROM words WHERE WoID=$wid";
    $res = do_mysqli_query($sql);

    $record = mysqli_fetch_assoc($res);

    $attrs = array(
        "class" => "click mword {$showAll}wsty TERM$hex word$wid status" . 
        $record["WoStatus"],
        "data_trans" => $record["WoTranslation"],
        "data_rom" => $record["WoRomanization"],
        "data_code" => $len,
        "data_status" => $record["WoStatus"],
        "data_wid" => $wid
    ); 
    mysqli_free_result($res);

?>
<script type="text/javascript">
    let term = <?php echo json_encode($attrs); ?>;

    let title = '';
    if (window.parent.JQ_TOOLTIP) 
        title = make_tooltip(
            <?php echo json_encode($appendtext); ?>, term.data_trans, term.data_rom, 
            parseInt(term.data_status, 10)
        );
    term['title'] = title;
    let attrs = ""; 
    Object.entries(term).forEach(([k, v]) => attrs += " " + k + '="' + v + '"');
    // keys(term).map((k) => k + '="' + term[k] + '"').join(" ");
    
    newExpressionInteractable(
        <?php echo json_encode($appendtext); ?>, 
        attrs,
        <?php echo json_encode($len); ?>, 
        <?php echo json_encode($hex); ?>,
        <?php echo json_encode(!$showAll); ?>
    );
</script>
<?php
    flush();
}


/**
 * Alter the database to add a new word
 *
 * @param string $textlc Text in lower case
 * @param string $lid    Language ID
 * @param string $len
 * @param int    $mode   Function mode
 *                       - 0: Default mode, do nothing special
 *                       - 1: Runs an expresion inserter interactable 
 *                       - 2: Return the sql output
 * @param array  $sentenceIDRange   [ lower SeID, upper SeID ] to consider.
 *
 * @return null|string If $mode == 2 return values to insert in textitems2, nothing otherwise.
 *
 */
function insertExpressions($textlc, $lid, $wid, $len, $mode, $sentenceIDRange = NULL): ?string 
{
    $sql = "SELECT * FROM languages WHERE LgID=$lid";
    $res = do_mysqli_query($sql);
    $record = mysqli_fetch_assoc($res);
    $mecab = 'MECAB' == strtoupper(trim($record['LgRegexpWordCharacters']));
    $splitEachChar = !$mecab && $record['LgSplitEachChar'];
    mysqli_free_result($res);
    if ($splitEachChar) {
        $textlc = preg_replace('/([^\s])/u', "$1 ", $textlc);
    }

    if ($mecab) {
        list($appendtext, $sqlarr) = insert_expression_from_mecab(
            $textlc, $lid, $wid, $len, $sentenceIDRange
        );
    } else {
        list($appendtext, $sqlarr) = insert_standard_expression(
            $textlc, $lid, $wid, $len, $sentenceIDRange
        );
    }
    $sqltext = null;
    if (!empty($sqlarr)) {
        $sqltext = '';
        if ($mode != 2) {
            $sqltext .= 
            "INSERT INTO textitems2
             (Ti2WoID,Ti2LgID,Ti2TxID,Ti2SeID,Ti2Order,Ti2WordCount,Ti2Text)
             VALUES ";
        }
        $sqltext .= implode(',', $sqlarr);
        unset($sqlarr);
    }

    if ($mode == 0) {
        $hex = strToClassName(prepare_textdata($textlc)); 
        new_expression_interactable2($hex, $appendtext, $wid, $len);
    }
    if ($mode == 2) { 
        return $sqltext; 
    }
    if (isset($sqltext)) {
        do_mysqli_query($sqltext);
    }
    return null;
}


/** END INSERTING EXPRESSIONS *****************************/

/**
 * Move data from temptextitems to final tables.
 * 
 * @param int    $id  New default text ID
 * @param int    $lid New default language ID
 * 
 * @return void
 */
function import_temptextitems($id, $lid)
{
    do_mysqli_query(
        'INSERT INTO sentences (
            SeLgID, SeTxID, SeOrder, SeFirstPos, SeText
        ) SELECT ' . $lid . ', ' . $id . ',
        TiSeID, 
        min(if(TiWordCount=0,TiOrder+1,TiOrder)),
        GROUP_CONCAT(TiText order by TiOrder SEPARATOR "") 
        FROM temptextitems 
        group by TiSeID'
    );

    $firstsql = "SELECT MIN(SeID) as value FROM sentences WHERE SeTxID = {$id}";
    $firstSeID = (int) get_first_value($firstsql);
    $lastsql = "SELECT MAX(SeID) as value FROM sentences WHERE SeTxID = {$id}";
    $lastSeID = (int) get_first_value($lastsql);

    $addti2 = "INSERT INTO textitems2 (
            Ti2LgID, Ti2TxID, Ti2WoID, Ti2SeID, Ti2Order, Ti2WordCount, Ti2Text
        )
        select {$lid}, {$id}, WoID, TiSeID + {$firstSeID}, TiOrder, TiWordCount, TiText 
        FROM temptextitems 
        left join words 
        on lower(TiText) = WoTextLC and TiWordCount>0 and WoLgID = {$lid} 
        order by TiOrder,TiWordCount";
    do_mysqli_query($addti2);

    // For each expession in the language, add expressions for the sentence range.
    // Inefficient, but for now I don't care -- will see how slow it is.
    $sentenceRange = [ $firstSeID, $lastSeID ];
    $mwordsql = "SELECT * FROM words WHERE WoLgID = $lid AND WoWordCount > 1";
    $res = do_mysqli_query($mwordsql);
    while ($record = mysqli_fetch_assoc($res)) {
        insertExpressions(
            $record['WoTextLC'],
            $lid,
            $record['WoID'],
            $record['WoWordCount'],
            1,
            $sentenceRange);
    }
    mysqli_free_result($res);
}

/**
 * Check a text and display statistics about it.
 * 
 * @param string   $sql
 * @param bool     $rtlScript true if language is right-to-left
 * @param string[] $wl        Words lengths
 * 
 * @return void
 */
function check_text($sql, $rtlScript, $wl)
{
    $mw = array();
    if(!empty($wl)) {
        $res = do_mysqli_query($sql);
        while($record = mysqli_fetch_assoc($res)){
            $mw[]= array(
                tohtml($record['word']),
                $record['cnt'],
                tohtml($record['WoTranslation'])
            );
        }
        mysqli_free_result($res);
    }
    ?>
<script type="text/javascript">
    MWORDS = <?php echo json_encode($mw) ?>;
    if (<?php echo json_encode($rtlScript); ?>) {
        $(function() {
            $("li").attr("dir", "rtl");
        });
    }
    let h='<h4>Word List <span class="red2">(red = already saved)</span></h4><ul class="wordlist">';
    $.each(
        WORDS, 
        function (k,v) {
            h += '<li><span' + (v[2]==""?"":' class="red2"') + '>[' + v[0] + '] — ' 
            + v[1] + (v[2]==""?"":' — ' + v[2]) + '</span></li>';
        }
        );
    h += '</ul><p>TOTAL: ' + WORDS.length 
    + '</p><h4>Expression List</span></h4><ul class="expressionlist">';
    $.each(MWORDS, function (k,v) {
        h+= '<li><span>[' + v[0] + '] — ' + v[1] + 
        (v[2]==""?"":' — ' + v[2]) + '</span></li>';
    });
    h += '</ul><p>TOTAL: ' + MWORDS.length + 
    '</p><h4>Non-Word List</span></h4><ul class="nonwordlist">';
    $.each(NOWORDS, function(k,v) {
        h+= '<li>[' + v[0] + '] — ' + v[1] + '</li>';
    });
    h += '</ul><p>TOTAL: ' + NOWORDS.length + '</p>'
    $('#check_text').append(h);
</script>

    <?php
}


/**
 * Parse the input text.
 *
 * @param string     $text Text to parse
 * @param string|int $lid  Language ID (LgID from languages table)
 * @param int        $id   References whether the text is new to the database
 *                     $id = -1     => Check, return protocol
 *                     $id = -2     => Only return sentence array
 *                     $id = TextID => Split: insert sentences/textitems entries in DB
 *
 * @return null|string[] The sentence array if $id = -2
 *
 * @psalm-return non-empty-list<string>|null
 */
function splitCheckText($text, $lid, $id) 
{
    $wl = array();
    $wl_max = 0;
    $mw_sql = '';
    $sql = "SELECT LgRightToLeft FROM languages WHERE LgID=$lid";
    $res = do_mysqli_query($sql);
    $record = mysqli_fetch_assoc($res);
    // Just checking if LgID exists with ID should be enough
    if ($record == false) {
        my_die("Language data not found: $sql"); 
    }
    $rtlScript = $record['LgRightToLeft'];
    mysqli_free_result($res);

    if ($id == -2) {
        /*
        Replacement code not created yet 

        trigger_error(
            "Using splitCheckText with \$id == -2 is deprectad and won't work in 
            LWT 3.0.0. Use format_text instead.", 
            E_USER_WARNING
        );*/
        return prepare_text_parsing($text, -2, $lid);
    }
    prepare_text_parsing($text, $id, $lid);

    // Check text
    if ($id == -1) {
        check_text_valid($lid);
    }

    if ($id > 0) {
        import_temptextitems($id, $lid);
    }
    // Check text
    if ($id == -1) {
        check_text($sql, (bool)$rtlScript, $wl);
    }
    do_mysqli_query("TRUNCATE TABLE temptextitems");
}


/**
 * Reparse all texts in order.
 */
function reparse_all_texts(): void 
{
    runsql('TRUNCATE sentences', '');
    runsql('TRUNCATE textitems2', '');
    init_word_count();
    $sql = "select TxID, TxLgID from texts";
    $res = do_mysqli_query($sql);
    while ($record = mysqli_fetch_assoc($res)) {
        $id = (int) $record['TxID'];
        splitCheckText(
            get_first_value(
                'select TxText as value 
                from texts 
                where TxID = ' . $id
            ), 
            $record['TxLgID'], $id 
        );
    }
    mysqli_free_result($res);
}

/**
 * Update the database if it is using an outdate version.
 */
function update_database()
{
    apply_migrations();
}

/**
 * Check and/or update the database.
 *
 * @global mysqli $DBCONNECTION Connection to the database
 *
 * TODO - deprecate and remove this.
 */
function check_update_db($debug, $dbname): void 
{
    $tables = array();
    
    $res = do_mysqli_query("SHOW TABLES");
    while ($row = mysqli_fetch_row($res)) {
        $tables[] = $row[0]; 
    }
    mysqli_free_result($res);

    if (count($tables) == 0) {
        install_new_db();
    }

    // Update the database
    update_database();

    // TODO: move this to testing start, _or_ get rid of testing altogether.
    // Do Scoring once per day, clean Word/Texttags, and optimize db
    $lastscorecalc = getSetting('lastscorecalc');
    $today = date('Y-m-d');
    if ($lastscorecalc != $today) {
        if ($debug) { 
            echo '<p>DEBUG: Doing score recalc. Today: ' . $today . ' / Last: ' . $lastscorecalc . '</p>'; 
        }
        runsql("UPDATE words SET " . make_score_random_insert_update('u') ." where WoTodayScore>=-100 and WoStatus<98", '');
        runsql("DELETE wordtags FROM (wordtags LEFT JOIN tags on WtTgID = TgID) WHERE TgID IS NULL", '');
        runsql("DELETE wordtags FROM (wordtags LEFT JOIN words on WtWoID = WoID) WHERE WoID IS NULL", '');
        runsql("DELETE texttags FROM (texttags LEFT JOIN tags2 on TtT2ID = T2ID) WHERE T2ID IS NULL", '');
        runsql("DELETE texttags FROM (texttags LEFT JOIN texts on TtTxID = TxID) WHERE TxID IS NULL", '');
        optimizedb();
        saveSetting('lastscorecalc', $today);
    }
}


/**
 * Make the connection to the database.
 *
 * @return mysqli Connection to the database
 *
 * @psalm-suppress UndefinedDocblockClass
 */
function connect_to_database($server, $userid, $passwd, $dbname): mysqli 
{
    // @ suppresses error messages
    
    // Necessary since mysqli_report default setting in PHP 8.1+ has changed
    @mysqli_report(MYSQLI_REPORT_OFF); 

    $DBCONNECTION = @mysqli_connect($server, $userid, $passwd, $dbname);

    if (!$DBCONNECTION && mysqli_connect_errno() == 1049) {
        // Database unknown, try with generic database
        $DBCONNECTION = @mysqli_connect($server, $userid, $passwd);
        if (!$DBCONNECTION) { 
            my_die(
                'DB connect error (MySQL not running or connection parameters are wrong; start MySQL and/or correct file "connect.inc.php"). 
                Please read the documentation: https://hugofara.github.io/lwt/docs/install.html 
                [Error Code: ' . mysqli_connect_errno() . 
                ' / Error Message: ' . mysqli_connect_error() . ']'
            );
        }
        $result = mysqli_query(
            $DBCONNECTION, 
            "CREATE DATABASE `" . $dbname . "` 
            DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci"
        );
        if (!$result) {
            my_die("Failed to create database! " . $result);
        }
        mysqli_close($DBCONNECTION);
        $DBCONNECTION = @mysqli_connect($server, $userid, $passwd, $dbname);
    }

    if (!$DBCONNECTION) { 
        my_die(
            'DB connect error (MySQL not running or connection parameters are wrong; start MySQL and/or correct file "connect.inc.php"). 
            Please read the documentation: https://hugofara.github.io/lwt/docs/install.html 
            [Error Code: ' . mysqli_connect_errno() . 
            ' / Error Message: ' . mysqli_connect_error() . ']'
        ); 
    }

    @mysqli_query($DBCONNECTION, "SET NAMES 'utf8'");

    // @mysqli_query($DBCONNECTION, "SET SESSION sql_mode = 'STRICT_ALL_TABLES'");
    @mysqli_query($DBCONNECTION, "SET SESSION sql_mode = ''");
    return $DBCONNECTION;
}


// --------------------  S T A R T  --------------------------- //

// Start Timer
if (!empty($dspltime)) {
    get_execution_time(); 
}

/**
 * @var mysqli $DBCONNECTION Connection to the database
 */
global $DBCONNECTION;
$DBCONNECTION = connect_to_database($server, $userid, $passwd, $dbname);


/** DISABLING database updates.
// TODO: remove this and document db migrations.
// check/update db - only once per session.
global $debug;
if (!isset($_SESSION['DBUPDATED'])) {
    check_update_db($debug, $dbname);
    $_SESSION['DBUPDATED'] = true;
}
*/

?>