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
     * @param ItemDocument $final            
     * @param ItemDocument $current            
     *
     * @return boolean
     */
    public function updateDocument(&$final, &$current, $update = true, &$error = 0)
    {
        if (! $this->solrService || ! $this->solrService->configured() || ! $this->solrService->getClient())
            return false;
        
        try {
            if ($final == null || $current == null)
                return false;
            
            $modifs = false;
            if (! MiscService::arraysIdentical($final->getShare(), $current->getShare()))
                $modifs = true;
            if (! MiscService::arraysIdentical($final->getShareGroup(), $current->getShareGroup()))
                $modifs = true;
            if ($final->getPath() !== $current->getPath())
                $modifs = true;
            if ($final->getOwner() !== $current->getOwner())
                $modifs = true;
            if ($final->isDeleted() != $current->isDeleted())
                $modifs = true;
            
            if (! $modifs)
                return true;
            
            $final->needUpdate(true);
            
            if (! $update)
                return true;
            
            $client = $this->solrService->getClient();
            $query = $client->createUpdate();
            
            $doc = $query->createDocument();
            $doc->setKey('id', $final->getType() . '_' . $final->getId());
            
            if ($final->getOwner() !== $current->getOwner()) {
                $doc->setField('nextant_owner', $final->getOwner());
                $doc->setFieldModifier('nextant_owner', 'set');
            }
            
            if ($final->getPath() !== $current->getPath()) {
                $doc->setField('nextant_path', $final->getPath());
                $doc->setFieldModifier('nextant_path', 'set');
            }
            
            if (! MiscService::arraysIdentical($final->getShare(), $current->getShare())) {
                if (sizeof($final->getShare()) > 0) {
                    $doc->setField('nextant_share', $final->getShare());
                    $doc->setFieldModifier('nextant_share', 'set');
                } else {
                    $doc->setField('nextant_share', array(
                        ''
                    ));
                    $doc->setFieldModifier('nextant_share', 'set');
                }
            }
            
            if (! MiscService::arraysIdentical($final->getShareGroup(), $current->getShareGroup())) {
                if (sizeof($final->getShareGroup()) > 0) {
                    $doc->setField('nextant_sharegroup', $final->getShareGroup());
                    $doc->setFieldModifier('nextant_sharegroup', 'set');
                } else {
                    $doc->setField('nextant_sharegroup', array(
                        ''
                    ));
                    $doc->setFieldModifier('nextant_sharegroup', 'set');
                }
            }
            
            if ($final->isDeleted() != $current->isDeleted()) {
                $doc->setField('nextant_deleted', ($final->isDeleted()) ? 'true' : 'false');
                $doc->setFieldModifier('nextant_deleted', 'set');
            }
            
            $query->addDocuments(array(
                $doc
            ))->addCommit();
            
            if ($request = $client->update($query)) {
                // fixing solrDocs' data
                $current->setShare($final->getShare());
                $current->setShareGroup($final->getShareGroup());
                $current->setPath($final->getPath());
                $current->setOwner($final->getOwner());
                $current->deleted($final->isDeleted());
                $current->updated(true);
                
                $final->updated(true);
                return true;
            } else
                $error = SolrService::EXCEPTION_UPDATE_QUERY_FAILED;
        } catch (\Solarium\Exception\HttpException $ehe) {
            if ($ehe->getStatusMessage() == 'OK')
                $error = SolrService::EXCEPTION_UPDATE_FIELD_FAILED;
            else
                $error = SolrService::EXCEPTION_HTTPEXCEPTION;
        } catch (\Solarium\Exception $e) {
            $error = SolrService::EXCEPTION;
        }
        
        $final->failedUpdate(true);
        
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
            
            $ret = $client->update($update);
            
            if ($ret) {
                $doc->removed(true);
                return true;
            }
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
            if ($document->isExtractable())
                $document->extracted(true);
            $document->indexed(true);
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
                    if ($document->isExtractable())
                        $document->extracted(true);
                    $document->indexed(true);
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
        
        try {
            $client = $this->solrService->getAdminClient();
            
            $query = $client->createSelect();
            $request = $client->createRequest($query);
            
            $request->setHandler('admin/info/system');
            
            $response = $client->executeRequest($request);
            if ($response->getStatusCode() != 200)
                return false;
            
            $result = json_decode($response->getBody());
            
            return $result;
        } catch (\Solarium\Exception\HttpException $ehe) {
            if ($ehe->getStatusMessage() == 'OK')
                $error = SolrService::EXCEPTION_SOLRURI;
            else
                $error = SolrService::EXCEPTION_HTTPEXCEPTION;
        } catch (\Solarium\Exception $e) {
            $error = SolrService::EXCEPTION;
        }
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
    