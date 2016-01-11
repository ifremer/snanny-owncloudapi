<?php

namespace OCA\SnannyOwncloudApi\Db;

use OCP\AppFramework\Db\Entity;

class SystemAncestor extends Entity
{

    protected $parentUuid;
    protected $parentName;
    protected $childUuid;
    protected $componentName;
    protected $status;

    public function __construct()
    {
        $this->addType('status', 'boolean');
    }
}