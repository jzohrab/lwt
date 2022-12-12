<?php declare(strict_types=1);

// Repository tests require an entity manager.
// See ref https://symfony.com/doc/current/testing.html#integration-tests
// for some notes about the kernel and entity manager.
// Note that tests must be run with the phpunit.xml.dist config file.

require_once __DIR__ . '/db_helpers.php';

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class DatabaseTestBase extends WebTestCase
{

    public function setUp(): void
    {
        $inimsg = 'php.ini must set mysqli.allow_local_infile to 1.';
        $this->assertEquals(ini_get('mysqli.allow_local_infile'), '1', $inimsg);

        // Set up db.
        DbHelpers::ensure_using_test_db();
        DbHelpers::clean_db();

        $kernel = static::createKernel();
        $kernel->boot();
        $this->entity_manager = $kernel->getContainer()->get('doctrine.orm.entity_manager');

        $this->text_repo = $this->entity_manager->getRepository(App\Entity\Text::class);
        $this->language_repo = $this->entity_manager->getRepository(App\Entity\Language::class);
        $this->texttag_repo = $this->entity_manager->getRepository(App\Entity\TextTag::class);

        $this->childSetUp();
    }

    public function childSetUp() {
        // no-op, child tests can override this to set up stuff.
    }

    public function tearDown(): void
    {
        // echo "tearing down ... \n";
        $this->childTearDown();
    }

    public function childTearDown(): void
    {
        // echo "tearing down ... \n";
    }

    public function load_test_data(): void
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

}
