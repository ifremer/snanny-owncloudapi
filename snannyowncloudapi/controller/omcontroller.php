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
use OCA\SnannyOwncloudApi\Db\FileCacheDao;
use OCA\SnannyOwncloudApi\Db\IndexHistory;
use OCA\SnannyOwncloudApi\Db\IndexHistoryMapper;
use OCA\SnannyOwncloudApi\Db\ObservationModelMapper;
use OCA\SnannyOwncloudApi\Parser\OMParser;
use OCA\SnannyOwncloudApi\Util\FileUtils;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\AppFramework\Http\DownloadResponse;
use OCP\AppFramework\Http\NotFoundResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\StreamResponse;
use OCP\IRequest;

const SOS_NAMESPACE = "http://www.opengis.net/sos/2.0";
const OM_NAMESPACE = "http://www.opengis.net/om/2.0";
const XLINK_NAMESPACE = "http://www.w3.org/1999/xlink";
const GML_NAMESPACE = "http://www.opengis.net/gml/3.2";

class OmController extends Controller
{

    private $omMapper;
    private $indexHistoryMapper;

    public function __construct($AppName, IRequest $request, ObservationModelMapper $omMapper, IndexHistoryMapper $indexHistoryMapper)
    {
        parent::__construct($AppName, $request);
        $this->omMapper = $omMapper;
        $this->indexHistoryMapper = $indexHistoryMapper;
    }

    /**
     * @NoCSRFRequired
     */
    public function updateIndex($uuid, $time, $status, $message, $indexedObservations, $fileId)
    {

        $data = new IndexHistory();
        $data->setUuid($uuid);
        $data->setTime($time);
        $data->setStatus($status);
        $data->setMessage($message);
        $data->setIndexedObservations($indexedObservations);
        $data->setFileId($fileId);

        $this->indexHistoryMapper->insert($data);

        return new JSONResponse(['uuid' => $uuid, 'time' => $time, 'status' => $status]);
    }

    /**
     * get file content from file_id
     * @NoCSRFRequired
     * @NoAdminRequired
     */
    public function info($uuid)
    {
        $data = array();
        $observation = $this->omMapper->getByIdOrUuid($uuid, $uuid);
        if ($observation !== null) {

            $data['uuid'] = $observation->getUuid();
            $data['name'] = $observation->getName();
            $data['description'] = $observation->getDescription();
            $data['systemUuid'] = $observation->getSystemUuid();
            $data['resultFile'] = $observation->getResultFile();
            $data['indexed'] = false;


            $indexedProperties = $this->indexHistoryMapper->getByUuid($uuid, $uuid);
            if($indexedProperties !== null){
                $data['index_history'] = array();

                foreach($indexedProperties as $index){
                    $data['indexed'] = true;
                    $arr = array(
                        'time'=>date('c',$index->getTime()/1000),
                        'status'=>($index->getStatus())?"success":"failure",
                        'succeded'=>$index->getStatus()

                    );
                    if($index->getStatus()){
                        $arr['indexedObservations'] = $index->getIndexedObservations();
                    }else{
                        $arr['message'] = $index->getMessage();
                    }
                    $data['index_history'][] = $arr;
                }
            }

        }
        return new JSONResponse($data);
    }

    /**
     * get file content from file_id
     * @NoCSRFRequired
     * @NoAdminRequired
     */
    public function detail($uuid)
    {

        $observation = $this->omMapper->getByUuid($uuid);

        if ($observation !== null) {

            $content = FileCacheDao::getContentByFileId($observation->getFileId());

            $parsed = OMParser::parse($content, $observation);
            $parsed['fileId'] = $observation->getFileId();

            return new JSONResponse($parsed);
        } else {
            return new JSONResponse(array('NotFound'));
        }

    }


    /**
     * get file content from file_id
     * @NoCSRFRequired
     *
     */
    public function downloadData($omid, $filename)
    {

        $cacheInfo = FileCacheDao::getCacheInfo($omid);
        //Get file information (storage urn and user)
        $fileInfo = FileCacheDao::getFileInfo($cacheInfo['storage'], $cacheInfo['path']);
        //Parse file om
        $dataFile = dirname($fileInfo['urn']) . '/' . $filename;
        if (file_exists($dataFile)) {
            return new StreamResponse($dataFile);
        }
        return new NotFoundResponse();
    }


    /**
     * get file content from uuid
     * @NoCSRFRequired
     * @NoAdminRequired
     * @PublicPage
     *
     */
    public function downloadResult($uuid)
    {
        $observation = $this->omMapper->getByIdOrUuid($uuid, $uuid);
        if ($observation !== null) {
            $cacheInfo = FileCacheDao::getCacheInfo($observation->getFileId());
            //Get file information (storage urn and user)
            $fileInfo = FileCacheDao::getFileInfo($cacheInfo['storage'], $cacheInfo['path']);
            //Parse file om
            $resultFileName = $observation->getResultFile();
            $dataFile = dirname($fileInfo['urn']) . '/' . $observation->getResultFile();
            if (file_exists($dataFile)) {
                return new DataDownloadResponse(file_get_contents($dataFile), $resultFileName, '');
            }
        }
        return new NotFoundResponse();
    }


    /**
     * get file content from file_id
     * @NoCSRFRequired
     */
    public function infoData($omid, $filename)
    {

        $cacheInfo = FileCacheDao::getCacheInfo($omid);
        //Get file information (storage urn and user)
        $fileInfo = FileCacheDao::getFileInfo($cacheInfo['storage'], $cacheInfo['path']);
        //Parse file om
        $dataFile = dirname($fileInfo['urn']) . '/' . $filename;
        if (file_exists($dataFile)) {
            return new JSONResponse(array('fileSize' => filesize($dataFile)));
        }
        return new NotFoundResponse();
    }

    /**
     * get file content from navigation nodeId
     * @NoCSRFRequired
     * @NoAdminRequired
     */
    public function infoFile($nodeId)
    {
        $cacheInfo = FileCacheDao::getCacheInfo($nodeId);
        $filename = basename($cacheInfo['path']);
        $observation = $this->omMapper->getByDataFileName($filename);
        if($observation){
            $data = [];
            $data['uuid'] = $observation->getUuid();
            $data['name'] = $observation->getName();
            $data['description'] = $observation->getDescription();
            $data['systemUuid'] = $observation->getSystemUuid();
            $data['resultFile'] = $observation->getResultFile();
            $data['indexed'] = false;
            return new JSONResponse(array('status'=>'success', 'data'=>$data));
        }
        return new JSONResponse(array('status'=>'failure'));
    }


    /**
     * get file content from navigation nodeId
     * @NoCSRFRequired
     * @NoAdminRequired
     */
    public function postFile($nodeId, $name, $description, $system)
    {
        return new JSONResponse(array('status'=>'success', 'data'=>array('name'=>$name, 'description'=>$description, 'system'=>$system)));
    }
}