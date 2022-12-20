<?php

namespace App\Domain;

use App\Entity\Text;
use App\Repository\ReadingRepository;

require_once __DIR__ . '/../../connect.inc.php';


class ReadingFacade {

    public function __construct(ReadingRepository $repo) {
        $this->repo = $repo;
    }
    
    public function getSentences(Text $text)
    {
        return $this->repo->getTextItems($text);
    }

}