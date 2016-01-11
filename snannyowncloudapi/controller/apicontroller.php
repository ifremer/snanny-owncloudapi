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
use OCA\SnannyOwncloudApi\Hooks\DelegateOmHook;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataDisplayResponse;
use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\NotFoundResponse;
use OCP\AppFramework\Http\StreamResponse;
use OCP\IRequest;

class ApiController extends Controller
{

    private $systemMapper;
    private $ancestorsMapper;

    public function __construct($AppName, IRequest $request, Mapper $systemMapper, SystemAncestorsMapper $ancestorsMapper)
    {
        parent::__construct($AppName, $request);
        $this->systemMapper = $systemMapper;
        $this->ancestorsMapper = $ancestorsMapper;
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function index($nodeId, $shareType)
    {
        DelegateOmHook::updateShare($nodeId, $shareType);
        return new JSONResponse(['info' => 'snanny-owncloud-api is up']);
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
        return new JSONResponse(array('user' => $fileInfo['user'], 'content' => $content, 'path' => $cacheInfo['path'], 'shares'=>$shares));
    }


    /**
     * get file content from file_id
     * @param $uuid identifier of the sensorML
     * @param bool|false $pretty indicates if the element have to be pretified
     * @return DataDisplayResponse|NotFoundResponse Reponse if document exist, otherwise raised exception
     *
     * @NoCSRFRequired
     */
    public function sensorML($uuid, $pretty = false)
    {
        $system = $this->systemMapper->getByUuid($uuid);
        if ($system !== null) {
            $cacheInfo = FileCacheDao::getCacheInfo($system->getFileId());
            if ($cacheInfo !== null) {
                $fileInfo = FileCacheDao::getFileInfo($cacheInfo['storage'], $cacheInfo['path']);
                $content = FileCacheDao::getContentByUrn($fileInfo['urn']);
                if($pretty == true){
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
     */
    public function downloadSensorML($uuid)
    {
        $system = $this->systemMapper->getByUuid($uuid);
        if ($system !== null) {
            $cacheInfo = FileCacheDao::getCacheInfo($system->getFileId());
            if ($cacheInfo !== null) {
                $fileInfo = FileCacheDao::getFileInfo($cacheInfo['storage'], $cacheInfo['path']);
                $content = FileCacheDao::getContentByUrn($fileInfo['urn']);
                return new DataDownloadResponse($content, "$uuid.xml", 'application/xml');
            }
        }
        return new NotFoundResponse();
    }

    /**
     * get file content from file_id
     * @NoCSRFRequired
     */
    public function downloadData($omid, $filename){

        $cacheInfo = FileCacheDao::getCacheInfo($omid);
        //Get file information (storage urn and user)
        $fileInfo = FileCacheDao::getFileInfo($cacheInfo['storage'], $cacheInfo['path']);
        //Parse file om
        $dataFile = dirname($fileInfo['urn']).'/'.$filename;
        if(file_exists($dataFile)) {
            return new StreamResponse($dataFile);
        }
        return new NotFoundResponse();
    }



    /**
     * get file content from file_id
     * @NoCSRFRequired
     */
    public function infoData($omid, $filename){

        $cacheInfo = FileCacheDao::getCacheInfo($omid);
        //Get file information (storage urn and user)
        $fileInfo = FileCacheDao::getFileInfo($cacheInfo['storage'], $cacheInfo['path']);
        //Parse file om
        $dataFile = dirname($fileInfo['urn']).'/'.$filename;
        if(file_exists($dataFile)) {
            return new JSONResponse(array('fileSize'=>filesize($dataFile)));
        }
        return new NotFoundResponse();
    }


    /**
     * get file content from file_id
     * @NoCSRFRequired
     */
    public function ancestorSML($uuid)
    {
        $system = $this->ancestorsMapper->getAncestors($uuid);
        return new JSONResponse(array('ancestors' => $system));
    }

    /**
     * get file content from file_id
     * @NoCSRFRequired
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


}