<?php declare(strict_types=1);

require_once __DIR__ . '/../../db_helpers.php';
require_once __DIR__ . '/../../DatabaseTestBase.php';

use App\Entity\TextTag;

final class TextTagRepository_Test extends DatabaseTestBase
{

    private TextTag $t;

    public function childSetUp() {
        $this->t = new TextTag();
        $this->t->setText("Hola");
        $this->t->setComment("Hola comment");
        $this->texttag_repo->save($this->t, true);
    }

    public function test_save()
    {
        $sql = "select T2ID, T2Text, T2Comment from tags2";
        $expected = [ "1; Hola; Hola comment" ];
        DbHelpers::assertTableContains($sql, $expected);
    }

    public function test_get_by_text()
    {
        $retrieved = $this->texttag_repo->findByText("Hola");
        $this->assertEquals($this->t->getId(), $retrieved->getId(), 'same item returned');
    }

    public function test_get_by_text_returns_null_if_not_exact_match()
    {
        $retrieved = $this->texttag_repo->findByText("hola");
        $this->assertNull($retrieved, 'not exact text = no match');
    }

}
