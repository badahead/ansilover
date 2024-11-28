<?php

namespace Badahead\AnsiLover\Core {

    use Exception;
    use GdImage;

    error_reporting(E_ALL ^ E_NOTICE);

    class Main
    {
        public string         $ANSILOVE_LOG_FILE    = "ansilove.log";
        public array          $PCBOARD_STRIP_CODES  = ['@POFF@',
                                                       '@WAIT@'];
        public array          $DIZ_EXTENSIONS       = ['.diz',
                                                       '.ion'];
        public bool           $SUBSTITUTE_BREAK     = true;
        public bool           $WRAP_COLUMN_80       = true;
        public array          $CED_BACKGROUND_COLOR = [170,
                                                       170,
                                                       170];
        public array          $CED_FOREGROUND_COLOR = [0,
                                                       0,
                                                       0];
        public array          $WORKBENCH_COLOR_0    = [170,
                                                       170,
                                                       170];
        public array          $WORKBENCH_COLOR_1    = [0,
                                                       0,
                                                       255];
        public array          $WORKBENCH_COLOR_2    = [255,
                                                       255,
                                                       255];
        public array          $WORKBENCH_COLOR_3    = [0,
                                                       255,
                                                       255];
        public array          $WORKBENCH_COLOR_4    = [0,
                                                       0,
                                                       0];
        public array          $WORKBENCH_COLOR_5    = [255,
                                                       0,
                                                       255];
        public array          $WORKBENCH_COLOR_6    = [102,
                                                       136,
                                                       187];
        public array          $WORKBENCH_COLOR_7    = [255,
                                                       255,
                                                       255];
        public int            $THUMBNAILS_SIZE      = 1;
        public int            $THUMBNAILS_HEIGHT    = 0;
        public string         $THUMBNAILS_TAG       = "-thumbnail";
        public bool           $SPLIT                = false;
        public int            $SPLIT_HEIGHT         = 4096;
        public string         $SPLIT_SEPARATOR      = ".";
        protected ?string     $filename             = null;
        protected ?array      $sauce                = null;
        protected ?Font       $font                 = null;
        protected ?Background $background           = null;
        protected int         $columns              = 80;

        public function __construct(?string $fontName, public string $content) {
            if (!extension_loaded('gd')) {
                throw new Exception('GD library required');
            }
            $this->background = new Background();
            $this->font       = Font::get(fontName: $fontName);
            $this->loadSauce();
        }

        private function loadSauce(): void {
            if (strlen($this->content) >= 128) {
                $sauce           = ['ID'      => substr($this->content, 0, 5),
                                    'Version' => substr($this->content, 5, 2),
                                    'Title'   => substr($this->content, 7, 35),
                                    'Author'  => substr($this->content, 42, 20),
                                    'Group'   => substr($this->content, 62, 20),
                                    'Date'    => substr($this->content, 82, 8),];
                $sauce           = array_merge($sauce, unpack('lFileSize/CDataType/CFileType/v4TInfo/CComments/CFlags', substr($this->content, 90, 16)));
                $sauce['Filler'] = substr($this->content, 106, 22);
            }
            if ($sauce['ID'] === 'SAUCE') {
                $this->sauce = $sauce;
            }
            else {
                $this->sauce = null;
            }
        }

        final protected function getInputFileSize(): int {
            if ($this->sauce !== null) {
                return $this->sauce['FileSize'];
            }
            return strlen($this->content);
        }

        final protected function thumbnail(GdImage $source, int $columns, int $position_y_max): string {
            $columns = min($columns, 80);
            if ($this->THUMBNAILS_SIZE <= 0) {
                $size = 1;
            }
            else {
                $size = $this->THUMBNAILS_SIZE;
            }
            if ($this->THUMBNAILS_HEIGHT === 0) {
                $height        = $position_y_max * ($this->font->height / 8);
                $height_source = $position_y_max * $this->font->height;
            }
            else {
                $height        = min($position_y_max * ($this->font->height / 8), $this->THUMBNAILS_HEIGHT);
                $height_source = $height * 8;
            }
            $width_source = $columns * 8;
            $height       *= $size;
            $columns      *= $size;
            if (!$thumbnail = imagecreatetruecolor($columns, $height)) {
                throw new Exception("Can't allocate buffer image memory");
            }
            imagecopyresampled($thumbnail, $source, 0, 0, 0, 0, $columns, $height, $width_source, $height_source);
            return $this->returnImage(image: $thumbnail);
        }

        final protected function returnImage(GdImage $image): string {
            ob_start();
            imagepng($image);
            $return = ob_get_clean();
            if ($return === false) {
                throw new Exception("Can't allocate image memory");
            }
            return $return;
        }
    }
}