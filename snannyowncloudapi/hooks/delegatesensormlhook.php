<?php


namespace OCA\SnannyOwncloudApi\Hooks;


use OCA\SnannyOwncloudApi\Db\System;
use OCA\SnannyOwncloudApi\Db\SystemAncestor;
use OCA\SnannyOwncloudApi\Db\SystemAncestorsMapper;
use OCA\SnannyOwncloudApi\Db\SystemMapper;

class DelegateSensorMLHook
{
    private $systemMapper;
    private $systemAncestorsMapper;

    public function __construct(SystemMapper $systemMapper, SystemAncestorsMapper $systemAncestorsMapper)
    {
        $this->systemMapper = $systemMapper;
        $this->systemAncestorsMapper = $systemAncestorsMapper;
    }

    public function onUpdateOrCreate($node)
    {
        $xml = new \SimpleXMLElement($node->getContent());

        $sml = $xml->children(SML_NAMESPACE);
        if ($sml != null) {
            $uuid = $sml->identification->IdentifierList->identifier->Term->value;

            $gml = $xml->children(GML_NAMESPACE);
            $name = trim($gml->name);
            $desc = trim($gml->description);
            $smlComponents = $sml->components;
            $this->systemAncestorsMapper->deleteChildren($uuid);
            if ($smlComponents != null) {
                $arr = $smlComponents->ComponentList;
                foreach ($arr->component as $component) {
                    $systemAncestor = new SystemAncestor();
                    $systemAncestor->setParentUuid($uuid);
                    $systemAncestor->setParentName($name);
                    $systemAncestor->setComponentName(trim($component->attributes()->name));
                    $systemAncestor->setStatus(true);
                    $childRef = $component->attributes(XLINK_NAMESPACE)->href;
                    $systemAncestor->setChildUuid(basename($childRef, ".xml"));
                    $this->systemAncestorsMapper->insert($systemAncestor);
                }
            }

            $system = $this->systemMapper->getByIdOrUuid($node->getId(), $uuid);
            $insert = false;
            if ($system == null) {
                $system = new System();
                $insert = true;
            }

            $system->setUuid($uuid);
            $system->setName($name);
            $system->setDescription($desc);
            $system->setFileId($node->getId());
            $system->setStatus(true);
            if ($insert === true) {
                $this->systemMapper->insert($system);
            } else {
                $this->systemMapper->update($system);
            }
        }
    }

    public function onDelete($node)
    {
        $system = $this->systemMapper->getByFileId($node->getId());
        if ($system != null) {
            $system->setStatus(false);
            $this->systemMapper->update($system);
            $this->systemAncestorsMapper->logicalDeleteChildren($system->getUuid());
        }
    }

}