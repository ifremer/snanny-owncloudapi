<?php

namespace OCA\SnannyOwncloudApi\Hooks;

use OCA\SnannyOwncloudApi\Db\FileCacheDao;
use OCA\SnannyOwncloudApi\Parser\OMParser;
use OCA\SnannyOwncloudApi\Parser\SensorMLParser;
use OCA\SnannyOwncloudApi\Tar\TarParser;
use OCA\SnannyOwncloudApi\Util\FileUtils;
use OCP\Files\FileInfo;
use OCP\Util;

const GML_NAMESPACE = "http://www.opengis.net/gml/3.2";
const SML_NAMESPACE = "http://www.opengis.net/sensorml/2.0";
const XLINK_NAMESPACE = "http://www.w3.org/1999/xlink";

const UNKNOW = 0;
const OM = 1;
const SML = 2;

const XML = '.xml';
const TAR = '.tar';

class FileHook
{
    private $fileSystemManager;
    private $sensorMLHook;
    private $omHook;


    public function __construct($fileSystemManager, DelegateSensorMLHook $sensorMLHook, DelegateOmHook $omHook)
    {
        $this->fileSystemManager = $fileSystemManager;
        $this->sensorMLHook = $sensorMLHook;
        $this->omHook = $omHook;
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

                } else if ($type === OM) {

                    $this->omHook->onUpdateOrCreate($node->getId(), $node->getContent());

                } else if (FileUtils::endsWith($node->getName(), TAR)) {

                    $tarContent = TarParser::parse(FileCacheDao::getFullUrl($node->getId()));
                    foreach ($tarContent as $item) {
                        $path = $item['path'];
                        if ($item['file']) {
                            if (FileUtils::endsWith($path, 'xml')) {
                                $data = file_get_contents($path);
                                $xml = new \SimpleXMLElement($data);
                                if (OMParser::accept($xml)) {
                                    $this->omHook->onUpdateOrCreate($node->getId(), $data, $item['pharPath']);
                                } else if (SensorMLParser::accept($xml)) {
                                    $this->sensorMLHook->onUpdateOrCreate($node->getId(), $data, $item['pharPath']);
                                }
                            }
                        }
                    }

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
        if (FileUtils::endsWith($node->getName(), XML)) {
            $xml = new \SimpleXMLElement($node->getContent());
            if (OMParser::accept($xml)) {
                return OM;
            } else if (SensorMLParser::accept($xml)) {
                return SML;
            }
        }
        return UNKNOW;
    }


}