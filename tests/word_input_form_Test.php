<?php declare(strict_types=1);

require_once __DIR__ . '/../inc/word_input_form.php';
require_once __DIR__ . '/db_helpers.php';

use PHPUnit\Framework\TestCase;

final class word_input_form_Test extends TestCase
{

    public function setUp(): void
    {
        // Set up db.
        DbHelpers::ensure_using_test_db();
        DbHelpers::clean_db();
        DbHelpers::load_language_spanish();
        do_mysqli_query("ALTER TABLE words AUTO_INCREMENT = 1");

        $this->child = $this->make_formdata("CHILD");
        $this->parent = $this->make_formdata("PARENT");
        $this->stepdad = $this->make_formdata("STEPDAD");
    }

    private function make_formdata($term) {
        $f = new FormData();
        $f->lang = 1;
        // $f->wid = 0;
        $f->term = $term;
        $f->termlc = strtolower($term);
        $f->translation = 'translation ' . $term;
        $f->romanization = 'rom ' . $term;
        $f->sentence = 'sent ' . $term;
        $f->status = 3;
        $f->status_old = 1;
        return $f;
    }
    
    public function tearDown(): void
    {
        // echo "tearing down ... \n";
    }

    private function assert_wordparents_equals($expected, $message) {
        $sql = 'select WpWoID, WpParentWoID from wordparents';
        DbHelpers::assertTableContains($sql, $expected, $message);
    }

