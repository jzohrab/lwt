<?php declare(strict_types=1);

require_once __DIR__ . '/../../../src/Domain/Parser.php';
require_once __DIR__ . '/../../DatabaseTestBase.php';

use App\Domain\Parser;

final class Parser_Test extends DatabaseTestBase
{

    public function childSetUp(): void
    {
        $this->load_languages();
    }

    public function tearDown(): void
    {
        // echo "tearing down ... \n";
    }

    public function test_existing_cruft_deleted() {
        $this->load_spanish_texts(false);
        $t = $this->spanish_hola_text;
        DbHelpers::add_textitems2(1, "CRAP", "crap", $t->getID());

        $sql = "select * FROM textitems2 where ti2Text = 'CRAP'";
        DbHelpers::assertRecordcountEquals($sql, 1, 'before');

        Parser::parse($t);
        DbHelpers::assertRecordcountEquals($sql, 0, 'after');
    }


    public function test_parse_no_words_defined()
    {
        $this->load_spanish_texts(false);
        $t = $this->spanish_hola_text;

        $sql = "select ti2seid, ti2order, ti2text from textitems2 where ti2woid = 0 order by ti2order";
        DbHelpers::assertTableContains($sql, [], 'nothing in table before parsing.');
        
        Parser::parse($t);


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
            "3; 18; ¶",
            "4; 19; Ella",
            "4; 20;  ",
            "4; 21; tiene",
            "4; 22;  ",
            "4; 23; una",
            "4; 24;  ",
            "4; 25; bebida",
            "4; 26; ."
        ];
        DbHelpers::assertTableContains($sql, $expected, 'after parse');
    }

    /**
     * @group current
     */
    public function test_parse_words_defined()
    {
        $this->load_spanish_words();
        $this->load_spanish_texts(false);
        $t = $this->spanish_hola_text;

        Parser::parse($t);

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
            "2; 17; .",
            "3; 18; ¶",
            "4; 19; Ella",
            "4; 20;  ",
            "4; 21; tiene",
            "4; 22;  ",
            "4; 23; una",
            "4; 24;  ",
            "4; 25; bebida",
            "4; 26; ."
        ];
        DbHelpers::assertTableContains($sql, $expected);

        $sql = "select ti2woid, ti2seid, ti2order, ti2text from textitems2 where ti2woid > 0 order by ti2order";
        $expected = [
            "1; 1; 5; un gato",
            "2; 2; 16; lista",
            "3; 4; 21; tiene una"
        ];
        DbHelpers::assertTableContains($sql, $expected);
    }

}
