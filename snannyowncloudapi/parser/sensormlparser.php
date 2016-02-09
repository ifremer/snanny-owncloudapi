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
}