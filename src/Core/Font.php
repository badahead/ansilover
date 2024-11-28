<?php
declare(strict_types=1);

namespace Badahead\AnsiLover\Core {

    use Exception;
    use GdImage;
    use function imagecreatefrompng;

    class Font
    {
        private const array FONT_80x25           = ['is_amiga' => false,
                                                    'file'     => 'pc_80x25.png',
                                                    'width'    => 9,
                                                    'height'   => 16];
        private const array FONT_80x50           = ['is_amiga' => false,
                                                    'file'     => 'pc_80x50.png',
                                                    'width'    => 9,
                                                    'height'   => 8];
        private const array FONT_armenian        = ['is_amiga' => false,
                                                    'file'     => 'pc_armenian.png',
                                                    'width'    => 9,
                                                    'height'   => 16];
        private const array FONT_baltic          = ['is_amiga' => false,
                                                    'file'     => 'pc_baltic.png',
                                                    'width'    => 9,
                                                    'height'   => 16];
        private const array FONT_cyrillic        = ['is_amiga' => false,
                                                    'file'     => 'pc_cyrillic.png',
                                                    'width'    => 9,
                                                    'height'   => 16];
        private const array FONT_french_canadian = ['is_amiga' => false,
                                                    'file'     => 'pc_french_canadian.png',
                                                    'width'    => 9,
                                                    'height'   => 16];
        private const array FONT_greek           = ['is_amiga' => false,
                                                    'file'     => 'pc_greek.png',
                                                    'width'    => 9,
                                                    'height'   => 16];
        private const array FONT_greek_869       = ['is_amiga' => false,
                                                    'file'     => 'pc_greek_869.png',
                                                    'width'    => 9,
                                                    'height'   => 16];
        private const array FONT_hebrew          = ['is_amiga' => false,
                                                    'file'     => 'pc_hebrew.png',
                                                    'width'    => 9,
                                                    'height'   => 16];
        private const array FONT_icelandic       = ['is_amiga' => false,
                                                    'file'     => 'pc_icelandic.png',
                                                    'width'    => 9,
                                                    'height'   => 16];
        private const array FONT_latin1          = ['is_amiga' => false,
                                                    'file'     => 'pc_latin1.png',
                                                    'width'    => 9,
                                                    'height'   => 16];
        private const array FONT_latin2          = ['is_amiga' => false,
                                                    'file'     => 'pc_latin2.png',
                                                    'width'    => 9,
                                                    'height'   => 16];
        private const array FONT_nordic          = ['is_amiga' => false,
                                                    'file'     => 'pc_nordic.png',
                                                    'width'    => 9,
                                                    'height'   => 16];
        private const array FONT_persian         = ['is_amiga' => false,
                                                    'file'     => 'pc_persian.png',
                                                    'width'    => 9,
                                                    'height'   => 16];
        private const array FONT_portuguese      = ['is_amiga' => false,
                                                    'file'     => 'pc_portuguese.png',
                                                    'width'    => 9,
                                                    'height'   => 16];
        private const array FONT_russian         = ['is_amiga' => false,
                                                    'file'     => 'pc_russian.png',
                                                    'width'    => 9,
                                                    'height'   => 16];
        private const array FONT_terminus        = ['is_amiga' => false,
                                                    'file'     => 'pc_terminus.png',
                                                    'width'    => 9,
                                                    'height'   => 16];
        private const array FONT_turkish         = ['is_amiga' => false,
                                                    'file'     => 'pc_turkish.png',
                                                    'width'    => 9,
                                                    'height'   => 16];
        private const array FONT_amiga           = ['is_amiga' => true,
                                                    'file'     => 'amiga_topaz_1200.png',
                                                    'width'    => 8,
                                                    'height'   => 16];
        private const array FONT_b_strict        = ['is_amiga' => true,
                                                    'file'     => 'amiga_b-strict.png',
                                                    'width'    => 8,
                                                    'height'   => 8];
        private const array FONT_b_struct        = ['is_amiga' => true,
                                                    'file'     => 'amiga_b-struct.png',
                                                    'width'    => 8,
                                                    'height'   => 8];
        private const array FONT_microknight     = ['is_amiga' => true,
                                                    'file'     => 'amiga_microknight.png',
                                                    'width'    => 8,
                                                    'height'   => 16];
        private const array FONT_microknightplus = ['is_amiga' => true,
                                                    'file'     => 'amiga_microknight+.png',
                                                    'width'    => 8,
                                                    'height'   => 16];
        private const array FONT_mosoul          = ['is_amiga' => true,
                                                    'file'     => 'amiga_mosoul.png',
                                                    'width'    => 8,
                                                    'height'   => 16];
        private const array FONT_pot_noodle      = ['is_amiga' => true,
                                                    'file'     => 'amiga_pot-noodle.png',
                                                    'width'    => 8,
                                                    'height'   => 16];
        private const array FONT_topaz           = ['is_amiga' => true,
                                                    'file'     => 'amiga_topaz_1200.png',
                                                    'width'    => 8,
                                                    'height'   => 16];
        private const array FONT_topazplus       = ['is_amiga' => true,
                                                    'file'     => 'amiga_topaz_1200+.png',
                                                    'width'    => 8,
                                                    'height'   => 16];
        private const array FONT_topaz500        = ['is_amiga' => true,
                                                    'file'     => 'amiga_topaz_500.png',
                                                    'width'    => 8,
                                                    'height'   => 16];
        private const array FONT_topaz500plus    = ['is_amiga' => true,
                                                    'file'     => 'amiga_topaz_500+.png',
                                                    'width'    => 8,
                                                    'height'   => 16];
        public const array  FONTS                = ['80x25'           => self::FONT_80x25,
                                                    '80x50'           => self::FONT_80x50,
                                                    'armenian'        => self::FONT_armenian,
                                                    'baltic'          => self::FONT_baltic,
                                                    'cyrillic'        => self::FONT_cyrillic,
                                                    'french_canadian' => self::FONT_french_canadian,
                                                    'greek'           => self::FONT_greek,
                                                    'greek_869'       => self::FONT_greek_869,
                                                    'hebrew'          => self::FONT_hebrew,
                                                    'icelandic'       => self::FONT_icelandic,
                                                    'latin1'          => self::FONT_latin1,
                                                    'latin2'          => self::FONT_latin2,
                                                    'nordic'          => self::FONT_nordic,
                                                    'persian'         => self::FONT_persian,
                                                    'portuguese'      => self::FONT_portuguese,
                                                    'russian'         => self::FONT_russian,
                                                    'terminus'        => self::FONT_terminus,
                                                    'turkish'         => self::FONT_turkish,
                                                    'amiga'           => self::FONT_amiga,
                                                    'b_strict'        => self::FONT_b_strict,
                                                    'b_struct'        => self::FONT_b_struct,
                                                    'microknight'     => self::FONT_microknight,
                                                    'microknightplus' => self::FONT_microknightplus,
                                                    'mosoul'          => self::FONT_mosoul,
                                                    'pot_noodle'      => self::FONT_pot_noodle,
                                                    'topaz'           => self::FONT_topaz,
                                                    'topazplus'       => self::FONT_topazplus,
                                                    'topaz500'        => self::FONT_topaz500,
                                                    'topaz500plus'    => self::FONT_topaz500plus];
        public bool    $is_amiga = false;
        public string  $file     = '';
        public int     $width    = 0;
        public int     $height   = 0;
        public GdImage $content;

        /**
         *
         * @throws Exception
         */
        public static function get(?string $fontName): self {
            if ($fontName === null || $fontName === '') {
                $fontName = '80x25';
            }
            if (str_contains($fontName, '+')) {
                $fontName = str_replace('+', 'plus', $fontName);
            }
            if (!isset(self::FONTS[$fontName])) {
                throw new Exception('Font entry does not exist: ' . $fontName);
            }
            $font = self::FONTS[$fontName];
            $file = __DIR__ . '/../../fonts/' . $font['file'];
            if (!file_exists($file)) {
                throw new Exception('Font file does not exist: ' . $fontName);
            }
            $result           = new self();
            $result->is_amiga = $font['is_amiga'] ?? false;
            $result->file     = $file;
            $result->width    = $font['width'] ?? 0;
            $result->height   = $font['height'] ?? 0;
            $content          = imagecreatefrompng($file);
            if (!$content instanceof GdImage) {
                throw new Exception('GdImage entry does not exist: ' . $fontName);
            }
            $result->content = $content;
            return $result;
        }
    }
}