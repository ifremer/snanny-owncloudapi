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

    /**
     * DelegateSensorMLHook constructor.
     * @param SystemMapper $systemMapper
     * @param SystemAncestorsMapper $systemAncestorsMapper
     */
    public function __construct(SystemMapper $systemMapper, SystemAncestorsMapper $systemAncestorsMapper)
    {
        $this->systemMapper = $systemMapper;
        $this->systemAncestorsMapper = $systemAncestorsMapper;
    }

    /**
     * @param $fileId
     * @param $content
     * @param null $pharPath
     */
    public function onUpdateOrCreate($fileId, $content, $pharPath = null)
    {
        $sml = SensorMLParser::parse($content);
        $uuid = $sml['uuid'];
        if ($uuid) {
            $startDate = $sml['startDate'];
            $endDate = $sml['endDate'];
            $this->systemAncestorsMapper->deleteChildren($uuid, $startDate, $endDate);
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
                        $systemAncestor->setParentStartDate($startDate);
                        $systemAncestor->setParentEndDate($endDate);
                        $this->systemAncestorsMapper->insert($systemAncestor);
                    }
                }
            }

            $system = $this->systemMapper->getByUuidAndDate($uuid, $startDate, $endDate, true);

            if ($system == null) {
                $system = $this->systemMapper->getByUuidAndFileId($uuid, $fileId);
            }
            $insert = false;
            if ($system == null) {
                $system = new System();
                $insert = true;
            }

            $system->setUuid($uuid);
            $system->setName($sml['name']);
            $system->setDescription($sml['desc']);
            $system->setFileId($fileId);
            $system->setPharPath($pharPath);
            $system->setStatus(true);
            $system->setStartDate($startDate);
            $system->setEndDate($endDate);
            if ($insert === true) {
                $this->systemMapper->insert($system);
            } else {
                $this->systemMapper->update($system);
            }
        }
    }

    /**
     * @param $components
     * @return bool
     */
    private function ensureUnique($components)
    {
        $arr = [];
        foreach ($components as $comp) {
            if ($arr[$comp['name']]) {
                return false;
            }
            $arr[$comp['name']] = 1;
        }
        return true;
    }

    /**
     * @param $node
     */
    public function onDelete($node)
    {
        $this->systemMapper->logicalDelete($node->getId());
        $this->systemAncestorsMapper->logicalDeleteChildren($node->getId());
    }

}