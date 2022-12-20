<?php declare(strict_types=1);

require_once __DIR__ . '/../../../src/Domain/ReadingFacade.php';
require_once __DIR__ . '/../../DatabaseTestBase.php';

use App\Domain\ReadingFacade;
use App\Entity\Text;

final class ReadingFacade_Test extends DatabaseTestBase
{

    public function childSetUp(): void
    {
        $this->load_languages();
        $this->load_spanish_words();
        $this->load_spanish_texts(true);

        $this->facade = new ReadingFacade($this->reading_repo);
    }


    public function test_get_sentences_no_sentences() {
        $t = new Text();
        $sentences = $this->facade->getSentences($t);
        $this->assertEquals(0, count($sentences), "nothing for new text");
    }

    public function test_get_sentences_with_text()
    {
        $t = $this->create_text("Hola", "Hola. Adios amigo.", $this->spanish);
        $sentences = $this->facade->getSentences($t);
        $this->assertEquals(2, count($sentences));
    }

    public function test_get_sentences_reparses_text_if_no_sentences()
    {
        $t = $this->create_text("Hola", "Hola. Adios amigo.", $this->spanish);
        DbHelpers::exec_sql("delete from textitems2");
        $sentences = $this->facade->getSentences($t);
        $this->assertEquals(2, count($sentences), "reparsed");
    }

    /**
     * @group current
     */
    public function test_mark_unknown_as_known_creates_words_and_updates_ti2s()
    {
        $textitemssql = "select ti2woid, ti2order, ti2text from textitems2 where ti2wordcount > 0 order by ti2order, ti2wordcount desc";
        // DbHelpers::dumpTable($textitemssql);
        $expected = [
            "0; 1; Hola",
            "0; 3; tengo",
            "1; 5; un gato",
            "0; 5; un",
            "0; 7; gato",
            "0; 10; No",
            "0; 12; tengo",
            "0; 14; una",
            "2; 16; lista",
            "0; 19; Ella",
            "3; 21; tiene una",
            "0; 21; tiene",
            "0; 23; una",
            "0; 25; bebida"
        ];
        DbHelpers::assertTableContains($textitemssql, $expected, "prior to mark as known");

        $wordssql = "select woid, wotext, wowordcount, wostatus from words";
        // DbHelpers::dumpTable($wordssql);
        $expected = [
            "1; Un gato; 2; 1",
            "2; lista; 1; 1",
            "3; tiene una; 2; 1",
            "4; listo; 1; 1"
        ];
        DbHelpers::assertTableContains($wordssql, $expected, "prior to mark as known");
    }
}
