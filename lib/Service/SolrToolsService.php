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
use Solarium\Core\Client\Request;

class SolrToolsService
{

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
}
    