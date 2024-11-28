<?php
declare(strict_types=1);

namespace Badahead\AnsiLover {

    use Badahead\AnsiLover\Core\FileInterface;
    use Badahead\AnsiLover\Core\Main;
    use Exception;

    final class Ansi extends Main implements FileInterface
    {
        private const bool           SUBSTITUTE_BREAK     = true;
        private const bool           WRAP_COLUMN_80       = true;
        private const array          CED_BACKGROUND_COLOR = [170,
                                                             170,
                                                             170];
        private const array          CED_FOREGROUND_COLOR = [0,
                                                             0,
                                                             0];
        private const array          WORKBENCH_COLOR_0    = [170,
                                                             170,
                                                             170];
        private const array          WORKBENCH_COLOR_1    = [0,
                                                             0,
                                                             255];
        private const array          WORKBENCH_COLOR_2    = [255,
                                                             255,
                                                             255];
        private const array          WORKBENCH_COLOR_3    = [0,
                                                             255,
                                                             255];
        private const array          WORKBENCH_COLOR_4    = [0,
                                                             0,
                                                             0];
        private const array          WORKBENCH_COLOR_5    = [255,
                                                             0,
                                                             255];
        private const array          WORKBENCH_COLOR_6    = [102,
                                                             136,
                                                             187];
        private const array          WORKBENCH_COLOR_7    = [255,
                                                             255,
                                                             255];

        /**
         * @throws Exception
         */
        public function __construct(string $content, ?string $fontName = null, int $columns = 80, private ?int $bits = null, private readonly bool $thumbnail = false, private readonly bool $transparent = false, private readonly bool $workbench = false, private readonly bool $ced = false, private readonly bool $icecolors = false, private readonly bool $is_diz = false) {
            parent::__construct(fontName: $fontName, content: $content);
            $this->columns = $columns;
        }

