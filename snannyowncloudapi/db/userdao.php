<?php
/**
 * ownCloud - snannyowncloudapi
 *
 * Database access layer for user management.
 *
 * @author Geoffrey PAGNIEZ <gpagniez@asi.fr>
 */

namespace OCA\SnannyOwncloudApi\Db;


class UserDao
{
    public static function getUserGroups($identifier)
    {
        $sql = 'SELECT gu.gid FROM *PREFIX*group_user gu '.
            'WHERE gu.uid = :identifier';

        return DBUtil::executeQuery($sql,
            array(':identifier' => $identifier));
    }
}