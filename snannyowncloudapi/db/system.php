<?php

namespace OCA\SnannyOwncloudApi\Db;

use OCP\AppFramework\Db\Entity;

class System extends Entity
{

    protected $uuid;
    protected $fileId;
    protected $name;
    protected $description;
    protected $status;

    public function __construct()
    {
        $this->addType('status', 'boolean');
    }
}