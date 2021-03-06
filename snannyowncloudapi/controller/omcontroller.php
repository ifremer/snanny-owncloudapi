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
use OC\Files\Filesystem;
use OC\Share\Share;
use OCA\SnannyOwncloudApi\Config\Config;
use OCA\SnannyOwncloudApi\Db\DBUtil;
use OCA\SnannyOwncloudApi\Db\FileCacheDao;
use OCA\SnannyOwncloudApi\Db\IndexHistory;
use OCA\SnannyOwncloudApi\Db\IndexHistoryMapper;
use OCA\SnannyOwncloudApi\Db\ObservationModelMapper;
use OCA\SnannyOwncloudApi\Hooks\DelegateOmHook;
use OCA\SnannyOwncloudApi\Util\FileUtil;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\NotFoundResponse;
use OCP\AppFramework\Http\StreamResponse;
use OCP\Files;
use OCP\IRequest;


class OmController extends Controller
{

    const OBSERVATION_TEMPLATE = '/apps/snannyowncloudapi/templates/xml_om.xml';
    const OBSERVATION_TIME_TEMPLATE = '/apps/snannyowncloudapi/templates/xml_om_time.xml';
    private $omMapper;
    private $indexHistoryMapper;
    private $omHook;

    public function __construct($AppName, IRequest $request, ObservationModelMapper $omMapper, IndexHistoryMapper $indexHistoryMapper,  DelegateOmHook $omHook)
    {
        parent::__construct($AppName, $request);
        $this->omMapper = $omMapper;
        $this->indexHistoryMapper = $indexHistoryMapper;
        $this->omHook = $omHook;
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
            if ($indexedProperties !== null) {
                $data['index_history'] = array();

                foreach ($indexedProperties as $index) {
                    $data['indexed'] = true;
                    $arr = array(
                        'time' => date('c', $index->getTime() / 1000),
                        'status' => ($index->getStatus()) ? "success" : "failure",
                        'succeded' => $index->getStatus()

                    );
                    if ($index->getStatus()) {
                        $arr['indexedObservations'] = $index->getIndexedObservations();
                    } else {
                        $arr['message'] = $index->getMessage();
                    }
                    $data['index_history'][] = $arr;
                }
            }

        }
        return new JSONResponse($data);
    }

    /**
     * get file content from o&m uuid
     * @NoCSRFRequired
     *
     */
    public function omData($uuid)
    {
        $observation = $this->omMapper->getByUuid($uuid);
        if ($observation) {

            $cacheInfo = FileCacheDao::getCacheInfo($observation->getFileId());
            //Get file information (storage urn and user)
            $fileInfo = FileCacheDao::getFileInfo($cacheInfo['storage'], $cacheInfo['path'], $observation->getPharPath());
            //Get content
            $content = FileCacheDao::getContentByUrn($fileInfo['urn']);
            // Get shares
            //Requête pour récupérer les parents d'un fileId
            $fileId = $observation->getFileId();
            $shares = array();
            while ($fileId !== null && $fileId > 1) {
                $share = Share::getAllSharesForFileId($fileId);
                foreach ($share as $item) {
                    array_push($shares, $item);
                }
                $result = DBUtil::executeQuery("SELECT parent FROM *PREFIX*filecache where fileid=:fileid", array(':fileid' => $fileId));
                $fileId = null;
                if ($result != null) {
                    while ($row = $result->fetch()) {
                        $fileId = $row["parent"];
                    }
                }
            }
            // Return json data
            return new JSONResponse(array('user' => $fileInfo['user'], 'content' => $content, 'shares' => $shares));
        }
        return new NotFoundResponse();
    }


    /**
     * get file content from o&m uuid
     * @NoCSRFRequired
     *
     */
    public function downloadData($uuid)
    {
        $observation = $this->omMapper->getByUuid($uuid);
        if ($observation) {
            $cacheInfo = FileCacheDao::getCacheInfo($observation->getFileId());
            if ($cacheInfo) {
                //Get file information (storage urn and user)
                $fileInfo = FileCacheDao::getFileInfo($cacheInfo['storage'], $cacheInfo['path'], $observation->getPharPath());
                //Parse file om
                $dataFile = dirname($fileInfo['urn']) . '/' . $observation->getResultFile();
                if (file_exists($dataFile)) {
                    return new StreamResponse($dataFile);
                }
            }
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
        $observation = $this->omMapper->getByUuid($uuid);
        if ($observation !== null) {
            $cacheInfo = FileCacheDao::getCacheInfo($observation->getFileId());
            //Get file information (storage urn and user)
            $fileInfo = FileCacheDao::getFileInfo($cacheInfo['storage'], $cacheInfo['path'], $observation->getPharPath());
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
    public function infoData($uuid)
    {
        $observation = $this->omMapper->getByUuid($uuid);
        if ($observation !== null) {
            $cacheInfo = FileCacheDao::getCacheInfo($observation->getFileId());
            //Get file information (storage urn and user)
            $fileInfo = FileCacheDao::getFileInfo($cacheInfo['storage'], $cacheInfo['path'], $observation->getPharPath());
            //Parse file om
            $dataFile = dirname($fileInfo['urn']) . '/' . $observation->getResultFile();
            if (file_exists($dataFile)) {
                return new JSONResponse(array('fileSize' => filesize($dataFile), 'fileName' => $observation->getResultFile()));
            }
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
        $fileCachePath = preg_replace('"\.[^\.]*$"', '.xml', $cacheInfo['path']);
        $observation = $this->omMapper->getByDataFileName($filename, $fileCachePath);

        if ($observation) {
            $data = [];
            $data['uuid'] = $observation->getUuid();
            $data['name'] = $observation->getName();
            $data['description'] = $observation->getDescription();
            $data['systemUuid'] = $observation->getSystemUuid();
            $data['resultFile'] = $observation->getResultFile();
            $data['indexed'] = false;
            return new JSONResponse(array('status' => 'success', 'data' => $data));
        }
        return new JSONResponse(array('status' => 'failure'));
    }


    /**
     * Create an OML for nodeId
     * @NoCSRFRequired
     * @NoAdminRequired
     */
    public function postFile($nodeId, $name, $description, $system, $startDate = null, $endDate = null)
    {
        $node = FileCacheDao::getCacheInfo($nodeId);
        //Get file information (storage urn and user)
        $fileInfo = FileCacheDao::getFileInfo($node['storage'], $node['path']);

        if(FileUtil::endsWith($node['name'], array('.bz2', '.gz', '.zip'))){
            $mimetype = FileUtil::getArchiveContentMime($fileInfo);
        } else {
            $mimetype = \OC::$server->getMimeTypeLoader()->getMimetypeById($node['mimetype']);
        }
        //Fix bug owncloud type mime cause \OC::$server->getMimeTypeLoader()->getMimetypeById($node['mimetype'])
        // Doesn't return correct element
        if ($mimetype == 'application/octet-stream') {
            if (FileUtil::endsWith($mimetype, array('.nav', '.nc'))) {
                $mimetype = 'application/netcdf';
            }
        }

        $contentTime = '';
        if($startDate != null || $endDate != null) {
            $baseDateOC = getcwd() . self::OBSERVATION_TIME_TEMPLATE;
            $startDateTime = $startDate != null ? new \DateTime('@' . $startDate) : null;
            $endDateTime = $endDate != null ? new \DateTime('@' . $endDate) : null;
            $contentTime =  $this->templateIt(file_get_contents($baseDateOC), array(
                'startTime' => $startDateTime->format('Y-m-d\TH:i:sP\Z'),
                'endTime' => $endDateTime->format('Y-m-d\TH:i:sP\Z')
            ));
        }

        //Create the O&M file
        $baseOC = getcwd() . self::OBSERVATION_TEMPLATE;

        $uuid = uniqid('', true);

        $content = $this->templateIt(file_get_contents($baseOC), array(
            'uuid' => $uuid,
            'name' => $name,
            'description' => $description,
            'updateTime' => date(\DateTime::ISO8601, time()),
            'system' => Config::SENSORML_PERMALINK . $system,
            'resultFile' => $node['path'],
            'type' => $mimetype,
            'phenomenonTime' => $contentTime
        ));

        $baseUrn = dirname($fileInfo['urn']);

        $fileName = pathinfo($fileInfo['urn'], PATHINFO_FILENAME). '.xml';
        $filePath = $baseUrn . '/' . $fileName;
        
        file_put_contents($filePath, $content);

        $newNode = FileCacheDao::createFileNode($node, $fileName, filesize($filePath));
        $this->omHook->onUpdateOrCreate($newNode['fileid'], $content);


        $data = self::formatFileInfo($newNode);
        $data['status'] = 'success';


        return new JSONResponse($data);
    }


    private function templateIt($content, $arr)
    {
        foreach ($arr as $key => $value) {
            $content = str_replace('{' . $key . '}', $value, $content);
        }
        return $content;
    }

    /**
     * Formats the file info to be returned as JSON to the client.
     *
     * @param \OCP\Files\FileInfo $i
     * @return array formatted file info
     */
    public static function formatFileInfo($i) {
        $entry = array();
        $entry['id'] = $i['fileid'];
        $entry['parentId'] = $i['parent'];
        $entry['mtime'] = $i['mtime'] * 1000;
        $entry['isPreviewAvailable'] = true;
        $entry['name'] = $i['name'];
        $entry['permissions'] = $i['permissions'];
        $entry['mimetype'] = 'application/xml';
        $entry['originalname'] = $i['name'];
        $entry['size'] = $i['size'];
        $entry['type'] = $i['type'];
        $entry['etag'] = $i['etag'];
        $entry['uploadMaxFilesize'] = '';
        $entry['maxHumanFilesize'] = '';
        $entry['directory'] = Filesystem::normalizePath(dirname($i['path']));
        return $entry;
    }
}