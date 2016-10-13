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
namespace OCA\Nextant\Controller;

use \OCA\Nextant\Service\ConfigService;
use \OCA\Nextant\Service\SolrService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;

class SettingsController extends Controller
{

    private $configService;

    private $miscService;

    private $solrService;

    private $solrTools;

    private $solrAdmin;

    private $solr_url;

    private $solr_core;

    public function __construct($appName, IRequest $request, ConfigService $configService, $solrService, $solrTools, $solrAdmin, $miscService)
    {
        parent::__construct($appName, $request);
        $this->configService = $configService;
        $this->solrService = $solrService;
        $this->solrTools = $solrTools;
        $this->solrAdmin = $solrAdmin;
        $this->miscService = $miscService;
    }

    /**
     * @NoCSRFRequired
     */
    public function index()
    {
        $params = [
            'solr_url' => $this->configService->getAppValue('solr_url'),
            'solr_core' => $this->configService->getAppValue('solr_core')
        ];
        
        return new TemplateResponse($this->appName, 'settings.admin', $params, 'blank');
    }

    public function forceIndex()
    {
        $this->configService->needIndexFiles(true);
    }

    public function updateSubOptions($instant)
    {
        $response = array(
            'instant' => $instant,
            'configured' => $this->configService->getAppValue('configured'),
            'ping' => $this->solrAdmin->ping($error),
            'solr_url' => $this->configService->getAppValue('solr_url'),
            'solr_core' => $this->configService->getAppValue('solr_core'),
            'nextant_version' => $this->configService->getAppValue('installed_version') . ' (beta)',
            'index_files_last' => $this->configService->getAppValue('index_files_last'),
            'index_files_last_format' => date('r', $this->configService->getAppValue('index_files_last')),
            'index_files_needed' => $this->configService->getAppValue('index_files_needed'),
            'index_files_max_size' => $this->configService->getAppValue('index_files_max_size'),
            'index_files_live_extract' => $this->configService->getAppValue('index_files_live_extract'),
            'index_files_live_update' => $this->configService->getAppValue('index_files_live_update'),
            'index_files_external_index' => $this->configService->getAppValue('index_files_external_index'),
            'display_result' => $this->configService->getAppValue('display_result'),
            'current_docs' => $this->solrTools->count('files', $error),
            'bookmarks_app_enabled' => (\OCP\App::isEnabled('bookmarks')),
            'index_bookmarks' => $this->configService->getAppValue('index_bookmarks'),
            'index_bookmarks_needed' => $this->configService->getAppValue('index_bookmarks_needed'),
            'index_bookmarks_last' => $this->configService->getAppValue('index_bookmarks_last'),
            'index_bookmarks_last_format' => date('r', $this->configService->getAppValue('index_bookmarks_last')),
            'index_locked' => $this->configService->getAppValue('index_locked')
        );
        
        return $response;
    }

    public function setOptions($index_files_live_extract, $index_files_live_update, $index_files_max_size, $index_files_external_index, $index_bookmarks, $display_result, $index_files_needed)
    {
        $this->configService->setAppValue('index_files_live_extract', $index_files_live_extract);
        $this->configService->setAppValue('index_files_live_update', $index_files_live_update);
        $this->configService->setAppValue('index_files_external_index', $index_files_external_index);
        $this->configService->setAppValue('index_files_max_size', $index_files_max_size);
        $this->configService->setAppValue('index_bookmarks', $index_bookmarks);
        $this->configService->setAppValue('display_result', $display_result);
        if ($index_files_needed == 1)
            $this->configService->needIndexFiles(true, true);
        
        return $this->updateSubOptions(false);
    }

