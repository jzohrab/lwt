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

    public function __construct()
    {
        global $userid, $passwd, $server, $dbname; // From connect.inc.php
        $conn = @mysqli_connect($server, $userid, $passwd, $dbname);
        @mysqli_query($conn, "SET SESSION sql_mode = ''");
        $this->conn = $conn;
    }

    /** PRIVATE **/

    private function exec_sql($sql, $params = null) {
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new Exception($this->conn->error);
        }
        if ($params) {
            $stmt->bind_param(...$params);
        }
        if (!$stmt->execute()) {
            throw new \Exception($stmt->error);
        }
        // return $stmt->get_result();
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

        $this->prepare_text_parsing($text, $id, $lid);
        // $this->import_temptextitems($id, $lid);

        $this->exec_sql("TRUNCATE TABLE temptextitems");
    }

    private function prepare_text_parsing(Text $entity) {

        $text = $entity->getText();

        // Initial cleanup.
        $text = str_replace("\r\n", "\n", $text);
        // because of sentence special characters
        $text = str_replace(array('}','{'), array(']','['), $text);

        $charSubs = $entity
                  ->getLanguage()
                  ->getLgCharacterSubstitutions();
        $replace = explode("|", $charSubs);
        foreach ($replace as $value) {
            $fromto = explode("=", trim($value));
            if (count($fromto) >= 2) {
                $rfrom = trim($fromto[0]);
                $rto = trim($fromto[1]);
                $text = str_replace($rfrom, $rto, $text);
            }
        }

        $termchar = $record['LgRegexpWordCharacters'];
        if ('MECAB' == strtoupper(trim($termchar))) {
            // TODO:japanese MECAB parsing.
            throw new \Exception("MECAB parsing not supported");
            // Ref code in https://github.com/HugoFara/lwt/blob/master/inc/database_connect.php
            // return parse_japanese_text($text, $id);
        }

        return $this->parse_standard_text($text, $id, $lid);
    }

    /**
     * Parse non-japanese text.
     *
     * @param string $text Text to parse
     * @param int    $id   Text ID. If $id == -2, only split the text.
     * @param int    $lid  Language ID.
     *
     * @return null|string[] If $id == -2 return a splitted version of the text.
     */
    private function parse_standard_text($text, $id, $lid): ?array
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
    // "\r" => Sentence delimiter, "\t" and "\n" => Word delimiter

    $text = preg_replace_callback(
        "/(\S+)\s*((\.+)|([$splitSentence]))([]'`\"”)‘’‹›“„«»』」]*)(?=(\s*)(\S+|$))/u",
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

}