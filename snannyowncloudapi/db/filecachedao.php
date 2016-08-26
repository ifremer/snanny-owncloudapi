<?php
// db/activitydao.php

/**
 * ownCloud - snannyowncloudapi
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Arnaud THOREL <athorel@asi.fr>
 * @copyright Arnaud THOREL 2015
 */

namespace OCA\SnannyOwncloudApi\Db;

use OC\Files\Cache\Storage;
use OCA\SnannyOwncloudApi\Tar\TarParser;


class FileCacheDao
{

    /**
     * Get complete url of fileId
     * @param $fileId id of file
     * @return string urn of file
     */
    public static function getFullUrl($fileId)
    {
        $cacheInfo = self::getCacheInfo($fileId);
        $result = self::getFileInfo($cacheInfo['storage'], $cacheInfo['path']);
        return $result['urn'];

    }

    public static function getCacheInfo($id)
    {
        $sql = 'SELECT * FROM *PREFIX*filecache WHERE fileid = :fileid';
        $result = DBUtil::executeQuery($sql, array(':fileid' => $id));

        if ($row = $result->fetch()) {
            return $row;
        }
        return null;
    }

    public static function getFileInfo($numericId, $path, $pharPath = null)
    {
        $storage = Storage::getStorageId($numericId);

        $config = \OC::$server->getConfig();
        $home = $config->getSystemValue('datadirectory', \OC::$SERVERROOT . '/data/');
        $user = explode("::", $storage)[1];
        $urn = $home . '/' . $user . '/' . $path;
        if ($pharPath != null) {
            $urn = TarParser::PHAR_PROTOCOLE . $urn . $pharPath;
        }

        return array('urn' => $urn, 'user' => $user);
    }

    public static function getContentByFileId($id)
    {
        $fileCacheInfo = self::getCacheInfo($id);
        return self::getContent($fileCacheInfo['storage'], $fileCacheInfo['path']);
    }

    public static function getContent($numericId, $path)
    {
        $fileInfo = FileCacheDao::getFileInfo($numericId, $path);
        return FileCacheDao::getContentByUrn($fileInfo['urn']);
    }

    public static function getContentByUrn($urn, $pharPath = null)
    {
        if ($pharPath) {
            return TarParser::getContent($urn, $pharPath);
        }
        return file_get_contents($urn);
    }

    public static function createFileNode($node, $fileName, $size){

        $filePath = dirname($node['path']) .'/'. $fileName;
        $item = array(
            'storage'=>$node['storage'],
            'path'=>$filePath,
            'path_hash'=>md5($filePath),
            'parent'=>$node['parent'],
            'name'=>$fileName,
            'mimetype' => \OC::$server->getMimeTypeLoader()->getId('application/xml'),
            'mimepart' => \OC::$server->getMimeTypeLoader()->getId('application'),
            'size'=>$size,
            'mtime'=>$node['mtime'],
            'storage_mtime'=>$node['storage_mtime'],
            'encrypted'=>0,
            'unencrypted_size'=>0,
            'etag'=>md5($fileName),
            'permissions'=>$node['permissions']
        );
        DBUtil::insert('*PREFIX*filecache', $item);

        $result = DBUtil::executeQuery('SELECT * FROM *PREFIX*filecache WHERE storage = :storage AND path_hash = :path_hash',
            array(':storage'=>$item['storage'],
                ':path_hash'=>$item['path_hash']));
        while($row = $result->fetch()){
            return $row;
        }
        return null;

    }

    public static function getFileCacheByPath($path) {
        $result = DBUtil::executeQuery('SELECT * FROM *PREFIX*filecache WHERE path = :path',
            array(':path'=>$path));
        while($row = $result->fetch()){
            return $row;
        }
        return null;
    }

    public static function getFileCacheByFileId($fileid) {
        $result = DBUtil::executeQuery('SELECT * FROM *PREFIX*filecache WHERE fileid = :fileid',
            array(':fileid'=>$fileid));
        while($row = $result->fetch()){
            return $row;
        }
        return null;
    }
}