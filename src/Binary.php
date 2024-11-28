<?php
declare(strict_types=1);

namespace Badahead\AnsiLover {

    use Badahead\AnsiLover\Core\Main;
    use Exception;

    class Binary extends Main
    {
        private const array BINARY_COLORS = [0  => 0,
                                             1  => 4,
                                             2  => 2,
                                             3  => 6,
                                             4  => 1,
                                             5  => 5,
                                             6  => 3,
                                             7  => 7,
                                             8  => 8,
                                             9  => 12,
                                             10 => 10,
                                             11 => 14,
                                             12 => 9,
                                             13 => 13,
                                             14 => 11,
                                             15 => 15,];

        /**
         * @throws Exception
         */
        public function __construct(string $content, ?string $fontName = null, int $columns = 160, private ?int $bits = null, private readonly bool $thumbnail = false, private readonly bool $icecolors = false) {
            parent::__construct(fontName: $fontName, content: $content);
            if ($columns === 0) {
                $this->columns = 160;
            }
            else {
                $this->columns = $columns;
            }
        }

        /**
         * @throws Exception
         */
        final public function render(): string {
            if ($this->bits !== 8 && $this->bits !== 9) {
                $this->bits = 8;
            }
            $input_file_size = $this->getInputFileSize();
            // LOAD FONT AND ALLOCATE IMAGE BUFFER MEMORY
            imagecolortransparent($this->font->content, 20);
            if (!$binary = imagecreate($this->columns * $this->bits, (($input_file_size / 2) / $this->columns) * $this->font->height)) {
                throw new Exception("Can't allocate buffer image memory");
            }
            imagecolorallocate($binary, 0, 0, 0);
            // PROCESS BINARY
            $position_x = 0;
            $position_y = 0;
            $loop       = 0;
            while ($loop < $input_file_size) {
                if ($position_x === $this->columns) {
                    $position_x = 0;
                    $position_y++;
                }
                $character        = ord($this->content[$loop]);
                $attribute        = ord($this->content[$loop + 1]);
                $color_background = self::BINARY_COLORS[($attribute & 240) >> 4];
                $color_foreground = self::BINARY_COLORS[$attribute & 15];
                if ($color_background > 8 && $this->icecolors === false) {
                    $color_background -= 8;
                }
                imagecopy($binary, $this->background->content, $position_x * $this->bits, $position_y * $this->font->height, $color_background * 9, 0, $this->bits, $this->font->height);
                imagecopy($binary, $this->font->content, $position_x * $this->bits, $position_y * $this->font->height, $character * $this->font->width, $color_foreground * $this->font->height, $this->bits, $this->font->height);
                $position_x++;
                $loop += 2;
            }
            // CREATE OUTPUT FILE                                                        
            if ($this->thumbnail) {
                $position_y_max = ($input_file_size / 2) / $this->columns;
                return $this->thumbnail($binary, $this->columns, $position_y_max);
            }
            return $this->returnImage(image: $binary);
        }
    }
}