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

class ObservationModelMapper extends Mapper
{

    public function __construct(IDb $db)
    {
        parent::__construct($db, 'snanny_observation_model');
    }

    public function getByFileId($fileId)
    {
        try {
            $sql = 'SELECT * FROM *PREFIX*snanny_observation_model WHERE file_id = ?';
            return $this->findEntity($sql, array($fileId));
        } catch (DoesNotExistException $e) {
            return null;
        }
    }

    public function getByUuid($uuid)
    {
        try {
            $sql = 'SELECT * FROM *PREFIX*snanny_observation_model WHERE uuid = ?';
            return $this->findEntity($sql, array($uuid));
        } catch (DoesNotExistException $e) {
            return null;
        }
    }


    public function getByIdOrUuid($id, $uuid)
    {
        try {
            $sql = 'SELECT * FROM *PREFIX*snanny_observation_model WHERE file_id = ? OR uuid = ?';
            return $this->findEntity($sql, array($id, $uuid));
        } catch (DoesNotExistException $e) {
            return null;
        }
    }
}