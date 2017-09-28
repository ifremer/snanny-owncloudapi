<?php
/**
 * ownCloud - snannyowncloudapi
 *
 * Controller for all /users endpoint REST methods
 *
 * @author Geoffrey PAGNIEZ <gpagniez@asi.fr>
 */

namespace OCA\SnannyOwncloudApi\Controller;


use OCA\SnannyOwncloudApi\Db\UserDao;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;

class UserController extends Controller
{

    function __construct($AppName, IRequest $request)
    {
        parent::__construct($AppName, $request);
    }

    /**
     * Return all groups linked to the given user id
     * @NoCSRFRequired
     */
    public function getUserGroups($id)
    {
        $statement = UserDao::getUserGroups($id);
        $groups = array();
        if($statement != null) {
            while ($row = $statement->fetch()) {
                array_push($groups, $row["gid"]);
            }
        }
        return new JSONResponse($groups);

    }
}