<?php

namespace OCA\SnannyOwncloudApi\Parser;


use OCA\SnannyOwncloudApi\Db\ObservationModel;

const SOS_NAMESPACE = "http://www.opengis.net/sos/2.0";
const OM_NAMESPACE = "http://www.opengis.net/om/2.0";
const XLINK_NAMESPACE = "http://www.w3.org/1999/xlink";
const GML_NAMESPACE = "http://www.opengis.net/gml/3.2";

class OMParser
{

    public static function parse($content)
    {
        $arr = array();
        $xml = new \SimpleXMLElement($content);

        $sos = $xml->children(SOS_NAMESPACE);
        if ($sos != null) {
            $obs = $sos->observation;
            $omObs = $obs->children(OM_NAMESPACE)->OM_Observation;


            $gml = $omObs->children(GML_NAMESPACE);

            $desc = $gml->description;
            $name = $gml->name;
            $uuidI = null;
            foreach ($gml->identifier as $identifier) {
                if ($identifier->attributes()->codeSpace == 'uuid') {
                    $uuidI = (string)$identifier;
                }
            }

            $observation = new ObservationModel();
            $arr['uuid'] = $uuidI;
            $arr['name'] = trim($name);
            $arr['description'] = trim($desc);

            $childRef = $omObs->procedure->attributes(XLINK_NAMESPACE)->href;
            $arr['system-uuid'] = basename($childRef, ".xml");

            $childRef = $omObs->result->attributes(XLINK_NAMESPACE)->href;
            $arr['result-file'] = basename($childRef, "");

        }
        return $arr;
    }
}