        /**
         * @throws Exception
         */
        final public function render(): string {
            if (($this->bits !== 8 && $this->bits !== 9) || $this->font->is_amiga) {
                $this->bits = 8;
            }
            $input_file_size = $this->getInputFileSize();
            if ($this->is_diz) {
                $this->content   = preg_replace("/^(\s+[\r\n])+/", "", $this->content);
                $this->content   = rtrim((string)$this->content);
                $input_file_size = strlen($this->content);
            }
            imagecolortransparent($this->font->content, 20);
            $color_background = 0;
            $color_foreground = 7;
            $loop             = 0;
            $position_x       = 0;
            $position_y       = 0;
            $position_x_max   = 0;
            $position_y_max   = 0;
            $bold             = false;
            $underline        = false;
            $italics          = false;
            $blink            = false;
            while ($loop < $input_file_size) {
                $current_character = ord($this->content[$loop]);
                $next_character    = ord($this->content[$loop + 1] ?? '');
                if ($position_x === 80 && self::WRAP_COLUMN_80) {
                    $position_y++;
                    $position_x = 0;
                }
                // CR+LF
                if ($current_character === 13 && $next_character === 10) {
                    $position_y++;
                    $position_x = 0;
                    $loop++;
                }
                // LF
                if ($current_character === 10) {
                    $position_y++;
                    $position_x = 0;
                }
                // TAB
                if ($current_character === 9) {
                    $position_x += 8;
                }
                // SUB
                if ($current_character === 26 && self::SUBSTITUTE_BREAK) {
                    break;
                }
                // ANSI SEQUENCE                                                             
                if ($current_character === 27 && $next_character === 91) {
                    unset($ansi_sequence);
                    for ($ansi_sequence_loop = 0; $ansi_sequence_loop < 12; $ansi_sequence_loop++) {
                        $ansi_sequence_character = $this->content[$loop + 2 + $ansi_sequence_loop];
                        // CURSOR POSITION                                                           
                        if ($ansi_sequence_character === 'H' || $ansi_sequence_character === 'f') {
                            if (!isset($ansi_sequence)) {
                                $ansi_sequence = '';
                            }
                            $ansi_sequence_exploded = explode(";", $ansi_sequence);
                            $position_y             = $ansi_sequence_exploded[0] - 1;
                            $position_x             = $ansi_sequence_exploded[1] - 1;
                            $loop                   += $ansi_sequence_loop + 2;
                            break;
                        }
                        // CURSOR UP                                                                 
                        if ($ansi_sequence_character === 'A') {
                            if (($ansi_sequence ?? '') === '') {
                                $ansi_sequence = 1;
                            }
                            $position_y -= $ansi_sequence;
                            $loop       += $ansi_sequence_loop + 2;
                            break;
                        }
                        // CURSOR DOWN                                                               
                        if ($ansi_sequence_character === 'B') {
                            if (($ansi_sequence ?? '') === '') {
                                $ansi_sequence = 1;
                            }
                            $position_y += $ansi_sequence;
                            $loop       += $ansi_sequence_loop + 2;
                            break;
                        }
                        // CURSOR FORWARD                                                            
                        if ($ansi_sequence_character === 'C') {
                            if (($ansi_sequence ?? '') === '') {
                                $ansi_sequence = 1;
                            }
                            $position_x += $ansi_sequence;
                            if ($position_x > 80) {
                                $position_x = 80;
                            }
                            $loop += $ansi_sequence_loop + 2;
                            break;
                        }
                        // CURSOR BACKWARD                                                           
                        if ($ansi_sequence_character === 'D') {
                            if (($ansi_sequence ?? '') === '') {
                                $ansi_sequence = 1;
                            }
                            $position_x -= $ansi_sequence;
                            if ($position_x < 0) {
                                $position_x = 0;
                            }
                            $loop += $ansi_sequence_loop + 2;
                            break;
                        }
                        // SAVE CURSOR POSITION                                                      
                        if ($ansi_sequence_character === 's') {
                            $saved_position_y = $position_y;
                            $saved_position_x = $position_x;
                            $loop             += $ansi_sequence_loop + 2;
                            break;
                        }
                        // RESTORE CURSOR POSITION                                                   
                        if ($ansi_sequence_character === 'u') {
                            $position_y = $saved_position_y;
                            $position_x = $saved_position_x;
                            $loop       += $ansi_sequence_loop + 2;
                            break;
                        }
                        // ERASE DISPLAY                                                             
                        if ($ansi_sequence_character === 'J') {
                            if ((int)$ansi_sequence === 2) {
                                unset($ansi_buffer);
                                $position_x     = 0;
                                $position_y     = 0;
                                $position_x_max = 0;
                                $position_y_max = 0;
                            }
                            $loop += $ansi_sequence_loop + 2;
                            break;
                        }
                        // SET GRAPHIC RENDITION                                                     
                        if ($ansi_sequence_character === 'm') {
                            $ansi_sequence_exploded = explode(";", $ansi_sequence);
                            sort($ansi_sequence_exploded);
                            $counter = count($ansi_sequence_exploded);
                            for ($loop_ansi_sequence = 0; $loop_ansi_sequence < $counter; $loop_ansi_sequence++) {
                                $ansi_sequence_value = $ansi_sequence_exploded[$loop_ansi_sequence];
                                if ((int)$ansi_sequence_value === 0) {
                                    $color_background = 0;
                                    $color_foreground = 7;
                                    $bold             = false;
                                    $underline        = false;
                                    $italics          = false;
                                    $blink            = false;
                                }
                                elseif ((int)$ansi_sequence_value === 1) {
                                    if (!$this->workbench) {
                                        $color_foreground += 8;
                                    }
                                    $bold = true;
                                }
                                elseif ((int)$ansi_sequence_value === 3) {
                                    $italics = true;
                                }
                                elseif ((int)$ansi_sequence_value === 4) {
                                    $underline = true;
                                }
                                elseif ((int)$ansi_sequence_value === 5) {
                                    if (!$this->workbench) {
                                        $color_background += 8;
                                    }
                                    $blink = true;
                                }
                                elseif ((int)$ansi_sequence_value > 29 && (int)$ansi_sequence_value < 38) {
                                    $color_foreground = (int)$ansi_sequence_value - 30;
                                    if ($bold) {
                                        $color_foreground += 8;
                                    }
                                }
                                elseif ($ansi_sequence_value > 39 && $ansi_sequence_value < 48) {
                                    $color_background = $ansi_sequence_value - 40;
                                    if ($blink && $this->icecolors) {
                                        $color_background += 8;
                                    }
                                }
                            }
                            $loop += $ansi_sequence_loop + 2;
                            break;
                        }
                        // CURSOR DE/ACTIVATION (AMIGA ANSI)                                         
                        if ($ansi_sequence_character === 'p') {
                            $loop += $ansi_sequence_loop + 2;
                            break;
                        }
                        // SKIPPING SET MODE AND RESET MODE SEQUENCES                                
                        if ($ansi_sequence_character === 'h' || $ansi_sequence_character === 'l') {
                            $loop += $ansi_sequence_loop + 2;
                            break;
                        }
                        if (!isset($ansi_sequence)) {
                            $ansi_sequence = '';
                        }
                        $ansi_sequence .= $ansi_sequence_character;
                    }
                }
                elseif ($current_character !== 10 && $current_character !== 13 && $current_character !== 9) {
                    // RECORD NUMBER OF COLUMNS AND LINES USED                                   
                    if ($position_x > $position_x_max) {
                        $position_x_max = $position_x;
                    }
                    if ($position_y > $position_y_max) {
                        $position_y_max = $position_y;
                    }
                    // WRITE CURRENT CHARACTER INFO IN A TEMPORARY ARRAY                         
                    if (!$this->font->is_amiga || ($current_character !== 12 && $current_character !== 13)) {
                        if (!isset($ansi_buffer)) {
                            $ansi_buffer = '';
                        }
                        $ansi_buffer .= chr($color_background);
                        $ansi_buffer .= chr($color_foreground);
                        $ansi_buffer .= chr($current_character);
                        $ansi_buffer .= chr($bold ?? false ? 1 : 0);
                        $ansi_buffer .= chr($italics ?? false ? 1 : 0);
                        $ansi_buffer .= chr($underline ?? false ? 1 : 0);
                        $ansi_buffer .= chr($position_x);
                        $ansi_buffer .= chr($position_y & 0xFF);
                        $ansi_buffer .= chr($position_y >> 8);
                        $position_x++;
                    }
                }
                $loop++;
            }
            // ALLOCATE IMAGE BUFFER MEMORY                                              
            $position_x_max++;
            $position_y_max++;
            if ($this->ced) {
                $this->columns = 78;
            }
            if ($this->is_diz) {
                $this->columns = min($position_x_max, 80);
            }
            if (!$ansi = imagecreate($this->columns * $this->bits, ($position_y_max) * $this->font->height)) {
                throw new Exception("Can't allocate buffer image memory");
            }
            if ($this->ced) {
                $ced_background_color = self::CED_BACKGROUND_COLOR;
                $ced_foreground_color = self::CED_FOREGROUND_COLOR;
                imagecolorallocate($ansi, $ced_background_color[0], $ced_background_color[1], $ced_background_color[2]);
                $ced_color = imagecolorallocate($ansi, $ced_background_color[0], $ced_background_color[1], $ced_background_color[2]);
                $ced_color = imagecolorallocate($this->background->content, $ced_background_color[0], $ced_background_color[1], $ced_background_color[2]);
                imagefill($ansi, 0, 0, $ced_color);
                imagefilledrectangle($this->background->content, 0, 0, 144, 16, $ced_color);
                for ($loop = 0; $loop < 16; $loop++) {
                    imagecolorset($this->font->content, $loop, $ced_foreground_color[0], $ced_foreground_color[1], $ced_foreground_color[2]);
                }
            }
            elseif ($this->workbench) {
                $workbench_color = [0 => self::WORKBENCH_COLOR_0,
                                    1 => self::WORKBENCH_COLOR_4,
                                    2 => self::WORKBENCH_COLOR_2,
                                    3 => self::WORKBENCH_COLOR_6,
                                    4 => self::WORKBENCH_COLOR_1,
                                    5 => self::WORKBENCH_COLOR_5,
                                    6 => self::WORKBENCH_COLOR_3,
                                    7 => self::WORKBENCH_COLOR_7,];
                imagecolorallocate($ansi, $workbench_color[0][0], $workbench_color[0][1], $workbench_color[0][2]);
                $workbench_background = imagecolorallocate($ansi, $workbench_color[0][0], $workbench_color[0][1], $workbench_color[0][2]);
                $workbench_background = imagecolorallocate($this->background->content, $workbench_color[0][0], $workbench_color[0][1], $workbench_color[0][2]);
                imagefill($ansi, 0, 0, $workbench_background);
                for ($loop = 0; $loop < 8; $loop++) {
                    imagecolorset($this->background->content, $loop, $workbench_color[$loop][0], $workbench_color[$loop][1], $workbench_color[$loop][2]);
                    imagecolorset($this->background->content, $loop + 8, $workbench_color[$loop][0], $workbench_color[$loop][1], $workbench_color[$loop][2]);
                    imagecolorset($this->font->content, $loop, $workbench_color[$loop][0], $workbench_color[$loop][1], $workbench_color[$loop][2]);
                    imagecolorset($this->font->content, $loop + 8, $workbench_color[$loop][0], $workbench_color[$loop][1], $workbench_color[$loop][2]);
                }
            }
            else {
                $background_canvas = imagecolorallocate($ansi, 0, 0, 0);
            }
            for ($loop = 0; $loop < 16; $loop++) {
                // Generating ANSI colors array in order to be able to draw underlines 
                $color_index   = imagecolorsforindex($this->background->content, $loop);
                $colors[$loop] = imagecolorallocate($ansi, $color_index['red'], $color_index['green'], $color_index['blue']);
            }
            // RENDER ANSI                                                   
            for ($loop = 0; $loop < strlen($ansi_buffer); $loop += 9) {
                $color_background = ord($ansi_buffer[$loop]);
                $color_foreground = ord($ansi_buffer[$loop + 1]);
                $character        = ord($ansi_buffer[$loop + 2]);
                $bold             = ord($ansi_buffer[$loop + 3]);
                $italics          = ord($ansi_buffer[$loop + 4]);
                $underline        = ord($ansi_buffer[$loop + 5]);
                $position_x       = ord($ansi_buffer[$loop + 6]);
                $position_y       = ord($ansi_buffer[$loop + 7]) + (ord($ansi_buffer[$loop + 8]) << 8);
                if (!$this->font->is_amiga) {
                    imagecopy($ansi, $this->background->content, $position_x * $this->bits, $position_y * $this->font->height, $color_background * 9, 0, $this->bits, $this->font->height);
                    imagecopy($ansi, $this->font->content, $position_x * $this->bits, $position_y * $this->font->height, $character * $this->font->width, $color_foreground * $this->font->height, $this->bits, $this->font->height);
                }
                else {
                    if ($color_background !== 0 || !$italics) {
                        imagecopy($ansi, $this->background->content, $position_x * $this->bits, $position_y * $this->font->height, $color_background * 9, 0, $this->bits, $this->font->height);
                    }
                    if ($italics === 0) {
                        imagecopy($ansi, $this->font->content, $position_x * $this->bits, $position_y * $this->font->height, $character * $this->font->width, $color_foreground * $this->font->height, $this->bits, $this->font->height);
                    }
                    else {
                        imagecopy($ansi, $this->font->content, $position_x * $this->bits + 3, $position_y * $this->font->height, $character * $this->font->width, $color_foreground * $this->font->height, $this->bits, 2);
                        imagecopy($ansi, $this->font->content, $position_x * $this->bits + 2, $position_y * $this->font->height + 2, $character * $this->font->width, $color_foreground * $this->font->height + 2, $this->bits, 4);
                        imagecopy($ansi, $this->font->content, $position_x * $this->bits + 1, $position_y * $this->font->height + 6, $character * $this->font->width, $color_foreground * $this->font->height + 6, $this->bits, 4);
                        imagecopy($ansi, $this->font->content, $position_x * $this->bits, $position_y * $this->font->height + 10, $character * $this->font->width, $color_foreground * $this->font->height + 10, $this->bits, 4);
                        imagecopy($ansi, $this->font->content, $position_x * $this->bits - 1, $position_y * $this->font->height + 14, $character * $this->font->width, $color_foreground * $this->font->height + 14, $this->bits, 2);
                    }
                    if ($italics && $bold) {
                        imagecopy($ansi, $this->font->content, $position_x * $this->bits + 3 + 1, $position_y * $this->font->height, $character * $this->font->width, $color_foreground * $this->font->height, $this->bits, 2);
                        imagecopy($ansi, $this->font->content, $position_x * $this->bits + 2 + 1, $position_y * $this->font->height + 2, $character * $this->font->width, $color_foreground * $this->font->height + 2, $this->bits, 4);
                        imagecopy($ansi, $this->font->content, $position_x * $this->bits + 1 + 1, $position_y * $this->font->height + 6, $character * $this->font->width, $color_foreground * $this->font->height + 6, $this->bits, 4);
                        imagecopy($ansi, $this->font->content, $position_x * $this->bits + 1, $position_y * $this->font->height + 10, $character * $this->font->width, $color_foreground * $this->font->height + 10, $this->bits, 4);
                        imagecopy($ansi, $this->font->content, $position_x * $this->bits - 1 + 1, $position_y * $this->font->height + 14, $character * $this->font->width, $color_foreground * $this->font->height + 14, $this->bits, 2);
                    }
                    if ($bold && !$italics && ($this->ced || $this->workbench)) {
                        imagecopy($ansi, $this->font->content, 1 + $position_x * $this->bits, $position_y * $this->font->height, $character * $this->font->width, $color_foreground * $this->font->height, $this->bits, $this->font->height);
                    }
                    if ($underline !== 0) {
                        $loop_column      = 0;
                        $character_size_x = 8;
                        if ($bold !== 0) {
                            $character_size_x++;
                        }
                        if ($italics !== 0) {
                            $loop_column      = -1;
                            $character_size_x = 11;
                        }
                        while ($loop_column < $character_size_x) {
                            if (imagecolorat($ansi, $position_x * $this->bits + $loop_column, $position_y * $this->font->height + 15) === $color_background && imagecolorat($ansi, $position_x * $this->bits + $loop_column + 1, $position_y * $this->font->height + 15) === $color_background) {
                                imagesetpixel($ansi, $position_x * $this->bits + $loop_column, $position_y * $this->font->height + 14, $colors[$color_foreground]);
                                imagesetpixel($ansi, $position_x * $this->bits + $loop_column, $position_y * $this->font->height + 15, $colors[$color_foreground]);
                            }
                            elseif (imagecolorat($ansi, $position_x * $this->bits + $loop_column, $position_y * $this->font->height + 15) !== $color_background && imagecolorat($ansi, $position_x * $this->bits + $loop_column + 1, $position_y * $this->font->height + 15) === $color_background) {
                                $loop_column++;
                            }
                            $loop_column++;
                        }
                        if ($pixel_carry ?? false) {
                            imagesetpixel($ansi, $position_x * $this->bits, $position_y * $this->font->height + 14, $colors[$color_foreground]);
                            imagesetpixel($ansi, $position_x * $this->bits, $position_y * $this->font->height + 15, $colors[$color_foreground]);
                            $pixel_carry = false;
                        }
                        if (imagecolorat($this->font->content, $character * $this->font->width, $color_foreground * $this->font->height + 15) !== 20) {
                            imagesetpixel($ansi, $position_x * $this->bits - 1, $position_y * $this->font->height + 14, $colors[$color_foreground]);
                            imagesetpixel($ansi, $position_x * $this->bits - 1, $position_y * $this->font->height + 15, $colors[$color_foreground]);
                        }
                        if (imagecolorat($this->font->content, $character * $this->font->width + $character_size_x - 1, $color_foreground * $this->font->height + 15) !== 20) {
                            $pixel_carry = true;
                        }
                    }
                }
            }
            if ($this->transparent) {
                imagecolortransparent($ansi, $background_canvas);
            }
            if ($this->thumbnail) {
                return $this->thumbnail(source: $ansi, columns: $this->columns, position_y_max: $position_y_max);
            }
            return $this->returnImage(image: $ansi);
        }
    }
}