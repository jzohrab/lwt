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

    /**
     * @group current
     */
    public function test_get_sentences_no_sentences() {
        $t = new Text();
        $sentences = $this->facade->getSentences($t);
        $this->assertEquals(0, count($sentences), "nothing for new text");
    }

    /**
     * @group current
     */
    public function disabled_test_parse_textitems2_textlc_is_set()
    {
        $t = new Text();
        $t->setTitle("Hola");
        $t->setText("Hola. Adios.");
        $t->setLanguage($this->spanish);
        $this->text_repo->save($t, true, true);

        $sentences = $this->facade->getSentences($t);
        $this->assertEquals(2, count($sentences));
    }

}
