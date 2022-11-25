<?php declare(strict_types=1);

require_once __DIR__ . '/word_input_form_TestBase.php';
require_once __DIR__ . '/db_helpers.php';

use PHPUnit\Framework\TestCase;

class word_input_form__Smoke_Test extends word_input_form_TestBase
{

    public function test_export_js_dict()
    {
        $s = $this->child->export_js_dict();
        $this->assertTrue(is_string($s), "got string");
    }

    public function test_tags_to_list() {
        $f = $this->child;
        $f->tags = [];
        $this->assertEquals('<ul id="termtags"></ul>', $f->tags_to_list(), "list");
        $f->tags = ['hi', 'there'];
        $this->assertEquals('<ul id="termtags"><li>hi</li><li>there</li></ul>', $f->tags_to_list(), "list");
    }

}
