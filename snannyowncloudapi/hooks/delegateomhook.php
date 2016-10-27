<?php

namespace OCA\SnannyOwncloudApi\Hooks;

use OCA\SnannyOwncloudApi\Db\DBUtil;
use OCA\SnannyOwncloudApi\Db\ObservationModel;
use OCA\SnannyOwncloudApi\Db\ObservationModelMapper;
use OCA\SnannyOwncloudApi\Parser\OMParser;


class DelegateOmHook
{
    private $omMapper;

    public function __construct(ObservationModelMapper $omMapper)
    {
        $this->omMapper = $omMapper;
    }

    public static function onShare($params)
    {
        self::updateShare($params['itemSource'], $params['shareType']);
    }

    public static function updateShare($nodeId, $shareType)
    {
        //Get OMFileIds from file || folder 
        $pathRequest = DBUtil::executeQuery('SELECT path FROM *PREFIX*filecache WHERE fileid=?',array($nodeId));
        $path = "files";
        while ($row = $pathRequest->fetch()) {
            $path = $row['path'];
        }

        //else 
        $obs = DBUtil::executeQuery('SELECT * FROM *PREFIX*snanny_observation_model '.
            'WHERE file_id IN('.
                'SELECT fileid FROM *PREFIX*filecache WHERE path LIKE ? AND path LIKE "%xml") '.
            'OR file_id = ?'
                 , array($path.'/%', $nodeId));
        // End

        while ($row = $obs->fetch()) {
            if ($row != null) {
                $fileId = $row['file_id'];
                $sharedTime = time();
                DBUtil::executeQuery("UPDATE *PREFIX*snanny_observation_model SET share_updated_time = $sharedTime WHERE file_id=?", array($fileId));
            }
        }
    }

    public static function onUnshare($params)
    {
        self::updateShare($params['itemSource'], -1);
    }

    public function onUpdateOrCreate($fileId, $content, $pharPath = null)
    {
        $parsed = OMParser::parse($content);
        $observation = $this->omMapper->getByUuid($parsed['uuid']);
        $insert = false;
        if ($observation == null) {
            $observation = new ObservationModel();
            $insert = true;
        }

        $observation->setUuid($parsed['uuid']);
        $observation->setName($parsed['name']);
        $observation->setDescription($parsed['description']);
        $observation->setFileId($fileId);
        $observation->setStatus(true);
        $observation->setSystemUuid($parsed['system-uuid']);
        $observation->setResultFile($parsed['result-file']);
        $observation->setTimestamp(time());
        $observation->setPharPath($pharPath);

        if ($insert === true) {
            $this->omMapper->insert($observation);
        } else {
            $this->omMapper->update($observation);
        }
    }

    public function getDirectOM($systemuuid) {
        return $this->omMapper->getBySystemUuid($systemuuid);
    }

    public function getOMByData($filePath, $fileCachePath) {
        return $this->omMapper->getByDataFileName($filePath, $fileCachePath);
    }

    public function updateOM($observation) {
        $this->omMapper->updateOMTime($observation->getId());
    }

    public function onDelete($node)
    {
        $this->omMapper->logicalDelete($node->getId());
    }

}