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
use OCP\AppFramework\Http\DataDownloadResponse;
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
     * @param $uuid identifier of the sensorML
     * @param bool|false $pretty indicates if the element have to be pretified
     * @return DataDisplayResponse|NotFoundResponse Reponse if document exist, otherwise raised exception
     *
     * @NoCSRFRequired
     * @NoAdminRequired
     */
    public function sensorML($uuid, $pretty = false)
    {
        $system = $this->systemMapper->getByUuid($uuid);
        if ($system !== null) {
            $cacheInfo = FileCacheDao::getCacheInfo($system->getFileId());
            if ($cacheInfo !== null) {
                $fileInfo = FileCacheDao::getFileInfo($cacheInfo['storage'], $cacheInfo['path'], $system->getPharPath());

                $content = FileCacheDao::getContentByUrn($fileInfo['urn']);
                if ($pretty == true) {
                    return new DataDisplayResponse('<pre>' . htmlentities($content) . '</pre>');
                }
                return new DataDisplayResponse($content);
            }
        }
        return new NotFoundResponse();
    }


    /**
     * get file content from file_id
     * @NoCSRFRequired
     * @NoAdminRequired
     */
    public function downloadSensorML($uuid)
    {
        $system = $this->systemMapper->getByUuid($uuid);
        if ($system !== null) {
            $cacheInfo = FileCacheDao::getCacheInfo($system->getFileId());
            if ($cacheInfo !== null) {
                $fileInfo = FileCacheDao::getFileInfo($cacheInfo['storage'], $cacheInfo['path'], $system->getPharPath());
                $content = FileCacheDao::getContentByUrn($fileInfo['urn']);
                return new DataDownloadResponse($content, "$uuid.xml", 'application/xml');
            }
        }
        return new NotFoundResponse();
    }


    /**
     * get file content from file_id
     * @NoCSRFRequired
     * @NoAdminRequired
     */
    public function ancestorSML($uuid)
    {
        $system = $this->ancestorsMapper->getAncestors($uuid);
        return new JSONResponse(array('ancestors' => $system));
    }

    /**
     * get file content from file_id
     * @NoCSRFRequired
     * @NoAdminRequired
     */
    public function infoSML($uuid)
    {
        $data = array();
        $system = $this->systemMapper->getByIdOrUuid($uuid, $uuid);
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
    public function autocompleteSensors($term){

        $user = \OC::$server->getUserSession()->getUser()->getUID();


        $result = $this->systemMapper->autocomplete($user , $term);

        //Search term
        $data = array();
        foreach ($result as $item) {
            $data[] = array('label'=>$item->getName().' - '.$item->getUuid(), 'uuid'=>$item->getUuid());
        }
        return new JSONResponse($data);
    }
}