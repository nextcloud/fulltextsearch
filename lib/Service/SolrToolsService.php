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
namespace OCA\Nextant\Service;

use \OCA\Nextant\Service\SolrService;
use \OCA\Nextant\Service\MiscService;
use \OCA\Nextant\Items\ItemDocument;

/**
 *
 * Tools to manage Solr Core
 *
 * @author Maxence Lange
 *        
 */
class SolrToolsService
{

    const UPDATE_MAXIMUM_QUERYTIME = 2000;

    const UPDATE_MAXIMUM_FILEPROCESS = 15;

    const UPDATE_CHUNK_SIZE = 5;

    private $solrService;

    private $configService;

    private $miscService;

    private $output;

    /**
     *
     * @param SolrService $solrService            
     * @param ConfigService $configService            
     * @param MiscService $miscService            
     */
    public function __construct(SolrService $solrService, ConfigService $configService, MiscService $miscService)
    {
        $this->solrService = $solrService;
        $this->configService = $configService;
        $this->miscService = $miscService;
        $this->output = null;
    }

    /**
     * Set debug
     *
     * @param boolean $debug            
     */
    public function setDebug($debug = false)
    {
        $this->miscService->setDebug($debug);
    }

    /**
     * optimize the Solr Core
     *
     * @param number $error            
     * @return boolean|Solarium\Core\Query\Result
     */
    public function optimizeSolrIndex(&$error = 0)
    {
        if (! $this->solrService || ! $this->solrService->configured() || ! $this->solrService->getClient())
            return false;
        
        try {
            $client = $this->solrService->getClient();
            
            $update = $client->createUpdate();
            $update->addOptimize(true, true, 5);
            $result = $client->update($update);
            
            return $result;
        } catch (\Solarium\Exception\HttpException $ehe) {
            if ($ehe->getStatusMessage() == 'OK')
                $error = SolrService::EXCEPTION_OPTIMIZE_FAILED;
            else
                $error = SolrService::EXCEPTION_HTTPEXCEPTION;
        } catch (\Solarium\Exception $e) {
            $error = SolrService::EXCEPTION;
        }
        
        return false;
    }

    /**
     *
     * @param ItemDocument $source            
     * @param ItemDocument $final            
     *
     * @return boolean
     */
    public function updateDocument(&$source, $final, $update = true, &$error = 0)
    {
        if (! $this->solrService || ! $this->solrService->configured() || ! $this->solrService->getClient())
            return false;
        
        try {
            if ($source == null || $final == null)
                return false;
            
            $modifs = false;
            if ($source->isDeleted()) {
                $source->setShare();
                $source->setShareGroup();
            }
            
            if (! MiscService::arraysIdentical($source->getShare(), $final->getShare()))
                $modifs = true;
            if (! MiscService::arraysIdentical($source->getShareGroup(), $final->getShareGroup()))
                $modifs = true;
            if ($source->getPath() != $final->getPath())
                $modifs = true;
            if ($source->getOwner() != $final->getOwner())
                $modifs = true;
            if ($source->isDeleted() != $final->isDeleted())
                $modifs = true;
            if (! $modifs)
                return false;
            if (! $update)
                return true;
            
            $client = $this->solrService->getClient();
            $query = $client->createUpdate();
            
            $doc = $query->createDocument();
            $doc->setKey('id', $final->getType() . '_' . $final->getId());
            
            if ($source->getOwner() != $final->getOwner()) {
                $doc->setField('nextant_owner', $source->getOwner());
                $doc->setFieldModifier('nextant_owner', 'set');
            }
            
            if ($source->getPath() != $final->getPath()) {
                $doc->setField('nextant_path', $source->getPath());
                $doc->setFieldModifier('nextant_path', 'set');
            }
            
            if (! MiscService::arraysIdentical($source->getShare(), $final->getShare())) {
                if (sizeof($source->getShare()) > 0) {
                    $doc->setField('nextant_share', $source->getShare());
                    $doc->setFieldModifier('nextant_share', 'set');
                } else {
                    $doc->setField('nextant_share', array(
                        ''
                    ));
                    $doc->setFieldModifier('nextant_share', 'set');
                }
            }
            
            if (! MiscService::arraysIdentical($source->getShareGroup(), $final->getShareGroup())) {
                if (sizeof($source->getShareGroup()) > 0) {
                    $doc->setField('nextant_sharegroup', $source->getShareGroup());
                    $doc->setFieldModifier('nextant_sharegroup', 'set');
                } else {
                    $doc->setField('nextant_sharegroup', array(
                        ''
                    ));
                    $doc->setFieldModifier('nextant_sharegroup', 'set');
                }
            }
            
            if ($source->isDeleted() != $final->isDeleted()) {
                $doc->setField('nextant_deleted', ($source->isDeleted()) ? 'true' : 'false');
                $doc->setFieldModifier('nextant_deleted', 'set');
            }
            
            $query->addDocuments(array(
                $doc
            ))->addCommit();
            
            if (! $request = $client->update($query)) {
                $this->miscService->log('updateDocument update query failed');
                $error = SolrService::EXCEPTION_UPDATE_QUERY_FAILED;
                $document->failed(ItemDocument::FAIL_UPDATE);
                return false;
            }
            
            $source->setShare($final->getShare());
            $source->setShareGroup($final->getShareGroup());
            $source->setPath($final->getPath());
            $source->setOwner($final->getOwner());
            $source->deleted($final->isDeleted());
            
            $source->updated(true);
            
            return true;
        } catch (\Solarium\Exception\HttpException $ehe) {
            if ($ehe->getStatusMessage() == 'OK')
                $error = SolrService::EXCEPTION_UPDATE_FIELD_FAILED;
            else
                $error = SolrService::EXCEPTION_HTTPEXCEPTION;
        } catch (\Solarium\Exception $e) {
            $error = SolrService::EXCEPTION;
        }
        
        $this->miscService->debug('updateDocument error #' . $error);
        
        return false;
    }

