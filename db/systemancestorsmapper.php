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

class SystemAncestorsMapper extends Mapper
{

    public function __construct(IDb $db)
    {
        parent::__construct($db, 'snanny_system_ancestors');
    }

    public function getAncestors($smlUuid, array &$ancestors)
    {
        try {
            $sql = 'SELECT * FROM *PREFIX*snanny_system_ancestors WHERE child_uuid = :uuid';
            $result = DBUtil::executeQuery($sql, array(':uuid'=>$smlUuid));
            if($result != null) {
                if ($row = $result->fetch()) {
                    $parent = $row['parent_uuid'];
                    $this->getAncestors($parent, $ancestors);
                    $ancestors[]=$parent;
                }
            }
            return $ancestors;
        } catch (DoesNotExistException $e) {
            return null;
        }
    }

    public function deleteChildren($parentUuid){
        $sql = 'DELETE FROM *PREFIX*snanny_system_ancestors WHERE parent_uuid = :uuid';
        DBUtil::executeQuery($sql, array(':uuid'=>$parentUuid));
    }

    public function logicalDeleteChildren($parentUuid){
        $sql = 'UPDATE *PREFIX*snanny_system_ancestors SET status = 0 WHERE parent_uuid = :uuid';
        DBUtil::executeQuery($sql, array(':uuid'=>$parentUuid));
    }
}