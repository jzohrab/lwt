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

    /**
     * Finding text items that should be rendered.
     *
     * Each text item has a "range", given as [ start_order, end_order ].
     * For example, for an item of WordCount = 1, the start and end are the same.
     * For item of WordCount 0, it's the same.
     * For WordCount n, [start, end] = [start, start + (2*n - 1)]
     *
     * If any text item's range fully contains any other text item's range, the
     * latter can be excluded.
     *
     * Graphically, suppose we had the following text items, where A-I are
     * WordCount 0 or WordCount 1, and J-M are multiwords:
     *
     *  A   B   C   D   E   F   G   H   I
     *    |---J---|   |---------K---------|
     *                    |---L---|
     *        |-----M---|
     *
     * J contains B and C, so B and C should not be rendered.
     * 
     * K contains E-I and also L, so none of those should be rendered.
     *
     * M is _not_ contained by anything else, so it should be rendered.
     */
    private function textitems_not_contained_by_other_text_items(): array
    {
        $items = $this->_textitems;
        foreach($items as $ti) {
            $n = max($ti->WordCount, 1);
            $ti->OrderEnd = $ti->Order + 2 * ($n - 1);
            $ti->Keep = true;  // Assume keep them all at first.
        }

        $isMultiword = function($i) { return $i->WordCount > 1; };
        $multiwords = array_filter($items, $isMultiword);

        // Don't keep anything contained by a multiword,
        // but don't delete the multiword itself!
        foreach ($multiwords as $mw) {
            $isContained = function($i) use ($mw) {
                $contained = ($i->Order >= $mw->Order) && ($i->OrderEnd <= $mw->OrderEnd);
                $equivalent = ($i->Order == $mw->Order) && ($i->OrderEnd == $mw->OrderEnd);
                return $contained && !$equivalent;
            };

            foreach (array_filter($items, $isContained) as $c) {
                $c->Keep = false;
            }
        }

        return array_filter($items, function($i) { return $i->Keep; });
    }


    public function render($renderer) {
        $items = $this->textitems_not_contained_by_other_text_items();
        $items = $this->sort_by_order_and_wordcount($items);
        foreach ($items as $i) {
            $renderer($i);
        }
    }


    public function renderable() {
        $items = $this->textitems_not_contained_by_other_text_items();
        $items = $this->sort_by_order_and_wordcount($items);
        return $items;
    }

}
