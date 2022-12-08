<?php declare(strict_types=1);

require_once __DIR__ . '/../inc/word_input_form.php';
require_once __DIR__ . '/db_helpers.php';

use PHPUnit\Framework\TestCase;

final class insert_standard_expression_Test extends TestCase
{

    public function setUp(): void
    {
        // Set up db.
        DbHelpers::ensure_using_test_db();
        DbHelpers::clean_db();
        DbHelpers::load_language_spanish();
        $this->langid = (int) get_first_value("select LgID as value from languages");
    }

    public function tearDown(): void
    {
        // echo "tearing down ... \n";
    }


    // Debugging a specific case where one term was not getting added.
    public function test_debug_prod_case_term_not_getting_added_to_expressions_despite_match()
    {
        $sql = "insert into words (woid, wolgid, wotext, wotextlc, wostatus, wowordcount) 
values 
(3412, 1, 'de refilón', 'de refilón', 1, 2),
(5030, 1, 'Con el tiempo', 'con el tiempo', 1, 3),
(12927, 1, 'pabellón auditivo', 'pabellón auditivo', 1, 2),
(12937, 1, 'nos marcamos', 'nos marcamos', 1, 2)";
        do_mysqli_query($sql);
        
        $sql = "insert into sentences (SeID, SeLgID, SeTxID, SeOrder, SeText, SeFirstPos) 
   values 
   ( 21210, 1, 146, 35, '¿Qué me dice si nos acercamos al bar de la plaza de Sarriá y nos marcamos dos bocadillos de tortilla con muchísima cebolla?', 624),
   ( 21225, 1, 146, 50, 'Un doctor de Cáceres le dijo una vez a mi madre que los Romero de Torres éramos el eslabón perdido entre el hombre y el pez martillo, porque el noventa por ciento de nuestro organismo es cartílago, mayormente concentrado en la nariz y en el pabellón auditivo.', 1017 ),
   ( 21215, 1, 146, 40, 'En la mesa contigua, un hombre observaba a Fermín de refilón por encima del periódico, probablemente pensando lo mismo que yo.', 811 ),
   ( 21184, 1, 146, 9, 'Pese a todo lo que pasó luego y a que nos distanciamos con el tiempo, fuimos buenos amigos:', 196)";
        do_mysqli_query($sql);

        $ret = insert_standard_expression('con el tiempo', 1, 5030, 3, [21175, 21225]);
        $this->assertEquals($ret[1], ["(5030, 1, 146, 21184, 220, 3, 'con el tiempo')"]);

        $ret = insert_standard_expression('pabellón auditivo', 1, 12927, 2, [21175, 21225]);
        $this->assertEquals($ret[1], ["(12927, 1, 146, 21225, 1107, 2, 'pabellón auditivo')"]);

        $ret = insert_standard_expression('nos marcamos', 1, 12937, 2, [21175, 21225]);
        $this->assertEquals($ret[1], ["(12937, 1, 146, 21210, 652, 2, 'nos marcamos')"]);

        $ret = insert_standard_expression('de refilón', 1, 3412, 2, [21175, 21225]);
        $this->assertEquals($ret[1], ["(3412, 1, 146, 21215, 829, 2, 'de refilón')"], 'Fixed!');
    }


    // Not actually encountered yet, but should work.
    public function test_sentence_with_same_term_many_times()
    {
        $sql = "insert into words (woid, wolgid, wotext, wotextlc, wostatus, wowordcount) 
            values (11, 1, 'hay un', 'hay un', 1, 2)";
        do_mysqli_query($sql);
        
        $sql = "insert into sentences (SeID, SeLgID, SeTxID, SeOrder, SeText, SeFirstPos) 
            values ( 2, 1, 42, 145, 'Hoy hay un gato.  HAY UN PERRO.  No hay UN coche.  Y hay una cosa.', 666)";
        do_mysqli_query($sql);

        $ret = insert_standard_expression('hay un', 1, 11, 2, [1, 5]);
        $expected = [
            "(11, 1, 42, 2, 682, 2, 'hay UN')",
            "(11, 1, 42, 2, 674, 2, 'HAY UN')",
            "(11, 1, 42, 2, 668, 2, 'hay un')"
        ];
        $this->assertEquals($ret[1], $expected);
    }
}
