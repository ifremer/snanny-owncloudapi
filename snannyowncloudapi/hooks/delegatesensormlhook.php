<?php


namespace OCA\SnannyOwncloudApi\Hooks;


use OCA\SnannyOwncloudApi\Db\System;
use OCA\SnannyOwncloudApi\Db\SystemAncestor;
use OCA\SnannyOwncloudApi\Db\SystemAncestorsMapper;
use OCA\SnannyOwncloudApi\Db\SystemMapper;
use OCA\SnannyOwncloudApi\Parser\SensorMLParser;

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
        $sml = SensorMLParser::parse($node->getContent());
        if($sml['uuid']) {
            $uuid = $sml['uuid'];
            $this->systemAncestorsMapper->deleteChildren($uuid);
            $components = $sml['components'];
            if ($components) {
                //IF data is not unique, there is no link created between ancestors and children
                if($this->ensureUnique($components)) {
                    foreach ($components as $component) {
                        $systemAncestor = new SystemAncestor();
                        $systemAncestor->setParentUuid($sml['uuid']);
                        $systemAncestor->setParentName($sml['name']);
                        $systemAncestor->setComponentName($component['name']);
                        $systemAncestor->setStatus(true);
                        $systemAncestor->setChildUuid($component['uuid']);
                        $this->systemAncestorsMapper->insert($systemAncestor);
                    }
                }
            }

            $system = $this->systemMapper->getByIdOrUuid($node->getId(), $uuid);
            $insert = false;
            if ($system == null) {
                $system = new System();
                $insert = true;
            }

            $system->setUuid($uuid);
            $system->setName($sml['name']);
            $system->setDescription($sml['desc']);
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

    private function ensureUnique($components){
        $arr = [];
        foreach ($components as $comp) {
            if($arr[$comp['name']]){
                return false;
            }
            $arr[$comp['name']] = 1;
        }
        return true;
    }

}