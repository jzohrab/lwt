<?php

namespace App\Domain;

use App\Entity\Text;
use App\Entity\Language;

require_once __DIR__ . '/../../connect.inc.php';


class Parser {

    /** PUBLIC **/
    
    public static function parse(Text $text) {
        $p = new Parser();
        $p->parseText($text);
    }

    public static function load_local_infile_enabled(): bool {
        global $userid, $passwd, $server, $dbname; // From connect.inc.php
        $conn = @mysqli_connect($server, $userid, $passwd, $dbname);
        $val = $conn->query("SELECT @@GLOBAL.local_infile as val")->fetch_array()[0];
        return intval($val) == 1;
    }

    public function __construct()
    {
        global $userid, $passwd, $server, $dbname; // From connect.inc.php
        $conn = @mysqli_connect($server, $userid, $passwd, $dbname);
        @mysqli_query($conn, "SET SESSION sql_mode = ''");
        $this->conn = $conn;

        if (!Parser::load_local_infile_enabled()) {
            $msg = "SELECT @@GLOBAL.local_infile must be 1, check your mysql configuration.";
            throw new \Exception($msg);
        }
    }

    /** PRIVATE **/

    private function exec_sql($sql, $params = null) {
        // echo $sql . "\n";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new \Exception($this->conn->error);
        }
        if ($params) {
            $stmt->bind_param(...$params);
        }
        if (!$stmt->execute()) {
            throw new \Exception($stmt->error);
        }
        return $stmt->get_result();
    }
 
    private function parseText(Text $text) {

        $id = $text->getID();
        $cleanup = [
            "DROP TABLE IF EXISTS temptextitems",
            "DELETE FROM sentences WHERE SeTxID = $id",
            "DELETE FROM textitems2 WHERE Ti2TxID = $id"
        ];
        foreach ($cleanup as $sql)
            $this->exec_sql($sql);

        $rechars = $text
                 ->getLanguage()
                 ->getLgRegexpWordCharacters();
        $isJapanese = 'MECAB' == strtoupper(trim($rechars));
        if ($isJapanese) {
            // TODO:japanese MECAB parsing.
            throw new \Exception("MECAB parsing not supported");
            // Ref parse_japanese_text($text, $id)
            // and insert_expression_from_mecab()
            // in
            // https://github.com/HugoFara/lwt/blob/master/inc/database_connect.php
        }

        // TODO: get rid of duplicate processing.
        $cleantext = $this->legacy_clean_standard_text($text);
        $newcleantext = $this->new_clean_standard_text($text);
        if ($cleantext != $newcleantext) {
            echo "Legacy\n:";
            echo $cleantext . "\n\n";
            echo "New\n:";
            echo $newcleantext . "\n\n";
            throw new \Exception("not equal cleaning?");
        }
        $this->load_temptextitems($newcleantext);

        $this->import_temptextitems($text);

        // $this->exec_sql("DROP TABLE IF EXISTS temptextitems");
    }


    // TODO:obsolete - currently running this in parallel with the
    // newer method below it.  Can delete in the future after have
    // done some more imports.
    /**
     * @param string $text Text to clean, using regexs.
     */
    private function legacy_clean_standard_text(Text $entity): string
    {
        $lang = $entity->getLanguage();

        $text = $entity->getText();

        // Initial cleanup.
        $text = str_replace("\r\n", "\n", $text);
        // because of sentence special characters
        $text = str_replace(array('}','{'), array(']','['), $text);

        $replace = explode("|", $lang->getLgCharacterSubstitutions());
        foreach ($replace as $value) {
            $fromto = explode("=", trim($value));
            if (count($fromto) >= 2) {
                $rfrom = trim($fromto[0]);
                $rto = trim($fromto[1]);
                $text = str_replace($rfrom, $rto, $text);
            }
        }

        $text = str_replace("\n", " ¶", $text);
        $text = trim($text);
        if ($lang->isLgSplitEachChar()) {
            $text = preg_replace('/([^\s])/u', "$1\t", $text);
        }
        $text = preg_replace('/\s+/u', ' ', $text);

        $splitSentence = $lang->getLgRegexpSplitSentences();
        
        $callback = function($matches) use ($lang) {
            $notEnd = $lang->getLgExceptionsSplitSentences();
            return $this->find_latin_sentence_end($matches, $notEnd);
        };
        $re = "/(\S+)\s*((\.+)|([$splitSentence]))([]'`\"”)‘’‹›“„«»』」]*)(?=(\s*)(\S+|$))/u";
        $text = preg_replace_callback($re, $callback, $text);

        // Para delims include \r
        $text = str_replace(array("¶"," ¶"), array("¶\r","\r¶"), $text);

        $termchar = $lang->getLgRegexpWordCharacters();
        $punctchars = "'`\"”)\]‘’‹›“„«»』」";
        $text = preg_replace(
            array(
                '/([^' . $termchar . '])/u',
                '/\n([' . $splitSentence . '][' . $punctchars . ']*)\n\t/u',
                '/([0-9])[\n]([:.,])[\n]([0-9])/u'
            ),
            array("\n$1\n", "$1", "$1$2$3"),
            $text
        );

        $text = str_replace(array("\t","\n\n"), array("\n",""), $text);

        $text = preg_replace(
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
                $text
        );

        $text = trim($text);

        $text = preg_replace("/(\n|^)(?!1\t)/u", "\n0\t", $text);

        if ($lang->isLgRemoveSpaces()) {
            $text = str_replace(' ', '', $text);
        }

        return $text;
    }

 
     /**
     * @param string $text Text to clean, using regexs.
     */
    private function new_clean_standard_text(Text $entity): string
    {
        // A (possibly) easier way to do substitutions -- each
        // pair in $replacements is run in order.
        // Possible entries:
        // ( <src string or regex string (starting with '/')>, <target (string or callback)> [, <condition>] )
        $do_replacements = function($text, $replacements) {
            foreach($replacements as $r) {
                if ($r == 'trim') {
                    $text = trim($text);
                    continue;
                }

                $src = $r[0];
                $tgt = $r[1];

                if (count($r) == 3) {
                    if ($r[2] == false) {
                        continue;
                    }
                }

                if (is_string($tgt)) {
                    if (substr($src, 0, 1) == '/') {
                        $text = preg_replace($src, $tgt, $text);
                    }
                    else {
                        $text = str_replace($src, $tgt, $text);
                    }
                }
                else {
                    $text = preg_replace_callback($src, $tgt, $text);
                }
            }
            return $text;
        };

        $lang = $entity->getLanguage();

        $text = $entity->getText();

        $replace = explode("|", $lang->getLgCharacterSubstitutions());
        foreach ($replace as $value) {
            $fromto = explode("=", trim($value));
            if (count($fromto) >= 2) {
                $rfrom = trim($fromto[0]);
                $rto = trim($fromto[1]);
                $text = str_replace($rfrom, $rto, $text);
            }
        }

        $splitSentencecallback = function($matches) use ($lang) {
            $notEnd = $lang->getLgExceptionsSplitSentences();
            return $this->find_latin_sentence_end($matches, $notEnd);
        };

        $splitSentence = $lang->getLgRegexpSplitSentences();
        $resplitsent = "/(\S+)\s*((\.+)|([$splitSentence]))([]'`\"”)‘’‹›“„«»』」]*)(?=(\s*)(\S+|$))/u";

        $termchar = $lang->getLgRegexpWordCharacters();
        $punctchars = "'`\"”)\]‘’‹›“„«»』」";
        
        $text = $do_replacements($text, [
            [ "\r\n", "\n" ],
            [ '}', ']'],
            [ '{', '['],
            [ "\n", " ¶" ],
            [ '/([^\s])/u', "$1\t", $lang->isLgSplitEachChar() ],
            'trim',
            [ '/\s+/u', ' ' ],
            [ $resplitsent, $splitSentencecallback ],
            [ "¶", "¶\r" ],
            [ " ¶", "\r¶" ],
            [ '/([^' . $termchar . '])/u', "\n$1\n" ],
            [ '/\n([' . $splitSentence . '][' . $punctchars . ']*)\n\t/u', "\n$1\n" ],
            [ '/([0-9])[\n]([:.,])[\n]([0-9])/u', "$1$2$3" ],
            [ "\t", "\n" ],
            [ "\n\n", "" ],
            [ "/\r(?=[]'`\"”)‘’‹›“„«»』」 ]*\r)/u", "" ],
            [ '/[\n]+\r/u', "\r" ],
            [ '/\r([^\n])/u', "\r\n$1" ],
            [ "/\n[.](?![]'`\"”)‘’‹›“„«»』」]*\r)/u", ".\n" ],
            [ "/(\n|^)(?=.?[$termchar][^\n]*\n)/u", "\n1\t" ],
            'trim',
            [ "/(\n|^)(?!1\t)/u", "\n0\t" ],
            [ ' ', '', $lang->isLgRemoveSpaces() ]
        ]);
        
        return $text;
    }


    /**
     * Load temptextitems using load local infile.
     */
    private function load_temptextitems($text)
    {
        $file_name = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "tmpti.txt";
        $fp = fopen($file_name, 'w');
        fwrite($fp, $text);
        fclose($fp);

        /*
        echo "\n";
        echo $text;
        echo "\n";
        */
        
        $this->conn->query("drop table if exists temptextitems");

        $sql = "create table temptextitems (
          TiSeID int not null, TiOrder int not null, TiWordCount int not null, TiText varchar(250) not null
        )";
        $this->conn->query($sql);

        $this->conn->query("SET @order=0, @sid=0, @count=0");
        // TODO:parsing - fix the text file to be loaded so it already has
        // order, sid, and count ... no need for this query to have more
        // logic.

        $file_name = mysqli_real_escape_string($this->conn, $file_name);
        $sql = "LOAD DATA LOCAL INFILE '{$file_name}'
        INTO TABLE temptextitems
        FIELDS TERMINATED BY '\\t' LINES TERMINATED BY '\\n' (@word_count, @term)
        SET
            TiSeID = @sid,
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

        if (!($this->conn->query($sql))) {
            $msg = "Query execute failed: ERRNO: (" . $this->conn->errno . ") " . $this->conn->error;
            throw new \Exception($msg);
        };
        unlink($file_name);
    }


    /**
     * Find end-of-sentence characters in a sentence using latin alphabet.
     * 
     * @param string[] $matches       All the matches from a capturing regex
     * @param string   $noSentenceEnd If different from '', can declare that a string a not the end of a sentence.
     * 
     * @return string $matches[0] with ends of sentences marked with \t and \r.
     */
    private function find_latin_sentence_end($matches, $noSentenceEnd)
    {
        if (!strlen($matches[6]) && strlen($matches[7]) && preg_match('/[a-zA-Z0-9]/', substr($matches[1], -1))) { 
            return preg_replace("/[.]/", ".\t", $matches[0]); 
        }
        if (is_numeric($matches[1])) {
            if (strlen($matches[1]) < 3) { 
                return $matches[0];
            }
        } else if ($matches[3] && (preg_match('/^[B-DF-HJ-NP-TV-XZb-df-hj-np-tv-xz][b-df-hj-np-tv-xzñ]*$/u', $matches[1]) || preg_match('/^[AEIOUY]$/', $matches[1]))
        ) { 
            return $matches[0]; 
        }
        if (preg_match('/[.:]/', $matches[2]) && preg_match('/^[a-z]/', $matches[7])) {
            return $matches[0];
        }
        if ($noSentenceEnd != '' && preg_match("/^($noSentenceEnd)$/", $matches[0])) {
            return $matches[0]; 
        }
        return $matches[0] . "\r";
    }


    /**
     * Move data from temptextitems to final tables.
     * 
     * @param int    $id  New default text ID
     * @param int    $lid New default language ID
     * 
     * @return void
     */
    private function import_temptextitems(Text $text)
    {
        $id = $text->getID();
        $lid = $text->getLanguage()->getLgID();

        $sql = "INSERT INTO sentences (SeLgID, SeTxID, SeOrder, SeFirstPos, SeText)
            SELECT {$lid}, {$id}, TiSeID, 
            min(if(TiWordCount=0, TiOrder+1, TiOrder)),
            GROUP_CONCAT(TiText order by TiOrder SEPARATOR \"\") 
            FROM temptextitems 
            group by TiSeID";
        $this->exec_sql($sql);

        $minmax = "SELECT MIN(SeID) as minseid, MAX(SeID) as maxseid FROM sentences WHERE SeTxID = {$id}";
        $rec = $this->conn
             ->query($minmax)->fetch_array();
        $firstSeID = intval($rec['minseid']);
        $lastSeID = intval($rec['maxseid']);
    
        $addti2 = "INSERT INTO textitems2 (
                Ti2LgID, Ti2TxID, Ti2WoID, Ti2SeID, Ti2Order, Ti2WordCount, Ti2Text, Ti2TextLC
            )
            select {$lid}, {$id}, WoID, TiSeID + {$firstSeID}, TiOrder, TiWordCount, TiText, lower(TiText) 
            FROM temptextitems 
            left join words 
            on lower(TiText) = WoTextLC and TiWordCount>0 and WoLgID = {$lid} 
            order by TiOrder,TiWordCount";
        // echo "\n\n" . $addti2 . "\n\n";
        $this->exec_sql($addti2);

        // For each expession in the language, add expressions for the sentence range.
        // Inefficient, but for now I don't care -- will see how slow it is.
        $sentenceRange = [ $firstSeID, $lastSeID ];
        $mwordsql = "SELECT * FROM words WHERE WoLgID = $lid AND WoWordCount > 1";
        $res = $this->conn->query($mwordsql);
        while ($record = mysqli_fetch_assoc($res)) {
            $this->insertExpressions(
                $record['WoTextLC'],
                $text->getLanguage(),
                $record['WoID'],
                $record['WoWordCount'],
                $sentenceRange);
        }
        mysqli_free_result($res);

    }


    /** Expressions **************************/


    // TODO:parsing - sentence range feels redundant, but is used elsewhere when new expr defined and ll texts in language have to be updated.
    /**
     * Alter the database to add a new word
     *
     * @param string $textlc Text in lower case
     * @param Language the language
     * @param string $len
     * @param array  $sentenceIDRange   [ lower SeID, upper SeID ] to consider.
     */
    private function insertExpressions(
        $textlc, Language $lang, $wid, $len, $sentenceIDRange = NULL
    )
    {
        $splitEachChar = $lang->isLgSplitEachChar();
        if ($splitEachChar) {
            $textlc = preg_replace('/([^\s])/u', "$1 ", $textlc);
        }

        $lid = $lang->getLgID();
        $this->insert_standard_expression(
            $lang, $textlc, $wid, $len, $sentenceIDRange
        );
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
    private function pregMatchCapture($matchAll, $pattern, $subject, $offset = 0)
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
     * @param array  $sentenceIDRange
     */
    private function insert_standard_expression(
        Language $lang, $textlc, $wid, $len, $sentenceIDRange
    )
    {
        $lid = $lang->getLgID();

        // DEBUGGING HELPER FOR FUTURE, because this code is brutal and
        // needs to be completely replaced, but I need to understand it
        // first.
        // Change $problemterm to the term that's not getting handled
        // correctly.  e.g.,
        // $problemterm = mb_strtolower('de refilón');
        $problemterm = mb_strtolower('un gato');
        $logme = function($s) {};
        $logdump = function($s) {};
        if ($textlc == $problemterm) {
            $logme = function($s) { echo "{$s}\n"; };
            $logdump = function($s) { var_dump($s); };
            $logme("\n\n================");
            $r = implode(', ', $sentenceIDRange);
            $logme("Starting search for $textlc, lid = $lid, wid = $wid, len = $len, range = {$r}");
        }
        
        $appendtext = array();
        $sqlarr = array();

        $removeSpaces = $lang->isLgRemoveSpaces();
        $splitEachChar = $lang->isLgSplitEachChar();
        $termchar = $lang->getLgRegexpWordCharacters();
        
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
        AND SeText LIKE LIKE concat('%', ?, '%') 
        AND Ti2WordCount < 2 
        GROUP BY SeID";
        } else {
            $sql = "SELECT * FROM sentences 
        WHERE {$whereSeIDRange} SeLgID = $lid AND SeText LIKE concat('%', ?, '%')";
        }
        $logme($sql);

        $params = [ 's', $textlc ];
        $res = $this->exec_sql($sql, $params);
        
        $wis = $textlc;

        $notermchar = "/[^$termchar]({$textlc})[^$termchar]/ui";
        // For each sentence in the language containing the query
        $matches = null;
        while ($record = mysqli_fetch_assoc($res)) {
            $string = ' ' . $record['SeText'] . ' ';
            $logme('"' . $string . '"');
            if ($splitEachChar) {
                $string = preg_replace('/([^\s])/u', "$1 ", $string);
            } else if ($removeSpaces == 1) {
                $ma = $this->pregMatchCapture(
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
                $matches = $this->pregMatchCapture(false, $notermchar, " $string ", $last_pos - 1);
                if (count($matches) == 0) {
                    $logme("preg_match returned no matches?");
                }
                else {
                    $c = count($matches);
                    $logme("big pregmatch = $c");
                    $logme('match data ---------------------');
                    $logdump($matches);
                    $logme('/end ---------------------');
                }
                if ($splitEachChar || $removeSpaces || count($matches) > 0) {
                    // Number of terms before group
                    $beforesubstr = mb_substr($string, 0, $last_pos, 'UTF-8');
                    $logme("Checking count of terms in: $beforesubstr");
                    $before = $this->pregMatchCapture(true, "/([$termchar]+)/u", $beforesubstr);
                    // var_dump($before);
                
                    $cnt = null;
                    if (count($before) == 0) {
                        // Term is at start of sentence.
                        $cnt = 0;
                    }
                    else {
                        // Note pregMatchCapture returns a few arrays, we want
                        // the first one.  (I confess I don't grok what's
                        // happening here, but inspecting a var_dump of the
                        // returned data led me to this.  jz)
                        $cnt = count($before[0]);
                    }
                
                    $pos = 2 * $cnt + (int) $record['SeFirstPos'];
                    $logme("Got count = $cnt, pos = $pos");
                    // $txt = $textlc;
                
                    $txt = $matches[1][0];
                    if ($txt != $textlc) {
                        $txt = $splitEachChar ? $wis : $matches[1][0]; 
                    }

                
                    $sql = "INSERT INTO textitems2
                  (Ti2WoID,Ti2LgID,Ti2TxID,Ti2SeID,Ti2Order,Ti2WordCount,Ti2Text)
                  VALUES (?, ?, ?, ?, ?, ?, ?)";
                    $params = array(
                        "iiiiiis",
                        $wid, $lid, $record['SeTxID'], $record['SeID'], $pos, $len, $txt);
                    $this->exec_sql($sql, $params);
                    $pstring = implode(',', $params);
                    $logme("-----------------\nadded entry: {$pstring} \n-----------------");
                }
                // Cut the sentence to before the right-most term starts
                $string = mb_substr($string, 0, $last_pos, 'UTF-8');
                $last_pos = mb_strripos($string, $textlc, 0, 'UTF-8');
                $logme("string is now: $string");
                $logme("last_pos is now: $last_pos");
            }
        }
        mysqli_free_result($res);
    }

}