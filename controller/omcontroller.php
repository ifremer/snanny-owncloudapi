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
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
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
    public function index()
    {
        return new JSONResponse(['data' => ['message' => 'snannyowncloudapi om']]);
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

}