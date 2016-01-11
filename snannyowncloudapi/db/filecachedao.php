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


class FileCacheDao
{

    public static function getCacheInfo($id)
    {
        $sql = 'SELECT * FROM *PREFIX*filecache WHERE fileid = :fileid';
        $result = DBUtil::executeQuery($sql, array(':fileid' => $id));

        if ($row = $result->fetch()) {
            return $row;
        }
        return null;
    }

    public static function getFileInfo($numericId, $path)
    {
        $storage = Storage::getStorageId($numericId);
        $home = '/var/www/owncloud/data';
        $user = explode("::", $storage)[1];
        $urn = $home . '/' . $user . '/' . $path;
        return array('urn' => $urn, 'user' => $user);
    }

    public static function getContent($numericId, $path)
    {
        $fileInfo = FileCacheDao::getFileInfo($numericId, $path);
        return FileCacheDao::getContentByUrn($fileInfo['urn']);
    }

    public static function getContentByUrn($urn)
    {
        return file_get_contents($urn);
    }

    public static function getContentByFileId($id){
        $fileCacheInfo = self::getCacheInfo($id);
        return self::getContent($fileCacheInfo['storage'], $fileCacheInfo['path']);
    }
}