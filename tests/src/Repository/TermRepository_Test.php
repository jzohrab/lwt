<?php declare(strict_types=1);

require_once __DIR__ . '/../../db_helpers.php';
require_once __DIR__ . '/../../DatabaseTestBase.php';

use App\Entity\TermTag;
use App\Entity\Term;

final class TermRepository_Test extends DatabaseTestBase
{

    public function childSetUp() {
        $this->load_languages();

        $this->t = new TermTag();
        $this->t->setText("Hola");
        $this->t->setComment("Hola comment");
        $this->termtag_repo->save($this->t, true);
    }

    public function test_create_and_save()
    {
        $t = new Term();
        $t->setLanguage($this->spanish);
        $t->setText("HOLA");
        $t->setStatus(1);
        $t->setWordCount(1);
        $t->setTranslation('hi');
        $t->setRomanization('ho-la');
        $this->term_repo->save($t, true);

        $this->assertEquals($t->getTextLC(), 'hola', "sanity check of case");

        $sql = "select WoID, WoText, WoTextLC from words";
        $expected = [ "1; HOLA; hola" ];
        DbHelpers::assertTableContains($sql, $expected, "sanity check on save");
    }

    /*
    public function test_word_with_parent_and_tags()
    {
    }
    */

    /* Tests
       - can't change text of saved word ... see other tests in src/word_form_ thing.
    */
}
