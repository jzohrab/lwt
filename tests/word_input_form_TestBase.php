<?php declare(strict_types=1);

require_once __DIR__ . '/../inc/word_input_form.php';
require_once __DIR__ . '/db_helpers.php';

use PHPUnit\Framework\TestCase;

abstract class word_input_form_TestBase extends TestCase
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

        $this->childSetUp();
    }

    public function childSetUp() {
        // no-op, child tests can override this to set up stuff.
    }

    public function make_formdata($term) {
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

    public function assert_wordparents_equals($expected, $message) {
        $sql = 'select WpWoID, WpParentWoID from wordparents';
        DbHelpers::assertTableContains($sql, $expected, $message);
    }

    public function test_dummy() {
        // Needed this base class to have a test to prevent warning:
        // "No tests found in class "word_input_form_Test".
        // Probably a better way to do this, but who cares.
        $this->assertTrue(true);
    }

}
