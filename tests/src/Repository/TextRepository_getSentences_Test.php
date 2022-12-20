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
        $this->load_languages();
        $this->langid = $this->spanish->getLgID();

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
        $t->setLanguage($this->spanish);
        $this->text_repo->save($t, true);
        $this->text = $t;
    }


    public function test_getTextItems_matching()
    {
        $textitems = $this->text_repo->getTextItems($this->text, $this->tengo_wid);
        $this->assertEquals(count($textitems), 2, '2 matches');
    }


    public function test_getTextItems_all()
    {
        $textitems = $this->text_repo->getTextItems($this->text);
        $this->assertEquals(count($textitems), 28, '28 items');
        $this->assertEquals($textitems[0]->SeID, 1, 'sanity check, SeID set');
    }

}
