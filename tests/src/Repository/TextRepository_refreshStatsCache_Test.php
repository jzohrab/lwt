<?php declare(strict_types=1);

require_once __DIR__ . '/../../db_helpers.php';
require_once __DIR__ . '/../../DatabaseTestBase.php';

use App\Entity\Text;
    
final class TextRepository_refreshStatsCache_Test extends DatabaseTestBase
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


    public function test_smoke_test()
    {
        DbHelpers::exec_sql("delete from textstatscache");
        $sentences = $this->text_repo->refreshStatsCache();
        DbHelpers::assertRecordcountEquals("textstatscache", 1, "one record");
    }

}
