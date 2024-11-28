<?php

namespace Badahead\AnsiLover\Core {

    use Exception;
    use GdImage;

    error_reporting(E_ALL ^ E_NOTICE);

    class Main
    {
        private const int             THUMBNAILS_SIZE   = 1;
        private const  int            THUMBNAILS_HEIGHT = 0;
        protected ?array      $sauce      = null;
        protected ?Font       $font       = null;
        protected ?Background $background = null;
        protected int         $columns    = 80;

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
            $this->sauce = $sauce['ID'] === 'SAUCE' ? $sauce : null;
        }

        final protected function getInputFileSize(): int {
            if ($this->sauce !== null) {
                return $this->sauce['FileSize'];
            }
            return strlen($this->content);
        }

        final protected function thumbnail(GdImage $source, int $columns, int $position_y_max): string {
            $columns = min($columns, 80);
            $size    = self::THUMBNAILS_SIZE <= 0 ? 1 : self::THUMBNAILS_SIZE;
            if (self::THUMBNAILS_HEIGHT === 0) {
                $height        = $position_y_max * ($this->font->height / 8);
                $height_source = $position_y_max * $this->font->height;
            }
            else {
                $height        = min($position_y_max * ($this->font->height / 8), self::THUMBNAILS_HEIGHT);
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
