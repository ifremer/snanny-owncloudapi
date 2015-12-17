<?php

namespace OCA\SnannyOwncloudApi\Db;

use OCP\AppFramework\Db\Entity;

class ObservationModel extends Entity
{

    protected $uuid;
    protected $fileId;
    protected $name;
    protected $description;
    protected $status;
    protected $systemUuid;
    protected $resultFile;

    public function __construct()
    {
        $this->addType('status', 'boolean');
    }
}