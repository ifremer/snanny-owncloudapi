<?php

namespace OCA\SnannyOwncloudApi\Db;

use OCP\AppFramework\Db\Entity;

class SystemIdentifier extends Entity
{

    protected $systemUuid;
    protected $name;
    protected $identifier;
}