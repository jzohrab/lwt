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
    }

    public function tearDown(): void
    {
        // echo "tearing down ... \n";
    }

    // tests

    public function test_stub() {
        $sql = "select * FROM textitems2 where ti2Text = 'CRAP'";
        DbHelpers::assertRecordcountEquals($sql, 1, 'before');
    }
    
    public function disabled_test_parse_textitems2_textlc_is_set()
    {
        $t = new Text();
        $t->setTitle("Hola");
        $t->setText("Hola");
        $t->setLanguage($this->spanish);
        $this->text_repo->save($t, true, true);

        $sql = "select ti2text, concat('*', ti2textlc, '*') from textitems2 where ti2txid = {$t->getID()}";
        $expected = [ "Hola; *hola*" ];
        DbHelpers::assertTableContains($sql, $expected, 'lowercase set');
    }


}
