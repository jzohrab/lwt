<?php declare(strict_types=1);

require_once __DIR__ . '/../../db_helpers.php';
require_once __DIR__ . '/../../DatabaseTestBase.php';

use App\Entity\TermTag;

final class TermTagRepository_Test extends DatabaseTestBase
{

    public function childSetUp() {
        $this->t = new TermTag();
        $this->t->setText("Hola");
        $this->t->setComment("Hola comment");
        $this->termtag_repo->save($this->t, true);
    }

    public function test_save()
    {
        $sql = "select TgID, TgText, TgComment from tags";
        $expected = [ "1; Hola; Hola comment" ];
        DbHelpers::assertTableContains($sql, $expected);
    }

    public function test_new_dup_tag_text_fails()
    {
        $t = new TermTag();
        $t->setText("Hola");
        $t->setComment("Hola 2 comment");

        $this->expectException(Doctrine\DBAL\Exception\UniqueConstraintViolationException::class);
        $this->termtag_repo->save($t, true);
    }

    public function test_get_by_text()
    {
        $retrieved = $this->termtag_repo->findByText("Hola");
        $this->assertEquals($this->t->getId(), $retrieved->getId(), 'same item returned');
    }

    public function test_get_by_text_returns_null_if_not_exact_match()
    {
        $retrieved = $this->termtag_repo->findByText("hola");
        $this->assertNull($retrieved, 'not exact text = no match');
    }

}
