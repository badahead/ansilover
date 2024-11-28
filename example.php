<?php
declare(strict_types=1);

use Badahead\AnsiLover\Ansi;
use Badahead\AnsiLover\Xbin;

require_once __DIR__ . '/vendor/autoload.php';
$dir = __DIR__ . '/ansis/';
foreach (scandir($dir) as $file) {
    if (strlen($file) < 4 || str_ends_with($file, '.png')) {
        continue;
    }
    echo $file;
    if (str_ends_with($file, '.ans') || str_ends_with($file, '.asc') || str_ends_with($file, '.txt')) {
        $ansi = new Ansi(content: file_get_contents($dir . $file));
        file_put_contents($dir . $file . '.png', $ansi->render());
        echo " - DONE!";
    }
    elseif (str_ends_with($file, '.xb')) {
        $ansi = new Xbin(content: file_get_contents($dir . $file));
        file_put_contents($dir . $file . '.png', $ansi->render());
        echo " - DONE!";
    }
    else {
        echo " - SKIPPED";
    }
    echo "\n";
}

