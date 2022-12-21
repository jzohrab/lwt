<?php declare(strict_types=1);

require_once __DIR__ . '/../../db_helpers.php';
require_once __DIR__ . '/../../DatabaseTestBase.php';

use App\Entity\TermTag;
use App\Entity\Term;
use App\Entity\Text;

// Tests for checking WoStatusChanged field updates.
final class TermRepository_WoStatusChanged_Test extends DatabaseTestBase
{

    public function childSetUp() {
        $this->load_languages();
    }

    public function test_creating_new_term_sets_field() {
        $t = new Term();
        $t->setLanguage($this->spanish);
        $t->setText("PARENT");
        $t->setStatus(1);
        $t->setWordCount(1);
        $this->term_repo->save($t, true);

        $sql = "select WoCreated, WoStatusChanged from words where WoID = {$t->getID()}";

        // get field, should be set
        $rec = DbHelpers::exec_sql_get_result($sql);
        $a = mysqli_fetch_assoc($rec);
        $this->assertEquals($a['WoCreated'], $a['WoStatusChanged']);
    }

    // new term status = today
    // term update status = today
    // term change status to same = not changed
    // sql update to same = not changed
    // sql update = changed
}
