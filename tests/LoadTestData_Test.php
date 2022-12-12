<?php declare(strict_types=1);

require_once __DIR__ . '/DatabaseTestBase.php';

use App\Entity\Text;
use App\Entity\Language;

// This isn't really a test ... it just loads the database with data.
// Still reasonable to keep as a test though as it needs to always
// work.
final class LoadTestData_Test extends DatabaseTestBase
{

    public function childSetUp(): void
    {
        $this->load_all_test_data();
    }


    public function test_smoke_test()
    {
        $this->assertEquals(1, 1, 'dummy test to stop phpunit complaint');
    }

}
