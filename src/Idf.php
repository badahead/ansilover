<?php
declare(strict_types=1);

namespace Badahead\AnsiLover {

    use Badahead\AnsiLover\Core\FileInterface;
    use Badahead\AnsiLover\Core\Main;
    use Exception;

    final class Idf extends Main implements FileInterface
    {
        /**
         * @throws Exception
         */
        public function __construct(string $content, ?string $fontName = null, private readonly bool $thumbnail = false) {
            parent::__construct(fontName: $fontName, content: $content);
        }

        /**
         * @throws Exception
         */
        final public function render(): string {
            $input_file_size = $this->getInputFileSize();
            // EXTRACT IDF HEADER
            $idf_header['ID'] = substr($this->content, 0, 4);
            $idf_header       = array_merge($idf_header, unpack('vx1/vy1/vx2/vy2', substr($this->content, 4, 8)));
            // ALLOCATE BACKGROUND/FONT IMAGE BUFFER MEMORY                              
            if (!$background = imagecreate(128, 16)) {
                throw new Exception("Can't allocate background buffer image memory");
            }
            if (!$font_inverted = imagecreate(2048, 16)) {
                throw new Exception("Can't allocate temporary font buffer image memory");
            }
            // PROCESS IDF PALETTE                                                       
            for ($loop = 0; $loop < 16; $loop++) {
                $index         = ($loop * 3) + $input_file_size - 48;
                $colors[$loop] = imagecolorallocate($background, (ord($this->content[$index]) << 2 | ord($this->content[$index]) >> 4), (ord($this->content[$index + 1]) << 2 | ord($this->content[$index + 1]) >> 4), (ord($this->content[$index + 2]) << 2 | ord($this->content[$index + 2]) >> 4));
            }
            imagepalettecopy($this->font->content, $background);
            imagepalettecopy($font_inverted, $background);
            $color_index = imagecolorsforindex($background, 0);
            $colors[16]  = imagecolorallocate($this->font->content, $color_index['red'], $color_index['green'], $color_index['blue']);
            $colors[20]  = imagecolorallocate($font_inverted, 200, 220, 169);
            for ($loop = 0; $loop < 16; $loop++) {
                imagefilledrectangle($background, $loop << 3, 0, ($loop << 3) + 8, 16, $colors[$loop]);
            }
            // PROCESS IDF FONT                                                          
            imagefilledrectangle($font_inverted, 0, 0, 2048, 16, $colors[20]);
            imagecolortransparent($font_inverted, $colors[20]);
            for ($loop = 0; $loop < 256; $loop++) {
                for ($idf_font_size_y = 0; $idf_font_size_y < 16; $idf_font_size_y++) {
                    $idf_character_line = ord($this->content[$input_file_size - 48 - 4096 + $idf_font_size_y + ($loop * 16)]);
                    for ($loop_column = 0; $loop_column < 8; $loop_column++) {
                        if (($idf_character_line & 0x80 >> $loop_column) == 0) {
                            imagesetpixel($font_inverted, ($loop * 8) + $loop_column, $idf_font_size_y, $colors[0]);
                        }
                    }
                }
            }
            for ($loop = 1; $loop < 16; $loop++) {
                imagefilledrectangle($this->font->content, 0, $loop * 16, 2048, ($loop * 16) + 16, $colors[$loop]);
            }
            imagefilledrectangle($this->font->content, 0, 0, 2048, 15, $colors[16]);
            for ($loop = 0; $loop < 16; $loop++) {
                imagecopy($this->font->content, $font_inverted, 0, $loop * 16, 0, 0, 2048, 16);
            }
            imagecolortransparent($this->font->content, $colors[0]);
            // PROCESS IDF                                                               
            $loop       = 12;
            $idf_buffer = [];
            while ($loop < $input_file_size - 4096 - 48) {
                $idf_data = unpack('vdata', substr($this->content, $loop, 2));
                if ($idf_data['data'] == 1) {
                    $idf_data            = unpack('vlength', substr($this->content, $loop + 2, 2));
                    $idf_sequence_length = $idf_data['length'] & 255;
                    $idf_data            = unpack('Ccharacter/Cattribute', substr($this->content, $loop + 4, 2));
                    for ($idf_sequence_loop = 0; $idf_sequence_loop < $idf_sequence_length; $idf_sequence_loop++) {
                        $idf_buffer[] = $idf_data['character'];
                        $idf_buffer[] = $idf_data['attribute'];
                    }
                    $loop += 4;
                }
                else {
                    $idf_data     = unpack('Ccharacter/Cattribute', substr($this->content, $loop, 2));
                    $idf_buffer[] = $idf_data['character'];
                    $idf_buffer[] = $idf_data['attribute'];
                }
                $loop += 2;
            }
            // ALLOCATE IMAGE BUFFER MEMORY                                              
            if (!$idf = imagecreate(($idf_header['x2'] + 1) * 8, (count($idf_buffer) / 2 / 80) * 16)) {
                throw new Exception("Can't allocate buffer image memory");
            }
            imagecolorallocate($idf, 0, 0, 0);
            // RENDER IDF
            $position_x = 0;
            $position_y = 0;
            $counter    = count($idf_buffer);
            for ($loop = 0; $loop < $counter; $loop += 2) {
                if ($position_x == $idf_header['x2'] + 1) {
                    $position_x = 0;
                    $position_y++;
                }
                $character        = $idf_buffer[$loop];
                $attribute        = $idf_buffer[$loop + 1];
                $color_background = ($attribute & 240) >> 4;
                $color_foreground = $attribute & 15;
                imagecopy($idf, $background, $position_x * 8, $position_y * 16, $color_background * 8, 0, 8, 16);
                imagecopy($idf, $this->font->content, $position_x * 8, $position_y * 16, $character * 8, $color_foreground * 16, 8, 16);
                $position_x++;
            }
            // CREATE OUTPUT FILE                                                        
            if ($this->thumbnail) {
                $position_y_max = $position_y;
                $this->columns  = 80;
                return $this->thumbnail($idf, $this->columns, $position_y_max);
            }
            return $this->returnImage(image: $idf);
        }
    }
}