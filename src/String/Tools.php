<?php

declare(strict_types=1);

/*
 * Contao Facebook Import Bundle for Contao Open Source CMS
 *
 * @copyright  Copyright (c) 2017-2018, Moritz Vondano
 * @license    MIT
 * @link       https://github.com/m-vo/contao-facebook-import
 *
 * @author     Moritz Vondano
 */

namespace Mvo\ContaoFacebookImport\String;

class Tools
{
    /**
     * @param string $str
     * @param int    $maxWords
     *
     * @return mixed
     */
    public static function formatText(string $str, int $maxWords = 0)
    {
        $str = utf8_decode($str);
        $str = self::replaceUrls($str);
        if ($maxWords > 0) {
            $str = self::shortenText($str, $maxWords);
        }
        return self::formatWhitespaces($str);
    }

    /**
     * @param string $str
     *
     * @return mixed
     */
    private static function replaceUrls(string $str)
    {
        // surround urls with <a> tags
        return \preg_replace("#(http|https):\/\/([\S]+?)#Uis", '<a rel="nofollow" href="\\1://\\2">\\2</a>', $str);
    }

    /**
     * @param string $str
     *
     * @return mixed
     */
    private static function formatWhitespaces(string $str)
    {
        return \nl2br_html5(str_replace('  ', '&nbsp;&nbsp;', $str));
    }

    /**
     * @param string $str
     * @param int    $maxWords
     *
     * @return mixed
     */
    private static function shortenText(string $str, int $maxWords)
    {
        $words            = explode(' ', $str);
        $initialWordCount = \count($words);

        // slice it
        $words = \array_slice($words, 0, $maxWords);
        if (0 === \count($words)) {
            return '';
        }

        // remove last , . -
        $words[\count($words) - 1] = \str_replace([',', '.', '-'], '', $words[\count($words) - 1]);
        $str                       = implode($words, ' ');

        return ($initialWordCount > \count($words)) ? sprintf('%s&hellip;', $str) : $str;
    }
}