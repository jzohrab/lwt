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
        $t = new Term();
        $t->setLanguage($this->spanish);
        $t->setText("PARENT");
        $t->setStatus(1);
        $t->setWordCount(1);
        $this->term_repo->save($t, true);
        $this->term = $t;
    }

    private function set_WoStatusChanged($newval) {
        $sql = "update words set WoStatusChanged = '" . $newval . "'";
        DbHelpers::exec_sql($sql);
    }

    private function assertUpdated($msg = '') {
        // Cleanest way to check is to timestampdiff ... can't check
        // vs current time because comparison would change with clock
        // ticks.  Yes, this is totally geeky.
        $sql = "SELECT
          WoStatusChanged,
          TIMESTAMPDIFF(SECOND, WoStatusChanged, NOW()) as diffsecs
          FROM words where WoID = {$this->term->getID()}";
        $rec = DbHelpers::exec_sql_get_result($sql);
        $a = mysqli_fetch_assoc($rec);
        $diff = intval($a['diffsecs']);
        $msg = $msg . " Was updated (set to " . $a['WoStatusChanged'] . ")";
        $this->assertTrue($diff < 10, $msg);
    }
    
    public function test_creating_new_term_sets_field() {
        $sql = "select WoCreated, WoStatusChanged from words
                where WoID = {$this->term->getID()}";
        $rec = DbHelpers::exec_sql_get_result($sql);
        $a = mysqli_fetch_assoc($rec);
        $this->assertEquals($a['WoCreated'], $a['WoStatusChanged']);
    }

    public function test_updating_status_updates_field() {
        $this->set_WoStatusChanged("1970-01-01");
        $this->term->setStatus(2);
        $this->term_repo->save($this->term, true);
        $this->assertUpdated();
    }
    
    // term update status = today
    //         $rec = DbHelpers::exec_sql("update words set WoStatusChanged = '1970-01-01'");

    // term change status to same = not changed
    // sql update to same = not changed
    // sql update = changed
}
