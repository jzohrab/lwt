<?php

namespace App\Domain;

use App\Entity\Text;

class Parser {

    /** PUBLIC **/
    
    public static function parse(Text $text) {
        $p = new Parser();
        $p->parseText($text);
    }

    /** PRIVATE **/

    private function parseText(Text $text) {
        // TODO
    }
}