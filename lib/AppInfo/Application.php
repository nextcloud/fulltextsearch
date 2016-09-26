<?php

/**
 * Nextcloud - nextant
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@pontapreta.net>
 * @copyright Maxence Lange 2016
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\Nextant\AppInfo;

use \OCA\Nextant\Controller\SettingsController;
use \OCA\Nextant\Db\IndexMapper;
use \OCA\Nextant\Events\FilesEvents;
use \OCA\Nextant\Hooks\FilesHooks;
use \OCA\Nextant\Provider\SearchProvider;
use \OCA\Nextant\Service\ConfigService;
use \OCA\Nextant\Service\MiscService;
use \OCA\Nextant\Service\FileService;
use \OCA\Nextant\Service\SolrService;
use \OCA\Nextant\Service\SolrAdminService;
use \OCA\Nextant\Service\SolrToolsService;
use \OCA\Nextant\Migration\NextantUpgrade;
use OCP\AppFramework\App;
use OCP\Util;
use Solarium\Solarium;

class Application extends App
{

    private $registeredDatabase = false;

    /**
     *
     * @param array $params            
     */
    public function __construct(array $params = array())
    {
        parent::__construct('nextant', $params);
        $container = $this->getContainer();
        
        /**
         * Controllers
         */
        $container->registerService('MiscService', function ($c) {
            return new MiscService($c->query('Logger'), $c->query('AppName'));
        });
        
        $container->registerService('ConfigService', function ($c) {
            return new ConfigService($c->query('AppName'), $c->query('CoreConfig'), $c->query('MiscService'));
        });
        
        $container->registerService('FileService', function ($c) {
            return new FileService($c->query('ConfigService'), $c->query('SolrService'), $c->query('SolrToolsService'), $c->query('MiscService'));
        });
        
        $container->registerService('SolrService', function ($c) {
            return new SolrService($c->query('SolariumClient'), $c->query('ConfigService'), $c->query('MiscService'));
        });
        
        $container->registerService('SolrAdminService', function ($c) {
            return new SolrAdminService($c->query('SolrService'), $c->query('ConfigService'), $c->query('MiscService'));
        });
        
        $container->registerService('SolrToolsService', function ($c) {
            return new SolrToolsService($c->query('SolrService'), $c->query('ConfigService'), $c->query('MiscService'));
        });
        
        $container->registerService('IndexMapper', function ($c) {
            return new IndexMapper($c->query('ServerContainer')
                ->getDb());
        });
        
        $container->registerService('FilesEvents', function ($c) {
            return new FilesEvents($c->query('ConfigService'), $c->query('UserId'), $c->query('FileService'), $c->query('SolrService'), $c->query('MiscService'));
        });
        
        // $container->query('IndexMapper')->insert(new IndexEntity(array(userid => 2, 'path' => '/toto', 'clef' => 'CLEFCLEF')));
        
        // SearchController is now useless
        // $container->registerService('SearchController', function ($c) {
        // return new SearchController($c->query('AppName'), $c->query('Request'), $c->query('ConfigService'), $c->query('UserId'), $c->query('MiscService'));
        // });
        
        $container->registerService('SettingsController', function ($c) {
            return new SettingsController($c->query('AppName'), $c->query('Request'), $c->query('ConfigService'), $c->query('SolrService'), $c->query('SolrToolsService'), $c->query('SolrAdminService'), $c->query('MiscService'));
        });
        
        /**
         * Providers
         */
        // Registering the SearchProvider as a Service seems useless.
        // $container->registerService('SearchProvider', function ($c) {
        // return new SearchProvider($c->query('MiscService'));
        // });
        
        /**
         * Core
         */
        $container->registerService('Logger', function ($c) {
            return $c->query('ServerContainer')
                ->getLogger();
        });
        $container->registerService('CoreConfig', function ($c) {
            return $c->query('ServerContainer')
                ->getConfig();
        });
        $container->registerService('UserId', function ($c) {
            $user = $c->query('ServerContainer')
                ->getUserSession()
                ->getUser();
            return is_null($user) ? '' : $user->getUID();
        });
        
        $container->registerService('UserManager', function ($c) {
            return \OC::$server->getUserManager();
        });
        
        $container->registerService('GroupManager', function ($c) {
            return \OC::$server->getGroupManager();
        });
        
        $container->registerService('UserFolder', function ($c) {
            return \OC::$server->getUserFolder();
        });
        
        $container->registerService('RootFolder', function ($c) {
            return $c->getServer()
                ->getRootFolder();
        });
        
        $container->registerService('SolariumClient', function ($c) {
            $toS = $c->query('ConfigService')
                ->toSolarium();
            if (! $toS)
                return false;
            return new \Solarium\Client($toS);
        });
        
        /**
         * Migration scripts
         */
        $container->registerService('OCA\Nextant\Migration\NextantUpgrade', function ($c) {
            return new NextantUpgrade($c->query('ConfigService'), $c->query('SolrService'), $c->query('SolrAdminService'));
        });
    }

    public function registerFilesHooks()
    {
        Util::connectHook('OC_Filesystem', 'post_create', '\OCA\Nextant\Hooks\FilesHooks', 'fileCreated');
        Util::connectHook('OC_Filesystem', 'post_update', '\OCA\Nextant\Hooks\FilesHooks', 'fileUpdated');
        Util::connectHook('OC_Filesystem', 'post_rename', '\OCA\Nextant\Hooks\FilesHooks', 'fileRenamed');
        Util::connectHook('OC_Filesystem', 'delete', '\OCA\Nextant\Hooks\FilesHooks', 'fileTrashed');
        Util::connectHook('\OCA\Files_Trashbin\Trashbin', 'post_restore', '\OCA\Nextant\Hooks\FilesHooks', 'fileRestored');
        Util::connectHook('\OCP\Trashbin', 'preDelete', '\OCA\Nextant\Hooks\FilesHooks', 'fileDeleted');
        Util::connectHook('OCP\Share', 'post_shared', '\OCA\Nextant\Hooks\FilesHooks', 'fileShared');
        Util::connectHook('OCP\Share', 'post_unshare', '\OCA\Nextant\Hooks\FilesHooks', 'fileUnshared');
    }

    public function registerSearchProvider()
    {
        \OC::$server->getSearch()->registerProvider('\OCA\Nextant\Provider\SearchProvider', array(
            'apps' => array(
                'files'
            )
        ));
    }

    public function registerSettingsAdmin()
    {
        \OCP\App::registerAdmin($this->getContainer()->query('AppName'), 'settings/admin');
    }
    
    // public function registerNavigation()
    // {
    // $appName = $this->getContainer()->getAppName();
    // $server = $this->getContainer()->getServer();
    
    // $this->getContainer()
    // ->query('MiscService')
    // ->log('registerNavigation()', 2);
    // $server->getNavigationManager()->add(array(
    // 'id' => $appName,
    // 'order' => 5,
    // 'href' => $server->getURLGenerator()
    // ->linkToRoute('nextant.page.index'),
    // 'icon' => $server->getURLGenerator()
    // ->imagePath($appName, 'app.svg'),
    // 'name' => $server->getL10N($appName)
    // ->t('Nextant')
    // ));
    // }
}

