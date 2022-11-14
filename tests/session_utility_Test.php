<?php declare(strict_types=1);

require_once __DIR__ . '/../inc/session_utility.php';
require_once __DIR__ . '/db_helpers.php';

use PHPUnit\Framework\TestCase;

final class session_utility_Test extends TestCase
{

    /**
     * Hack/fake session and server information.
     */
    public function setUp(): void
    {
        DbHelpers::ensure_using_test_db();
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

        // TODO - before adding new smoke tests, have to complete
        // dbsetup ... in particular, need to ensure that the tests
        // are only run with a db named TESTING_xxx.
    }


    /**
     * STUB TESTS ONLY.
     *
     * These don't really test functionality at the moment,
     * they just ensure that the queries etc work.
     * When the db setup/teardown is in place, and test data
     * loaded, these tests can be replaced/updated to show
     * actual data.
     */

    /** TAGS **/

    public function test_smoke_get_tags(): void
    {
        $t = get_tags();
        $this->assertIsArray($t, 'returns tags');
        $keys = [ 'TAGS', 'TBPREF_TAGS' ];
        $this->assert_all_session_keys_set($keys);
    }

    public function test_smoke_get_texttags(): void
    {
        $t = get_texttags();
        $this->assertIsArray($t, 'returns tags');
        $keys = [ 'TEXTTAGS', 'TBPREF_TEXTTAGS' ];
        $this->assert_all_session_keys_set($keys);
    }

}
