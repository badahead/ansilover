#!/usr/bin/env php
<?PHP
/*****************************************************************************/
/*   ____   __ __   ___. __  __   _______._______                            */
/* __\   \./  \| \. \  |_| \|  |_/    \  |  /  _/                            */
/* \___\__|__|\___|_____/___|____/____/\___/\____/plur                       */
/*                                                                           */
/* Ansilove/PHP 1.12                                                         */
/* Copyright (c) 2003-2017, Frederic Cambus                                  */
/* https://www.ansilove.org                                                  */
/*                                                                           */
/* Created:      2003-07-17                                                  */
/* Last Updated: 2017-02-06                                                  */
/*                                                                           */
/* Ansilove is released under the BSD 2-Clause license.                      */
/* See LICENSE file for details.                                             */
/*                                                                           */
/*****************************************************************************/
/*****************************************************************************/
/* LOAD CONFIGURATION FILE                                                   */
/*****************************************************************************/
if (!@require_once(__DIR__ . '/ansilove.cfg.php')) {
    echo "ERROR: Can't load Ansilove configuration file.\n\n";
    exit(-1);
}
/*****************************************************************************/
/* SHOW USAGE                                                                */
/*****************************************************************************/
function show_usage() {
    echo "USAGE:    ansilove inputfile columns (.BIN only) font bits icecolors\n\n";
    echo "          Check the README to have details about supported options for each\n";
    echo "          file format.\n\n";
    echo "EXAMPLES: ansilove ansi.ans\n";
    echo "          ansilove ansi.ans 80x25 9 (80x25 font, 9-bit)\n";
    echo "          ansilove ansi.ans 80x25 thumbnail (80x25 font, thumbnail rendering)\n";
    echo "          ansilove ansi.ans 80x50 9 (80x50 font, 9-bit)\n";
    echo "          ansilove ansi.ans russian 9 (Russian font, 9-bit)\n";
    echo "          ansilove ansi.ans amiga (Amiga font)\n";
    echo "          ansilove pcboard.pcb\n";
    echo "          ansilove pcboard.pcb 80x25 9 (80x25 font, 9-bit)\n";
    echo "          ansilove binary.bin 160\n";
    echo "          ansilove binary.bin 160 80x25 9 (80x25 font, 9-bit)\n";
    echo "          ansilove binary.bin 160 80x50 9 (80x50 font, 9-bit)\n";
    echo "          ansilove adf.adf\n";
    echo "          ansilove idf.idf\n";
    echo "          ansilove tundra.tnd\n";
    echo "          ansilove tundra.tnd 80x25 9 (80x25 font, 9-bit)\n";
    echo "          ansilove xbin.xb\n\n";
    exit;
}

/*****************************************************************************/
/* MAIN                                                                      */
/*****************************************************************************/
echo "-------------------------------------------------------------------------------\n              AnsiLove/PHP 1.12 (c) by Frederic Cambus 2003-2017\n-------------------------------------------------------------------------------\n\n";
if (!require_once(__DIR__ . '/ansilove.php')) {
    echo "ERROR: Can't load Ansilove library.\n\n";
    exit(-1);
}
$columns   = null;
$font      = null;
$bits      = null;
$icecolors = null;
$input     = $argv[1];
$output    = $argv[1] . ".png";
/*****************************************************************************/
/* CHECK INPUT PARAMETERS                                                    */
/*****************************************************************************/
$input_file_extension = strtolower(substr($input, strlen($input) - 4, 4));
if ($argc === 1) {
    show_usage();
}
if ($input_file_extension === '.bin') {
    if (isset($argv[2])) {
        $columns = $argv[2];
    }
    if (isset($argv[3])) {
        $font = $argv[3];
    }
    if (isset($argv[4])) {
        $bits = $argv[4];
    }
    if (isset($argv[5])) {
        $icecolors = $argv[5];
    }
}
else {
    if (isset($argv[2])) {
        $font = $argv[2];
    }
    if (isset($argv[3])) {
        $bits = $argv[3];
    }
    if (isset($argv[3])) {
        $icecolors = $argv[4];
    }
}
if (strtolower(substr($input, strlen($input) - 3, 3)) === '.xb') {
    $input_file_extension = '.xb';
}
if ($bits === 'thumbnail') {
    $output = $argv[1] . $this->THUMBNAILS_TAG . ".png";
    $bits   = 'thumbnail';
}
echo "Input File: $input\n";
echo "Output File: $output\n";
echo "Columns (.BIN only): $columns\n";
echo "Font (.ANS/.BIN only): $font\n";
echo "Bits (.ANS/.BIN only): $bits\n";
echo "iCE Colors (.ANS/.BIN only): $icecolors\n\n";
/*****************************************************************************/
/* CREATE OUTPUT FILE                                                        */
/*****************************************************************************/
switch ($input_file_extension) {
    case '.pcb':
        load_pcboard($input, $output, $this->font, $bits, $icecolors);
        break;
    case '.bin':
        load_binary($input, $output, $columns, $this->font, $bits, $icecolors);
        break;
    case '.adf':
        load_adf($input, $output, $bits);
        break;
    case '.idf':
        load_idf($input, $output, $bits);
        break;
    case '.tnd':
        load_tundra($input, $output, $this->font, $bits);
        break;
    case '.xb':
        load_xbin($input, $output, $bits);
        break;
    default:
        load_ansi($input, $output, $this->font, $bits, $icecolors);
}
/*****************************************************************************/
/* DISPLAY SAUCE INFORMATIONS                                                */
/*****************************************************************************/
$input_file_sauce = $this->load_sauce($input);
if ($input_file_sauce !== null) {
    echo "Title: $input_file_sauce[Title]\n";
    echo "Author: $input_file_sauce[Author]\n";
    echo "Group: $input_file_sauce[Group]\n";
    echo "Date: $input_file_sauce[Date]\n";
    echo "Comment: $input_file_sauce[Comment]\n\n";
}
/*****************************************************************************/
/* TERMINATE PROGRAM                                                         */
/*****************************************************************************/
echo "Successfully created file $output\n\n";
?>
