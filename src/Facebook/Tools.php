<?php

namespace Mvo\ContaoFacebookImport\Facebook;

class Tools
{
    /**
     * @param string $str
     *
     * @return string
     */
    public static function encodeText(string $str)
    {
        return utf8_encode($str);
    }

    /**
     * @param string $str
     */
    public static function formatText(string $str, int $maxWords = 0)
    {
        $str = self::decode($str);
        $str = self::replaceUrls($str);
        if($maxWords > 0) {
            $str = self::shortenText($str, $maxWords);
        }
        return self::formatWhitespaces($str);
    }

    /**
     * @param string $str
     *
     * @return string
     */
    private static function decode(string $str)
    {
        return utf8_decode($str);
    }

    /**
     * @param string $str
     *
     * @return mixed
     */
    private static function replaceUrls(string $str)
    {
        // surround urls with <a> tags
        return preg_replace("#http://([\S]+?)#Uis", '<a rel="nofollow" href="http://\\1">\\1</a>', $str);
    }

    /**
     * @param string $str
     *
     * @return mixed
     */
    private static function formatWhitespaces(string $str)
    {
        return nl2br_html5(str_replace('  ', '&nbsp;&nbsp;', $str));
    }


    /**
     * @param string $str
     * @param int    $maxWords
     *
     * @return mixed
     */
    private static function shortenText(string $str, int $maxWords)
    {
        $words = explode(' ', $str);
        $initialWordCount = count($words);

        // slice it
        $words = array_slice($words, 0, $maxWords);
        if(count($words) == 0) {
            return '';
        }

        // remove last , . -
        $words[count($words) - 1] = str_replace([',', '.','-'], '', $words[count($words) - 1]);
        $str = implode($words, ' ');

        return ($initialWordCount > count($words)) ? sprintf('%s&hellip;', $str) : $str;
    }
}