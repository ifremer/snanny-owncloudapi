<?php

namespace OCA\SnannyOwncloudApi\Hooks;

use OCP\Files\FileInfo;
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
    }

    function endsWith($haystack, $needle) {
        // search forward starting from end minus needle length characters
        return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== FALSE);
    }

}