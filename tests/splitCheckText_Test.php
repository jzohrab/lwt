<?php declare(strict_types=1);

require_once __DIR__ . '/../inc/word_input_form.php';
require_once __DIR__ . '/db_helpers.php';

use PHPUnit\Framework\TestCase;

final class splitCheckText_Test extends TestCase
{

    public function setUp(): void
    {
        $inimsg = 'php.ini must set mysqli.allow_local_infile to 1.';
        $this->assertEquals(ini_get('mysqli.allow_local_infile'), '1', $inimsg);

        // Set up db.
        DbHelpers::ensure_using_test_db();
        DbHelpers::clean_db();
        DbHelpers::load_language_spanish();
        $this->langid = (int) get_first_value("select LgID as value from languages");

        $this->text = "Hola tengo un gato.  No tengo una lista.  Ella tiene una bebida.";
        DbHelpers::add_text($this->text, $this->langid);
    }

    public function tearDown(): void
    {
        // echo "tearing down ... \n";
    }

    public function test_split_check_no_words_defined()
    {
        splitCheckText($this->text, $this->langid, 1);
        $sql = "select ti2seid, ti2order, ti2text from textitems2 where ti2woid = 0 order by ti2order";
        $expected = [
            "1; 1; Hola",
            "1; 2;  ",
            "1; 3; tengo",
            "1; 4;  ",
            "1; 5; un",
            "1; 6;  ",
            "1; 7; gato",
            "1; 8; .",
            "2; 9;  ",
            "2; 10; No",
            "2; 11;  ",
            "2; 12; tengo",
            "2; 13;  ",
            "2; 14; una",
            "2; 15;  ",
            "2; 16; lista",
            "2; 17; .",
            "3; 18;  ",
            "3; 19; Ella",
            "3; 20;  ",
            "3; 21; tiene",
            "3; 22;  ",
            "3; 23; una",
            "3; 24;  ",
            "3; 25; bebida",
            "3; 26; ."
        ];
        DbHelpers::assertTableContains($sql, $expected);
    }

    public function test_split_check_words_defined()
    {
        $lid = $this->langid;
        DbHelpers::add_word($lid, "Un gato", "un gato", 1, 2);
        DbHelpers::add_word($lid, "lista", "lista", 1, 1);
        DbHelpers::add_word($lid, "tiene una", "tiene una", 1, 2);


        splitCheckText($this->text, $this->langid, 1);
        $sql = "select ti2seid, ti2order, ti2text from textitems2 where ti2woid = 0 order by ti2order";
        $expected = [
            "1; 1; Hola",
            "1; 2;  ",
            "1; 3; tengo",
            "1; 4;  ",
            "1; 5; un",
            "1; 6;  ",
            "1; 7; gato",
            "1; 8; .",
            "2; 9;  ",
            "2; 10; No",
            "2; 11;  ",
            "2; 12; tengo",
            "2; 13;  ",
            "2; 14; una",
            "2; 15;  ",
            // "2; 16; lista",  // Now is a word
            "2; 17; .",
            "3; 18;  ",
            "3; 19; Ella",
            "3; 20;  ",
            "3; 21; tiene",
            "3; 22;  ",
            "3; 23; una",
            "3; 24;  ",
            "3; 25; bebida",
            "3; 26; ."
        ];
        DbHelpers::assertTableContains($sql, $expected);

        $sql = "select ti2woid, ti2seid, ti2order, ti2text from textitems2 where ti2woid > 0 order by ti2order";
        $expected = [
            "1; 1; 5; un gato",
            "2; 2; 16; lista",
            "3; 3; 21; tiene una"
        ];
        DbHelpers::assertTableContains($sql, $expected);
    }

}
