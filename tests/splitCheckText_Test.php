<?php declare(strict_types=1);

require_once __DIR__ . '/../inc/word_input_form.php';
require_once __DIR__ . '/DatabaseTestBase.php';

use PHPUnit\Framework\TestCase;

final class splitCheckText_Test extends DatabaseTestBase
{

    public function childSetUp(): void
    {
        $this->load_languages();
    }

    public function tearDown(): void
    {
        // echo "tearing down ... \n";
    }

    public function test_split_check_no_words_defined()
    {
        $this->load_spanish_texts();
        $t = $this->spanish_hola_text;
        splitCheckText($t->getText(), $this->spanish->getLgID(), $t->getID());
        $sql = "select ti2seid, ti2order, ti2text from textitems2 where ti2woid = 0 order by ti2order";

        // Note the SeIDs start at 5 because the text was initially
        // parsed when the Text was saved to the TextRepository,
        // but then calling splitCheckText directly deletes the
        // existing parsed data.
        $expected = [
            "5; 1; Hola",
            "5; 2;  ",
            "5; 3; tengo",
            "5; 4;  ",
            "5; 5; un",
            "5; 6;  ",
            "5; 7; gato",
            "5; 8; .",
            "6; 9;  ",
            "6; 10; No",
            "6; 11;  ",
            "6; 12; tengo",
            "6; 13;  ",
            "6; 14; una",
            "6; 15;  ",
            "6; 16; lista",
            "6; 17; .",
            "7; 18; ¶",
            "8; 19; Ella",
            "8; 20;  ",
            "8; 21; tiene",
            "8; 22;  ",
            "8; 23; una",
            "8; 24;  ",
            "8; 25; bebida",
            "8; 26; ."
        ];
        DbHelpers::assertTableContains($sql, $expected);
    }

    public function test_split_check_words_defined()
    {
        $this->load_spanish_words();
        $this->load_spanish_texts();
        $t = $this->spanish_hola_text;
        splitCheckText($t->getText(), $this->spanish->getLgID(), $t->getID());

        $sql = "select ti2seid, ti2order, ti2text from textitems2 where ti2woid = 0 order by ti2order";
        $expected = [
            "5; 1; Hola",
            "5; 2;  ",
            "5; 3; tengo",
            "5; 4;  ",
            "5; 5; un",
            "5; 6;  ",
            "5; 7; gato",
            "5; 8; .",
            "6; 9;  ",
            "6; 10; No",
            "6; 11;  ",
            "6; 12; tengo",
            "6; 13;  ",
            "6; 14; una",
            "6; 15;  ",
            "6; 17; .",
            "7; 18; ¶",
            "8; 19; Ella",
            "8; 20;  ",
            "8; 21; tiene",
            "8; 22;  ",
            "8; 23; una",
            "8; 24;  ",
            "8; 25; bebida",
            "8; 26; ."
        ];
        DbHelpers::assertTableContains($sql, $expected);

        $sql = "select ti2woid, ti2seid, ti2order, ti2text from textitems2 where ti2woid > 0 order by ti2order";
        $expected = [
            "1; 5; 5; un gato",
            "2; 6; 16; lista",
            "3; 8; 21; tiene una"
        ];
        DbHelpers::assertTableContains($sql, $expected);
    }

}
