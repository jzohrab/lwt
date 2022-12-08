<?php

/** Simple helper functions to log to a file in htdocs/log */

function debug_log($log_msg) {
    if (! isset($_SERVER['DOCUMENT_ROOT']) || $_SERVER['DOCUMENT_ROOT'] == null) {
        echo $log_msg . "\n";
        return;
    }
    $log_filename = $_SERVER['DOCUMENT_ROOT']."/log";
    if (!file_exists($log_filename))
    {
        // create directory/folder uploads.
        mkdir($log_filename, 0777, true);
    }
    $log_file_data = $log_filename.'/debug_log_' . date('d-M-Y') . '.log';
    file_put_contents($log_file_data, $log_msg . "\n", FILE_APPEND);
}

?>