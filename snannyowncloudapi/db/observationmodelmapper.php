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

/**
 * Class ObservationModelMapper
 * @package OCA\SnannyOwncloudApi\Db
 * Observation mapper to access data from snanny_observation_model
 */
class ObservationModelMapper extends Mapper
{
    /**
     * ObservationModelMapper constructor.
     * @param IDb $db database access
     */
    public function __construct(IDb $db)
    {
        parent::__construct($db, 'snanny_observation_model');
    }

    /**
     * Get the observation model datas from a file id
     * @param $fileId node id of the file
     * @return null|\OCP\AppFramework\Db\Entity
     */
    public function getByFileId($fileId)
    {
        try {
            $sql = 'SELECT * FROM *PREFIX*snanny_observation_model WHERE file_id = ?';
            return $this->findEntity($sql, array($fileId));
        } catch (DoesNotExistException $e) {
            return null;
        }
    }

    /**
     * Get the observation model datas from an observation uuid
     * @param $uuid unique identifier of the observation
     * @return null|\OCP\AppFramework\Db\Entity
     */
    public function getByUuid($uuid)
    {
        try {
            $sql = 'SELECT * FROM *PREFIX*snanny_observation_model WHERE uuid = ?';
            return $this->findEntity($sql, array($uuid));
        } catch (DoesNotExistException $e) {
            return null;
        }
    }


    /**
     * Get the observation model datas from a file_id or a unique identifier
     * @param $id file id
     * @param $uuid unique identifier of the observation
     * @return null|\OCP\AppFramework\Db\Entity
     */
    public function getByIdOrUuid($id, $uuid)
    {
        try {
            $sql = 'SELECT * FROM *PREFIX*snanny_observation_model WHERE file_id = ? OR uuid = ?';
            return $this->findEntity($sql, array($id, $uuid));
        } catch (DoesNotExistException $e) {
            return null;
        }
    }

    /**
     * Get the observation model datas from a nav file name
     * @param $filename filename of the navigation file
     * @return null|\OCP\AppFramework\Db\Entity
     */
    public function getByDataFileName($filepath)
    {
        try {
            $sql = 'SELECT * FROM *PREFIX*snanny_observation_model WHERE result_file = ?';
            return $this->findEntity($sql, array($filepath));
        } catch (DoesNotExistException $e) {
            return null;
        }
    }
}