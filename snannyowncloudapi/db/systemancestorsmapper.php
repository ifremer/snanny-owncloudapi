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

    public function getAncestors($smlUuid, array &$ancestors, $beginTime, $endTime)
    {
        try {
            $params = array(':uuid'=>$smlUuid);
            $beginTimeQuery = "";
            if($beginTime != null) {
                $beginTimeQuery = " AND parent_start_date <= :startTime";
                $params[":startTime"] = $beginTime;
            }
            $endTimeQuery = "";
            if($endTime != null) {
                $endTimeQuery = " AND parent_end_date >= :endTime";
                $params[":endTime"] = $endTime;
            }

            $sql = 'SELECT * FROM *PREFIX*snanny_system_ancestors WHERE child_uuid = :uuid' . $beginTimeQuery . $endTimeQuery;
            $result = DBUtil::executeQuery($sql, $params);
            if($result != null) {

                $row = $result->fetch();
                while ($row != null) {
                    $parent = $row['parent_uuid'];
                    $this->getAncestors($parent, $ancestors, $beginTime, $endTime);
                    if (!in_array($parent, $ancestors)) {
                        $ancestors[] = $parent;
                    }
                    $row = $result->fetch();
                }
            }
            return $ancestors;
        } catch (DoesNotExistException $e) {
            return null;
        }
    }

    public function getAncestorsWithName($smlUuid, array &$ancestors)
    {
        try {
            $sql = 'SELECT * FROM *PREFIX*snanny_system_ancestors WHERE child_uuid = :uuid';
            $result = DBUtil::executeQuery($sql, array(':uuid'=>$smlUuid));
            if($result != null) {
                if ($row = $result->fetch()) {
                    $this->getAncestorsWithName($row['parent_uuid'], $ancestors);
                    $ancestors[]=array('uuid'=>$row['parent_uuid'], 'name'=>$row['parent_name']);
                }
            }
            return $ancestors;
        } catch (DoesNotExistException $e) {
            return null;
        }
    }


    public function getChildren($smlUuid)
    {
        try {
            $sql = 'SELECT * FROM *PREFIX*snanny_system_ancestors WHERE parent_uuid = :uuid';
            $result = DBUtil::executeQuery($sql, array(':uuid'=>$smlUuid));
            if($result != null) {
                while($row = $result->fetch()) {
                    $children[]=array('uuid'=>$row['child_uuid'], 'name'=>$row['component_name']);
                }
            }
            return $children;
        } catch (DoesNotExistException $e) {
            return null;
        }
    }

    public function deleteChildren($parentUuid, $startDate, $endDate){
        $startDateQuery = null;
        $params = array(':uuid'=>$parentUuid);
        if ($startDate == null) {
            $startDateQuery = ' AND parent_start_date is null';
        } else {
            $startDateQuery = ' AND parent_start_date = :startDate';
            $params[':startDate'] = $startDate;
        }
        $endDateQuery = null;
        if ($endDate == null) {
            $endDateQuery = ' AND parent_end_date is null';
        } else {
            $endDateQuery = ' AND parent_end_date = :endDate';
            $params[':endDate'] = $endDate;
        }

        $sql = 'DELETE FROM *PREFIX*snanny_system_ancestors WHERE parent_uuid = :uuid' . $startDateQuery . $endDateQuery;
        DBUtil::executeQuery($sql, $params);
    }

    public function logicalDeleteChildren($id)
    {
        $sql = 'UPDATE *PREFIX*snanny_system_ancestors ancestor INNER JOIN *PREFIX*snanny_system syst ON syst.uuid = ancestor.parent_uuid SET ancestor.status = 0 WHERE syst.file_id = :id';
        DBUtil::executeQuery($sql, array(':id' => $id));
    }
}