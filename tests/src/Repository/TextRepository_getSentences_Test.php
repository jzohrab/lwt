<?php declare(strict_types=1);

require_once __DIR__ . '/../../db_helpers.php';
require_once __DIR__ . '/RepositoryTestBase.php';

use App\Entity\Text;
    
final class TextRepository_getSentences_Test extends RepositoryTestBase
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
        DbHelpers::add_word_parent("lista", "listo");

        $t = new Text();
        $t->setTitle("Hola.");
        $t->setText("Hola tengo un gato.  No tengo una lista.  Ella tiene una bebida.");
        $lang = $this->language_repo->find($this->langid);
        $t->setLanguage($lang);
        $this->text_repo->save($t, true);
        $this->text = $t;

        // Initial double-check.
        $allti2 = "select Ti2SeID, Ti2Order, Ti2Text from textitems2 order by Ti2Order, Ti2WordCount DESC";
        DbHelpers::assertRecordcountEquals($allti2, 28, 'setup, terms included');

        $expected = [
"1; 1; Hola",
"1; 2;  ",
"1; 3; tengo",
"1; 4;  ",
"1; 5; un gato",
"1; 5; un",
"1; 6;  ",
"1; 7; gato",
"1; 8; .",
"2; 9;  ",
"2; 10; No",
"2; 11;  ",
"2; 12; tengo",
"2; 13;  ",
"2; 14; una",
"2; 15;  ",
"2; 16; lista",
"2; 17; .",
"3; 18;  ",
"3; 19; Ella",
"3; 20;  ",
"3; 21; tiene una",
"3; 21; tiene",
"3; 22;  ",
"3; 23; una",
"3; 24;  ",
"3; 25; bebida",
"3; 26; .",

        ];
        DbHelpers::assertTableContains($allti2, $expected, 'terms');
    }


    public function test_smoke_test()
    {
        // original text:
        // Hola tengo un gato.  No tengo una lista.  Ella tiene una bebida.

        DbHelpers::assertRecordcountEquals("sentences", 3, 'sentences');
        $sentences = $this->text_repo->getSentences($this->text);
        $this->assertEquals(count($sentences), 3, '3 sentences');
    }

}
