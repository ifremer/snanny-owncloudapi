<?php
// db/activitydao.php

/**
 * ownCloud - snannyowncloudapi
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Arnaud THOREL <athorel@asi.fr>
 * @copyright Arnaud THOREL 2015
 */

namespace OCA\SnannyOwncloudApi\Db;

class ActivityDao
{

    public static function findDistinctIds($from, $to)
    {
        $sql = 'SELECT ac.object_id, ac.file, ac.user, ac.type FROM *PREFIX*activity ac INNER JOIN *PREFIX*snanny_observation_model om ON ac.object_id = om.file_id ' .
            'WHERE type LIKE :type AND timestamp between :from AND :to
            AND object_type = :object_type';
        $sql .= ' ORDER BY object_id ASC, timestamp DESC';

        return DBUtil::executeQuery($sql,
            array(':from' => $from,
                ':to' => $to,
                ':type' => 'file%',
                ':object_type' => 'files'
            ));
    }

    public static function findDistinctIdsFailed()
    {
        $sql = 'SELECT * FROM *PREFIX*snanny_observation_model model '.
                'INNER JOIN('.
                    'SELECT his.uuid '.
                    'FROM *PREFIX*snanny_observation_index_history his '.
                    'INNER JOIN '.
                        '(SELECT uuid, MAX(time) AS MaxDateTime '.
                            'FROM *PREFIX*snanny_observation_index_history '.
                            'GROUP BY uuid) innerHis '.
                        'ON his.uuid = innerHis.uuid '.
                        'AND his.time = innerHis.MaxDateTime '.
                        'AND status = 0) '.
                'lastErr ON model.uuid = lastErr.uuid;';

        return DBUtil::executeQuery($sql);
    }

}