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
        if ($text->getID() == null)
            return [];

        $tis = $this->repo->getTextItems($text);

        if (count($tis) == 0) {
            // Catch-all to clean up bad parsing data.
            // TODO:future:2023/02/01 - remove this, slow, when text re-rendering is done.
            Parser::parse($text);
            // TODO:parsing - Seems odd to have to call this separately after parsing.
            ExpressionUpdater::associateExpressionsInText($text);

            $tis = $this->repo->getTextItems($text);
        }

        return $this->buildSentences($tis);
    }

    public function mark_unknowns_as_known(Text $text) {
        $tis = $this->repo->getTextItems($text);

        $is_unknown = function($ti) {
            return $ti->WoID == 0 && $ti->WordCount == 1;
        };
        $unknowns = array_filter($tis, $is_unknown);
        $words_lc = array_map(fn($ti) => $ti->TextLC, $unknowns);
        $uniques = array_unique($words_lc, SORT_STRING);
        sort($uniques);
        echo "will create terms for:\n";
        foreach ($uniques as $u) {
            echo $u . "\n";
        }
    }
}