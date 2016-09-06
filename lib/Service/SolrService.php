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
    
    // Owner is not set - mostly a developper mistake
    const ERROR_OWNER_NOT_SET = 4;

    const ERROR_TOOWIDE_SEARCH = 8;
    
    // Config is not well formed
    const ERROR_SOLR_CONFIG = 19;
    
    // can't reach http - solr running at the right place ?
    const EXCEPTION_HTTPEXCEPTION = 21;
    
    // can't reach solr - check uri
    const EXCEPTION_SOLRURI = 24;
    
    // can't extract - check solr configuration for the solr-cell plugin
    const EXCEPTION_EXTRACT_FAILED = 41;

    const EXCEPTION_SEARCH_FAILED = 61;

    const EXCEPTION_SEARCH_FAILED_OWNER = 64;

    const EXCEPTION_REMOVE_FAILED = 81;
    
    // undocumented exception
    const EXCEPTION = 9;

    const SEARCH_OWNER = 1;

    const SEARCH_SHARED = 2;

    const SEARCH_SHARED_GROUP = 4;

    const SEARCH_ALL = 7;

    const SEARCH_TRASHBIN_ALL = 1;

    const SEARCH_TRASHBIN_ONLY = 2;

    const SEARCH_TRASHBIN_NOT = 4;

    private $solariumClient;

    private $configService;

    private $miscService;

    private $owner = '';

    private $groups = array();

    public function __construct($client, $configService, $miscService)
    {
        $this->solariumClient = $client;
        $this->configService = $configService;
        $this->miscService = $miscService;
    }
    
    // If $config == null, reset config to the one set in the admin
    public function setClient($config)
    {
        $toS = $this->configService->toSolarium($config);
        if (! $toS)
            return false;
        
        $this->solariumClient = new \Solarium\Client($toS);
        return true;
    }

    public function setOwner($owner, $groups = array())
    {
        $this->owner = $owner;
        $this->groups = $groups;
    }

    /**
     * extract a file.
     *
     * @param string $path            
     * @param int $docid            
     * @param string $mimetype            
     * @return result
     */
    public function extractFile($path, $docid, $mimetype, $shares, $deleted, &$error = '')
    {
        switch (FileService::getBaseTypeFromMime($mimetype)) {
            case 'text':
                return $this->extractSimpleTextFile($path, $docid, $shares, $deleted, $error);
        }
        
        switch ($mimetype) {
            case 'application/epub+zip':
                return $this->extractSimpleTextFile($path, $docid, $shares, $deleted, $error);
            
            case 'application/pdf':
                return $this->extractSimpleTextFile($path, $docid, $shares, $deleted, $error);
            
            case 'application/rtf':
                return $this->extractSimpleTextFile($path, $docid, $shares, $deleted, $error);
        }
        
        $acceptedMimeType = array(
            'vnd' => array(
                'application/vnd.oasis.opendocument',
                'application/vnd.sun.xml',
                'application/vnd.openxmlformats-officedocument',
                'application/vnd.ms-word',
                'application/vnd.ms-powerpoint',
                'application/vnd.ms-excel'
            )
        );
        
        foreach ($acceptedMimeType['vnd'] as $mt) {
            if (substr($mimetype, 0, strlen($mt)) == $mt)
                return $this->extractSimpleTextFile($path, $docid, $shares, $deleted, $error);
        }
        
        return false;
    }

    /**
     * extract a simple text file.
     *
     * @param string $path            
     * @param int $docid            
     */
    public function extractSimpleTextFile($path, $docid, $shares, $deleted, &$error)
    {
        if ($this->owner == '') {
            $error = self::ERROR_OWNER_NOT_SET;
            return false;
        }
        
        if (! $this->solariumClient) {
            $error = self::ERROR_SOLR_CONFIG;
            return false;
        }
        
        try {
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
            
            $this->documentShares($doc, $shares);
            $doc->nextant_deleted = ($deleted) ? 'true' : 'false';
            $query->setDocument($doc);
            
            $ret = $client->extract($query);
            return $ret;
        } catch (\Solarium\Exception\HttpException $ehe) {
            if ($ehe->getStatusMessage() == 'OK')
                $error = self::EXCEPTION_EXTRACT_FAILED;
            else
                $error = self::EXCEPTION_HTTPEXCEPTION;
        } catch (\Solarium\Exception $e) {
            $error = self::EXCEPTION;
        }
        
        return false;
    }

    public function removeDocument($docid, &$error = '')
    {
        if ($this->owner == '') {
            $error = self::ERROR_OWNER_NOT_SET;
            return false;
        }
        
        try {
            $client = $this->solariumClient;
            $update = $client->createUpdate();
            
            $update->addDeleteById($docid);
            $update->addCommit();
            
            return $client->update($update);
        } catch (\Solarium\Exception\HttpException $ehe) {
            if ($ehe->getStatusMessage() == 'OK')
                $error = self::EXCEPTION_REMOVE_FAILED;
            else
                $error = self::EXCEPTION_HTTPEXCEPTION;
        } catch (\Solarium\Exception $e) {
            $error = self::EXCEPTION;
        }
        
        return false;
    }

    private static function documentShares(&$doc, $shares)
    {
        $doc->nextant_share = $shares['users'];
        $doc->nextant_sharegroup = $shares['groups'];
    }

    private function generateOwnerQuery($type, &$error)
    {
        $ownerQuery = '';
        if ($type & self::SEARCH_OWNER) {
            if ($this->owner == '') {
                $error = self::ERROR_OWNER_NOT_SET;
                return false;
            }
            
            $ownerQuery .= 'nextant_owner:"' . $this->owner . '" ';
        }
        
        if ($type & self::SEARCH_SHARED) {
            if ($this->owner == '') {
                $error = self::ERROR_OWNER_NOT_SET;
                return false;
            }
            $ownerQuery .= (($ownerQuery != '') ? 'OR ' : '') . 'nextant_share:"' . $this->owner . '" ';
        }
        
        if ($type & self::SEARCH_SHARED_GROUP) {
            $ownerGroups = '';
            $groups = array();
            foreach ($this->groups as $group)
                array_push($groups, ' nextant_sharegroup:"' . $group . '"');
            
            if (sizeof($groups) > 0)
                $ownerGroups = implode(' OR ', $groups);
            
            $ownerQuery .= (($ownerQuery != '') ? 'OR ' : '') . $ownerGroups;
        }
        
        return $ownerQuery;
    }

    public function search($string, $type = self::SEARCH_ALL, $deleted = self::SEARCH_TRASHBIN_ALL, &$error = '')
    {
        if ($this->solariumClient == false)
            return false;
        
        $ownerQuery = $this->generateOwnerQuery($type, $error);
        if ($ownerQuery === false)
            return false;
        
        if ($ownerQuery == '') {
            $error = self::ERROR_TOOWIDE_SEARCH;
            return false;
        }
        
        try {
            $client = $this->solariumClient;
            $query = $client->createSelect();
            
            $query->setQuery('attr_text:' . $string);
            $query->createFilterQuery('owner')->setQuery($ownerQuery);
            if ($deleted & self::SEARCH_TRASHBIN_ONLY)
                $query->createFilterQuery('deleted')->setQuery('nextant_deleted:true');
            if ($deleted & self::SEARCH_TRASHBIN_NOT)
                $query->createFilterQuery('deleted')->setQuery('nextant_deleted:false');
            
            $resultset = $client->select($query);
            
            $return = array();
            foreach ($resultset as $document) {
                array_push($return, array(
                    'id' => $document->id,
                    'score' => $document->score
                ));
            }
            
            return $return;
        } catch (\Solarium\Exception\HttpException $ehe) {
            if ($ehe->getStatusMessage() == 'OK') {
                switch ($type) {
                    case self::SEARCH_OWNER:
                        $error = self::EXCEPTION_SEARCH_FAILED_OWNER;
                        break;
                    default:
                        $error = self::EXCEPTION_SEARCH_FAILED;
                        break;
                }
            } else
                $error = self::EXCEPTION_HTTPEXCEPTION;
        } catch (\Solarium\Exception $e) {
            $error = self::EXCEPTION;
        }
        
        return false;
    }

    public function ping(&$error = '')
    {
        $client = $this->solariumClient;
        $ping = $client->createPing();
        
        try {
            $result = $client->ping($ping);
            return true;
        } catch (\Solarium\Exception\HttpException $ehe) {
            if ($ehe->getStatusMessage() == 'OK')
                $error = self::EXCEPTION_SOLRURI;
            else
                $error = self::EXCEPTION_HTTPEXCEPTION;
        } catch (\Solarium\Exception $e) {
            $error = self::EXCEPTION;
        }
        
        return false;
    }

    public function count(&$error = '')
    {
        $client = $this->solariumClient;
        
        try {
            $query = $client->createSelect();
            $query->setQuery('*:*');
            $query->setRows(0);
            $resultset = $client->execute($query);
            
            return $resultset->getNumFound();
        } catch (\Solarium\Exception\HttpException $ehe) {
            if ($ehe->getStatusMessage() == 'OK')
                $error = self::EXCEPTION_SOLRURI;
            else
                $error = self::EXCEPTION_HTTPEXCEPTION;
        } catch (\Solarium\Exception $e) {
            $error = self::EXCEPTION;
        }
        
        return false;
    }
}
    