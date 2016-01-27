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

    public function getByUuid($uuid)
    {
        try {
            $sql = 'SELECT * FROM *PREFIX*snanny_system WHERE uuid = ?';
            return $this->findEntity($sql, array($uuid));
        } catch (DoesNotExistException $e) {
            return null;
        }
    }


    public function getByIdOrUuid($id, $uuid)
    {
        try {
            $sql = 'SELECT * FROM *PREFIX*snanny_system WHERE file_id = ? OR uuid = ?';
            return $this->findEntity($sql, array($id, $uuid));
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
    public function autocomplete($userId, $term){
        $sql = 'SELECT s.* FROM *PREFIX*snanny_system s'
            .' INNER JOIN *PREFIX*filecache c ON s.file_id = c.fileid'
            .' INNER JOIN *PREFIX*storages d ON d.numeric_id=c.storage'
            .' WHERE d.id=:user_id AND s.status = :status'
            .' AND UPPER(s.name) LIKE :term'
            .' ORDER by s.name';

        return $this->findEntities($sql, array(':status'=>1, ':term'=>'%'.strtoupper($term).'%', ':user_id'=>'home::'.$userId));
    }
}