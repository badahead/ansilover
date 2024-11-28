<?php
declare(strict_types=1);

namespace Badahead\AnsiLover {

    use Badahead\AnsiLover\Core\Main;
    use Exception;

    class PcBoard extends Main
    {
        /**
         * @throws Exception
         */
        public function __construct(string $content, ?string $fontName = null, private ?int $bits = null, private readonly bool $thumbnail = false) {
            parent::__construct(fontName: $fontName, content: $content);
            $this->columns = 80;
        }

        final public function render():string {
            if ($this->bits !== 8 && $this->bits !== 9) {
                $this->bits = 8;
            }
            imagecolortransparent($this->font->content, 20);
            // ALLOCATE BACKGROUND/FOREGROUND COLOR ARRAYS                               
            $pcb_colors[48] = 0;
            $pcb_colors[49] = 4;
            $pcb_colors[50] = 2;
            $pcb_colors[51] = 6;
            $pcb_colors[52] = 1;
            $pcb_colors[53] = 5;
            $pcb_colors[54] = 3;
            $pcb_colors[55] = 7;
            $pcb_colors[56] = 8;
            $pcb_colors[57] = 12;
            $pcb_colors[65] = 10;
            $pcb_colors[66] = 14;
            $pcb_colors[67] = 9;
            $pcb_colors[68] = 13;
            $pcb_colors[69] = 11;
            $pcb_colors[70] = 15;
            // STRIP UNWANTED PCBOARD CODES (DEFINED IN CONFIG FILE)                     
            $pcboard_strip_codes_exploded = $this->PCBOARD_STRIP_CODES;
            for ($loop = 0; $loop < sizeof($pcboard_strip_codes_exploded); $loop++) {
                $this->content = preg_replace("/(" . $pcboard_strip_codes_exploded[$loop] . ")/", "", $this->content);
            }
            // PROCESS PCB
            $color_background = 0;
            $color_foreground = 7;
            $loop             = 0;
            $position_x       = 0;
            $position_y       = 0;
            $position_x_max   = 0;
            $position_y_max   = 0;
            $input_file_size = $this->getInputFileSize();
            while ($loop < $input_file_size) {
                $current_character = ord($this->content[$loop]);
                $next_character    = ord($this->content[$loop + 1]);
                if ($position_x === 80) {
                    $position_y++;
                    $position_x = 0;
                }
                // CR+LF                                                                     
                if ($current_character === 13) {
                    if ($next_character === 10) {
                        $position_y++;
                        $position_x = 0;
                        $loop++;
                    }
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
                if ($current_character === 26) {
                    break;
                }
                // PCB SEQUENCE                                                              
                if ($current_character === 64 & $next_character === 88) {
                    // SET GRAPHIC RENDITION                                                     
                    $color_background = $pcb_colors[ord($this->content[$loop + 2])];
                    $color_foreground = $pcb_colors[ord($this->content[$loop + 3])];
                    $loop             += 3;
                }
                elseif ($current_character === 64 & $next_character === 67 & $this->content[$loop + 2] === 'L' & $this->content[$loop + 3] === 'S') {
                    // ERASE DISPLAY                                                             
                    unset($pcboard_buffer);
                    $position_x     = 0;
                    $position_y     = 0;
                    $position_x_max = 0;
                    $position_y_max = 0;
                    $loop           += 4;
                }
                elseif ($current_character === 64 & $next_character === 80 & $this->content[$loop + 2] === 'O' & $this->content[$loop + 3] === 'S' & $this->content[$loop + 4] === ':') {
                    // CURSOR POSITION                                                           
                    if ($this->content[$loop + 6] === '@') {
                        $position_x = (ord($this->content[$loop + 5]) - 48) - 1;
                        $loop       += 5;
                    }
                    else {
                        $position_x = (10 * (ord($this->content[$loop + 5]) - 48) + ord($this->content[$loop + 6]) - 48) - 1;
                        $loop       += 6;
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
                    $pcboard_buffer[] = $position_x;
                    $pcboard_buffer[] = $position_y;
                    $pcboard_buffer[] = $color_background;
                    $pcboard_buffer[] = $color_foreground;
                    $pcboard_buffer[] = $current_character;
                    $position_x++;
                }
                $loop++;
            }
            // ALLOCATE IMAGE BUFFER MEMORY                                              
            $position_x_max++;
            $position_y_max++;
            if (!$pcboard = imagecreate($this->columns * $this->bits, ($position_y_max) * $this->font->height)) {
                throw new Exception("Can't allocate buffer image memory");
            }
            imagecolorallocate($pcboard, 0, 0, 0);
            // RENDER PCB                                                                
            for ($loop = 0; $loop < sizeof($pcboard_buffer); $loop += 5) {
                $position_x       = $pcboard_buffer[$loop];
                $position_y       = $pcboard_buffer[$loop + 1];
                $color_background = $pcboard_buffer[$loop + 2];
                $color_foreground = $pcboard_buffer[$loop + 3];
                $character        = $pcboard_buffer[$loop + 4];
                imagecopy($pcboard, $this->background->content, $position_x * $this->bits, $position_y * $this->font->height, $color_background * 9, 0, $this->bits, $this->font->height);
                imagecopy($pcboard, $this->font->content, $position_x * $this->bits, $position_y * $this->font->height, $character * $this->font->width, $color_foreground * $this->font->height, $this->bits, $this->font->height);
            }
            // CREATE OUTPUT FILE                                                        
            if ($this->thumbnail) {
                return $this->thumbnail($pcboard, $this->columns, $position_y_max);
            }
            return $this->returnImage(image: $pcboard);
        }
    }
}