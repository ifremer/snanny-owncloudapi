<?php

namespace OCA\SnannyOwncloudApi\Util;

use OCA\SnannyOwncloudApi\Tar\TarParser;
use OCP\Files;

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

    /**
     * Get the mime type of the compressed file
     * @param $file
     * @return string a mime type corresponding to the archive content. "" if not detected
     */
    public static function getArchiveContentMime($file){
        $filePath = $file['urn'];
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);

        $fileName = '';
        if($extension === 'bz2' || $extension === 'gz'){
            $fileName = pathinfo($filePath, PATHINFO_FILENAME);
        } else if($extension === 'zip'){
            $zip = new \ZipArchive();
            if($zip->open($filePath)){
                $fileName = $zip->getNameIndex(0);
            }
        }

        return Files::getMimeType($fileName);
    }
}