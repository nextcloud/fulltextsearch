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
use Solarium\Core\Client\Request;

class SolrToolsService
{

    const UPDATE_MAXIMUM_QUERYTIME = 2000;

    const UPDATE_MAXIMUM_FILEPROCESS = 30;

    const UPDATE_CHUNK_SIZE = 15;

    private $solrService;

    private $configService;

    private $miscService;

    private $output;

    private $lastMessage;

    public function __construct($solrService, $configService, $miscService)
    {
        // $this->solariumClient = $solrClient;
        $this->solrService = $solrService;
        $this->configService = $configService;
        $this->miscService = $miscService;
        $this->output = null;
    }

    public function updateDocuments($data, &$error = '')
    {
        if (! $this->solrService || ! $this->solrService->configured() || ! $this->solrService->getClient())
            return false;
        
        try {
            $client = $this->solrService->getClient();
            
            $documentProcessed = 0;
            $cycle = array_chunk($data, self::UPDATE_CHUNK_SIZE);
            foreach ($cycle as $batch) {
                
                // get documents status per ids
                $docIds = array();
                foreach ($batch as $entry)
                    array_push($docIds, $entry['id']);
                
                $currentStatus = $this->getDocumentsStatus($docIds, $error);
                if (! $currentStatus || sizeof($currentStatus) == 0)
                    continue;
                    
                    // create query to update those in needs.
                $query = $client->createUpdate();
                $docs = array();
                
                foreach ($batch as $upd) {
                    if ($upd == null || ! is_array($upd))
                        continue;
                    
                    if (! key_exists($upd['id'], $currentStatus))
                        continue;
                    
                    $doc = $query->createDocument();
                    $doc->setKey('id', $upd['id']);
                    
                    $docStatus = $currentStatus[$upd['id']];
                    $edited = false;
                    if (key_exists('owner', $upd) && $upd['owner'] != $docStatus['nextant_owner']) {
                        $doc->setField('nextant_owner', $upd['owner']);
                        $doc->setFieldModifier('nextant_owner', 'set');
                        $edited = true;
                    }
                    
                    if (key_exists('share_users', $upd) && ! MiscService::arraysIdentical($upd['share_users'], $docStatus['nextant_share'])) {
                        if (sizeof($upd['share_users']) > 0) {
                            $doc->setField('nextant_share', $upd['share_users']);
                            $doc->setFieldModifier('nextant_share', 'set');
                        } else {
                            $doc->setField('nextant_share', array(
                                ''
                            ));
                            $doc->setFieldModifier('nextant_share', 'set');
                        }
                        $edited = true;
                    }
                    
                    if (key_exists('share_groups', $upd) && ! MiscService::arraysIdentical($upd['share_groups'], $docStatus['nextant_sharegroup'])) {
                        if (sizeof($upd['share_groups']) > 0) {
                            $doc->setField('nextant_sharegroup', $upd['share_groups']);
                            $doc->setFieldModifier('nextant_sharegroup', 'set');
                        } else {
                            $doc->setField('nextant_sharegroup', array(
                                ''
                            ));
                            $doc->setFieldModifier('nextant_sharegroup', 'set');
                        }
                        $edited = true;
                    }
                    
                    if (key_exists('deleted', $upd) && $upd['deleted'] != $docStatus['nextant_deleted']) {
                        
                        $doc->setField('nextant_deleted', ($upd['deleted']) ? 'true' : 'false');
                        $doc->setFieldModifier('nextant_deleted', 'set');
                        $edited = true;
                    }
                    
                    if ($edited)
                        array_push($docs, $doc);
                }
                
                $this->miscService->log('> ' . sizeof($docs));
                if (sizeof($docs) == 0)
                    continue;
                
                $query->addDocuments($docs)->addCommit();
                
                if (! $request = $client->update($query))
                    return false;
                
                if ($request->getQueryTime() > self::UPDATE_MAXIMUM_QUERYTIME) {
                    $this->miscService->log('Maximum Update Query Time (' . self::UPDATE_MAXIMUM_QUERYTIME . 'ms) reached, we won\'t go any further.', 2);
                    return false;
                }
                
                $documentProcessed += sizeof($docs);
                $this->miscService->log('__' . $documentProcessed);
                if ($documentProcessed >= self::UPDATE_MAXIMUM_FILEPROCESS) {
                    $this->miscService->log('Maximum number of processed files (' . self::UPDATE_MAXIMUM_FILEPROCESS . ') reached, we won\'t go any further.', 2);
                    $error = SolrService::EXCEPTION_UPDATE_MAXIMUM_REACHED;
                    return false;
                }
            }
            
            return true;
        } catch (\Solarium\Exception\HttpException $ehe) {
            if ($ehe->getStatusMessage() == 'OK')
                $error = SolrService::EXCEPTION_UPDATE_FIELD_FAILED;
            else
                $error = SolrService::EXCEPTION_HTTPEXCEPTION;
        } catch (\Solarium\Exception $e) {
            $error = SolrService::EXCEPTION;
        }
        
        return false;
    }

    private function getDocumentsStatus($docs, &$error = '')
    {
        if (! $this->solrService || ! $this->solrService->configured() || ! $this->solrService->getClient())
            return false;
        
        $client = $this->solrService->getClient();
        
        if (! is_array($docs))
            $docs = array(
                $docs
            );
        
        if (sizeof($docs) > self::UPDATE_CHUNK_SIZE) {
            $error = SolrService::EXCEPTION_UPDATE_MAXIMUM_REACHED;
            return false;
        }
        
        $result = array();
        
        try {
            $query = $client->createSelect();
            $query->setQuery('id:' . implode(' ', $docs));
            $query->setRows(sizeof($docs));
            $query->setFields(array(
                'id',
                'nextant_owner',
                'nextant_share',
                'nextant_sharegroup',
                'nextant_deleted'
            ));
            
            $resultset = $client->select($query);
            
            foreach ($resultset as $document) {
                $result[$document->id] = array(
                    'nextant_owner' => $document->nextant_owner,
                    'nextant_share' => $document->nextant_share,
                    'nextant_sharegroup' => $document->nextant_sharegroup,
                    'nextant_deleted' => $document->nextant_deleted
                );
            }
            
            return $result;
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

    public function removeDocument($docid, &$error = '')
    {
        if (! $this->solrService || ! $this->solrService->configured() || ! $this->solrService->getClient())
            return false;
        
        try {
            $client = $this->solrService->getClient();
            $update = $client->createUpdate();
            
            $update->addDeleteById($docid);
            $update->addCommit();
            
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

    public function isDocumentUpToDate($docid, $mtime, &$error = '')
    {
        if (intval($docid) == 0)
            return false;
        
        if (! $this->solrService || ! $this->solrService->configured() || ! $this->solrService->getClient())
            return false;
        
        $client = $this->solrService->getClient();
        
        try {
            $query = $client->createSelect();
            $query->setQuery('id:' . $docid);
            $query->setFields(array(
                'nextant_mtime'
            ));
            
            $resultset = $client->select($query);
            
            if ($resultset->getNumFound() != 1)
                return false;
            
            foreach ($resultset as $document) {
                if ($mtime == $document->nextant_mtime)
                    return true;
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

    public function getInfoSystem(&$error = '')
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
}
    