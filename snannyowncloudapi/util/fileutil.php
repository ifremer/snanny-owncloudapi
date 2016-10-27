<?php

namespace OCA\SnannyOwncloudApi\Util;

use OCA\SnannyOwncloudApi\Tar\TarParser;

/**
 * Created by PhpStorm.
 * User: athorel
 * Date: 18/01/2016
 * Time: 17:53
 */
class FileUtil
{
    const PHAR_PREFIX = 'phar:';

    /**
     * Check if string endswith the needle
     * @param $haystack string to search in
     * @param $needle needle to search
     * @return bool true if haystack ends with needle
     */
    public static function endsWith($haystack, $needles)
    {
        // search forward starting from end minus needle length characters
        $endWith = false;
        foreach ($needles as $needle) {
            $endWith = $endWith || ($needle === '' || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== FALSE));
        }
        return $endWith;
    }

    /**
     * normalize a path
     * @param $path file path
     * @param $pharPath pharPath
     * @return string normalized path
     */
    public static function normalizePath($path, $pharPath)
    {
        if ($pharPath !== '') {
            return TarParser::PHAR_PROTOCOLE . $path . $pharPath;
        }
        return $path;
    }
}