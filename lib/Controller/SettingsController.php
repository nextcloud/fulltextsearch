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
use \OCA\Nextant\Items\ItemDocument;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;

class SettingsController extends Controller
{

    private $configService;

    private $miscService;

    private $indexService;

    private $solrService;

    private $solrTools;

    private $solrAdmin;

    private $solr_url;

    private $solr_core;

    public function __construct($appName, IRequest $request, ConfigService $configService, $indexService, $solrService, $solrTools, $solrAdmin, $miscService)
    {
        parent::__construct($appName, $request);
        $this->configService = $configService;
        $this->indexService = $indexService;
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

    public function updateSubOptions($instant, $source = '')
    {
        $response = array(
            'instant' => $instant,
            'configured' => $this->configService->getAppValue('configured'),
            'ping' => $this->solrAdmin->ping($error),
            'solr_url' => $this->configService->getAppValue('solr_url'),
            'solr_core' => $this->configService->getAppValue('solr_core'),
            'nextant_version' => $this->configService->getAppValue('installed_version') . ' (beta)',
            'index_files' => $this->configService->getAppValue('index_files'),
            'index_files_needed' => $this->configService->getAppValue('index_files_needed'),
            'index_files_max_size' => $this->configService->getAppValue('index_files_max_size'),
            'index_files_live' => $this->configService->getAppValue('index_files_live'),
            'index_files_external_index' => $this->configService->getAppValue('index_files_external_index'),
            'display_result' => $this->configService->getAppValue('display_result'),
            'current_docs' => $this->solrTools->count('files', $error),
            'bookmarks_app_enabled' => (\OCP\App::isEnabled('bookmarks')),
            'index_bookmarks' => $this->configService->getAppValue('index_bookmarks'),
            'index_bookmarks_needed' => $this->configService->getAppValue('index_bookmarks_needed'),
            'index_delay' => $this->configService->getAppValue('index_delay'),
            'index_locked' => $this->configService->getAppValue('index_locked'),
            'index_files_last' => $this->configService->getAppValue('index_files_last'),
            'index_files_last_format' => date('r', $this->configService->getAppValue('index_files_last')),
            'index_bookmarks_last' => $this->configService->getAppValue('index_bookmarks_last'),
            'index_bookmarks_last_format' => date('r', $this->configService->getAppValue('index_bookmarks_last')),
            'source' => $source
        );
        
        return $response;
    }

    public function setOptionsFiles($index_files, $index_files_live, $index_files_max_size, $index_files_external_index)
    {
        $this->configService->setAppValue('index_files', $index_files);
        $this->configService->setAppValue('index_files_live', $index_files_live);
        $this->configService->setAppValue('index_files_external_index', $index_files_external_index);
        $this->configService->setAppValue('index_files_max_size', $index_files_max_size);
        
        return $this->updateSubOptions(false, 'files');
    }

    public function setOptionsBookmarks($index_bookmarks)
    {
        $this->configService->setAppValue('index_bookmarks', $index_bookmarks);
        
        return $this->updateSubOptions(false, 'bookmarks');
    }

    public function setOptionsStatus($index_delay, $display_result, $force_index)
    {
        if ($index_delay > 0)
            $this->configService->setAppValue('index_delay', $index_delay);
        $this->configService->setAppValue('display_result', $display_result);
        if ($force_index === '1') {
            $this->configService->setAppValue('configured', '1');
            $this->configService->needIndexFiles(true);
        }
        
        return $this->updateSubOptions(false, 'status');
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
        $doc = new ItemDocument(ItemDocument::TYPE_TEST, 1);
        $doc->setAbsolutePath($testFile);
        $doc->setPath('/LICENSE');
        $doc->setMTime(time());
        
        $data = array(
            $doc
        );
        $solrDocs = null;
        $this->indexService->extract(ItemDocument::TYPE_TEST, '_nextant_test', $data, $solrDocs, true, $error);
        
        if ($doc->isProcessed()) {
            $message = 'Text successfully extracted';
            return true;
        }
        
        $message = 'Extract failed. Please check the configuration of your Solr server (Error #' . $error . ')';
        return false;
    }

    private function test_update(&$message)
    {
        $asource = $this->indexService->getDocuments(ItemDocument::TYPE_TEST, '_nextant_test', 1, $error);
        
        if (sizeof($asource) != 1) {
            $message('Error Updating field - Can\'t find original document');
            return false;
        }
        
        $source = $asource[0];
        $final = new ItemDocument(ItemDocument::TYPE_TEST, 1);
        $final->setOwner('_nextant_test');
        $final->setPath('/LICENSE2');
        $final->setShare(array(
            'nextant_test_share'
        ));
        $final->setShareGroup(array(
            'nextant_test_share_group'
        ));
        $final->deleted(false);
        
        $this->solrTools->updateDocument($final, $source, true, $error);
        
        if (! $source->isUpdated()) {
            $message = 'Error Updating field (Error #' . $error . ')';
            return false;
        }
        
        $message = 'Document successfully updated';
        return true;
    }

    private function test_search(&$message)
    {
        $keyword = 'LICENSE';
        $this->solrService->setOwner('_nextant_test');
        if ($result = $this->solrService->search($keyword, array(), $error)) {
            if (sizeof($result) > 0) {
                
                foreach ($result as $doc) {
                    if ($doc['type'] === ItemDocument::TYPE_TEST && $doc['id'] === '1') {
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
        $doc = new ItemDocument(ItemDocument::TYPE_TEST, 1);
        $this->solrTools->removeDocument($doc);
        if ($doc->isRemoved()) {
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
            if ($this->configService->getAppValue('configured') !== '1')
                $this->configService->setAppValue('configured', '2');
            
            $message = "Your configuration has been saved";
            return true;
        }
        
        $message = 'Configuration failed to be saved. Please reload this page.';
        return false;
    }
}