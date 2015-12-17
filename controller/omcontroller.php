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

    public function __construct($AppName, IRequest $request, Mapper $omMapper)
    {
        parent::__construct($AppName, $request);
        $this->omMapper = $omMapper;
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function index()
    {
        return new JSONResponse(['data' => ['message' => 'snannyowncloudapi om']]);
    }

    /**
     * get file content from file_id
     * @NoCSRFRequired
     */
    public function info($uuid)
    {
        $data = array();
        $observation = $this->omMapper->getByUuid($uuid);

        if ($observation !== null) {
            $data['uuid'] = $observation->getUuid();
            $data['name'] = $observation->getName();
            $data['description'] = $observation->getDescription();
            $data['systemUuid'] = $observation->getSystemUuid();
            $data['resultFile'] = $observation->getResultFile();
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