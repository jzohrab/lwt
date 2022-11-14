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

    public function test_smoke_tests_tags(): void
    {
        $session_keys = [ 'TAGS', 'TBPREF_TAGS' ];
        foreach($session_keys as $k) {
            unset($_SESSION[$k]);
            $this->assertFalse(isset($_SESSION[$k]), 'sanity check');
        };
        $t = get_tags();
        $this->assertIsArray($t, 'returns tags');
        foreach($session_keys as $k) {
            $this->assertTrue(isset($_SESSION[$k]), "Session {$k} set");
        };
    }

}
