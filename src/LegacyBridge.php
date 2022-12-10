<?php

// src/LegacyBridge.php
// Copied from https://symfony.com/doc/current/migration.html, then modified.

namespace App;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class LegacyBridge
{
    public static function handleRequest(Request $request, Response $response, string $publicDirectory)
    {
        // The executed php scripts are all in the root directory,
        // _except_ for some ajax scripts which are called directly
        // and are in /inc/.
        function getscript($s) {
            if ($s == '/') {
                return 'index.php';
            }

            $a = explode('/', $s);
            $r = array_reverse($a);
            $ret = [ $r[0] ];
            if (count($r) > 1 && $r[1] == 'inc' || $r[1] == 'docs') {
                $ret[] = $r[1];
            }
            return implode('/', array_reverse($ret));
        }

        $p = $request->getPathInfo();
        $filename = getscript($p);
        if (strpos($filename, '.css') !== false || strpos($filename, '.js') !== false) {
            throw new \Exception("index.php should not route .js, .css {$filename}");
        }

        $fullpath = __DIR__ . '/../' . $filename;
        $_SERVER['PHP_SELF'] = $p;
        $_SERVER['SCRIPT_NAME'] = $p;
        $_SERVER['SCRIPT_FILENAME'] = $fullpath;

        require $fullpath;
    }
}