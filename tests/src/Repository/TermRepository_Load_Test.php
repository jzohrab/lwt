<?php declare(strict_types=1);

require_once __DIR__ . '/../../db_helpers.php';
require_once __DIR__ . '/../../DatabaseTestBase.php';

use App\Entity\Term;
use App\Entity\Text;

final class TermRepository_Load_Test extends DatabaseTestBase {

    public function childSetUp() {
        // set up the text
        $this->load_languages();

        $t = new Text();
        $t->setTitle("Hola.");
        $t->setText("Hola tengo un gato.  No TENGO una lista.  Ella tiene una bebida.");
        $lang = $this->spanish;
        $t->setLanguage($lang);
        $this->text_repo->save($t, true);

        $spot_check_sql = "select ti2woid, ti2seid, ti2order, ti2text from textitems2
where ti2order in (1, 12, 25) order by ti2order";
        $expected = [
            "0; 1; 1; Hola",
            "0; 2; 12; TENGO",
            "0; 3; 25; bebida"
        ];
        DbHelpers::assertTableContains($spot_check_sql, $expected);

        $term = new Term();
        $term->setLanguage($this->spanish);
        $term->setText("BEBIDA");
        $term->setStatus(1);
        $term->setWordCount(1);
        $this->term_repo->save($term, true);
        $this->bebida = $term;

        $spot_check_sql = "select ti2woid, Ti2TxID, Ti2Order, ti2seid, ti2text from textitems2
where ti2order = 25";
        $expected = [
            "{$term->getID()}; 1; 25; 3; bebida"
        ];
        DbHelpers::assertTableContains($spot_check_sql, $expected);
    }

    public function test_load_existing_wid() {
        $t = $this->term_repo->load($this->bebida->getID(), 1, 25, '');
        $this->assertEquals($t->getID(), $this->bebida->getID(), 'id');
        $this->assertEquals($t->getText(), "BEBIDA", 'text');
    }

    /*
    public function test_no_wid_load_by_tid_and_ord_matches_existing_word() {
        $fd = load_formdata_from_db('', 1, 25);
        $expected = array(
            'wid' => ($this->wid),
            'lang' => 1,
            'term' => 'BEBIDA',
            'termlc' => 'bebida',
            'scrdir' => '',
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

    public function test_no_wid_load_by_tid_and_ord_new_word() {
        $fd = load_formdata_from_db('', 1, 12);
        $expected = array(
            'wid' => 0,  // New word!
            'lang' => 1,
            'term' => 'TENGO',
            'termlc' => 'tengo',
            'scrdir' => '',
            'translation' => '',
            'tags' => [],
            'romanization' => '',
            'sentence' => 'No {TENGO} una lista.',
            'status' => 1,
            'status_old' => 1,
            'parent_id' => 0,
            'parent_text' => ''
        );
        foreach ($expected as $k => $v) {
            $this->assertEquals($v, $fd->$k, "checking $k");
        }
    }


    public function test_multi_word_overrides_tid_and_ord() {
        $fd = load_formdata_from_db('', 1, 12, 'TENGO una');
        $expected = array(
            'wid' => 0,  // New word!
            'lang' => 1,
            'term' => 'TENGO una',
            'termlc' => 'tengo una',
            'scrdir' => '',
            'translation' => '',
            'tags' => [],
            'romanization' => '',
            'sentence' => 'No {TENGO una} lista.',
            'status' => 1,
            'status_old' => 1,
            'parent_id' => 0,
            'parent_text' => ''
        );
        foreach ($expected as $k => $v) {
            $this->assertEquals($v, $fd->$k, "checking $k");
        }
    }


    public function test_multi_word_returns_existing_word_if_it_matches() {
        $wid = DbHelpers::add_word(1, 'TENGO UNA', 'tengo una', 4, 2);

        $fd = load_formdata_from_db('', 1, 12, 'TENGO una');
        $expected = array(
            'wid' => $wid,  // Matches existing word!
            'lang' => 1,
            'term' => 'TENGO UNA',
            'termlc' => 'tengo una',
            'scrdir' => '',
            'status' => 4,
            'status_old' => 4,
            'parent_id' => 0,
            'parent_text' => ''
        );
        foreach ($expected as $k => $v) {
            $this->assertEquals($v, $fd->$k, "checking $k");
        }
    }


    public function test_missing_tid_or_ord_throws() {
        $msg = '';
        try { load_formdata_from_db('', 0, 0); }
        catch (Exception $e) { $msg .= '1'; }
        try { load_formdata_from_db('', 0, 1); }
        catch (Exception $e) { $msg .= '2'; }
        try { load_formdata_from_db('', 1, 0); }
        catch (Exception $e) { $msg .= '3'; }

        try { load_formdata_from_db('', 1, 1); }
        catch (Exception $e) { $msg .= 'this does not throw, the tid and ord are sufficient'; }
        try { load_formdata_from_db(1, 1, null); }
        catch (Exception $e) { $msg .= 'this does not throw, the wid is sufficient'; }
        $this->assertEquals('123', $msg, 'all failed :-P');
    }

    */

}
