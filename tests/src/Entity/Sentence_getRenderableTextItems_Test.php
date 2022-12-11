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
        return new Sentence($textItems);
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
        $sentence = $this->make_sentence($data);
        $sentence->render($this->fakeRender);
        $expected = '[some-1][ -0][data-1][ -0][here-1][.-0]';
        $this->assertEquals($this->rendered, $expected);
    }

    // Just in case, since ordering is so important.
    public function test_data_out_of_order_still_ok()
    {
        $data = [
            [ 1, 'some', 1 ],
            [ 5, 'here', 1 ],
            [ 4, ' ', 0 ],
            [ 3, 'data', 1 ],
            [ 3, 'data here', 2 ],  // <<<
            [ 2, ' ', 0 ],
            [ 6, '.', 0 ]
        ];
        $sentence = $this->make_sentence($data);
        $sentence->render($this->fakeRender);
        $expected = '[some-1][ -0][data here-2][data-1][ -0][here-1][.-0]';
        $this->assertEquals($this->rendered, $expected);
    }

    // Just in case, since ordering is so important.
    public function test_multiword_items_are_rendered_first()
    {
        $data = [
            [ 1, 'some', 1 ],
            [ 5, 'here', 1 ],
            [ 4, ' ', 0 ],
            [ 3, 'data', 1 ],
            [ 2, ' ', 0 ],
            [ 6, '.', 0 ]
        ];
        $sentence = $this->make_sentence($data);
        $sentence->render($this->fakeRender);
        $expected = '[some-1][ -0][data-1][ -0][here-1][.-0]';
        $this->assertEquals($this->rendered, $expected);
    }

    /*
        // order, text, wordcount
        $data = [
            [ 1, 'some', 1 ],
            [ 2, ' ', 0 ],
            [ 3, 'data here', 2 ],
            [ 3, 'data', 1 ],
            [ 4, ' ', 0 ],
            [ 5, 'here', 1 ],
            [ 6, '.', 0 ]
        ];
    */
}