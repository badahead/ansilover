<?php
declare(strict_types=1);

namespace Badahead\AnsiLover {

    use Badahead\AnsiLover\Core\Main;
    use Exception;

    class Tundra extends Main
    {
        /**
         * @throws Exception
         */
        public function __construct(string $content, ?string $fontName = null, private ?int $bits = null, private readonly bool $thumbnail = false) {
            parent::__construct(fontName: $fontName, content: $content);
            $this->columns = 80;
        }

        /**
         * @throws Exception
         */
        final public function render(): string {
            if ($this->bits !== 8 && $this->bits !== 9) {
                $this->bits = 8;
            }
            $input_file_size = $this->getInputFileSize();
            // EXTRACT TUNDRA HEADER
            $tundra_header['value']  = $this->content[0];
            $tundra_header['string'] = substr($this->content, 1, 8);
            if (ord($tundra_header['value']) !== 24 || $tundra_header['string'] !== 'TUNDRA24') {
                throw new Exception("Not a TUNDRA file");
            }
            imagecolorset($this->font->content, 20, 0, 0, 0);
            // READ TUNDRA FILE A FIRST TIME TO FIND THE IMAGE SIZE                      
            $loop       = 9;
            $position_x = 0;
            $position_y = 0;
            while ($loop < $input_file_size) {
                if ($position_x === 80) {
                    $position_x = 0;
                    $position_y++;
                }
                $character = ord($this->content[$loop]);
                if ($character === 1) {
                    $tundra_position_array = unpack('Nposition_y/Nposition_x', substr($this->content, $loop + 1, 8));
                    $position_y            = $tundra_position_array['position_y'];
                    $position_x            = $tundra_position_array['position_x'];
                    $loop                  += 8;
                }
                if ($character === 2) {
                    $character = ord($this->content[$loop + 1]);
                    $loop      += 5;
                }
                if ($character === 4) {
                    $character = ord($this->content[$loop + 1]);
                    $loop      += 5;
                }
                if ($character === 6) {
                    $character = ord($this->content[$loop + 1]);
                    $loop      += 9;
                }
                if ($character !== 1 && $character !== 2 && $character !== 4 && $character !== 6) {
                    $position_x++;
                }
                $loop++;
            }
            $position_y++;
            // ALLOCATE IMAGE BUFFER MEMORY                                              
            if (!$tundra = imagecreate($this->columns * $this->bits, ($position_y) * $this->font->height)) {
                throw new Exception("Can't allocate buffer image memory");
            }
            imagecolorallocate($tundra, 0, 0, 0);
            // PROCESS TUNDRA FILE                                                       
            $position_x = 0;
            $position_y = 0;
            $loop       = 9;
            while ($loop < $input_file_size) {
                if ($position_x === $this->columns) {
                    $position_x = 0;
                    $position_y++;
                }
                $character = ord($this->content[$loop]);
                if ($character === 1) {
                    $tundra_position_array = unpack('Nposition_y/Nposition_x', substr($this->content, $loop + 1, 8));
                    $position_y            = $tundra_position_array['position_y'];
                    $position_x            = $tundra_position_array['position_x'];
                    $loop                  += 8;
                }
                if ($character === 2) {
                    $tundra_color_array = unpack('Ncolor_foreground', substr($this->content, $loop + 2, 4));
                    $color_foreground   = $tundra_color_array['color_foreground'];
                    $red                = ($color_foreground >> 16) & 0x000000FF;
                    $green              = ($color_foreground >> 8) & 0x000000FF;
                    $blue               = $color_foreground & 0x000000FF;
                    imagecolorset($this->font->content, 0, $red, $green, $blue);
                    $character = ord($this->content[$loop + 1]);
                    $loop      += 5;
                }
                if ($character === 4) {
                    $tundra_color_array = unpack('Ncolor_background', substr($this->content, $loop + 2, 4));
                    $color_background   = $tundra_color_array['color_background'];
                    $red                = ($color_background >> 16) & 0x000000FF;
                    $green              = ($color_background >> 8) & 0x000000FF;
                    $blue               = $color_background & 0x000000FF;
                    imagecolorset($this->font->content, 20, $red, $green, $blue);
                    $character = ord($this->content[$loop + 1]);
                    $loop      += 5;
                }
                if ($character === 6) {
                    $tundra_color_array = unpack('Ncolor_foreground/Ncolor_background', substr($this->content, $loop + 2, 8));
                    $color_foreground   = $tundra_color_array['color_foreground'];
                    $red                = ($color_foreground >> 16) & 0x000000FF;
                    $green              = ($color_foreground >> 8) & 0x000000FF;
                    $blue               = $color_foreground & 0x000000FF;
                    imagecolorset($this->font->content, 0, $red, $green, $blue);
                    $color_background = $tundra_color_array['color_background'];
                    $red              = ($color_background >> 16) & 0x000000FF;
                    $green            = ($color_background >> 8) & 0x000000FF;
                    $blue             = $color_background & 0x000000FF;
                    imagecolorset($this->font->content, 20, $red, $green, $blue);
                    $character = ord($this->content[$loop + 1]);
                    $loop      += 9;
                }
                if ($character !== 1 && $character !== 2 && $character !== 4 && $character !== 6) {
                    imagecopy($tundra, $this->font->content, $position_x * $this->bits, $position_y * $this->font->height, $character * $this->font->width, 0, $this->bits, $this->font->height);
                    $position_x++;
                }
                $loop++;
            }
            // CREATE OUTPUT FILE                                                        
            if ($this->thumbnail) {
                return $this->thumbnail($tundra, $this->columns, $position_y);
            }
            return $this->returnImage(image: $tundra);
        }
    }
}