    /**
     * remove document by its id
     *
     * @param number $docid            
     * @param number $error            
     * @return boolean
     */
    public function removeDocument(&$doc)
    {
        if (! $this->solrService || ! $this->solrService->configured() || ! $this->solrService->getClient())
            return false;
        
        if ($doc == null)
            return false;
        
        try {
            $client = $this->solrService->getClient();
            $update = $client->createUpdate();
            
            $update->addDeleteById($doc->getType() . '_' . $doc->getId());
            $update->addCommit();
            
            $doc->removed(true);
            
            return $client->update($update);
        } catch (\Solarium\Exception\HttpException $ehe) {
            if ($ehe->getStatusMessage() == 'OK')
                $error = SolrService::EXCEPTION_REMOVE_FAILED;
            else
                $error = SolrService::EXCEPTION_HTTPEXCEPTION;
        } catch (\Solarium\Exception $e) {
            $error = SolrService::EXCEPTION;
        }
        
        return false;
    }

    /**
     * Check the mtime of a file and return if document is up to date
     *
     * @param number $docid            
     * @param number $mtime            
     * @param number $error            
     * @return boolean
     */
    public function isDocumentUpToDate(&$document, $solr = null, &$error = 0)
    {
        if (intval($document->getId()) == 0)
            return false;
        
        if ($solr != null && $solr != '' && ($document->getMTime() == $solr->getMTime())) {
            $document->extracted(true);
            return true;
        }
        
        try {
            
            if (! $this->solrService || ! $this->solrService->configured() || ! $this->solrService->getClient())
                return false;
            
            $client = $this->solrService->getClient();
            
            $query = $client->createSelect();
            $query->setQuery('id:' . $document->getType() . '_' . $document->getId());
            $query->setFields(array(
                'nextant_mtime'
            ));
            
            $resultset = $client->select($query);
            
            if ($resultset->getNumFound() != 1)
                return false;
            
            foreach ($resultset as $doc) {
                if ($document->getMTime() == $doc->nextant_mtime) {
                    $document->extracted(true);
                    return true;
                }
            }
        } catch (\Solarium\Exception\HttpException $ehe) {
            if ($ehe->getStatusMessage() == 'OK')
                $error = SolrService::EXCEPTION_SEARCH_FAILED;
            else
                $error = SolrService::EXCEPTION_HTTPEXCEPTION;
        } catch (\Solarium\Exception $e) {
            $error = SolrService::EXCEPTION;
        }
        
        return false;
    }

    /**
     * return information about the current status of the Solr servlet.
     *
     * @param number $error            
     * @return boolean|Solarium\Core\Query\Result
     */
    public function getInfoSystem(&$error = 0)
    {
        if (! $this->solrService || ! $this->solrService->configured() || ! $this->solrService->getClient())
            return false;
        
        $client = $this->solrService->getAdminClient();
        
        $query = $client->createSelect();
        $request = $client->createRequest($query);
        
        $request->setHandler('admin/info/system');
        
        $response = $client->executeRequest($request);
        if ($response->getStatusCode() != 200)
            return false;
        
        $result = json_decode($response->getBody());
        
        return $result;
    }

    /**
     * Count document on Solr Core
     *
     * @param number $error            
     * @return boolean
     */
    public function count($type = '', $userId = '', &$error = 0)
    {
        if (! $this->solrService || ! $this->solrService->configured() || ! $this->solrService->getClient())
            return false;
        
        $client = $this->solrService->getClient();
        
        if ($type != '')
            $type .= '_';
        
        try {
            $query = $client->createSelect();
            $helper = $query->getHelper();
            
            $query->setQuery('id:' . $type . '*');
            if ($userId != '')
                $query->createFilterQuery('owner')->setQuery('nextant_owner:' . $helper->escapePhrase($userId));
            
            $query->setRows(0);
            $resultset = $client->execute($query);
            
            return $resultset->getNumFound();
        } catch (\Solarium\Exception\HttpException $ehe) {
            if ($ehe->getStatusMessage() == 'OK')
                $error = SolrService::EXCEPTION_SOLRURI;
            else
                $error = SolrService::EXCEPTION_HTTPEXCEPTION;
        } catch (\Solarium\Exception $e) {
            $error = SolrService::EXCEPTION;
        }
        
        return false;
    }
}
    