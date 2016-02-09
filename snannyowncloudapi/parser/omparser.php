<?php

namespace OCA\SnannyOwncloudApi\Parser;


use OCA\SnannyOwncloudApi\Db\ObservationModel;


class OMParser
{

    public static function parse($content)
    {
        $arr = array();
        $xml = new \SimpleXMLElement($content);

        $sos = $xml->children(XMLNamespace::SOS_NAMESPACE);
        if ($sos != null) {
            $obs = $sos->observation;
            $omObs = $obs->children(XMLNamespace::OM_NAMESPACE)->OM_Observation;


            $gml = $omObs->children(XMLNamespace::GML_NAMESPACE);

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

            $childRef = $omObs->procedure->attributes(XMLNamespace::XLINK_NAMESPACE)->href;
            $arr['system-uuid'] = basename($childRef, ".xml");

            $childRef = $omObs->result->attributes(XMLNamespace::XLINK_NAMESPACE)->href;
            $arr['result-file'] = basename($childRef, "");

        }
        return $arr;
    }

    public static function accept($xml){
        $sos = $xml->children(XMLNamespace::SOS_NAMESPACE);
        if ($sos != null && $sos->observation) {
            return true;
        }
        return false;
    }
}