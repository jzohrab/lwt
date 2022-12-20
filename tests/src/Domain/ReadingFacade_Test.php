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

        $this->facade = new ReadingFacade($this->reading_repo);
    }

    public function tearDown(): void
    {
        // echo "tearing down ... \n";
    }

    // tests

    public function test_get_sentences_no_sentences() {
        $t = new Text();
        $sentences = $this->facade->getSentences($t);
        $this->assertEquals(0, count($sentences), "nothing for new text");
    }

    /**
     * @group current
     */
    public function test_get_sentences_with_text()
    {
        $t = $this->create_text("Hola", "Hola. Adios amigo.", $this->spanish);
        $sentences = $this->facade->getSentences($t);
        $this->assertEquals(2, count($sentences));
    }

    /**
     * @group current
     */
    public function test_get_sentences_reparses_text_if_no_sentences()
    {
        $t = $this->create_text("Hola", "Hola. Adios amigo.", $this->spanish);
        DbHelpers::exec_sql("delete from textitems2");
        $sentences = $this->facade->getSentences($t);
        $this->assertEquals(2, count($sentences), "reparsed");
    }

}
