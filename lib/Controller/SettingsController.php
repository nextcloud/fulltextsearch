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

    private $solr_timeout;

    private $l10n;

    public function __construct($appName, IRequest $request, ConfigService $configService, $indexService, $solrService, $solrTools, $solrAdmin, $l10n, $miscService)
    {
        parent::__construct($appName, $request);
        $this->configService = $configService;
        $this->indexService = $indexService;
        $this->solrService = $solrService;
        $this->solrTools = $solrTools;
        $this->solrAdmin = $solrAdmin;
        $this->l10n = $l10n;
        $this->miscService = $miscService;
    }

    /**
     * @NoCSRFRequired
     */
    public function index()
    {
        $params = [
            'solr_url' => $this->configService->getAppValue('solr_url'),
            'solr_core' => $this->configService->getAppValue('solr_core'),
            'solr_timeout' => $this->configService->getAppValue('solr_timeout')
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
            'ping' => $this->solrAdmin->ping(),
            'solr_url' => $this->configService->getAppValue('solr_url'),
            'solr_core' => $this->configService->getAppValue('solr_core'),
            'solr_timeout' => $this->configService->getAppValue('solr_timeout'),
            'nextant_version' => $this->configService->getAppValue('installed_version'),
            'index_files' => $this->configService->getAppValue('index_files'),
            'index_files_needed' => $this->configService->getAppValue('index_files_needed'),
            'index_files_max_size' => $this->configService->getAppValue('index_files_max_size'),
            'index_files_tree' => $this->configService->getAppValue('index_files_tree'),
            'index_files_nextant_only' => $this->configService->getAppValue('index_files_nextant_only'),
            'index_files_sharelink' => $this->configService->getAppValue('index_files_sharelink'),
            'index_files_federated' => $this->configService->getAppValue('index_files_federated'),
            'index_files_external' => $this->configService->getAppValue('index_files_external'),
            'index_files_encrypted' => $this->configService->getAppValue('index_files_encrypted'),
            'index_files_filters_text' => $this->configService->getAppValue('index_files_filters_text'),
            'index_files_filters_pdf' => $this->configService->getAppValue('index_files_filters_pdf'),
            'index_files_filters_office' => $this->configService->getAppValue('index_files_filters_office'),
            'index_files_filters_image' => $this->configService->getAppValue('index_files_filters_image'),
            'index_files_filters_audio' => $this->configService->getAppValue('index_files_filters_audio'),
            'index_files_filters_extensions' => self::FileFiltersExtensionsAsList($this->configService->getAppValue('index_files_filters_extensions')),
            'current_docs' => $this->solrTools->count('files'),
            'current_segments' => (($this->solrTools->getInfoCore()) ? $this->solrTools->getInfoCore()->index->segmentCount : ''),
            'files_external_app_enabled' => (\OCP\App::isEnabled('files_external')),
            'bookmarks_app_enabled' => (\OCP\App::isEnabled('bookmarks')),
            'index_bookmarks' => $this->configService->getAppValue('index_bookmarks'),
            'index_bookmarks_needed' => $this->configService->getAppValue('index_bookmarks_needed'),
            'resource_level' => $this->configService->getAppValue('resource_level'),
            'index_live' => $this->configService->getAppValue('index_live'),
            'index_live_queuekey' => $this->configService->getAppValue('index_live_queuekey'),
            'index_delay' => $this->configService->getAppValue('index_delay'),
            'index_locked' => $this->configService->getAppValue('index_locked'),
            'index_files_last' => $this->configService->getAppValue('index_files_last'),
            'index_files_last_format' => date('r', $this->configService->getAppValue('index_files_last')),
            'index_bookmarks_last' => $this->configService->getAppValue('index_bookmarks_last'),
            'index_bookmarks_last_format' => date('r', $this->configService->getAppValue('index_bookmarks_last')),
            'ext_sysvmsg_loaded' => extension_loaded('sysvmsg'),
            'source' => $source
        );
        
        return $response;
    }

    public function setOptionsFiles($index_files, $index_files_max_size, $index_files_tree, $index_files_nextant_only, $index_files_sharelink, $index_files_federated, $index_files_external, $index_files_encrypted, $index_files_filters)
    {
        $this->configService->setAppValue('index_files', $index_files);
        $this->configService->setAppValue('index_files_tree', $index_files_tree);
        $this->configService->setAppValue('index_files_nextant_only', $index_files_nextant_only);
        $this->configService->setAppValue('index_files_sharelink', $index_files_sharelink);
        $this->configService->setAppValue('index_files_federated', $index_files_federated);
        $this->configService->setAppValue('index_files_external', $index_files_external);
        $this->configService->setAppValue('index_files_encrypted', $index_files_encrypted);
        $this->configService->setAppValue('index_files_max_size', $index_files_max_size);
        $this->configService->setAppValue('index_files_filters', $index_files_filters);
        
        return $this->updateSubOptions(false, 'files');
    }

    public function setOptionsFilesFilters($index_files_filters_text, $index_files_filters_pdf, $index_files_filters_office, $index_files_filters_image, $index_files_filters_audio, $index_files_filters_extensions)
    {
        $this->configService->setAppValue('index_files_filters_text', $index_files_filters_text);
        $this->configService->setAppValue('index_files_filters_pdf', $index_files_filters_pdf);
        $this->configService->setAppValue('index_files_filters_office', $index_files_filters_office);
        $this->configService->setAppValue('index_files_filters_image', $index_files_filters_image);
        $this->configService->setAppValue('index_files_filters_audio', $index_files_filters_audio);
        
        $this->configService->setAppValue('index_files_filters_extensions', self::FileFiltersExtensionsAsString($index_files_filters_extensions));
        
        return $this->updateSubOptions(false, 'files_filters');
    }

    public function setOptionsBookmarks($index_bookmarks)
    {
        $this->configService->setAppValue('index_bookmarks', $index_bookmarks);
        
        return $this->updateSubOptions(false, 'bookmarks');
    }

    public function setOptionsStatus($resource_level, $index_live, $index_delay, $force_index)
    {
        $this->configService->setAppValue('resource_level', $resource_level);
        
        if ($index_live === '1') {
            if (extension_loaded('sysvmsg')) {
                
                if ($this->configService->getAppValue('index_live') !== '1')
                    $this->configService->setAppValue('index_live_queuekey', rand(20000, 990000));
                
                $this->configService->setAppValue('index_live', $index_live);
            }
        } else {
            $this->configService->setAppValue('index_live', $index_live);
        }
        
        if ($index_delay > 0)
            $this->configService->setAppValue('index_delay', $index_delay);
        
        if ($force_index === '1') {
            $this->configService->setAppValue('configured', '1');
            $this->configService->needIndexFiles(true);
        }
        
        return $this->updateSubOptions(false, 'status');
    }

    public function setSettings($solr_url, $solr_core, $solr_timeout, $command)
    {
        $this->solr_url = $solr_url;
        $this->solr_core = $solr_core;
        $this->solr_timeout = $solr_timeout;
        
        $tmpConfig = array(
            'solr_url' => $solr_url,
            'solr_core' => $solr_core,
            'solr_timeout' => $solr_timeout
        );
        
        // testing with use __nextant_test_owner from the group __nextant_share_group
        $this->solrService->setOwner('__nextant_test_owner', array(
            '__nextant_share_group'
        ));
        
        $message = '';
        $result = false;
        if (! $this->solrService->setClient($tmpConfig))
            $message = $this->l10n->t('The format of the address is not correct');
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
        if ($this->solrAdmin->ping($ierror)) {
            $message = $this->l10n->t('Apache Solr is up, running and responding to ping query');
            return true;
        }
        
        $message = $this->l10n->t('Apache Solr is not responding to ping query (Error #%1$s)', array(
            $ierror->getCode()
        ));
        return false;
    }

    private function test_schema(&$message)
    {
        if ($this->solrAdmin->checkSchema(true, $ierror)) {
            $message = $this->l10n->t('Schema is fine');
            return true;
        }
        
        $message = $this->l10n->t('Were not able to verify/fix schema integrity (Error #%1$s)', array(
            $ierror->getCode()
        ));
        return false;
    }

    private function test_extract(&$message)
    {
        $doc = self::generateTestDocument(1, __DIR__ . '/../../LICENSE', '/LICENSE');
        
        $data = array(
            $doc
        );
        $solrDocs = null;
        $this->indexService->extract(ItemDocument::TYPE_TEST, '_nextant_test', $data, $solrDocs, true, $ierror);
        
        if ($doc->isProcessed()) {
            $message = $this->l10n->t('Text successfully extracted');
            return true;
        }
        
        $message = $this->l10n->t('Extract failed. Please check the configuration of your Solr server (Error #%1$s)', array(
            $ierror->getCode()
        ));
        return false;
    }

    private function test_update(&$message)
    {
        $doc = self::generateTestDocument(1, __DIR__ . '/../../LICENSE', '/LICENSE2');
        $asource = $this->indexService->getDocuments(ItemDocument::TYPE_TEST, '_nextant_test', 1, $ierror);
        
        if ($asource == false || sizeof($asource) != 1) {
            $message = $this->l10n->t('Error Updating field - Can\'t find original document - (Error #%1$s)', array(
                $ierror->getCode()
            ));
            return false;
        }
        
        $source = $asource[0];
        $doc->setPath('/LICENSE2');
        $doc->setShare(array(
            'nextant_test_share'
        ));
        $doc->setShareGroup(array(
            'nextant_test_share_group'
        ));
        $doc->deleted(false);
        
        $data = array(
            $doc
        );
        $this->indexService->updateDocuments(ItemDocument::TYPE_TEST, '_nextant_test', $data, $asource, $ierror);
        
        if (! $this->solrTools->commit(false, $ierror)) {
            $message = $this->l10n->t('Error during commit (Error #%1$s)', array(
                $ierror->getCode()
            ));
            return false;
        }
        
        if (! $source->isUpdated()) {
            $message = $this->l10n->t('Error during updating field (Error #%1$s)', array(
                $ierror->getCode()
            ));
            return false;
        }
        
        $message = $this->l10n->t('Document successfully updated');
        return true;
    }

    private function test_search(&$message)
    {
        $keyword = 'LICENSE';
        $this->solrService->setOwner('_nextant_test');
        if ($result = $this->solrService->search($keyword, array(), $ierror)) {
            if (sizeof($result) > 0) {
                
                foreach ($result as $doc) {
                    if ($doc->getType() === ItemDocument::TYPE_TEST && $doc->getId() === 1) {
                        $message = $this->l10n->t('Found exactly what we were looking for');
                        return true;
                    }
                }
                
                // CHECK ID DOCUMENT
                $message = $this->l10n->t('We found something, but not what we were expecting.');
                return false;
            }
            
            $message = $this->l10n->t('Cannot find any document');
            return false;
        }
        
        $message = $this->l10n->t('Search failed. Please check the configuration of your Solr server (Error #%1$s)', array(
            $ierror->getCode()
        ));
        return false;
    }

    private function test_delete(&$message)
    {
        $doc = new ItemDocument(ItemDocument::TYPE_TEST, 1);
        $data = array(
            $doc
        );
        $this->indexService->removeDocuments($data, $ierror);
        if ($doc->isRemoved()) {
            $message = $this->l10n->t('Test document deleted');
            return true;
        }
        
        $message = $this->l10n->t('We could not delete our test document. Please check the configuration of your Solr server (Error #%1$s)', array(
            $ierror->getCode()
        ));
        return false;
    }

    private function save(&$message)
    {
        if (! is_null($this->solr_url) && ! is_null($this->solr_core) && ! is_null($this->solr_timeout)) {
            
            if ($this->solr_timeout < 5)
                $this->solr_timeout = 5;
            
            $this->configService->setAppValue('solr_url', $this->solr_url);
            $this->configService->setAppValue('solr_core', $this->solr_core);
            $this->configService->setAppValue('solr_timeout', $this->solr_timeout);
            
            if ($this->configService->getAppValue('configured') !== '1') {
                $this->configService->setAppValue('configured', '2');
                if (! extension_loaded('sysvmsg'))
                    $this->configService->setAppValue('index_live', '2');
            }
            
            $message = $this->l10n->t('Your configuration has been saved');
            return true;
        }
        
        $message = $this->l10n->t('Configuration failed to be saved. Please reload this page.');
        return false;
    }

    private static function generateTestDocument($docid, $absolutePath, $path)
    {
        $doc = new ItemDocument(ItemDocument::TYPE_TEST, $docid);
        $doc->setAbsolutePath($absolutePath);
        $doc->setPath($path);
        $doc->setMTime(time());
        
        return $doc;
    }

    public static function FileFiltersExtensionsAsArray($text)
    {
        $extensions = array();
        if ($text == '')
            return $extensions;
        
        $lines = explode("\n", $text);
        foreach ($lines as $line) {
            $exts = explode(' ', $line);
            foreach ($exts as $ext) {
                $ext = trim($ext);
                if (strlen($ext) > 1 && substr($ext, 0, 1) === '.' && ! in_array($ext, $extensions))
                    $extensions[] = $ext;
            }
        }
        
        return $extensions;
    }

    public static function FileFiltersExtensionsAsString($arr)
    {
        if (! is_array($arr))
            $arr = self::FileFiltersExtensionsAsArray($arr);
        return implode(' ', $arr);
    }

    public static function FileFiltersExtensionsAsList($text)
    {
        $a = self::FileFiltersExtensionsAsArray($text);
        
        $lines = array();
        $chunk = array_chunk($a, 6);
        foreach ($chunk as $c) {
            $lines[] = self::FileFiltersExtensionsAsString($c);
        }
        
        return implode("\n", $lines);
    }
}