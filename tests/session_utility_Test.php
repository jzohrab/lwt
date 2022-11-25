<?php declare(strict_types=1);

require_once __DIR__ . '/../inc/session_utility.php';
require_once __DIR__ . '/db_helpers.php';

use PHPUnit\Framework\TestCase;

final class session_utility_Test extends TestCase
{

    public function setUp(): void
    {
        // Set up db.
        DbHelpers::ensure_using_test_db();
        DbHelpers::clean_db();

        // Fake session and server info.
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REQUEST_URI'] = '/dummyuri';
        $this->clear_session();
    }

    public function tearDown(): void
    {
        // echo "tearing down ... \n";
    }

    private function clear_session() {
        $session_keys = [ 'TAGS', 'TBPREF_TAGS', 'TEXTTAGS', 'TBPREF_TEXTTAGS' ];
        $this->unset_session_keys($session_keys);
    }

    private function unset_session_keys($keys) {
        foreach($keys as $k) {
            unset($_SESSION[$k]);
            $this->assertFalse(isset($_SESSION[$k]), 'sanity check');
        };
    }

    private function assert_all_session_keys_set($keys) {
        foreach($keys as $k) {
            $this->assertTrue(isset($_SESSION[$k]), "Session {$k} set");
        };
    }

    /**
     * SMOKE TESTS ONLY.
     *
     * These don't test functionality,
     * they just ensure that the queries etc work.
     */
    public function test_smoke_tests(): void
    {
        $this->clear_session();
        get_tags();

        $this->clear_session();
        get_texttags();

        getTextTitle(42);

        get_tag_selectoptions('cat', '');
        get_tag_selectoptions('cat', 42);

        get_texttag_selectoptions('cat', '');
        get_texttag_selectoptions('cat', 42);

        get_txtag_selectoptions('', 'cat');
        get_txtag_selectoptions(42, 'cat');

        get_archivedtexttag_selectoptions('cat', '');
        get_archivedtexttag_selectoptions('cat', 42);

        $_SESSION['TAGS'] = ['cat'];
        $_REQUEST = array('TermTags' => array('TagList' => ['a']));
        saveWordTags(42);

        $_SESSION['TEXTTAGS'] = ['cat'];
        $_REQUEST = array('TextTags' => array('TagList' => ['a']));
        saveTextTags(42);

        $_SESSION['TEXTTAGS'] = ['aoeuaoeu'];
        $_REQUEST = array('TextTags' => array('TagList' => ['a']));
        saveArchivedTextTags(42);

        getWordTags(42);

        getTextTags(42);

        getArchivedTextTags(42);

        addtaglist('cat', '(1,2)');
        addarchtexttaglist('cat', '(1,2)');
        addtexttaglist('cat', '(1,2)');
    }


    /** TAGS **/

    public function test_get_tags(): void
    {
        $expected = ['a', 'b', 'c'];
        DbHelpers::add_tags($expected);
        $t = get_tags();
        $this->assertEquals($t, $expected);
        $this->assertEquals($_SESSION['TAGS'], $expected);
        $this->assertEquals($_SESSION['TBPREF_TAGS'], 'http://localhost/dummyuri/');
    }

    public function test_get_texttags(): void
    {
        $expected = ['a2', 'b2', 'c2'];
        DbHelpers::add_texttags($expected);

        $t = get_texttags();

        $this->assertEquals($t, $expected);
        $this->assertEquals($_SESSION['TEXTTAGS'], $expected);
        $this->assertEquals($_SESSION['TBPREF_TEXTTAGS'], 'http://localhost/dummyuri/');
    }

    /** Word tags */

    public function test_getWordTags_empty()
    {
        $expected = '<ul id="termtags"></ul>';
        $this->assertEquals(getWordTags(42), $expected);
        $this->assertEquals(getWordTagsText(42), []);
    }

    public function test_getWordTags_with_tags()
    {
        DbHelpers::add_tags(['a', 'b']);
        $sql = "select TgID, TgText from tags";
        DbHelpers::assertTableContains($sql, ['1; a', '2; b']);

        do_mysqli_query("insert into wordtags (WtWoID, WtTgID)
VALUES (42, 1), (42, 2), (42,99999)");
        $expected = '<ul id="termtags"><li>a</li><li>b</li></ul>';
        $this->assertEquals(getWordTags(42), $expected);

        $this->assertEquals(getWordTagsText(42), ['a', 'b']);
    }

}