    public function setSettings($solr_url, $solr_core, $command)
    {
        $this->solr_url = $solr_url;
        $this->solr_core = $solr_core;
        
        $tmpConfig = array(
            'solr_url' => $solr_url,
            'solr_core' => $solr_core
        );
        
        // testing with use __nextant_test_owner from the group __nextant_share_group
        $this->solrService->setOwner('__nextant_test_owner', array(
            '__nextant_share_group'
        ));
        
        $message = '';
        $result = false;
        if (! $this->solrService->setClient($tmpConfig))
            $message = 'The format of your address is not correct';
        else {
            
            switch ($command) {
                case 'ping':
                    $result = $this->test_ping($message);
                    break;
                
                case 'schema':
                    $result = $this->test_schema($message);
                    break;
                
                case 'extract':
                    $result = $this->test_extract($message);
                    break;
                
                case 'update':
                    $result = $this->test_update($message);
                    break;
                
                case 'search':
                    // if ($this->test_search(SolrService::SEARCH_OWNER, SolrService::SEARCH_TRASHBIN_ALL, $message) && $this->test_search(SolrService::SEARCH_SHARED, SolrService::SEARCH_TRASHBIN_ALL, $message) && $this->test_search(SolrService::SEARCH_SHARED_GROUP, SolrService::SEARCH_TRASHBIN_ALL, $message) && $this->test_search(SolrService::SEARCH_OWNER, SolrService::SEARCH_TRASHBIN_ONLY, $message))
                    $result = $this->test_search($message);
                    break;
                
                case 'delete':
                    $result = $this->test_delete($message);
                    break;
                
                case 'save':
                    $result = $this->save($message);
                    break;
            }
        }
        
        $response = array(
            'command' => $command,
            'status' => $result ? 'success' : 'failure',
            'message' => $message
        );
        
        return $response;
    }
    
    // Wiki Error 9
    private function test_ping(&$message)
    {
        if ($this->solrAdmin->ping($error)) {
            $message = 'Apache Solr is up, running and responding to our ping query';
            return true;
        }
        
        $message = 'Apache Solr is not responding to our ping query (Error #' . $error . ')';
        return false;
    }

    private function test_schema(&$message)
    {
        if ($this->solrAdmin->checkSchema(true, $error)) {
            $message = 'Schema is fine';
            return true;
        }
        
        $message = 'Were not able to verify/fix your schema integrity (Error #' . $error . ')';
        return false;
    }

    private function test_extract(&$message)
    {
        $testFile = __DIR__ . '/../../LICENSE';
        
        if ($this->solrService->extractFile($testFile, 'test', 'nextant_test', '/LICENSE', 1234567890, $error)) {
            $message = 'Text successfully extracted';
            return true;
        }
        
        $message = 'Extract failed. Please check the configuration of your Solr server (Error #' . $error . ')';
        return false;
    }

    private function test_update(&$message)
    {
        $testUpdate = array(
            'id' => 'nextant_test',
            'path' => '/LICENSE2',
            'share_users' => array(
                '__nextant_test_owner'
            ),
            'share_groups' => array(
                '__nextant_share_group'
            ),
            'deleted' => false
        );
        
        if (! $this->solrTools->updateDocuments('test', array(
            $testUpdate
        ), $error)) {
            $message = 'Error Updating field (Error #' . $error . ')';
            return false;
        }
        
        $message = 'Document successfully updated';
        return true;
    }

    private function test_search(&$message)
    {
        $keyword = 'LICENSE';
        if ($result = $this->solrService->search($keyword, null, $error)) {
            if (sizeof($result) > 0) {
                
                foreach ($result as $doc) {
                    if ($doc['id'] == 'nextant_test') {
                        $message = 'Found exactly what we were looking for';
                        return true;
                    }
                }
                
                // CHECK ID DOCUMENT
                $message = 'We found something, but not what we were expecting.';
                return false;
            }
            $message = 'Canno\'t find any document';
            return false;
        }
        
        $message = 'Search failed. Please check the configuration of your Solr server (Error #' . $error . ')';
        return false;
    }

    private function test_delete(&$message)
    {
        if ($this->solrTools->removeDocument('test', 'nextant_test')) {
            $message = 'Test document deleted';
            return true;
        }
        
        $message = 'We could not delete our test document. Please check the configuration of your Solr server (Error #' . $error . ')';
        return false;
    }

    private function save(&$message)
    {
        if (! is_null($this->solr_url) && ! is_null($this->solr_core)) {
            $this->configService->setAppValue('solr_url', $this->solr_url);
            $this->configService->setAppValue('solr_core', $this->solr_core);
            if ($this->configService->getAppValue('configured') != 1)
                $this->configService->setAppValue('configured', '2');
            
            $message = "Your configuration has been saved";
            return true;
        }
        
        $message = 'Configuration failed to be saved. Please reload this page.';
        return false;
    }
}