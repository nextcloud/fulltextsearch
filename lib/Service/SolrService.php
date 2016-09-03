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

use \OCA\Nextant\Service\FileService;

class SolrService
{

    const EXCEPTION_HTTPEXCEPTION = 19;

    const EXCEPTION = 9;

    private $solariumClient;

    private $configService;

    private $miscService;

    private $owner = '';

    public function __construct($client, $configService, $miscService)
    {
        $this->solariumClient = $client;
        $this->configService = $configService;
        $this->miscService = $miscService;
    }
    
    // If $config == null, reset config to the one set in the admin
    public function setClient($config)
    {
        $this->solariumClient = new \Solarium\Client($this->configService->toSolarium($config));
    }

    public function setOwner($owner)
    {
        $this->owner = $owner;
    }

    /**
     * extract a file.
     *
     * @param string $path            
     * @param int $docid            
     * @param string $mimetype            
     * @return result
     */
    public function extractFile($path, $docid, $mimetype)
    {
        switch (FileService::getBaseTypeFromMime($mimetype)) {
            case 'text':
                return $this->extractSimpleTextFile($path, $docid);
        }
        
        switch ($mimetype) {
            // case 'application/vnd.oasis.opendocument.text':
            // return $this->extractSimpleTextFile($path, $docid);
            
            // case 'application/epub+zip':
            // return $this->extractSimpleTextFile($path, $docid);
            
            case 'application/pdf':
                return $this->extractSimpleTextFile($path, $docid);
        }
        
        return false;
    }

    /**
     * extract a simple text file.
     *
     * @param string $path            
     * @param int $docid            
     */
    public function extractSimpleTextFile($path, $docid)
    {
        if ($this->owner == '')
            return false;
        
        $client = $this->solariumClient;
        
        $query = $client->createExtract();
        $query->addFieldMapping('content', 'text');
        $query->setUprefix('attr_');
        $query->setFile($path);
        $query->setCommit(true);
        $query->setOmitHeader(false);
        
        // add document
        $doc = $query->createDocument();
        $doc->id = $docid;
        $doc->nextant_owner = $this->owner;
        $query->setDocument($doc);
        
        return $client->extract($query);
    }

    public function removeDocument($docid)
    {
        $client = $this->solariumClient;
        $update = $client->createUpdate();
        
        $update->addDeleteById($docid);
        $update->addCommit();
        
        // this executes the query and returns the result
        return $client->update($update);
    }

    public function ping(&$error)
    {
        $client = $this->solariumClient;
        $ping = $client->createPing();
        
        try {
            $result = $client->ping($ping);
            $this->miscService->log('ping: ' . var_export($result->getData(), true));
            return true;
        } catch (\Solarium\Exception\HttpException $ehe) {
            $error = self::EXCEPTION_HTTPEXCEPTION;
        } catch (\Solarium\Exception $e) {
            $error = self::EXCEPTION;
        }
        
        return false;
    }

    public function search($string)
    {
        if ($this->owner == '')
            return;
        
        $client = $this->solariumClient;
        $query = $client->createSelect();
        
        $query->setQuery($string);
        $query->createFilterQuery('owner')->setQuery('nextant_owner:' . $this->owner);
        
        $resultset = $client->select($query);
        
        $return = array();
        foreach ($resultset as $document) {
            array_push($return, array(
                'id' => $document->id,
                'score' => $document->score
            ));
        }
        
        return $return;
    }
}
    