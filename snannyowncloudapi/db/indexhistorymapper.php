<?php


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

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\Mapper;
use OCP\IDb;

class IndexHistoryMapper extends Mapper
{

    public function __construct(IDb $db)
    {
        parent::__construct($db, 'snanny_observation_index_history');
    }

    public function getByUuid($uuid, $fileId = -1)
    {
        try {
            $sql = 'SELECT * FROM *PREFIX*snanny_observation_index_history WHERE uuid = ? OR file_id = ? ORDER BY time DESC';
            return $this->findEntities($sql, array($uuid, $fileId));
        } catch (DoesNotExistException $e) {
            return null;
        }
    }
}