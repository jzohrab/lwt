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
        $f->translation = 'trans ' . $term;
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

    public function test_save_new_no_parent()
    {
        $this->child->parent_id = 0;
        save_new_formdata($this->child);
        $sql = 'select WoID, WoText from words';
        DbHelpers::assertTableContains($sql, [ '1; CHILD' ]);
        DbHelpers::assertTableContains('select * from wordparents', [], 'no parents');
    }

    public function test_save_new_with_existing_parent()
    {
        $pid = save_new_formdata($this->parent);
        $this->child->parent_id = $pid;
        save_new_formdata($this->child);

        $expected = [ '1; PARENT', '2; CHILD'];
        $sql = 'select WoID, WoText from words';
        DbHelpers::assertTableContains($sql, $expected, 'both created');

        DbHelpers::assertTableContains('select * from wordparents', ['2; 1'], 'parent set');
    }

    private function save_new_with_parent_and_child()
    {
        $pid = save_new_formdata($this->parent);
        $this->child->parent_id = $pid;
        return save_new_formdata($this->child);
    }

    /*
    public function test_update_remove_parent()
    {
        $wid = $this->save_parent_and_child();
        $this->formdata->wid = $wid;
        $this->formdata->parent_id = 0;
        $this->formdata->parent_text = '';
        $this->formdata->translation = "new translation";
        save_new_formdata($this->formdata);

        $sql = "select WoTranslation from words where WoID = {$wid}";
        DbHelpers::assertTableContains($sql, [ 'new translation' ]);
        DbHelpers::assertTableContains('select * from wordparents', [], 'no parents');
    }

    public function test_update_change_parent()
    {
        $wid = $this->save_parent_and_child();
        $this->formdata->wid = $wid;
        $this->formdata->parent_id = 0;
        $this->formdata->parent_text = '';
        $this->formdata->translation = "new translation";
        save_new_formdata($this->formdata);

        $sql = "select WoTranslation from words where WoID = {$wid}";
        DbHelpers::assertTableContains($sql, [ 'new translation' ]);
        DbHelpers::assertTableContains('select * from wordparents', [], 'no parents');

    }

    /** tests to do:
- save new with new parent -- complicated
- existing, new parent, complicated
     */

}
