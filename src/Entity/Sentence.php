<?php

namespace App\Entity;

use App\Entity\TextItem;

class Sentence
{

    public int $SeID;

    private ?array $_textitems = null;

    /**
     * @param TextItem[] $textitems
     */
    public function __construct(int $sentence_id, array $textitems)
    {
        $this->SeID = $sentence_id;
        $this->_textitems = $this->calculate_hides($textitems);
    }

    /**
     * Indicate which text items hide other items.
     *
     * Each text item has a "range", given as [ start_order, end_order ].
     * For example, for an item of WordCount = 1, the start and end are the same.
     * For item of WordCount 0, it's the same.
     * For WordCount n, [start, end] = [start, start + (2*n - 1)]
     *
     * If any text item's range fully contains any other text item's range,
     * that text item "hides" the other item.
     *
     * Graphically, suppose we had the following text items, where A-I are
     * WordCount 0 or WordCount 1, and J-M are multiwords:
     *
     *  A   B   C   D   E   F   G   H   I
     *    |---J---|   |---------K---------|
     *                    |---L---|
     *        |-----M---|
     *
     * J hides B and C, B and C should not be rendered.
     * 
     * K hides E-I and also L, so none of those should be rendered.
     *
     * M is _not_ contained by anything else, so it is not hidden.
     */
    private function calculate_hides($items) {
        foreach($items as $ti) {
            $n = max($ti->WordCount, 1);
            $ti->OrderEnd = $ti->Order + 2 * ($n - 1);

            $ti->hides = array();
            $ti->Render = true;  // Assume keep them all at first.
        }

        $isMultiword = function($i) { return $i->WordCount > 1; };
        $multiwords = array_filter($items, $isMultiword);

        foreach ($multiwords as $mw) {
            $isContained = function($i) use ($mw) {
                $contained = ($i->Order >= $mw->Order) && ($i->OrderEnd <= $mw->OrderEnd);
                $equivalent = ($i->Order == $mw->Order) && ($i->OrderEnd == $mw->OrderEnd);
                return $contained && !$equivalent;
            };

            $hides = array_filter($items, $isContained);
            $mw->hides = $hides;
            foreach ($hides as $hidden) {
                $hidden->Render = false;
            }
        }

        return $items;
    }


    public function getTextItems() {
        return $this->_textitems;
    }


    private function sort_by_order_and_wordcount($items): array
    {
        $cmp = function($a, $b) {
            if ($a->Order != $b->Order) {
                return ($a->Order > $b->Order) ? 1 : -1;
            }
            // Fallback: descending order, by word count.
            return ($a->WordCount > $b->WordCount) ? -1 : 1;
        };

        usort($items, $cmp);
        return $items;
    }


    public function renderable() {
        $items = array_filter($this->_textitems, fn($i) => $i->Render);
        $items = $this->sort_by_order_and_wordcount($items);
        return $items;
    }

}
