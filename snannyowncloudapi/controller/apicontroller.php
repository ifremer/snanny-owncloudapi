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

namespace OCA\SnannyOwncloudApi\Controller;

use OC\AppFramework\Http;
use OC\Files\Cache;
use OC\Share\Share;
use OCA\SnannyOwncloudApi\Db\ActivityDao;
use OCA\SnannyOwncloudApi\Db\FileCacheDao;
use OCA\SnannyOwncloudApi\Db\SystemAncestorsMapper;
use OCA\SnannyOwncloudApi\Db\SystemMapper;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataDisplayResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\NotFoundResponse;
use OCP\IRequest;

class ApiController extends Controller
{

    private $systemMapper;
    private $ancestorsMapper;

    public function __construct($AppName, IRequest $request, SystemMapper $systemMapper, SystemAncestorsMapper $ancestorsMapper)
    {
        parent::__construct($AppName, $request);
        $this->systemMapper = $systemMapper;
        $this->ancestorsMapper = $ancestorsMapper;
    }


    /**
     * get lasts modified files using query
     * from : From Date
     * to : To date
     * exts : file extensions
     * @NoCSRFRequired
     */
    public function files($from, $to, $exts)
    {
        $entities = array();
        $result = ActivityDao::findDistinctIds($from, $to, $exts);
        if ($result) {
            while ($row = $result->fetch()) {
                $entities[] = $row;
            }
        }
        return new JSONResponse($entities);
    }


    /**
     * get lasts modified files using query
     * from : From Date
     * to : To date
     * exts : file extensions
     * @NoCSRFRequired
     */
    public function lastfailure()
    {
        $entities = array();
        $result = ActivityDao::findDistinctIdsFailed();
        if ($result) {
            while ($row = $result->fetch()) {
                $entities[] = $row;
            }
        }
        return new JSONResponse($entities);
    }


    /**
     * get file content from file_id
     * @NoCSRFRequired
     */
    public function content($id)
    {
        $cacheInfo = FileCacheDao::getCacheInfo($id);
        //Get file information (storage urn and user)
        $fileInfo = FileCacheDao::getFileInfo($cacheInfo['storage'], $cacheInfo['path']);
        //Get content
        $content = FileCacheDao::getContentByUrn($fileInfo['urn']);
        // Get shares
        $shares = Share::getAllSharesForFileId($id);
        // Return json data
        return new JSONResponse(array('user' => $fileInfo['user'], 'content' => $content, 'path' => $cacheInfo['path'], 'shares' => $shares));
    }


    /**
     * get file content from file_id
     * @param $id id technique
     * @param bool|false $pretty indicates if the element have to be pretified
     * @return DataDisplayResponse|NotFoundResponse Reponse if document exist, otherwise raised exception
     *
     * @NoCSRFRequired
     * @NoAdminRequired
     * @PublicPage
     */
    public function sensorML($id, $pretty = false, $startTime = null, $endTime = null)
    {
        $system = null;
		if (preg_match('"^[\d]*$"', $id)) {
            $system = $this->systemMapper->getById($id);
        } else {
			//If uuid is encoded uuid => uuid#startTime#endTime
			if (preg_match('"^.+#[\d]*#[\d]*$"', $id)) {
				$exploded_id = explode("#", $id);
				$id = $exploded_id[0];
				$startTime = $exploded_id[1];
				$endTime = $exploded_id[2];
			}
            $systems = $this->systemMapper->getByUuidAndDate($id, $startTime, $endTime, false);

            if ($systems != null && count($systems) == 1) {
                $system = $systems[0];
            } else {
                $system = $this->systemMapper->getByUuidAndDate($id, null, null, true);
            }
        }

        if ($system === null) {
            return new DataDisplayResponse("Unable to retrieve a single system for uuid " . $id .
                " and timestamps " . $startTime . ", " . $endTime, Http::STATUS_UNPROCESSABLE_ENTITY);
        }

        $cacheInfo = FileCacheDao::getCacheInfo($system->getFileId());
        if ($cacheInfo !== null) {
            $fileInfo = FileCacheDao::getFileInfo($cacheInfo['storage'], $cacheInfo['path'], $system->getPharPath());

            $content = FileCacheDao::getContentByUrn($fileInfo['urn']);
            if ($pretty === true) {
                return new DataDisplayResponse('<pre>' . htmlentities($content) . '</pre>');
            }
            return new DataDisplayResponse($content);
        }
        return new NotFoundResponse();
    }


