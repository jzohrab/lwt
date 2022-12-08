<?php declare(strict_types=1);

require_once __DIR__ . '/word_input_form_TestBase.php';
require_once __DIR__ . '/db_helpers.php';

use PHPUnit\Framework\TestCase;


final class word_input_form__New_Test extends word_input_form_TestBase {

    public function test_save_new_no_parent()
    {
        $this->child->parent_id = 0;
        save_new_formdata($this->child);
        $sql = 'select WoID, WoText from words';
        DbHelpers::assertTableContains($sql, [ '1; CHILD' ]);
        $this->assert_wordparents_equals([], 'no parents');
    }

    public function test_save_new_creates_tags()
    {
        DbHelpers::add_tags(['hi']);

        $this->child->parent_id = 0;
        $this->child->tags = ['hi', 'there'];
        save_new_formdata($this->child);

        $sql = 'select TgText from tags order by TgText';
        DbHelpers::assertTableContains($sql, [ 'hi', 'there' ]);

        $sql = "select TgText from tags inner join wordtags wt on wt.WtTgID = TgID";
        DbHelpers::assertTableContains($sql, [ 'hi', 'there' ]);
    }

    public function test_save_new_with_existing_parent()
    {
        $pid = save_new_formdata($this->parent);
        $this->child->parent_id = $pid;
        save_new_formdata($this->child);

        $expected = [ '1; PARENT', '2; CHILD'];
        $sql = 'select WoID, WoText from words';
        DbHelpers::assertTableContains($sql, $expected, 'both created');

        $this->assert_wordparents_equals(['2; 1'], 'parent set');
    }


    public function test_save_new_with_new_parent_parent_gets_same_tags()
    {
        $this->child->parent_id = 0;
        $this->child->parent_text = "NEWPARENT";
        $this->child->tags = ['t1', 't2'];
        save_new_formdata($this->child);

        $expected = [ '1; t1', '1; t2', '2; t1', '2; t2'];
        $sql = "select WtWoID, TgText
from wordtags inner join tags on WtTgID = TgID
order by WtWoID, TgText";
        DbHelpers::assertTableContains($sql, $expected, 'both have tags');
    }


    public function test_save_new_with_existing_parent_parent_keeps_own_tags()
    {
        $this->parent->tags = ['p1', 'p2'];
        $pid = save_new_formdata($this->parent);

        $this->child->parent_id = 0;
        $this->child->parent_text = "PARENT";
        $this->child->tags = ['c1', 'c2'];
        save_new_formdata($this->child);

        $expected = [ '1; p1', '1; p2', '2; c1', '2; c2'];
        $sql = "select WtWoID, TgText
from wordtags inner join tags on WtTgID = TgID
order by WtWoID, TgText";
        DbHelpers::assertTableContains($sql, $expected, 'both have their own tags');
    }

    public function test_save_new_word_count_set_correctly()
    {
        $a = $this->make_formdata("HELLO");
        save_new_formdata($a);
        $b = $this->make_formdata("GOOD BYE THEN");

        // Suppressing stdout per
        // https://stackoverflow.com/questions/486181/php-suppress-output-within-a-function,
        // since saving the data generates some junk to the screen.
        ob_start();
        save_new_formdata($b);
        $content = ob_get_flush();
        ob_end_clean();
        // Without the next ob_start, a warning is given:
        // There was 1 risky test: ...
        // Test code or tested code did not (only) close its own output buffers
        // Don't know thet ins and outs of this,
        // and don't really care.  This works fine. jz
        ob_start();

        $sql = 'select WoText, WoWordCount from words order by WoID';
        DbHelpers::assertTableContains($sql, [ 'HELLO; 1', 'GOOD BYE THEN; 3' ]);
    }

}
