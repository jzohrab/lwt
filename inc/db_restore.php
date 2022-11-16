<?php

/**
 * \file
 * \brief Database restore.
 * 
 * @package Lwt
 * @license Unlicense <http://unlicense.org/>
 */

require_once 'database_connect.php';


/**
 * Finalize db tables and optimize.
 */
function finalize_restore() {
    global $tbpref;
    global $debug;
    global $dbname;

    runsql('DROP TABLE IF EXISTS textitems', '');
    check_update_db($debug, $tbpref, $dbname);
    reparse_all_texts();
    optimizedb();
    get_tags(1);
    get_texttags(1);
}


/**
 * Execute a given filename.
 * 
 * @global string $tbpref Table name prefix
 * 
 * @return [ boolean pass_or_fail, string message ]
 */
function execute_sql_file($file): array
{
    global $DBCONNECTION;

    if (! file_exists($file) ) {
        return [ false, "Error: File ' . $file . ' does not exist" ];
    }

    $failed = false;
    $error = '';

    $commands = file_get_contents($file);
    // echo $commands;

    mysqli_multi_query($DBCONNECTION, $commands);
    do {
        if ($result = mysqli_store_result($DBCONNECTION)) {
            while ($row = mysqli_fetch_row($result)) {
                echo "{$row[0]}\n";
            }
        }
    } while (mysqli_next_result($DBCONNECTION));

    // mysqli_next_result returns false if the query fails,
    // so we have to check the status at the end:
    $err = mysqli_error($DBCONNECTION);
    if ($err != '') {
        // The last query failed, and the script exited early.
        return [ false, $err ];
    }

    return [ true, "" ];
}


/**
 * Install a db using a set of files.
 * 
 * @global string $tbpref Table name prefix
 * 
 * @return string message.
 */
function install_db_fileset($files, $name): string 
{
    foreach ($files as $file) {
        $fullfile = getcwd() . '/db/' . $file;
        [ $result, $error ] = execute_sql_file($fullfile);
        if (! $result) {
            return $name . " NOT installed.  Error in " . $file . ": " . $error;
        }
    }

    finalize_restore();
    return "Success: " . $name . " installed.";
}


/**
 * Install a new db, with no demo data.
 * 
 * @global string $tbpref Table name prefix
 * 
 * @return string message.
 */
function install_new_db(): string 
{
    return install_db_fileset([ 'baseline_schema.sql', 'reference_data.sql' ], "New database");
}


/**
 * Install the db, including demo data.
 * 
 * @global string $tbpref Table name prefix
 * 
 * @return string message.
 */
function install_demo_db(): string 
{
    return install_db_fileset([ 'baseline_schema.sql', 'reference_data.sql', 'demo_data.sql' ], "New database and demo data");
}


?>