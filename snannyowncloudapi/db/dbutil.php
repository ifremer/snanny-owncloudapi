<?php

namespace OCA\SnannyOwncloudApi\Db;

class DBUtil
{

    public static function executeQuery($sql, $params = null)
    {
        $connection = \OC::$server->getDatabaseConnection();
        return $connection->executeQuery($sql, $params);

    }

    public static function insert($table, $arr){
        $connection = \OC::$server->getDatabaseConnection();
        $connection->insertIfNotExist($table, $arr);
    }
}