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

namespace OCA\SnannyOwncloudApi\AppInfo;

use OCA\SnannyOwncloudApi\Controller\ApiController;
use OCA\SnannyOwncloudApi\Controller\OmController;
use OCA\SnannyOwncloudApi\Controller\UserController;
use OCA\SnannyOwncloudApi\Db\IndexHistoryMapper;
use OCA\SnannyOwncloudApi\Db\ObservationModelMapper;
use OCA\SnannyOwncloudApi\Db\SystemAncestorsMapper;
use OCA\SnannyOwncloudApi\Db\SystemMapper;
use OCA\SnannyOwncloudApi\Hooks\DelegateOmHook;
use OCA\SnannyOwncloudApi\Hooks\DelegateSensorMLHook;
use OCA\SnannyOwncloudApi\Hooks\FileHook;
use OCA\SnannyOwncloudApi\Hooks\OmHook;
use OCA\SnannyOwncloudApi\Hooks\SensorMLHook;
use OCP\AppFramework\App;
use OCP\Util;


class Application extends App{
	 public function __construct(array $urlParams=array()){
        parent::__construct('snannyowncloudapi', $urlParams);
        $container = $this->getContainer();

        $container->registerService('Logger', function($c){
            return $c->query('ServerContainer')->getLogger();
        });

        /**ApiController*/
        $container->registerService('ApiController', function($c){
        	return new ApiController(
        		$c->query('AppName'),
                $c->query('Request'),
                $c->query('SystemMapper'),
                $c->query('SystemAncestorsMapper'));
        });


         /**OmController*/
         $container->registerService('OmController', function($c){
             return new OmController(
                 $c->query('AppName'),
                 $c->query('Request'),
                 $c->query('ObservationModelMapper'),
                 $c->query('IndexHistoryMapper'),
                 $c->query('DelegateOmHook'));
         });

         $container->registerService('UserController', function($c){
             return new UserController(
                 $c->query('AppName'),
                 $c->query('Request')
             );
         });

         /**Mappers**/
        $container->registerService('SystemMapper', function($c) {
            return new SystemMapper($c->query('ServerContainer')->getDb());
        });
        $container->registerService('SystemAncestorsMapper', function($c) {
            return new SystemAncestorsMapper($c->query('ServerContainer')->getDb());
        });
         $container->registerService('ObservationModelMapper', function($c) {
             return new ObservationModelMapper($c->query('ServerContainer')->getDb());
         });

         $container->registerService('IndexHistoryMapper', function($c) {
             return new IndexHistoryMapper($c->query('ServerContainer')->getDb());
         });

         //Delegate Hook
         $container->registerService('DelegateOmHook', function($c){
             return new DelegateOmHook(
                 $c->query('ObservationModelMapper')
             );
         });

         $container->registerService('DelegateSensorMLHook', function($c){
             return new DelegateSensorMLHook(
                 $c->query('SystemMapper'),
                 $c->query('SystemAncestorsMapper')
             );
         });

         // Hooks
        $container->registerService('FileHook', function($c){
        	return new FileHook(
                $c->query('ServerContainer')->getRootFolder(), 
                $c->query('DelegateSensorMLHook'),
                $c->query('DelegateOmHook'),
                $c->query('Logger')
                );
        });

    }
}
$app = new Application();
$app->getContainer()->query('FileHook')->register();

Util::connectHook('OCP\Share', 'post_shared', 'OCA\SnannyOwncloudApi\Hooks\DelegateOmHook', 'onShare');
Util::connectHook('OCP\Share', 'post_unshare', 'OCA\SnannyOwncloudApi\Hooks\DelegateOmHook', 'onUnshare');
