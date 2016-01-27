<?php

namespace OCA\SnannyOwncloudApi\Hooks;

use OCA\SnannyOwncloudApi\Db\DBUtil;
use OCA\SnannyOwncloudApi\Db\ObservationModel;
use OCA\SnannyOwncloudApi\Db\ObservationModelMapper;
use OCA\SnannyOwncloudApi\Parser\OMParser;

const SOS_NAMESPACE = "http://www.opengis.net/sos/2.0";
const OM_NAMESPACE = "http://www.opengis.net/om/2.0";
const XLINK_NAMESPACE = "http://www.w3.org/1999/xlink";
const GML_NAMESPACE = "http://www.opengis.net/gml/3.2";

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
        $obs = DBUtil::executeQuery('SELECT * FROM *PREFIX*snanny_observation_model WHERE file_id = ?', array($nodeId));
        while ($row = $obs->fetch()) {
            if ($row != null) {
                $shared = 1;
                $sharedTime = time();
                if ($shareType == -1) {
                    //When unshare we check if there is still an existing share
                    $result = DBUtil::executeQuery('SELECT COUNT(*) as c FROM *PREFIX*share WHERE file_source = ?', array($nodeId));
                    $value = 0;
                    while ($row = $result->fetch()) {
                        $value = $row['c'];
                    }
                    //If there is no share then we passed the status to unshared
                    if ($value == 0) {
                        $shared = 0;
                    }
                }
                //DBUpdate
                DBUtil::executeQuery("UPDATE *PREFIX*snanny_observation_model SET shared = $shared, share_updated_time = $sharedTime WHERE file_id=?", array($nodeId));
            }
        }
    }

    public static function onUnshare($params)
    {
        self::updateShare($params['itemSource'], -1);
    }

    public function onUpdateOrCreateFromNode($node)
    {
        return $this->onUpdateOrCreate($node->getId(), $node->getContent());

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

    public function onDelete($node)
    {
        $observation = $this->omMapper->getByFileId($node->getId());
        if ($observation != null) {
            $observation->setStatus(false);
            $observation->setTimestamp(time());
            $this->omMapper->update($observation);
        }
    }


}