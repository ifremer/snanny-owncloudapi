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

    public function findDistinctIds($from, $to, $exts)
    {
        $sql = 'SELECT ac.object_id, ac.file, ac.user, ac.type FROM *PREFIX*activity ac INNER JOIN *PREFIX*snanny_observation_model om ON ac.object_id = om.file_id ' .
            'WHERE type LIKE :type AND timestamp between :from AND :to
            AND object_type = :object_type';

        if (!empty($exts)) {
            $ext_str = "'" . implode("','", explode(',', $exts)) . "'";
            $sql .= ' AND SUBSTRING_INDEX(file,".",-1) IN (' . $ext_str . ')';
        }
        $sql .= ' ORDER BY object_id ASC, timestamp DESC';

        return DBUtil::executeQuery($sql,
            array(':from' => $from,
                ':to' => $to,
                ':type' => 'file%',
                ':object_type' => 'files'
            ));
    }

}