<?php declare(strict_types=1);

require_once __DIR__ . '/../do_text_text.php';
require_once __DIR__ . '/db_helpers.php';

use PHPUnit\Framework\TestCase;

final class do_text_text_Test extends TestCase
{

    public function setUp(): void
    {
        $inimsg = 'php.ini must set mysqli.allow_local_infile to 1.';
        $this->assertEquals(ini_get('mysqli.allow_local_infile'), '1', $inimsg);

        // Set up db.
        DbHelpers::ensure_using_test_db();
        DbHelpers::clean_db();
        DbHelpers::load_language_spanish();
        $this->langid = (int) get_first_value("select LgID as value from languages");

        $words = [ 'Un gato', 'lista', 'tiene una' ];
        foreach ($words as $w) {
            DbHelpers::add_word($this->langid, $w, strtolower($w), 1, 2);
        }

        $this->text = "Hola tengo un gato.  No tengo una lista.  Ella tiene una bebida.";
        DbHelpers::add_text($this->text, $this->langid);

        // Sets everything up.
        splitCheckText($this->text, $this->langid, 1);
    }

    public function tearDown(): void
    {
        // echo "tearing down ... \n";
    }

    public function test_smoke_test()
    {
        // Suppressing stdout per
        // https://stackoverflow.com/questions/486181/php-suppress-output-within-a-function
        ob_start();
        main_word_loop(1, false);
        $content = ob_get_flush();
        ob_end_clean();  // Magic.

        // Without the next ob_start, a warning is given:
        // There was 1 risky test: ...
        // Test code or tested code did not (only) close its own output buffers
        // Don't know thet ins and outs of this,
        // and don't really care.  This works fine. jz
        ob_start();

        $expected = '<span id="sent_1"><span 
            id="ID-1-1" 
            class=" click word wsty status0 TERMhola" 
            data_pos="0" 
            data_order="1" 
            data_trans="" data_rom="" data_status="0" 
            data_wid="">Hola</span><span id="ID-2-1" class=""> </span><span 
            id="ID-3-1" 
            class=" click word wsty status0 TERMtengo" 
            data_pos="5" 
            data_order="3" 
            data_trans="" data_rom="" data_status="0" 
            data_wid="">tengo</span><span id="ID-4-1" class=""> </span><span id="ID-5-2" class=" click mword wsty order5 word1 status1 TERMun¤20gato"  data_pos="11" 
            data_order="5" 
            data_wid="1" 
            data_trans="*" 
            data_rom="" 
            data_status="1"  
            data_code="2" 
            data_text="un gato">un gato</span><span 
            id="ID-5-1" 
            class=" hide click word wsty status0 TERMun" 
            data_pos="11" 
            data_order="5" 
            data_trans="" data_rom="" data_status="0" 
            data_wid="">un</span><span id="ID-6-1" class=" hide"> </span><span 
            id="ID-7-1" 
            class=" hide click word wsty status0 TERMgato" 
            data_pos="14" 
            data_order="7" 
            data_trans="" data_rom="" data_status="0" 
            data_wid="">gato</span><span id="ID-8-1" class="">.</span></span><span id="sent_2"><span id="ID-9-1" class=""> </span><span 
            id="ID-10-1" 
            class=" click word wsty status0 TERMno" 
            data_pos="20" 
            data_order="10" 
            data_trans="" data_rom="" data_status="0" 
            data_wid="">No</span><span id="ID-11-1" class=""> </span><span 
            id="ID-12-1" 
            class=" click word wsty status0 TERMtengo" 
            data_pos="23" 
            data_order="12" 
            data_trans="" data_rom="" data_status="0" 
            data_wid="">tengo</span><span id="ID-13-1" class=""> </span><span id="ID-14-2" class=" click mword wsty order14 word2 status1 TERMuna¤20lista"  data_pos="29" 
            data_order="14" 
            data_wid="2" 
            data_trans="*" 
            data_rom="" 
            data_status="1"  
            data_code="2" 
            data_text="una lista">una lista</span><span 
            id="ID-14-1" 
            class=" hide click word wsty status0 TERMuna" 
            data_pos="29" 
            data_order="14" 
            data_trans="" data_rom="" data_status="0" 
            data_wid="">una</span><span id="ID-15-1" class=" hide"> </span><span 
            id="ID-16-1" 
            class=" hide click word wsty status0 TERMlista" 
            data_pos="33" 
            data_order="16" 
            data_trans="" data_rom="" data_status="0" 
            data_wid="">lista</span><span id="ID-17-1" class="">.</span></span><span id="sent_3"><span id="ID-18-1" class=""> </span><span 
            id="ID-19-1" 
            class=" click word wsty status0 TERMella" 
            data_pos="40" 
            data_order="19" 
            data_trans="" data_rom="" data_status="0" 
            data_wid="">Ella</span><span id="ID-20-1" class=""> </span><span id="ID-21-2" class=" click mword wsty order21 word3 status1 TERMtiene¤20una"  data_pos="45" 
            data_order="21" 
            data_wid="3" 
            data_trans="*" 
            data_rom="" 
            data_status="1"  
            data_code="2" 
            data_text="tiene una">tiene una</span><span 
            id="ID-21-1" 
            class=" hide click word wsty status0 TERMtiene" 
            data_pos="45" 
            data_order="21" 
            data_trans="" data_rom="" data_status="0" 
            data_wid="">tiene</span><span id="ID-22-1" class=" hide"> </span><span 
            id="ID-23-1" 
            class=" hide click word wsty status0 TERMuna" 
            data_pos="51" 
            data_order="23" 
            data_trans="" data_rom="" data_status="0" 
            data_wid="">una</span><span id="ID-24-1" class=""> </span><span 
            id="ID-25-1" 
            class=" click word wsty status0 TERMbebida" 
            data_pos="55" 
            data_order="25" 
            data_trans="" data_rom="" data_status="0" 
            data_wid="">bebida</span><span id="ID-26-1" class="">.</span><span id="totalcharcount" class="hide">62</span>';

        // Remove whitespace nonsense
        function clean($s) {
            $array = preg_split("/\r\n|\n|\r/", $s);
            // return $array;
            $tf = function($t) { return trim($t); };
            $t = implode(' ', array_map($tf, $array));
            return preg_split("/ +/" , $t);
        }

        $this->assertEquals(clean($content), clean($expected));
    }

}
