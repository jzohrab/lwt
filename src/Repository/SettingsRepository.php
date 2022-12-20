<?php

namespace App\Repository;

use Doctrine\ORM\EntityManagerInterface;

class SettingsRepository
{
    private $manager;

    public function __construct(EntityManagerInterface $manager)
    {
        $this->manager = $manager;
    }

    private function saveSetting($key, $value) {
        $sql = "update settings set StValue = $value where StKey = '{$key}'";
        $conn = $this->manager->getConnection();
        $stmt = $conn->prepare($sql);
        $res = $stmt->executeQuery();
    }
 
    public function saveCurrentTextID(int $textid) {
        $this->saveSetting('currenttext', $textid);
    }

}
