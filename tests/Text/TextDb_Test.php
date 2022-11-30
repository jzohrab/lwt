<?php declare(strict_types=1);

require_once __DIR__ . '/../../inc/word_input_form.php';
require_once __DIR__ . '/../db_helpers.php';

require_once __DIR__ . '/../../src/php/Text/TextDb.php';

use PHPUnit\Framework\TestCase;

final class TextDb_Test extends TestCase
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

        $this->text = "Hola tengo un gato.";
        $this->newid = DbHelpers::add_text($this->text, $this->langid);
    }

    public function tearDown(): void
    {
        // echo "tearing down ... \n";
    }

    public function test_delete_text_works()
    {
        splitCheckText($this->text, $this->langid, 1);

        $newid = $this->newid;
        $sentencesql = "select * from sentences where SeTxID = $newid";
        $ti2sql = "select Ti2Order, Ti2Text from textitems2 where Ti2TxID = $newid";
        $txsql = "select TxID, TxTitle from texts where TxID = $newid";
        DbHelpers::assertRecordcountEquals($sentencesql, 1, "sentences pre");
        DbHelpers::assertRecordcountEquals($ti2sql, 8, "ti2 pre");
        DbHelpers::assertRecordcountEquals($txsql, 1, "tx pre");

        LwtTextDatabase::delete($newid);

        DbHelpers::assertRecordcountEquals($sentencesql, 0, "sentences post");
        DbHelpers::assertRecordcountEquals($ti2sql, 0, "ti2 post");
        DbHelpers::assertRecordcountEquals($txsql, 0, "tx post");
    }

}

?>
