<?php declare(strict_types=1);

// Ref https://symfony.com/doc/current/testing.html#integration-tests
// as this requires an entity manager etc for the repository to work.

require_once __DIR__ . '/../../db_helpers.php';

/**
 * LWT users create a connect.inc.php file with db settings, so just
 * use that to create the db connection.
 */
require_once __DIR__ . '/../../../connect.inc.php';
global $userid, $passwd, $server, $dbname;
$DATABASE_URL = "mysql://{$userid}:{$passwd}@{$server}/{$dbname}?serverVersion=8&charset=utf8";
$_ENV['DATABASE_URL'] = $DATABASE_URL;
$_SERVER['DATABASE_URL'] = $DATABASE_URL;


use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Entity\Text;
use App\Repository\TextRepository;
use App\Repository\LanguageRepository;

final class TextRepository_Test extends WebTestCase
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

        $kernel = static::createKernel();
        $kernel->boot();
        $this->entity_manager = $kernel->getContainer()->get('doctrine.orm.entity_manager');

        $this->text_repo = $this->entity_manager->getRepository(App\Entity\Text::class);
        $this->language_repo = $this->entity_manager->getRepository(App\Entity\Language::class);
    }

    public function tearDown(): void
    {
        // echo "tearing down ... \n";
    }

    public function test_saving_Text_entity_loads_textitems2()
    {
        $t = new Text();
        $t->setTitle("Hola.");
        $t->setText("Hola tengo un gato.");
        $lang = $this->language_repo->find(1);
        $t->setLanguage($lang);

        $this->text_repo->save($t, true);
                        
        $sql = "select ti2seid, ti2order, ti2text from textitems2 where ti2woid = 0 order by ti2order";
        $expected = [
            "1; 1; Hola",
            "1; 2;  ",
            "1; 3; tengo",
            "1; 4;  ",
            "1; 5; un",
            "1; 6;  ",
            "1; 7; gato",
            "1; 8; ."
        ];
        DbHelpers::assertTableContains($sql, $expected);
    }

}
