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
            $this->rendered .= $ti.getText();
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
    
    public function test_render()
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
        $expected = 'some data here.';
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