<?php
declare(strict_types=1);

namespace Badahead\AnsiLover {

    use Badahead\AnsiLover\Core\Main;
    use Exception;

    class Adf extends Main
    {
        private const array    ADF_COLORS = [0,
                                             1,
                                             2,
                                             3,
                                             4,
                                             5,
                                             20,
                                             7,
                                             56,
                                             57,
                                             58,
                                             59,
                                             60,
                                             61,
                                             62,
                                             63];

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
            // ALLOCATE BACKGROUND/FONT IMAGE BUFFER MEMORY
            if (!$background = imagecreate(128, 16)) {
                throw new Exception("Can't allocate background buffer image memory");
            }
            if (!$font = imagecreate(2048, 256)) {
                throw new Exception("Can't allocate font buffer image memory");
            }
            if (!$font_inverted = imagecreate(2048, 16)) {
                throw new Exception("Can't allocate temporary font buffer image memory");
            }
            // PROCESS ADF PALETTE
            for ($loop = 0; $loop < 16; $loop++) {
                $index         = (self::ADF_COLORS[$loop] * 3) + 1;
                $colors[$loop] = imagecolorallocate($background, (ord($this->content[$index]) << 2 | ord($this->content[$index]) >> 4), (ord($this->content[$index + 1]) << 2 | ord($this->content[$index + 1]) >> 4), (ord($this->content[$index + 2]) << 2 | ord($this->content[$index + 2]) >> 4));
            }
            imagepalettecopy($font, $background);
            imagepalettecopy($font_inverted, $background);
            $color_index = imagecolorsforindex($background, 0);
            $colors[16]  = imagecolorallocate($font, $color_index['red'], $color_index['green'], $color_index['blue']);
            $colors[20]  = imagecolorallocate($font_inverted, 200, 220, 169);
            for ($loop = 0; $loop < 16; $loop++) {
                imagefilledrectangle($background, $loop << 3, 0, ($loop << 3) + 8, 16, $colors[$loop]);
            }
            // PROCESS ADF FONT
            imagefilledrectangle($font_inverted, 0, 0, 2048, 16, $colors[20]);
            imagecolortransparent($font_inverted, $colors[20]);
            for ($loop = 0; $loop < 256; $loop++) {
                for ($adf_font_size_y = 0; $adf_font_size_y < 16; $adf_font_size_y++) {
                    $adf_character_line = ord($this->content[193 + $adf_font_size_y + ($loop * 16)]);
                    for ($loop_column = 0; $loop_column < 8; $loop_column++) {
                        if (($adf_character_line & 0x80 >> $loop_column) == 0) {
                            imagesetpixel($font_inverted, ($loop * 8) + $loop_column, $adf_font_size_y, $colors[0]);
                        }
                    }
                }
            }
            for ($loop = 1; $loop < 16; $loop++) {
                imagefilledrectangle($font, 0, $loop * 16, 2048, ($loop * 16) + 16, $colors[$loop]);
            }
            imagefilledrectangle($font, 0, 0, 2048, 15, $colors[16]);
            for ($loop = 0; $loop < 16; $loop++) {
                imagecopy($font, $font_inverted, 0, $loop * 16, 0, 0, 2048, 16);
            }
            imagecolortransparent($font, $colors[0]);
            $input_file_size = $this->getInputFileSize();
            // ALLOCATE IMAGE BUFFER MEMORY
            if (!$adf = imagecreate(640, ((($input_file_size - 192 - 4096 - 1) / 2) / 80) * 16)) {
                throw new Exception("Can't allocate buffer image memory");
            }
            imagecolorallocate($adf, 0, 0, 0);
            // PROCESS ADF
            $loop       = 192 + 4096 + 1;
            $position_x = 0;
            $position_y = 0;
            while ($loop < $input_file_size) {
                if ($position_x == 80) {
                    $position_x = 0;
                    $position_y++;
                }
                $character        = ord($this->content[$loop]);
                $attribute        = ord($this->content[$loop + 1]);
                $color_background = ($attribute & 240) >> 4;
                $color_foreground = $attribute & 15;
                imagecopy($adf, $background, $position_x * 8, $position_y * 16, $color_background * 8, 0, 8, 16);
                imagecopy($adf, $this->font->content, $position_x * 8, $position_y * 16, $character * 8, $color_foreground * 16, 8, 16);
                $position_x++;
                $loop += 2;
            }
            // CREATE OUTPUT FILE
            if ($this->thumbnail) {
                $position_y_max     = (($input_file_size - 192 - 4096 - 1) / 2) / 80;
                $this->font->height = 16;
                return $this->thumbnail(source: $adf, columns: $this->columns, position_y_max: $position_y_max);
            }
            return $this->returnImage(image: $adf);
        }
    }
}