    /**
     * get file content from id
     * @NoCSRFRequired
     * @PublicPage
     */
    public function downloadSensorML($uuid)
    {
        $systems = $this->systemMapper->getByUuid($uuid);
        if ($systems !== null) {
            $fileIds = array();
            $resultFiles = new \PharData(sys_get_temp_dir() . "/$uuid.zip");
            foreach ($systems as $system) {
                $fileId = $system->getFileId();
                if (!in_array($fileId, $fileIds)) {
                    $fileIds[] = $fileId;
                    $cacheInfo = FileCacheDao::getCacheInfo($fileId);
                    if ($cacheInfo !== null) {
                        $fileInfo = FileCacheDao::getFileInfo($cacheInfo['storage'], $cacheInfo['path'], $system->getPharPath());

                        $content = FileCacheDao::getContentByUrn($fileInfo['urn']);
                        $resultFiles->addFromString($uuid . "_" . $fileId . ".xml", $content);
                    }
                }
            }

            $fullPath = $resultFiles->getPath();
            if ($fd = fopen($fullPath, "r")) {
                $path_parts = pathinfo($fullPath);
                header("Content-type: application/octet-stream");
                header("Content-Disposition: filename=\"" . $path_parts["basename"] . "\"");
                while (!feof($fd)) {
                    $buffer = fread($fd, 2048);
                    echo $buffer;
                }
            }
            fclose($fd);
        }
        return new NotFoundResponse();
    }


    /**
     * get file content from file_id
     * @NoCSRFRequired
     * @NoAdminRequired
     */
    public function ancestorSML($uuid, $beginTime = null, $endTime = null)
    {
        $empty_array = array();
        $systemAncestors = $this->ancestorsMapper->getAncestors($uuid, $empty_array, $beginTime, $endTime);
        $systemIds = array();
        foreach ($systemAncestors as $anUuid) {

            $systems = $this->systemMapper->getByUuidAndDate($anUuid, $beginTime, $endTime, false);
            if ($systems == null || count($systems) == 0) {
                $system = $this->systemMapper->getByUuidAndDate($anUuid, null, null, true);
                $systemId = $system->getId();
                $systemIds[] = $systemId;
            } else {
                foreach ($systems as $system) {
                    $systemId = $system->getId();
                    if (!in_array($systemId, $systemIds)) {
                        $systemIds[] = $systemId;
                    }
                }
            }


        }
        return new JSONResponse(array('ancestors' => $systemIds));
    }

    /**
     * get file content from file_id
     * @NoCSRFRequired
     * @NoAdminRequired
     */
    public function infoSML($fileId)
    {
        $data = array();
        $system = $this->systemMapper->getByFileId($fileId);
        if ($system !== null) {
            $data['uuid'] = $system->getUuid();
            $data['name'] = trim($system->getName());
            $data['description'] = trim($system->getDescription());
            $ancestors = $this->ancestorsMapper->getAncestorsWithName($system->getUuid());
            $data['hasAncestors'] = ($ancestors != null);
            $data['ancestors'] = $ancestors;
            $children = $this->ancestorsMapper->getChildren($system->getUuid());
            $data['children'] = $children;
            $data['hasChildren'] = ($children != null);
        }
        return new JSONResponse($data);
    }

    /**
     * get file content from file_id
     * @NoCSRFRequired
     * @NoAdminRequired
     */
    public function autocompleteSensors($term)
    {

        $user = \OC::$server->getUserSession()->getUser()->getUID();


        $result = $this->systemMapper->autocomplete($user, $term);

        //Search term
        $data = array();
        foreach ($result as $item) {
            $data[] = array('label' => $item->getName() . ' - ' . $item->getUuid(), 'uuid' => $item->getUuid(), 'startDate' => $item->getStartDate(), 'endDate' => $item->getEndDate());
        }
        return new JSONResponse($data);
    }
}
