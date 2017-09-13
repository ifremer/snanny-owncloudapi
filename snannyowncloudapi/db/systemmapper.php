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

class SystemMapper extends Mapper
{

    public function __construct(IDb $db)
    {
        parent::__construct($db, 'snanny_system');
    }

    public function getByFileId($fileId)
    {
        try {
            $sql = 'SELECT * FROM *PREFIX*snanny_system WHERE file_id = ?';
            return $this->findEntity($sql, array($fileId));
        } catch (DoesNotExistException $e) {
            return null;
        }
    }

    public function getById($id)
    {
        try {
            $sql = 'SELECT * FROM *PREFIX*snanny_system WHERE id = ?';
            return $this->findEntity($sql, array($id));
        } catch (DoesNotExistException $e) {
            return null;
        }
    }

    public function getByUuidAndDate($uuid, $startDate, $endDate, $dateStrict)
    {
        try {
            $params = array($uuid);
            $startDateQuery = null;
            if ($startDate == null) {
                $startDateQuery = $dateStrict ? ' AND start_date is null' : '';
            } else {
                $startDateQuery = $dateStrict ? ' AND start_date = ?' : ' AND (start_date <= ? AND end_date >= ?';
                array_push($params, $startDate);
                if (!$dateStrict) {
                    array_push($params, $startDate);
                }
            }
            $endDateQuery = null;
            if ($endDate == null) {
                $endDateQuery = $dateStrict ? ' AND end_date is null' : '';
            } else {
                $endDateQuery = $dateStrict ? ' AND end_date = ?' : ' OR start_date <= ? AND end_date >= ?)';
                array_push($params, $endDate);
                if (!$dateStrict) {
                    array_push($params, $endDate);
                }
            }

            //status should be active
            array_push($params, 1);

            $sql = 'SELECT * FROM *PREFIX*snanny_system WHERE uuid = ?' . $startDateQuery . $endDateQuery. ' and status = ?';
            return $dateStrict ? $this->findEntity($sql, $params) : $this->findEntities($sql, $params);
        } catch (DoesNotExistException $e) {
            return null;
        }
    }

    public function getByUuid($uuid)
    {
        try {
            $params = array($uuid);
            $sql = 'SELECT * FROM *PREFIX*snanny_system WHERE uuid = ?';
            return $this->findEntities($sql, $params);
        } catch (DoesNotExistException $e) {
            return null;
        }
    }

    /**
     * Autocomplete element
     * @param $userId user Id
     * @param $term search terms
     * @return array system elements
     */
    public function autocomplete($userId, $term)
    {
        $sql = 'SELECT s.* FROM *PREFIX*snanny_system s'
            . ' INNER JOIN *PREFIX*filecache c ON s.file_id = c.fileid'
            . ' INNER JOIN *PREFIX*storages d ON d.numeric_id=c.storage'
            . ' WHERE d.id=:user_id AND s.status = :status'
            . ' AND UPPER(s.name) LIKE :term'
            . ' ORDER by s.name';

        return $this->findEntities($sql, array(':status' => 1, ':term' => '%' . strtoupper($term) . '%', ':user_id' => 'home::' . $userId));
    }


    /**
     * change O&M status on deletion
     * @param $nodeId fileId of the node
     */
    public function logicalDelete($nodeId)
    {
        $sql = 'UPDATE *PREFIX*snanny_system SET status = 0 WHERE file_id = :id';
        DBUtil::executeQuery($sql, array(':id' => $nodeId));
    }


    public function getByUuidAndDateAndNotPath($uuid, $startDate, $endDate, $path)
    {
        try {
            $params = array($uuid);
            $pathQuery = null;
            if ($path == null) {
                $pathQuery = '';
            } else {
                $pathQuery = ' AND fc.path <> ? AND fc.path <> ?';
                array_push($params, $path . '.tar');
                array_push($params, $path . '.xml');
            }
            $startDateQuery = null;
            if ($startDate == null) {
                $startDateQuery = '';
            } else {
                $startDateQuery = ' AND (ss.start_date <= ? AND ss.end_date >= ?';
                array_push($params, $startDate);
                array_push($params, $startDate);
            }
            $endDateQuery = null;
            if ($endDate == null) {
                $endDateQuery = '';
            } else {
                $endDateQuery = ' OR ss.start_date <= ? AND ss.end_date >= ?)';
                array_push($params, $endDate);
                array_push($params, $endDate);
            }

            $sql = 'SELECT ss.* FROM *PREFIX*snanny_system ss JOIN *PREFIX*filecache fc ON ss.file_id=fc.fileid WHERE ss.uuid = ?' . $pathQuery . $startDateQuery . $endDateQuery;
            return $this->findEntities($sql, $params);
        } catch (DoesNotExistException $e) {
            return null;
        }
    }

    public function getByUuidAndFileId($uuid, $fileId) {
        try {
            $params = array($uuid, $fileId);
            $sql = 'SELECT * FROM *PREFIX*snanny_system WHERE uuid = ? AND file_id=?';
            return $this->findEntity($sql, $params);
        } catch (DoesNotExistException $e) {
            return null;
        }
    }
}