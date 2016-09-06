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

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use \OCA\Nextant\Service\ConfigService;
use \OCA\Nextant\Service\SolrService;

class SettingsController extends Controller
{

    private $configService;

    private $miscService;

    private $solrService;

    private $solr_url;

    private $solr_core;

    public function __construct($appName, IRequest $request, ConfigService $configService, $solrService, $miscService)
    {
        parent::__construct($appName, $request);
        $this->configService = $configService;
        $this->solrService = $solrService;
        $this->miscService = $miscService;
    }

    /**
     * @NoCSRFRequired
     */
    public function index()
    {
        $documentsCount = $this->solrService->count($error);
        
        $params = [
            'current_docs' => $documentsCount,
            'solr_url' => $this->configService->getAppValue('solr_url'),
            'solr_core' => $this->configService->getAppValue('solr_core')
        ];
        return new TemplateResponse($this->appName, 'settings.admin', $params, 'blank');
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
                
                case 'extract':
                    $result = $this->test_extract($message);
                    break;
                
                case 'search':
                    if ($this->test_search(SolrService::SEARCH_OWNER, SolrService::SEARCH_TRASHBIN_ALL, $message) && $this->test_search(SolrService::SEARCH_SHARED, SolrService::SEARCH_TRASHBIN_ALL, $message) && $this->test_search(SolrService::SEARCH_SHARED_GROUP, SolrService::SEARCH_TRASHBIN_ALL, $message) && $this->test_search(SolrService::SEARCH_OWNER, SolrService::SEARCH_TRASHBIN_ONLY, $message))
                        $result = true;
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
            'data' => array(
                'message' => $message
            )
        );
        
        return $response;
    }
    
    // Wiki Error 9
    private function test_ping(&$message)
    {
        if ($this->solrService->ping($error)) {
            $message = 'Apache Solr is up, running and responding to our ping query';
            return true;
        }
        
        $message = 'Apache Solr is not responding to our ping query (Error #' . $error . ')';
        return false;
    }

    private function test_extract(&$message)
    {
        $testFile = __DIR__ . '/../../LICENSE';
        $shares = array(
            'users' => array(
                '__nextant_test_owner'
            ),
            'groups' => array(
                '__nextant_share_group'
            )
        );
        
        if ($this->solrService->extractFile($testFile, '__nextant_test', 'text/plain', $shares, true, $error)) {
            $message = 'Text were successfully extracted';
            return true;
        }
        
        $message = 'Extract failed. Please check the configuration of your Solr server (Error #' . $error . ')';
        return false;
    }

    private function test_search($type, $deleted, &$message)
    {
        $keyword = 'LICENSE';
        if ($result = $this->solrService->search($keyword, $type, $deleted, $error)) {
            if (sizeof($result) > 0) {
                
                foreach ($result as $doc) {
                    if ($doc['id'] == '__nextant_test') {
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
        
        $message = 'Search failed. Please check the configuration of your Solr server (Error #' . $error . '.' . $type . '.' . $deleted . ')';
        return false;
    }

    private function test_delete(&$message)
    {
        if ($this->solrService->removeDocument('__nextant_test')) {
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
            $message = "Your configuration has been saved";
            return true;
        }
        
        $message = 'Configuration failed to be saved. Please reload this page.';
        return false;
    }
}