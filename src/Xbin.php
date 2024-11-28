<?php
declare(strict_types=1);

namespace Badahead\AnsiLover {

    use Badahead\AnsiLover\Core\Main;
    use Exception;

    class Xbin extends Main
    {
        public function __construct(string $content, ?string $fontName = null, int $columns = 80, private readonly bool $thumbnail = false) {
            parent::__construct(fontName: $fontName, content: $content);
            $this->columns = 80;
        }

        /**
         * @throws Exception
         */
        final public function render(): string {
            $input_file_size = $this->getInputFileSize();
            // EXTRACT XBIN HEADER                                                       
            $xbin_header['ID'] = substr($this->content, 0, 4);
            if ($xbin_header['ID'] !== 'XBIN') {
                throw new Exception("Not a XBiN file");
            }
            $xbin_header = array_merge($xbin_header, unpack('CEofChar/vWidth/vHeight/CFontsize/CFlags', substr($this->content, 4, 7)));
            if (($xbin_header['Flags'] & 1) === 1) {
                $xbin_flags['palette'] = 1;
            }
            if (($xbin_header['Flags'] & 2) === 2) {
                $xbin_flags['font'] = 1;
            }
            if (($xbin_header['Flags'] & 4) === 4) {
                $xbin_flags['compress'] = 1;
            }
            if (($xbin_header['Flags'] & 8) === 8) {
                $xbin_flags['nonblink'] = 1;
            }
            if (($xbin_header['Flags'] & 16) === 16) {
                $xbin_flags['512chars'] = 1;
            }
            // PROCESS XBIN PALETTE                                                      
            if (isset($xbin_flags['palette']) && (int)$xbin_flags['palette'] === 1) {
                if (!$background = imagecreate(128, 16)) {
                    throw new Exception("Can't allocate background buffer image memory");
                }
                for ($loop = 0; $loop < 16; $loop++) {
                    $index         = ($loop * 3) + 11;
                    $colors[$loop] = imagecolorallocate($background, (ord($this->content[$index]) << 2 | ord($this->content[$index]) >> 4), (ord($this->content[$index + 1]) << 2 | ord($this->content[$index + 1]) >> 4), (ord($this->content[$index + 2]) << 2 | ord($this->content[$index + 2]) >> 4));
                }
                for ($loop = 0; $loop < 16; $loop++) {
                    imagefilledrectangle($background, $loop << 3, 0, ($loop << 3) + 8, 16, $colors[$loop]);
                }
                $background_size_x = 8;
            }
            else {
                $background        = $this->background->content;
                $background_size_x = 9;
            }
            // PROCESS XBIN FONT                                                         
            if ($xbin_flags['font'] === 1) {
                if (!$font_inverted = imagecreate(2048, $xbin_header['Fontsize'])) {
                    throw new Exception("Can't allocate temporary font buffer image memory");
                }
                imagepalettecopy($this->font->content, $background);
                imagepalettecopy($font_inverted, $background);
                $color_index = imagecolorsforindex($background, 0);
                $colors[16]  = imagecolorallocate($this->font->content, $color_index['red'], $color_index['green'], $color_index['blue']);
                $colors[20]  = imagecolorallocate($font_inverted, 200, 220, 169);
                imagefilledrectangle($font_inverted, 0, 0, 2048, $xbin_header['Fontsize'], $colors[20]);
                imagecolortransparent($font_inverted, $colors[20]);
                for ($loop = 0; $loop < 256; $loop++) {
                    for ($xbin_font_size_y = 0; $xbin_font_size_y < $xbin_header['Fontsize']; $xbin_font_size_y++) {
                        $xbin_character_line = ord($this->content[11 + ($xbin_flags['palette'] ?? 0) * 48 + $xbin_font_size_y + ($loop * $xbin_header['Fontsize'])]);
                        for ($loop_column = 0; $loop_column < 8; $loop_column++) {
                            if (($xbin_character_line & 0x80 >> $loop_column) === 0) {
                                imagesetpixel($font_inverted, ($loop * 8) + $loop_column, $xbin_font_size_y, $colors[0] ?? 0);
                            }
                        }
                    }
                }
                for ($loop = 1; $loop < 16; $loop++) {
                    imagefilledrectangle($this->font->content, 0, $loop * $xbin_header['Fontsize'], 2048, ($loop * $xbin_header['Fontsize']) + $xbin_header['Fontsize'], $loop);
                }
                imagefilledrectangle($this->font->content, 0, 0, 2048, $xbin_header['Fontsize'] - 1, $colors[16]);
                for ($loop = 0; $loop < 16; $loop++) {
                    imagecopy($this->font->content, $font_inverted, 0, $loop * $xbin_header['Fontsize'], 0, 0, 2048, $xbin_header['Fontsize']);
                }
                imagecolortransparent($this->font->content, $colors[0] ?? 0);
                $this->font->width  = 8;
                $this->font->height = $xbin_header['Fontsize'];
            }
            else {
                if (!$font = imagecreatefrompng(__DIR__ . '/fonts/pc_80x25.png')) {
                    throw new Exception("Can't open file font file");
                }
                $this->font->width  = 9;
                $this->font->height = 16;
                imagecolortransparent($this->font->content, 20);
            }
            // PROCESS XBIN                                                              
            $loop = 11 + ($xbin_flags['palette'] ?? 0) * 48 + $xbin_flags['font'] * 256 * $xbin_header['Fontsize'];
            if ($xbin_flags['compress'] === 1) {
                while ($loop < $input_file_size) {
                    $character   = ord($this->content[$loop]);
                    $compression = $character & 192;
                    $repeat      = 1 + ($character & 63);
                    if ($compression === 0) {
                        for ($i = 0; $i < $repeat * 2; $i++) {
                            $xbin_buffer[] = ord($this->content[$loop + 1 + $i]);
                        }
                        $loop = $loop + 1 + ($repeat * 2);
                    }
                    if ($compression === 64) {
                        for ($i = 0; $i < $repeat; $i++) {
                            $xbin_buffer[] = ord($this->content[$loop + 1]);
                            $xbin_buffer[] = ord($this->content[$loop + 2 + $i]);
                        }
                        $loop = $loop + 2 + $repeat;
                    }
                    if ($compression === 128) {
                        for ($i = 0; $i < $repeat; $i++) {
                            $xbin_buffer[] = ord($this->content[$loop + 2 + $i]);
                            $xbin_buffer[] = ord($this->content[$loop + 1]);
                        }
                        $loop = $loop + 2 + $repeat;
                    }
                    if ($compression === 192) {
                        for ($i = 0; $i < $repeat; $i++) {
                            $xbin_buffer[] = ord($this->content[$loop + 1]);
                            $xbin_buffer[] = ord($this->content[$loop + 2]);
                        }
                        $loop += 3;
                    }
                }
            }
            else {
                while ($loop < $input_file_size) {
                    $xbin_buffer[] = ord($this->content[$loop]);
                    $loop++;
                }
            }
            // ALLOCATE IMAGE BUFFER MEMORY                                              
            if (!$xbin = imagecreatetruecolor($xbin_header['Width'] * 8, $xbin_header['Height'] * $this->font->height)) {
                throw new Exception("Can't allocate buffer image memory");
            }
            imagecolorallocate($xbin, 0, 0, 0);
            // RENDER XBIN
            $position_x = 0;
            $position_y = 0;
            $counter    = count($xbin_buffer);
            for ($loop = 0; $loop < $counter; $loop += 2) {
                if ($position_x === $xbin_header['Width']) {
                    $position_x = 0;
                    $position_y++;
                }
                $character        = ($xbin_buffer[$loop]);
                $attribute        = ($xbin_buffer[$loop + 1]);
                $color_background = ($attribute & 240) >> 4;
                $color_foreground = $attribute & 15;
                imagecopy($xbin, $background, $position_x * 8, $position_y * $this->font->height, $color_background * $background_size_x, 0, 8, $this->font->height);
                imagecopy($xbin, $this->font->content, $position_x * 8, $position_y * $this->font->height, $character * $this->font->width, $color_foreground * $this->font->height, 8, $this->font->height);
                $position_x++;
            }
            // CREATE OUTPUT FILE                                                        
            if ($this->thumbnail) {
                return $this->thumbnail($xbin, $xbin_header['Width'], $xbin_header['Height']);
            }
            return $this->returnImage(image: $xbin);
        }
    }
}