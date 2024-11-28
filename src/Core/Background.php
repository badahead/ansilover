<?php
declare(strict_types=1);

namespace Badahead\AnsiLover\Core {

    use Exception;
    use GdImage;

    class Background
    {
        public GdImage $content;

        /**
         * @throws Exception
         */
        public function __construct() {
            if (!$background = imagecreatefrompng(__DIR__ . '/../../backgrounds/default.png')) {
                throw new Exception("Can't open background image");
            }
            $this->content = $background;
        }
    }
}
