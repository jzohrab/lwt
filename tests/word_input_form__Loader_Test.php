<?php declare(strict_types=1);

require_once __DIR__ . '/word_input_form_TestBase.php';
require_once __DIR__ . '/db_helpers.php';

use PHPUnit\Framework\TestCase;

final class word_input_form__Loader_Test extends word_input_form_TestBase {

    public function childSetUp() {
        // set up the text
        $this->langid = (int) get_first_value("select LgID as value from languages");
        $this->text = "Hola tengo un gato.  No tengo una lista.  Ella tiene una bebida.";
        DbHelpers::add_text($this->text, $this->langid);

        splitCheckText($this->text, $this->langid, 1);
        $spot_check_sql = "select ti2woid, ti2seid, ti2order, ti2text from textitems2
where ti2order in (1, 10, 25) order by ti2order";
        $expected = [
            "0; 1; 1; Hola",
            "0; 2; 10; No",
            "0; 3; 25; bebida"
        ];
        DbHelpers::assertTableContains($spot_check_sql, $expected);

        $a = $this->make_formdata("BEBIDA");
        $this->wid = save_new_formdata($a);

        $spot_check_sql = "select ti2woid, Ti2TxID, Ti2Order, ti2seid, ti2text from textitems2
where ti2order = 25";
        $expected = [
            "{$this->wid}; 1; 25; 3; bebida"
        ];
        DbHelpers::assertTableContains($spot_check_sql, $expected);
    }

    public function test_load_existing_wid() {
        $fd = load_formdata_from_db($this->wid, 1, 25);
        $expected = array(
            'wid' => ($this->wid),
            'lang' => 1,
            'term' => 'BEBIDA',
            'termlc' => 'bebida',
            'scrdir' => 0,
            'translation' => 'translation BEBIDA',
            'tags' => [],
            'romanization' => 'rom BEBIDA',
            'sentence' => 'sent BEBIDA',
            'status' => 3,
            'status_old' => 3,
            'parent_id' => 0,
            'parent_text' => ''
        );
        foreach ($expected as $prop => $value) {
            $this->assertEquals($value, $fd->$prop, $prop);
        }
    }

    /** tests to do
     * normal cases:
     * - wid in db (existing word) - gets all of its data
     * - wid blank/not given, tid and ord given
     * - wid not given, tid and ord given, but word already exists in db

     * missing params cases:
     * - missing wid = must have tid and ord
     */
}
