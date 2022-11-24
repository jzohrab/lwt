<?php declare(strict_types=1);

require_once __DIR__ . '/word_input_form_TestBase.php';
require_once __DIR__ . '/db_helpers.php';

use PHPUnit\Framework\TestCase;

class word_input_form__Update_Test extends word_input_form_TestBase
{

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
