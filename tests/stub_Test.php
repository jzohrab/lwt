<?php declare(strict_types=1);

require_once __DIR__ . '/db_helpers.php';

use PHPUnit\Framework\TestCase;

final class stub_Test extends TestCase
{

    public function setUp(): void
    {
        DbHelpers::ensure_using_test_db();
        /*
        DbHelpers::clean_db();
        DbHelpers::load_language_spanish();
        $this->langid = (int) get_first_value("select LgID as value from languages");

        $lid = $this->langid;
        DbHelpers::add_word($lid, "Un gato", "un gato", 1, 2);
        */
    }

    public function tearDown(): void
    {
        // echo "tearing down ... \n";
    }

    public function test_smoke_test()
    {
        $this->assertEquals(1, 1, 'set up ok.');
    }

}
