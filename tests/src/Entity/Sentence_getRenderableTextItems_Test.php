<?php

namespace tests\App\Entity;
 
use App\Entity\Sentence;
use App\Entity\TextItem;
use PHPUnit\Framework\TestCase;
 
class Sentence_getRenderableTextItems_Test extends TestCase
{

    public function setUp(): void
    {
        $this->rendered = '';
        $this->fakeRender = function($ti) {
            $this->rendered .= "[{$ti->Text}-{$ti->WordCount}]";
        };
    }

    // $data = [ [ order, text, wordcount ], ... ]
    private function make_sentence($data)
    {
        $makeTextItem = function($row) {
            $t = new TextItem();
            $t->Order = $row[0];
            $t->Text = $row[1];
            $t->WordCount = $row[2];
            return $t;
        };
        $textItems = array_map($makeTextItem, $data);
        return new Sentence(1, $textItems);
    }


    private function assertRenderableEquals($data, $expected) {
        $sentence = $this->make_sentence($data);
        $actual = '';
        foreach ($sentence->renderable() as $ti)
            $actual .= "[{$ti->Text}-{$ti->WordCount}]";
        $this->assertEquals($actual, $expected);
    }


    public function test_simple_render()
    {
        $data = [
            [ 1, 'some', 1 ],
            [ 2, ' ', 0 ],
            [ 3, 'data', 1 ],
            [ 4, ' ', 0 ],
            [ 5, 'here', 1 ],
            [ 6, '.', 0 ]
        ];
        $expected = '[some-1][ -0][data-1][ -0][here-1][.-0]';
        $this->assertRenderableEquals($data, $expected);
    }

    // Just in case, since ordering is so important.
    public function test_data_out_of_order_still_ok()
    {
        $data = [
            [ 1, 'some', 1 ],
            [ 5, 'here', 1 ],
            [ 4, ' ', 0 ],
            [ 3, 'data', 1 ],
            [ 2, ' ', 0 ],
            [ 6, '.', 0 ]
        ];
        $expected = '[some-1][ -0][data-1][ -0][here-1][.-0]';
        $this->assertRenderableEquals($data, $expected);
    }

    public function test_multiword_items_cover_other_items()
    {
        $data = [
            [ 1, 'some', 1 ],
            [ 5, 'here', 1 ],
            [ 4, ' ', 0 ],
            [ 3, 'data', 1 ],
            [ 2, ' ', 0 ],
            [ 3, 'data here', 2 ],  // <<<
            [ 6, '.', 0 ]
        ];
        $expected = '[some-1][ -0][data here-2][.-0]';
        $this->assertRenderableEquals($data, $expected);
    }


    /* From the class documentation:
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
    public function test_crazy_case()
    {
        $data = [
            [ 1, 'A', 1 ],
            [ 2, 'B', 1 ],
            [ 3, 'C', 1 ],
            [ 4, 'D', 1 ],
            [ 5, 'E', 1 ],
            [ 6, 'F', 1 ],
            [ 7, 'G', 1 ],
            [ 8, 'H', 1 ],
            [ 9, 'I', 1 ],
            [ 2, 'J', 2 ],
            [ 5, 'K', 5 ],
            [ 6, 'L', 2 ],
            [ 3, 'M', 3 ]
        ];
        $expected = '[A-1][J-2][M-3][K-5]';
        $this->assertRenderableEquals($data, $expected);
    }

}