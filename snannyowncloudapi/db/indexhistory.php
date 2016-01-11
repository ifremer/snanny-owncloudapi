<?php

namespace OCA\SnannyOwncloudApi\Db;

use OCP\AppFramework\Db\Entity;

class IndexHistory extends Entity
{

    protected $fileId;
    protected $uuid;
    protected $status;
    protected $message;
    protected $time;
    protected $indexedObservations;

    public function __construct()
    {
        $this->addType('status', 'boolean');
    }
}