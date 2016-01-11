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
        $sql = 'SELECT * FROM *PREFIX*snanny_observation_model om' .
            ' WHERE (om.timestamp between :from AND :to) OR (om.share_updated_time between :from AND :to)';
        return DBUtil::executeQuery($sql,
            array(':from' => $from,
                ':to' => $to,
            ));
    }

    public static function findDistinctIdsFailed()
    {
        $sql = 'SELECT model.* FROM *PREFIX*snanny_observation_model model '.
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