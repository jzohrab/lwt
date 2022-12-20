<?php
/**
 * \file
 * \brief CSS minifier.
 * 
 * Minify CSS files from src/css to public/css/.
 * 
 * @package Lwt
 * @author  HugoFara <hugo.farajallah@protonmail.com>
 * @license Unlicense <http://unlicense.org/>
 */
require __DIR__ . '/../../vendor/autoload.php';
use MatthiasMullie\Minify;


function minifyCSS($path, $outputPath) 
{
    $minifier = new Minify\CSS();
    $minifier->add($path);
    // Save minified file to disk
    return $minifier->minify($outputPath);
}


function minifyAllCSS() 
{
    $cssFiles = array(
        'src/css/css_charts.css', 'src/css/feed_wizard.css', 'src/css/gallery.css', 
        'src/css/jplayer.css', 'src/css/jquery-ui.css', 'src/css/jquery.tagit.css',
        'src/css/styles.css', 'src/css/styles-compact.css',
    );

    foreach ($cssFiles as $path) {
        $name = basename($path);
        if (file_exists($path)) {
            minifyCSS($path, 'public/css/' . $name);
        }
    }
}


function minify_everything()
{
    echo "Minifying CSS...\n";
    minifyAllCSS();
}


?>