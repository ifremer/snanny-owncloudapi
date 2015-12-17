<?php

namespace OCA\SnannyOwncloudApi\Db;

class DBUtil
{

    public static function executeQuery($sql, $params = null)
    {
        $connection = \OC::$server->getDatabaseConnection();
        return $connection->executeQuery($sql, $params);
    }
}