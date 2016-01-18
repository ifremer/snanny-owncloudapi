<?php
/**
 * Created by PhpStorm.
 * User: athorel
 * Date: 11/01/2016
 * Time: 17:47
 */

namespace OCA\SnannyOwncloudApi\Parser;

const GML_NAMESPACE = "http://www.opengis.net/gml/3.2";
const SML_NAMESPACE = "http://www.opengis.net/sensorml/2.0";
const XLINK_NAMESPACE = "http://www.w3.org/1999/xlink";

class SensorMLParser
{
    public static function parse($content)
    {
        $arr = array();
        $xml = new \SimpleXMLElement($content);

        $sml = $xml->children(SML_NAMESPACE);
        if ($sml != null) {
            $arr['uuid'] = $sml->identification->IdentifierList->identifier->Term->value;

            $gml = $xml->children(GML_NAMESPACE);
            $arr['name'] = trim($gml->name);
            $arr['desc'] = trim($gml->description);
            $smlComponents = $sml->components;

            if ($smlComponents != null) {
                $comps = $smlComponents->ComponentList;
                $arr['components'] = array();
                foreach ($comps->component as $component) {
                    $childRef = $component->attributes(XLINK_NAMESPACE)->href;
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
        $sml = $xml->children(SML_NAMESPACE);
        if ($sml != null) {
            return true;
        }
        return false;
    }
}