    public function test_smoke_export_js_dict()
    {
        $s = $this->child->export_js_dict();
        $this->assertTrue(is_string($s), "got string");
    }

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
        save_new_formdata($b);
        $sql = 'select WoText, WoWordCount from words order by WoID';
        DbHelpers::assertTableContains($sql, [ 'HELLO; 1', 'GOOD BYE THEN; 3' ]);
    }


    public function test_update_sanity_check()
    {
        $a = $this->make_formdata("HELLO");
        $a->translation = 'old';
        $wid = save_new_formdata($a);

        $sql = "select WoTranslation from words";
        DbHelpers::assertTableContains($sql, [ 'old' ]);

        $a->wid = $wid;
        $a->translation = 'new';
        update_formdata($a);

        DbHelpers::assertTableContains($sql, [ 'new' ]);
    }


    public function test_update_throws_if_term_lcase_changes()
    {
        $a = $this->make_formdata("HELLO");
        $oldlcase = $a->termlc;
        $wid = save_new_formdata($a);

        $a->wid = $wid;
        $a->term = $oldlcase . 'CHANGED';
        $a->termlc = $oldlcase . 'changed';

        $msg = 'ok';
        try {
            update_formdata($a);
        }
        catch (Exception $e) {
            $msg = $e->getMessage();
        }

        $this->assertStringContainsString('cannot change', $msg, 'should have changed due to error');
    }


    private function save_parent_and_child()
    {
        $pid = save_new_formdata($this->parent);
        $this->child->parent_id = $pid;
        $wid = save_new_formdata($this->child);
        $this->child->wid = $wid;
        return $wid;
    }


    public function test_update_replaces_existing_tags()
    {
        DbHelpers::add_tags(['hi']);
        $this->child->parent_id = 0;
        $this->child->tags = ['hi', 'there'];
        save_new_formdata($this->child);

        $sql = 'select TgText from tags order by TgText';
        DbHelpers::assertTableContains($sql, [ 'hi', 'there' ]);

        $sql = "select TgText from tags inner join wordtags wt on wt.WtTgID = TgID";
        DbHelpers::assertTableContains($sql, [ 'hi', 'there' ]);

        $this->child->tags = ['new', 'stuff'];
        update_formdata($this->child);

        $sql = "select TgText from tags order by TgText";
        DbHelpers::assertTableContains($sql, [ 'hi', 'new', 'stuff', 'there']);

        $sql = "select TgText from tags inner join wordtags wt on wt.WtTgID = TgID";
        DbHelpers::assertTableContains($sql, [ 'new', 'stuff' ], 'tags replaced');
    }


    public function test_update_remove_parent()
    {
        $wid = $this->save_parent_and_child();
        $this->assert_wordparents_equals(['2; 1'], 'parent set');

        $this->child->parent_id = 0;
        $this->child->parent_text = '';
        $this->child->translation = "new translation";
        update_formdata($this->child);

        $sql = "select WoTranslation from words where WoID = {$wid}";
        DbHelpers::assertTableContains($sql, [ 'new translation' ]);
        $this->assert_wordparents_equals([], 'parent removed');
    }

    public function test_update_change_parent()
    {
        $this->save_parent_and_child();
        $this->assert_wordparents_equals(['2; 1'], 'parent set');

        $pid = save_new_formdata($this->stepdad);
        $this->child->parent_id = $pid;
        update_formdata($this->child);

        $sql = "select WoTranslation from words where WoID = {$this->child->wid}";
        $this->assert_wordparents_equals(['2; 3'], 'parent changed');
    }

    public function test_new_term_new_parent_text()
    {
        $f = $this->make_formdata("NewChild");
        $f->parent_id = 0;
        $f->parent_text = "MakeParent";

        $wid = save_new_formdata($f);

        $psql = "SELECT WoID as value FROM words WHERE WoText='MakeParent'";
        $pid = (int)get_first_value($psql);
        $this->assertTrue($pid > 0, "have parent");
        $this->assertEquals($wid - 1, $pid, "parent ({$pid}) created immed. before child ({$wid})");

        $sql = "SELECT WoTranslation, WoSentence FROM words where WoID = ";
        $childsql = "{$sql} {$wid}";
        $expected_child = [ "*; sent NewChild" ];
        $msg = "translation is sent to parent, child keeps sentence";
        DbHelpers::assertTableContains($childsql, $expected_child, $msg);

        $parentsql = "{$sql} {$pid}";
        $expected_parent = [ "translation NewChild; " ];
        DbHelpers::assertTableContains($parentsql, $expected_parent);

        $parentsql = "SELECT WoLgID, WoText, WoTextLC, WoStatus FROM words
          WHERE WoID = {$pid}";
        $expected_parent = [ "1; MakeParent; makeparent; 3" ];
        DbHelpers::assertTableContains($parentsql, $expected_parent);

        $this->assert_wordparents_equals(["{$wid}; {$pid}"], "parent set");
    }


    public function test_save_new_with_parent_text_already_existing()
    {
        $pid = save_new_formdata($this->parent);
        $wid = save_new_formdata($this->child);

        $this->assert_wordparents_equals([], 'no parents');

        $this->child->wid = $wid;
        $this->child->parent_id = 0;
        $this->child->parent_text = 'parent';

        update_formdata($this->child);
        $this->assert_wordparents_equals(["{$wid}; {$pid}"], "existing parent set");
    }

    public function test_existing_term_new_parent_text()
    {
        $wid = $this->save_parent_and_child();
        $this->assert_wordparents_equals(['2; 1'], 'parent set');

        $this->child->parent_id = 0;
        $this->child->parent_text = 'MakeParent';
        update_formdata($this->child);
        $wid = $this->child->wid;

        $psql = "SELECT WoID as value FROM words WHERE WoText='MakeParent'";
        $pid = (int)get_first_value($psql);
        $this->assertTrue($pid > 0, "have parent");

        $sql = "SELECT WoTranslation, WoSentence FROM words where WoID = ";
        $childsql = "{$sql} {$wid}";
        $expected_child = [ "translation CHILD; sent CHILD" ];
        $msg = "child keeps existing translation, child keeps sentence";
        DbHelpers::assertTableContains($childsql, $expected_child, $msg);

        $parentsql = "{$sql} {$pid}";
        $expected_parent = [ "translation CHILD; " ];
        $msg = "parent gets copy of translation";
        DbHelpers::assertTableContains($parentsql, $expected_parent, $msg);

        $parentsql = "SELECT WoLgID, WoText, WoTextLC, WoStatus FROM words
          WHERE WoID = {$pid}";
        $expected_parent = [ "1; MakeParent; makeparent; 3" ];
        DbHelpers::assertTableContains($parentsql, $expected_parent);

        $this->assert_wordparents_equals(["{$wid}; {$pid}"], "parent set");
    }

}
