<?php declare(strict_types=1);

require_once __DIR__ . '/DatabaseTestBase.php';

use App\Entity\Text;
use App\Entity\Language;

// This isn't really a test ... it just loads the database with data.
// Still reasonable to keep as a test though as it needs to always
// work.
final class LoadTestData_Test extends DatabaseTestBase
{

    public function childSetUp(): void
    {
        $this->load_languages();
        $this->load_spanish_words();

        $story = "Érase una vez una preciosa niña que siempre llevaba una capa roja con capucha para protegerse del frío. Por eso, todo el mundo la llamaba Caperucita Roja.

Caperucita vivía en una casita cerca del bosque. Un día, la mamá de  Caperucita le dijo:

– Hija mía, tu abuelita está enferma. He preparado una cestita con tortas y un tarrito de miel para que se la lleves. ¡Ya verás qué contenta se pone!

– ¡Estupendo, mamá! Yo también tengo muchas ganas de ir a visitarla – dijo Caperucita saltando de alegría.

Cuando Caperucita se disponía  a salir de casa, su mamá, con gesto un poco serio, le hizo una advertencia:

– Ten mucho cuidado, cariño. No te entretengas con nada y no hables con extraños. Sabes que en el bosque vive el lobo y es muy peligroso. Si ves que aparece, sigue tu camino sin detenerte.

– No te preocupes, mamita – dijo la niña -. Tendré en cuenta todo lo que me dices.

– Está bien – contestó la mamá, confiada –. Dame un besito y no tardes en regresar.

– Así lo haré, mamá – afirmó de nuevo Caperucita diciendo adiós con su manita mientras se alejaba.";

        $t = new Text();
        $t->setTitle("Caperucita Roja");
        $t->setText($story);
        $t->setLanguage($this->spanish);
        $this->text_repo->save($t, true);

        $this->load_french_data();

        // Load a pile of terms.
        // $spid = $this->spanish->getLgID();
        // DbHelpers::add_word($spid, "Un gato", "un gato", 1, 2);
        if (getenv("LOAD_LOTS_OF_TEST_DATA")) {
            $terms = $this->create_term_texts();
            echo "\nAdding " . count($terms) . " terms to db.\n";
            $this->load_terms($terms);
        }

    }

    private function create_term_texts() {
        $s = "abcde";
        $chars = array();
        for ($i = 0; $i < strlen($s); $i++)
            $chars[] = $s[$i];

        $result = array();
        for ($a = 0; $a < strlen($s); $a++)
            for ($b = 0; $b < strlen($s); $b++)
                for ($c = 0; $c < strlen($s); $c++)
                    for ($d = 0; $d < strlen($s); $d++)
                        $result[] = $chars[$a] . $chars[$b] . $chars[$c] . $chars[$d];
        return $result;
    }

    private function load_terms($terms) {
        $spid = $this->spanish->getLgID();
        $term_batches = array_chunk($terms, 100);
        $n = 0;
        echo "\nAdding " . count($term_batches) . " batches.\n";
        foreach ($term_batches as $term_batch) {
            $n += 1;
            echo "  ... batch {$n} (" . $term_batch[0] . ")\n";

            $term_vals = fn($t) => "({$spid}, '{$t}', '{$t}', 1, 1)";
            $vals = array_map($term_vals, $term_batch);
            $sql = "insert into words (WoLgID, WoText, WoTextLC, WoStatus, WoWordCount) values " .
                 implode(',', $vals);

            DbHelpers::exec_sql($sql);
        }
    }

    public function test_smoke_test()
    {
        $this->assertEquals(1, 1, 'dummy test to stop phpunit complaint');
    }

}
