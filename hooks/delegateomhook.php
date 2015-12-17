<?php

namespace OCA\SnannyOwncloudApi\Hooks;

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

    public function onUpdateOrCreate($node)
    {
        $parsed = OMParser::parse($node->getContent());
        $observation = $this->omMapper->getByIdOrUuid($node->getId(), $parsed['uuid']);
        $insert = false;
        if ($observation == null) {
            $observation = new ObservationModel();
            $insert = true;
        }

        $observation->setUuid($parsed['uuid']);
        $observation->setName($parsed['name']);
        $observation->setDescription($parsed['description']);
        $observation->setFileId($node->getId());
        $observation->setStatus(true);
        $observation->setSystemUuid($parsed['system-uuid']);
        $observation->setResultFile($parsed['result-file']);

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
            $this->omMapper->update($observation);
        }
    }


}