<?php declare(strict_types=1);

require_once __DIR__ . '/../../db_helpers.php';
require_once __DIR__ . '/../../DatabaseTestBase.php';

use App\Entity\Text;
use App\Entity\TextItem;

final class TextRepository_getSentences_Test extends DatabaseTestBase
{

    public function childSetUp(): void
    {
        // Set up db.
        DbHelpers::load_language_spanish();
        $this->langid = (int) get_first_value("select LgID as value from languages");

        $lid = $this->langid;
        DbHelpers::add_word($lid, "Un gato", "un gato", 1, 2);
        DbHelpers::add_word($lid, "lista", "lista", 1, 1);
        DbHelpers::add_word($lid, "tiene una", "tiene una", 1, 2);
        $this->tengo_wid = DbHelpers::add_word($lid, "tengo", "tengo", 1, 1);

        // A parent term.
        DbHelpers::add_word($lid, "listo", "listo", 1, 1);
        DbHelpers::add_word_parent($lid, "lista", "listo");

        // Some tags for fun.
        DbHelpers::add_word_tag($lid, "Un gato", "furry");
        DbHelpers::add_word_tag($lid, "lista", "adj");
        DbHelpers::add_word_tag($lid, "lista", "another");
        DbHelpers::add_word_tag($lid, "listo", "padj1");
        DbHelpers::add_word_tag($lid, "listo", "padj2");

        $t = new Text();
        $t->setTitle("Hola.");
        $t->setText("Hola tengo un gato.  No tengo una lista.\nElla tiene una bebida.");
        $lang = $this->language_repo->find($this->langid);
        $t->setLanguage($lang);
        $this->text_repo->save($t, true);
        $this->text = $t;
    }

    /**
     * @group curr
     */
    public function test_smoke_test()
    {
        // original text:
        // Hola tengo un gato.  No tengo una lista. \n Ella tiene una bebida.
        DbHelpers::assertRecordcountEquals("sentences", 4, 'sentences');
        $sentences = $this->text_repo->getSentences($this->text);
        $this->assertEquals(count($sentences), 4, '4 sentences');
    }

    /**
     * @group curr
     */
    public function test_getTextItems_matching()
    {
        $textitems = $this->text_repo->getTextItems($this->text, $this->tengo_wid);
        $this->assertEquals(count($textitems), 2, '2 matches');
    }

}
