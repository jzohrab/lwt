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

        splitCheckText($this->text, $this->langid, $this->newid);

        $sentencesql = "select * from sentences";
        $ti2sql = "select * from textitems2";
        $txsql = "select * from texts";
        DbHelpers::assertRecordcountEquals($sentencesql, 1, "sentences pre");
        DbHelpers::assertRecordcountEquals($ti2sql, 8, "ti2 pre");
        DbHelpers::assertRecordcountEquals($txsql, 1, "tx pre");
    }

    public function tearDown(): void
    {
        // echo "tearing down ... \n";
    }

    public function test_delete()
    {
        LwtTextDatabase::delete($this->newid);
        $tables = [ "sentences", "textitems2", "texts" ];
        foreach ($tables as $t) {
            $sql = "select * from $t";
            DbHelpers::assertRecordcountEquals($sql, 0, "$t after delete");
        }
    }

    public function test_archive()
    {
        $sql = "select * from texts where TxArchived = 1";
        DbHelpers::assertRecordcountEquals($sql, 0, "texts before archive");

        LwtTextDatabase::archive($this->newid);
        $tables = [ "sentences", "textitems2" ];
        foreach ($tables as $t) {
            $sql = "select * from $t";
            DbHelpers::assertRecordcountEquals($sql, 0, "$t after archive");
        }

        $sql = "select * from texts where TxArchived = 1";
        DbHelpers::assertRecordcountEquals($sql, 1, "texts after archive");
    }

    public function test_unarchive()
    {
        $sql = "select * from texts where TxArchived = 1";
        DbHelpers::assertRecordcountEquals($sql, 0, "texts before archive");

        LwtTextDatabase::archive($this->newid);
        $sql = "select * from texts where TxArchived = 1";
        DbHelpers::assertRecordcountEquals($sql, 1, "texts after archive");

        LwtTextDatabase::unarchive($this->newid);
        $sql = "select * from texts where TxArchived = 1";
        DbHelpers::assertRecordcountEquals($sql, 0, "texts after unarchive");

        $sentencesql = "select * from sentences";
        $ti2sql = "select * from textitems2";
        DbHelpers::assertRecordcountEquals($sentencesql, 1, "sentences restored");
        DbHelpers::assertRecordcountEquals($ti2sql, 8, "ti2 restored");
    }

}

?>
