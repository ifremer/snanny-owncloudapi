<?php

namespace OCA\SnannyOwncloudApi\Tar;
/**
 * Created by PhpStorm.
 * User: athorel
 * Date: 12/01/2016
 * Time: 16:41
 */
class TarParser
{
    const PHAR_PROTOCOLE = 'phar://';

    public static function parse($urn){
        //Get tar file and recurse items
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(self::PHAR_PROTOCOLE .$urn),
            \RecursiveIteratorIterator::CHILD_FIRST);
        $arr = array();
        foreach ($files as $file) {
            $arr[] = array('path'=>$file->getPathname(), 'filename'=>$file->getFileName(), 'file'=>is_file($file->getPathname()));
        }
        return $arr;
    }
}