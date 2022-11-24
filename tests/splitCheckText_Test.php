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

    public function test_smoke_test()
    {
        splitCheckText($this->text, $this->langid, 1);
        $sql = " select ti2woid, ti2seid, ti2order, ti2text from textitems2 order by ti2order";
        $expected = [
            "0; 1; 1; Hola",
            "0; 1; 2;  ",
            "0; 1; 3; tengo",
            "0; 1; 4;  ",
            "0; 1; 5; un",
            "0; 1; 6;  ",
            "0; 1; 7; gato",
            "0; 1; 8; .",
            "0; 2; 9;  ",
            "0; 2; 10; No",
            "0; 2; 11;  ",
            "0; 2; 12; tengo",
            "0; 2; 13;  ",
            "0; 2; 14; una",
            "0; 2; 15;  ",
            "0; 2; 16; lista",
            "0; 2; 17; .",
            "0; 3; 18;  ",
            "0; 3; 19; Ella",
            "0; 3; 20;  ",
            "0; 3; 21; tiene",
            "0; 3; 22;  ",
            "0; 3; 23; una",
            "0; 3; 24;  ",
            "0; 3; 25; bebida",
            "0; 3; 26; ."
        ];
        DbHelpers::assertTableContains($sql, $expected);
    }

}
