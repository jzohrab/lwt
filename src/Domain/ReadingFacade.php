<?php

namespace App\Domain;

use App\Entity\Text;
use App\Entity\Sentence;
use App\Repository\ReadingRepository;

require_once __DIR__ . '/../../connect.inc.php';


class ReadingFacade {

    public function __construct(ReadingRepository $repo) {
        $this->repo = $repo;
    }

    public function getTextItems(Text $text)
    {
        return $this->repo->getTextItems($text);
    }

    private function buildSentences($textitems) {
        $textitems_by_sentenceid = array();
        foreach($textitems as $t) {
            $textitems_by_sentenceid[$t->SeID][] = $t;
        }

        $sentences = [];
        foreach ($textitems_by_sentenceid as $seid => $textitems)
            $sentences[] = new Sentence($seid, $textitems);

        return $sentences;
    }

    public function getSentences(Text $text)
    {
        $tis = $this->repo->getTextItems($text);
        return $this->buildSentences($tis);
    }

}