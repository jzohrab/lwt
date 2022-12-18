<?php

namespace App\Domain;

use App\Entity\Text;

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
        echo $sql . "\n";
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
            "TRUNCATE TABLE temptextitems",
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
            // Ref parse_japanese_text($text, $id) in
            // https://github.com/HugoFara/lwt/blob/master/inc/database_connect.php
        }

        $this->parse_standard_text($text);

        $this->import_temptextitems($text);

        // $this->exec_sql("TRUNCATE TABLE temptextitems");
    }


    /**
     * @param string $text Text to parse
     * @param int    $id   Text ID. If $id == -2, only split the text.
     * @param int    $lid  Language ID.
     *
     * @return null|string[] If $id == -2 return a splitted version of the text.
     */
    private function parse_standard_text(Text $entity): ?array
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
        $text = preg_replace(
            array(
                '/([^' . $termchar . '])/u',
                '/\n([' . $splitSentence . '][\'`"”)\]‘’‹›“„«»』」]*)\n\t/u',
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

        echo "\nWRITING FILE:\n";
        echo $text;
        echo "\nDONE\n";
        $file_name = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "tmpti.txt";
        $fp = fopen($file_name, 'w');
        fwrite($fp, $text);
        fclose($fp);
        /*
do_mysqli_query(
            "SET @order=0, @sid=0, @count=0;"
        );
        */

        $this->conn->query("SET @order=0, @sid=0, @count=0");
        // TODO:parsing - fix the text file to be loaded so it already has
        // order, sid, and count ... no need for this query to have more
        // logic.

        // TODO:parsing Drop the temp table and re-create it.
        $file_name = mysqli_real_escape_string($this->conn, $file_name);
$sql = "LOAD DATA LOCAL INFILE '{$file_name}'
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

// $this->conn->execute($sql);

//Try to execute query (not stmt) and catch mysqli error from engine and php error
if (!($stmt = $this->conn->query($sql))) {
    echo "\nQuery execute failed: ERRNO: (" . $this->conn->errno . ") " . $this->conn->error;
};
        unlink($file_name);

        return null;
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
                Ti2LgID, Ti2TxID, Ti2WoID, Ti2SeID, Ti2Order, Ti2WordCount, Ti2Text
            )
            select {$lid}, {$id}, WoID, TiSeID + {$firstSeID}, TiOrder, TiWordCount, TiText 
            FROM temptextitems 
            left join words 
            on lower(TiText) = WoTextLC and TiWordCount>0 and WoLgID = {$lid} 
            order by TiOrder,TiWordCount";
        echo "\n\n" . $addti2 . "\n\n";
        $this->exec_sql($addti2);

        /*
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
        */
    }

}