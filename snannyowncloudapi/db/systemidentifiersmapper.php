<?php

namespace OCA\SnannyOwncloudApi\Db;

use OCP\AppFramework\Db\Mapper;
use OCP\IDb;
use OCP\AppFramework\Db\DoesNotExistException;


class SystemIdentifiersMapper extends Mapper
{

    public function __construct(IDb $db)
    {
        parent::__construct($db, 'snanny_system_identifiers');
    }

    public function getUUIDByIdentifier($identifier){
        try {
            $sql = 'SELECT DISTINCT system_uuid FROM *PREFIX*snanny_system_identifiers WHERE identifier = :identifier';
            $result =  DBUtil::executeQuery($sql, array('identifier' => $identifier));

            $uuid = array();
            if($result != null){
                $row = $result->fetch();
                while($row != null) {
                    $uuid[] = $row['system_uuid'];
                    $row = $result->fetch();
                }
            }
            return $uuid;
        } catch (DoesNotExistException $e) {
            return null;
        }
    }

    public function deleteBySystemUuid($uuid){
        $params = array(':uuid' => $uuid);
        $sql = 'DELETE FROM *PREFIX*snanny_system_identifiers WHERE system_uuid = :uuid';
        DBUtil::executeQuery($sql, $params);
    }
}