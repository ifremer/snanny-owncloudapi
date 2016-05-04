<?php
/**
 * Created by PhpStorm.
 * User: athorel
 * Date: 11/01/2016
 * Time: 17:47
 */

namespace OCA\SnannyOwncloudApi\Parser;



class SensorMLParser
{
    public static function parse($content)
    {
        $arr = array();
        $xml = new \SimpleXMLElement($content);

        $sml = $xml->children(XMLNamespace::SML_NAMESPACE);
        if ($sml != null) {
            $arr['uuid'] = $sml->identification->IdentifierList->identifier->Term->value;

            $gml = $xml->children(XMLNamespace::GML_NAMESPACE);

            $timePeriod = $sml->validTime->children(XMLNamespace::GML_NAMESPACE)->TimePeriod;
            $arr['startDate'] = SensorMLParser::parseSmlDate($timePeriod->beginPosition, true);
            $arr['endDate'] = SensorMLParser::parseSmlDate($timePeriod->endPosition, false);

            $arr['name'] = trim($gml->name);
            $arr['desc'] = trim($gml->description);
            $smlComponents = $sml->components;

            if ($smlComponents != null) {
                $comps = $smlComponents->ComponentList;
                $arr['components'] = array();
                foreach ($comps->component as $component) {
                    $childRef = $component->attributes(XMLNamespace::XLINK_NAMESPACE)->href;
                    $arr['components'][] = array(
                        'name'=>trim($component->attributes()->name),
                        'ref'=> $childRef,
                        'uuid'=>basename($childRef, ".xml"));
                }
            }
        }
        return $arr;
    }

    public static function accept($xml){
        $sml = $xml->children(XMLNamespace::SML_NAMESPACE);
        if ($sml != null) {
            return true;
        }
        return false;
    }

    public static function parseSmlDate($smlDate, $startDate) {
        $resultDate = null;
        if (preg_match('"^[\d]{4}$"', $smlDate)) {
            $dateComplement = null;
            if($startDate) {
                $dateComplement = '-01-01T00:00:00Z';
            } else {
                $dateComplement = '-12-31T00:00:00Z';
            }
            $resultDate = new \DateTime($smlDate . $dateComplement);
        } else if (preg_match('"^[\d]{4}-[\d]{2}-[\d]{2}$"', $smlDate)) {
            $resultDate = new \DateTime($smlDate . 'T00:00:00Z');
        } else if (preg_match('"^[\d]{4}-[\d]{2}-[\d]{2}T[\d]{2}:[\d]{2}:[\d]{2}[Z]{0,1}$"', $smlDate)) {
            $resultDate = new \DateTime($smlDate);
        }
        return $resultDate == null ? $resultDate : $resultDate->getTimestamp();
    }

}