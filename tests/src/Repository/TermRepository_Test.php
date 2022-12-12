<?php declare(strict_types=1);

require_once __DIR__ . '/../../db_helpers.php';
require_once __DIR__ . '/../../DatabaseTestBase.php';

use App\Entity\TermTag;
use App\Entity\Term;

final class TermRepository_Test extends DatabaseTestBase
{

    public function childSetUp() {
        $this->load_languages();
    }

    public function disab_test_create_and_save()
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


    public function test_word_with_parent_and_tags()
    {
        $tag = new TermTag();
        $tag->setText("tag");
        $tag->setComment("tag comment");
        $this->termtag_repo->save($tag, true);

        $p = new Term();
        $p->setLanguage($this->spanish);
        $p->setText("PARENT");
        $p->setStatus(1);
        $p->setWordCount(1);
        $this->term_repo->save($p, true);

        $t = new Term();
        $t->setLanguage($this->spanish);
        $t->setText("HOLA");
        $t->setStatus(1);
        $t->setWordCount(1);
        $t->setParent($p);
        $t->addTermTag($tag);
        $this->term_repo->save($t, true);

        $sql = "select WoID, WoText, WoTextLC from words";
        $expected = [ "1; PARENT; parent", "2; HOLA; hola" ];
        DbHelpers::assertTableContains($sql, $expected, "sanity check on save");

        // Hacky sql check.
        $sql = "select w.WoText, p.WoText as ptext, tags.TgText 
            FROM words w
            INNER JOIN wordparents on WpWoID = w.WoID
            INNER JOIN words p on p.WoID = wordparents.WpParentWoID
            INNER JOIN wordtags on WtWoID = w.WoID
            INNER JOIN tags on TgID = WtTgID";
        $exp = [ "HOLA; PARENT; tag" ];
        DbHelpers::assertTableContains($sql, $exp, "??? parents, tags");
    }

    /* Tests
       - can't change text of saved word ... see other tests in src/word_form_ thing.
    */
}
