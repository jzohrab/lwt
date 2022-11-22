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

        $f = new FormData();
        $f->lang = 1;
        // $f->wid = 0;
        $f->term = 'HELLO';
        $f->termlc = 'hello';
        $f->translation = 'trans';
        $f->romanization = 'rom';
        $f->sentence = 'sent';
        $f->status = 3;
        $f->status_old = 1;
        // $f->parent_id = $parentid;
        // $f->parent_text = 'HELLOPARENT';

        $this->formdata = $f;
    }

    public function tearDown(): void
    {
        // echo "tearing down ... \n";
    }

    public function test_save_new_no_parent()
    {
        save_new_formdata($this->formdata);
        $expected = [[ 'WoID' => 1, 'WoText' => 'HELLO' ]];
        $sql = 'select WoID, WoText from words';
        DbHelpers::assertTableContains($sql, $expected);
        DbHelpers::assertTableContains('select * from wordparents', [], 'no parents');
    }

    public function test_save_new_with_existing_parent()
    {
        $this->formdata->term = 'PARENT';
        $this->formdata->termlc = 'parent';
        $pid = save_new_formdata($this->formdata);

        $this->formdata->term = 'CHILD';
        $this->formdata->termlc = 'child';
        $this->formdata->parent_id = $pid;

        $pid = save_new_formdata($this->formdata);

        $expected = [['WoID' => 1, 'WoText' => 'PARENT'], ['WoID' => 2, 'WoText' => 'CHILD']];
        $sql = 'select WoID, WoText from words';
        DbHelpers::assertTableContains($sql, $expected, 'both created');

        $expected = [['WpWoID' => 2, 'WpParentWoID' => 1]];
        DbHelpers::assertTableContains('select * from wordparents', $expected, 'parent set');
    }

    /** tests to do:
- save new with new parent -- complicated

- save existing, no parent (existing removed)
- save existing, parent, relationship changed
- existing, parent, new parent
- existing, new parent, complicated
     */

}
