<?php

/**************************************************************
"Learning with Texts" (LWT) is free and unencumbered software 
released into the PUBLIC DOMAIN.

Anyone is free to copy, modify, publish, use, compile, sell, or
distribute this software, either in source code form or as a
compiled binary, for any purpose, commercial or non-commercial,
and by any means.

In jurisdictions that recognize copyright laws, the author or
authors of this software dedicate any and all copyright
interest in the software to the public domain. We make this
dedication for the benefit of the public at large and to the 
detriment of our heirs and successors. We intend this 
dedication to be an overt act of relinquishment in perpetuity
of all present and future rights to this software under
copyright law.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE 
WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE
AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS BE LIABLE 
FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN 
CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN 
THE SOFTWARE.

For more information, please refer to [http://unlicense.org/].
***************************************************************/

/**************************************************************
 * \file
 * \brief Database Utility Functions
 * 
 * This file is responsible for interacting with the database.
***************************************************************/

/**
 * Do a SQL query to the database. 
 * It is a wrapper for "mysqli_query" function
 */ 
function do_mysqli_query($sql) 
{
    $res = mysqli_query($GLOBALS['DBCONNECTION'], $sql);
    if ($res == false) {
        echo '</select></p></div><div style="padding: 1em; color:red; font-size:120%; background-color:#CEECF5;">' .
        '<p><b>Fatal Error in SQL Query:</b> ' . 
        tohtml($sql) . 
        '</p>' . 
        '<p><b>Error Code &amp; Message:</b> [' . 
        mysqli_errno($GLOBALS['DBCONNECTION']) . 
        '] ' . 
        tohtml(mysqli_error($GLOBALS['DBCONNECTION'])) . 
        "</p></div><hr /><pre>Backtrace:\n\n";
        debug_print_backtrace();
        echo '</pre><hr />';
        die('</body></html>');
    }
    else {
        return $res; 
    }
}

/**
 * Run a SQL query, you can specify its behavior and error message.
 * 
 * @param String $sql       MySQL query
 * @param String $m         Error message ('' to return the number of affected rows)
 * @param Bool   $sqlerrdie To die on errors (default = TRUE)
 */
function runsql($sql, $m, $sqlerrdie = true) 
{
    if ($sqlerrdie) {
        $res = do_mysqli_query($sql); 
    }
    else {
        $res = mysqli_query($GLOBALS['DBCONNECTION'], $sql); 
    }        
    if ($res == false) {
        $message = "Error: " . mysqli_error($GLOBALS['DBCONNECTION']);
    } else {
        $num = mysqli_affected_rows($GLOBALS['DBCONNECTION']);
        $message = (($m == '') ? $num : ($m . ": " . $num));
    }
    return $message;
}

/**
 * Returns the first line from the database 
 *
 * @param String $sql MySQL query
 */
function get_first_value($sql) 
{
    $res = do_mysqli_query($sql);        
    $record = mysqli_fetch_assoc($res);
    if ($record) { 
        $d = $record["value"]; 
    }
    else {
        $d = null; 
    }
    mysqli_free_result($res);
    return $d;
}

// -------------------------------------------------------------

?>
