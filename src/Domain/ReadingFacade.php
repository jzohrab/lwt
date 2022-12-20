<?php

namespace App\Domain;

use App\Entity\Text;
use App\Entity\Term;
use App\Entity\Status;
use App\Entity\Sentence;
use App\Repository\ReadingRepository;
use App\Repository\TermRepository;

require_once __DIR__ . '/../../connect.inc.php';


class ReadingFacade {

    public function __construct(ReadingRepository $repo, TermRepository $termrepo) {
        $this->repo = $repo;
        $this->termrepo = $termrepo;
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
        $lang =$text->getLanguage();
        foreach ($uniques as $u) {
            $t = new Term();
            $t->setLanguage($lang);
            $t->setText($u);
            $t->setStatus(Status::WELLKNOWN);
            $this->termrepo->save($t, true);
        }
    }

    public function update_status(Text $text, array $words, int $newstatus) {
        if (count($words) == 0)
            return;

        $uniques = array_unique($words, SORT_STRING);

        $lang =$text->getLanguage();
        $tid = $text->getID();
        foreach ($uniques as $u) {
            $t = $this->termrepo->load(0, $tid, 0, $u);
            $t->setLanguage($lang);
            $t->setStatus($newstatus);
            $this->termrepo->save($t, true);
        }
    }

    private function get_prev_or_next(Text $text, bool $getprev = true) {
        $op = $getprev ? " < " : " > ";
        $sortorder = $getprev ? " desc " : "";

        $dql = "SELECT t FROM App\Entity\Text t
        JOIN App\Entity\Language L WITH L = t.language
        WHERE L.LgID = :langid AND t.TxID $op :currid
        ORDER BY t.TxID $sortorder";
        $em = $this->termrepo->getEntityManager();
        $query = $em
               ->createQuery($dql)
               ->setParameter('langid', $text->getLgID())
               ->setParameter('currid', $text->getID())
               ->limit(1);
        $texts = $query->getResult();

        if (count($texts) == 0)
            return null;
        return $texts[0];
    }
    
    public function get_prev_next(Text $text) {
        $p = $this->get_prev_or_next($text, true);
        $n = $this->get_prev_or_next($text, false);
        return [ $p, $n ];
    }

}