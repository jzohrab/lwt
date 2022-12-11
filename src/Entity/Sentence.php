<?php

namespace App\Entity;

use App\Entity\TextItem;

class Sentence
{

    private ?int $SeID = null;

    private ?array $_textitems = null;

    /**
     * @param TextItem[] $textitems
     */
    public function __construct(array $textitems)
    {
        $this->_textitems = $textitems;
    }

    private function sortedTextItems(): array
    {
        $cmp = function($a, $b) {
            if ($a->Order != $b->Order) {
                return ($a->Order > $b->Order) ? 1 : -1;
            }
            // Fallback: descending order, by word count.
            return ($a->WordCount > $b->WordCount) ? -1 : 1;
        };

        usort($this->_textitems, $cmp);
        return $this->_textitems;
    }

    public function render($renderer) {
        foreach($this->sortedTextItems() as $ti) {
            $renderer($ti);
        }
    }
}
