<?php

namespace OCA\SnannyOwncloudApi\Hooks;

use OCA\SnannyOwncloudApi\Db\FileCacheDao;
use OCA\SnannyOwncloudApi\Parser\OMParser;
use OCA\SnannyOwncloudApi\Parser\SensorMLParser;
use OCA\SnannyOwncloudApi\Tar\TarParser;
use OCA\SnannyOwncloudApi\Util\FileUtil;
use OCP\Files\FileInfo;
use OCP\Files\Node;
use OCP\Util;

const UNKNOW = 0;
const OM = 1;
const SML = 2;
const TAR = 3;

const XML_FORMAT = '.xml';
const CSV_FORMAT = '.csv';
const TAR_FORMAT = '.tar';

class FileHook
{
    private $fileSystemManager;
    private $sensorMLHook;
    private $omHook;
    private $logger;


    public function __construct($fileSystemManager, DelegateSensorMLHook $sensorMLHook, DelegateOmHook $omHook,
                                Logger $logger)
    {
        $this->fileSystemManager = $fileSystemManager;
        $this->sensorMLHook = $sensorMLHook;
        $this->omHook = $omHook;
        $this->logger = $logger;
    }

    /**
     * Load additional scripts when the files app is visible
     */
    public static function onLoadFilesAppScripts()
    {
        Util::addScript('snannyowncloudapi', 'tabview');
        Util::addScript('snannyowncloudapi', 'filesplugin');
        Util::addScript('snannyowncloudapi', 'fileupload');
        Util::addScript('snannyowncloudapi', 'templateutil');
        Util::addStyle('snannyowncloudapi', 'style');
    }

    public function register()
    {

        $callback = function ($node) {
            if (FileInfo::TYPE_FILE === $node->getType()) {
                $type = $this->getType($node);
                if ($type === SML) {

                    $this->sensorMLHook->onUpdateOrCreate($node->getId(), $node->getContent());
                    $this->getSystemFromFileId($node->getContent());

                } else if ($type === OM) {

                    $this->omHook->onUpdateOrCreate($node->getId(), $node->getContent());

                } else if ($type === TAR) {

                    $tarContent = TarParser::parse(FileCacheDao::getFullUrl($node->getId()));

                    foreach ($tarContent as $item) {
                        $path = $item['path'];
                        if ($item['file']) {
                            if (FileUtil::endsWith($path, array('xml'))) {
                                $data = file_get_contents($path);
                                $xml = new \SimpleXMLElement($data);
                                if (OMParser::accept($xml)) {
                                    $this->omHook->onUpdateOrCreate($node->getId(), $data, $item['pharPath']);
                                } else if (SensorMLParser::accept($xml)) {
                                    $this->sensorMLHook->onUpdateOrCreate($node->getId(), $data, $item['pharPath']);
                                    //update all observations linked to the sensor
                                    $this->getSystemFromFileId($data);
                                }
                            }
                        }
                    }
                } else {
                    $this->getOMFromDataPath($node->getId());
                }
            }
        };

        //Create or update
        $this->fileSystemManager->listen('\OC\Files', 'postCreate', $callback);
        $this->fileSystemManager->listen('\OC\Files', 'postWrite', $callback);

        //deletion
        $this->fileSystemManager->listen('\OC\Files', 'preDelete', function ($node) {

            if (FileInfo::TYPE_FILE === $node->getType()) {
                $type = $this->getType($node);

                if ($type === SML) {
                    $this->sensorMLHook->onDelete($node);
                } else if ($type === OM) {
                    $this->omHook->onDelete($node);
                } else if ($type === TAR) {
                    $this->sensorMLHook->onDelete($node);
                }
            }
        });


        //Si la version d'owncloud est suffisante chargement uniquement dans les cas nécessaires
        if (method_exists(\OC::$server, 'getEventDispatcher')) {
            $eventDispatcher = \OC::$server->getEventDispatcher();
            $eventDispatcher->addListener('OCA\Files::loadAdditionalScripts',
                ['OCA\SnannyOwncloudApi\Hooks\FileHook', "onLoadFilesAppScripts"]);
        } else {
            //Sinon chargement indifférent du script js
            onLoadFilesAppScripts();
        }
    }

    /**
     * Return the type of the node
     * @param $node node to analyze
     */
    function getType($node)
    {
        $xmlFile = FileUtil::endsWith($node->getName(), array(XML_FORMAT));

        $tarFile = FileUtil::endsWith($node->getName(), array(TAR_FORMAT));

        if ($xmlFile === true) {
            $xml = new \SimpleXMLElement($node->getContent());

            if (OMParser::accept($xml)) {
                return OM;
            } else if (SensorMLParser::accept($xml)) {
                return SML;
            }
        } else if ($tarFile === true) {
            return TAR;
        }
        return UNKNOW;
    }

    function getSystemFromFileId($content) {
        $uuid = SensorMLParser::getUUID($content);
        $observations = $this->omHook->getDirectOM($uuid);
        if($observations == null && count($observations) === 0) {
            // Search OM via snannyancestors
            $this->updateAllOM($uuid);
        } else {
            // Update OM
            $this->omHook->updateOM($observations);
        }
    }

    function updateAllOM($uuid) {
        $childrens = $this->sensorMLHook->getChildren($uuid);
        if($childrens != null && count($childrens) > 0) {
            foreach ($childrens as $children) {
                $childUuid = $children['uuid'];
                if ($childUuid != $uuid) {

                    // Get OM file with system_uuid = uuid
                    $om = $this->omHook->getDirectOM($childUuid);

                    // if exist om file -> update
                    if($om != null) {
                        $this->omHook->updateOM($om);
                    }

                    $this->updateAllOM($childUuid);
                }
            }
        }
    }

    function getOMFromDataPath($fileid) {

        // Get cacheInfo from id
        $cacheInfo = FileCacheDao::getCacheInfo($fileid);

        // Get name and file path
        $fileName = $cacheInfo['name'];
        $fileCachePath = preg_replace('"\.[^\.]*$"', '.xml', $cacheInfo['path']);

        $obs = $this->omHook->getOMByData($fileName, $fileCachePath);
        if($obs != null) {
            $this->omHook->updateOM($obs);
        }
    }

}