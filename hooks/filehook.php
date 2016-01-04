<?php

namespace OCA\SnannyOwncloudApi\Hooks;

use OCP\Files\FileInfo;
use OCP\Util;

const GML_NAMESPACE = "http://www.opengis.net/gml/3.2";
const SML_NAMESPACE = "http://www.opengis.net/sensorml/2.0";
const XLINK_NAMESPACE = "http://www.w3.org/1999/xlink";

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

    public function register()
    {

        $callback = function ($node) {
            if (FileInfo::TYPE_FILE === $node->getType()) {
                if ($this->endsWith($node->getName(), 'sensorML.xml')) {
                    $this->sensorMLHook->onUpdateOrCreate($node);
                } else if ($this->endsWith($node->getName(), '.xml')) {
                    $this->omHook->onUpdateOrCreate($node);
                }
            }
        };

        //Create or update
        $this->fileSystemManager->listen('\OC\Files', 'postCreate', $callback);
        $this->fileSystemManager->listen('\OC\Files', 'postWrite', $callback);

        //deletion
        $this->fileSystemManager->listen('\OC\Files', 'preDelete', function ($node) {

            if (FileInfo::TYPE_FILE === $node->getType()) {
                if ($this->endsWith($node->getName(), 'sensorML.xml')) {
                    $this->sensorMLHook->onDelete($node);
                } else if ($this->endsWith($node->getName(), '.xml')) {
                    $this->omHook->onDelete($node);
                }
            }
        });

        //Si la version d'owncloud est suffisante chargement uniquement dans les cas nécessaires
        if(method_exists(\OC::$server, 'getEventDispatcher')){
            $eventDispatcher = \OC::$server->getEventDispatcher();
            $eventDispatcher->addListener('OCA\Files::loadAdditionalScripts', 
                ['OCA\SnannyOwncloudApi\Hooks\FileHook', "onLoadFilesAppScripts"]);
        }else{
            //Sinon chargement indifférent du script js
            onLoadFilesAppScripts();
        }



    }

    function endsWith($haystack, $needle) {
        // search forward starting from end minus needle length characters
        return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== FALSE);
    }

        /**
     * Load additional scripts when the files app is visible
     */
    public static function onLoadFilesAppScripts() {        
        Util::addScript('snannyowncloudapi', 'tabview');
        Util::addScript('snannyowncloudapi', 'filesplugin');
        Util::addStyle('snannyowncloudapi', 'style');
    }

}