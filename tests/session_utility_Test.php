<?php declare(strict_types=1);

require_once __DIR__ . '/../inc/session_utility.php';

use PHPUnit\Framework\TestCase;

final class session_utility_Test extends TestCase
{

    /**
     * Hack/fake session and server information.
     */
    public function setUp(): void
    {
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REQUEST_URI'] = '/dummyuri';
    }

    public function tearDown(): void
    {
        // echo "tearing down ... \n";
    }

    /**
     * SMOKE TESTS ONLY.
     *
     * These don't really test functionality at the moment,
     * they just ensure that the queries etc work.
     * When the db setup/teardown is in place, and test data
     * loaded, these tests can be replaced/updated to show
     * actual data.
     */

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

    public function test_smoke_get_tags(): void
    {
        $session_keys = [ 'TAGS', 'TBPREF_TAGS' ];
        $this->unset_session_keys($session_keys);
        $t = get_tags();
        $this->assertIsArray($t, 'returns tags');
        $this->assert_all_session_keys_set($session_keys);
    }

    public function test_smoke_get_texttags(): void
    {
        $session_keys = [ 'TEXTTAGS', 'TBPREF_TEXTTAGS' ];
        $this->unset_session_keys($session_keys);
        $t = get_texttags();
        $this->assertIsArray($t, 'returns tags');
        $this->assert_all_session_keys_set($session_keys);
    }

}
