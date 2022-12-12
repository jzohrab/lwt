<?php declare(strict_types=1);

require_once __DIR__ . '/../../db_helpers.php';
require_once __DIR__ . '/RepositoryTestBase.php';

use App\Entity\Text;
use App\Entity\Language;

// This isn't really a test ... it just loads the database with data.
// Still reasonable to keep as a test though as it needs to always
// work.
final class LoadTestData_Test extends RepositoryTestBase
{

    public function childSetUp(): void
    {
        // Set up db.
        $spanish = new Language();
        $spanish
            ->setLgName('Spanish')
            ->setLgDict1URI('https://es.thefreedictionary.com/###')
            ->setLgDict2URI('https://www.wordreference.com/es/en/translation.asp?spen=###')
            ->setLgGoogleTranslateURI('*https://www.deepl.com/translator#es/en/###');
        $this->language_repo->save($spanish, true);

        $spid = $spanish->getLgID();
        DbHelpers::add_word($spid, "Un gato", "un gato", 1, 2);
        DbHelpers::add_word($spid, "lista", "lista", 1, 1);
        DbHelpers::add_word($spid, "tiene una", "tiene una", 1, 2);

        // A parent term.
        DbHelpers::add_word($spid, "listo", "listo", 1, 1);
        DbHelpers::add_word_parent($spid, "lista", "listo");

        // Some tags for fun.
        DbHelpers::add_word_tag($spid, "Un gato", "furry");
        DbHelpers::add_word_tag($spid, "lista", "adj");
        DbHelpers::add_word_tag($spid, "lista", "another");
        DbHelpers::add_word_tag($spid, "listo", "padj1");
        DbHelpers::add_word_tag($spid, "listo", "padj2");

        $t = new Text();
        $t->setTitle("Hola.");
        $t->setText("Hola tengo un gato.  No tengo una lista.\nElla tiene una bebida.");
        $t->setLanguage($spanish);
        $this->text_repo->save($t, true);
        $this->text = $t;

        $french = new Language();
        $french
            ->setLgName('French')
            ->setLgDict1URI('https://fr.thefreedictionary.com/###')
            ->setLgDict2URI('https://www.wordreference.com/fren/###')
            ->setLgGoogleTranslateURI('*https://www.deepl.com/translator#fr/en/###');
        $this->language_repo->save($french, true);

        $frid = $french->getLgID();
        DbHelpers::add_word($frid, "lista", "lista", 1, 1);
        DbHelpers::add_word_tag($frid, "lista", "nonsense");
        $frt = new Text();
        $frt->setTitle("Bonjour.");
        $frt->setText("Bonjour je suis lista.");
        $frt->setLanguage($french);
        $this->text_repo->save($frt, true);
    }


    public function test_smoke_test()
    {
        $this->assertEquals(1, 1, 'dummy test to stop phpunit complaint');
    }

}
