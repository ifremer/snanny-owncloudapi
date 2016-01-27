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

/**
 * Create your routes in here. The name is the lowercase name of the controller
 * without the controller part, the stuff after the hash is the method.
 * e.g. api#index -> OCA\SnannyOwncloudApi\Controller\ApiController->index()
 *
 * The controller class has to be registered in the application.php file since
 * it's instantiated in there
 */
return [
    'routes' => [
        //Batch get files and content
        ['name' => 'api#files', 'url' => '/files', 'verb' => 'GET'],
        ['name' => 'api#lastfailure', 'url' => '/lastfailure', 'verb' => 'GET'],

        # tmp
        ['name' => 'api#tarInfo', 'url' => '/', 'verb' => 'GET'],

        //Public sensorML
        ['name' => 'api#sensorML', 'url' => '/sml/{uuid}', 'verb' => 'GET'],
        ['name' => 'api#ancestorSML', 'url' => '/sml/{uuid}/ancestors', 'verb' => 'GET'],
        ['name' => 'api#infoSML', 'url' => '/sml/{uuid}/info', 'verb' => 'GET'],
        ['name' => 'api#downloadSensorML', 'url' => '/sml/{uuid}/download', 'verb' => 'GET'],
        ['name' => 'api#autocompleteSensors', 'url' => '/sml', 'verb' => 'POST'],

        //Get OM Informations
        ['name' => 'om#updateIndex', 'url' => '/om', 'verb' => 'POST'],
        ['name' => 'om#info', 'url' => '/om/{uuid}/info', 'verb' => 'GET'],
        ['name' => 'om#omData', 'url' => '/om/{uuid}', 'verb' => 'GET'],

        //Nav files
        ['name' => 'om#infoData', 'url' => '/om/{uuid}/filesize', 'verb' => 'GET'],
        ['name' => 'om#downloadData', 'url' => '/om/{uuid}/stream', 'verb' => 'GET'],
        ['name' => 'om#infoFile', 'url'=>'/data/{nodeId}/info', 'verb'=>'GET'],
        ['name' => 'om#postFile', 'url'=>'/data/{nodeId}', 'verb'=>'POST'],
        ['name' => 'om#downloadResult', 'url'=>'/data/{uuid}/download', 'verb'=>'GET']

    ]
];