<?php

namespace OCA\SnannyOwncloudApi\Util;

/**
 * Created by PhpStorm.
 * User: athorel
 * Date: 18/01/2016
 * Time: 17:53
 */
class FileUtils
{
    public static function endsWith($haystack, $needle)
    {
        // search forward starting from end minus needle length characters
        return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== FALSE);
    }
}