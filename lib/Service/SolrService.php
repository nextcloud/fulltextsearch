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
 * GNU Affero` General Public License for more details.
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

    const EXCEPTION_UPDATE_FIELD_FAILED = 61;

    const EXCEPTION_UPDATE_QUERY_FAILED = 71;

    const EXCEPTION_UPDATE_MAXIMUM_REACHED = 63;

    const EXCEPTION_SEARCH_FAILED = 81;

    const EXCEPTION_REMOVE_FAILED = 101;

    const EXCEPTION_OPTIMIZE_FAILED = 121;
    
    // undocumented exception
    const EXCEPTION = 9;

    const SEARCH_OWNER = 1;

    const SEARCH_SHARED = 2;

    const SEARCH_SHARED_GROUP = 4;

    const SEARCH_ALL = 7;

    private $solariumClient;

    private $configService;

    private $miscService;

    private $owner = '';

    private $groups = array();

    private $configured = false;

    private $output = null;

    public function __construct($client, $configService, $miscService)
    {
        $this->solariumClient = $client;
        $this->configService = $configService;
        $this->miscService = $miscService;
    }

    public function setDebug($debug)
    {
        $this->miscService->setDebug($debug);
    }

    public function setOutput(&$output)
    {
        $this->output = $output;
    }

    public function configured()
    {
        if (! $this->configured) {
            $isIt = $this->configService->getAppValue('configured');
            if ($isIt == '1')
                $this->configured = true;
        }
        return $this->configured;
    }
    // If $config == null, reset config to the one set in the admin
    public function setClient($config)
    {
        $toS = $this->configService->toSolarium($config);
        if (! $toS)
            return false;
        
        $this->solariumClient = new \Solarium\Client($toS);
        if ($config != null)
            $this->configured = true;
        else
            $this->configured = false;
        
        return true;
    }

    public function getClient()
    {
        return $this->solariumClient;
    }

    public function getAdminClient()
    {
        if (! $this->solariumClient)
            return false;
        if (! $this->configured)
            return false;
        
        $options = $this->solariumClient->getOptions();
        unset($options['endpoint']['localhost']['core']);
        return new \Solarium\Client($options);
    }

    public function setOwner($owner, $groups = array())
    {
        $this->owner = $owner;
        $this->groups = $groups;
    }

    public static function extractableFile($mimetype)
    {
        switch (FileService::getBaseTypeFromMime($mimetype)) {
            case 'text':
                return true;
        }
        
        switch ($mimetype) {
            case 'application/epub+zip':
                return true;
            
            case 'application/pdf':
                return true;
            
            case 'application/rtf':
                return true;
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
                return true;
        }
        
        return false;
    }

    /**
     * extract a file.
     *
     * @param string $path            
     * @param int $docid            
     * @param string $mimetype            
     * @return result
     */
    public function extractFile($path, $docid, $mtime, &$error = '')
    {
        if (! $this->configured())
            return false;
        
        if ($this->owner == '') {
            $error = self::ERROR_OWNER_NOT_SET;
            return false;
        }
        
        if (! $this->getClient()) {
            $error = self::ERROR_SOLR_CONFIG;
            return false;
        }
        
        try {
            $client = $this->getClient();
            
            $query = $client->createExtract();
            $query->addFieldMapping('content', 'text');
            $query->setUprefix('nextant_attr_');
            
            $query->setFile($path);
            $query->setCommit(true);
            $query->setOmitHeader(false);
            
            // add document
            $doc = $query->createDocument();
            $doc->id = $docid;
            $doc->nextant_owner = $this->owner;
            $doc->nextant_mtime = $mtime;
            
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

    public function search($string, $options = array(), &$error = '')
    {
        if (! $this->configured())
            return false;
        
        if ($this->getClient() == false)
            return false;
        
        if ($options == null)
            $options = array();
        
        try {
            $client = $this->getClient();
            $query = $client->createSelect();
            
            $helper = $query->getHelper();
            $ownerQuery = $this->generateOwnerQuery(self::SEARCH_ALL, $helper, $error);
            if ($ownerQuery === false)
                return false;
            
            if ($ownerQuery == '') {
                $error = self::ERROR_TOOWIDE_SEARCH;
                return false;
            }
            
            $query->setFields(array(
                'id',
                'nextant_deleted',
                'nextant_owner'
            ));
            $query->setRows(25);
            
            $options = array(
                'complete_words'
            );
            $query->setQuery('nextant_attr_text:' . ((! in_array('complete_words', $options)) ? '*' : '') . $helper->escapePhrase('*' . $string));
            $query->createFilterQuery('owner')->setQuery($ownerQuery);
            
            $hl = $query->getHighlighting();
            $hl->setFields(array(
                'nextant_attr_text'
            ));
            // $hl->setSimplePrefix('<b>');
            // $hl->setSimplePostfix('</b>');
            $hl->setSimplePrefix('');
            $hl->setSimplePostfix('');
            $hl->setSnippets(3);
            
            $resultset = $client->select($query);
            $highlighting = $resultset->getHighlighting();
            
            $return = array();
            foreach ($resultset as $document) {
                
                // highlight
                $hlDoc = $highlighting->getResult($document->id);
                $hlString = implode(' (...) ', $hlDoc->getField('nextant_attr_text'));
                
                array_push($return, array(
                    'id' => $document->id,
                    'deleted' => $document->nextant_deleted,
                    'owner' => $document->nextant_owner,
                    'highlight' => $hlString,
                    'score' => $document->score
                ));
            }
            
            return $return;
        } catch (\Solarium\Exception\HttpException $ehe) {
            if ($ehe->getStatusMessage() == 'OK')
                $error = self::EXCEPTION_SEARCH_FAILED;
            else
                $error = self::EXCEPTION_HTTPEXCEPTION;
        } catch (\Solarium\Exception $e) {
            $error = self::EXCEPTION;
        }
        
        return false;
    }

    private function generateOwnerQuery($type, $helper, &$error)
    {
        $ownerQuery = '';
        if ($type & self::SEARCH_OWNER) {
            if ($this->owner == '') {
                $error = self::ERROR_OWNER_NOT_SET;
                return false;
            }
            
            $ownerQuery .= 'nextant_owner:' . $helper->escapePhrase($this->owner) . ' ';
        }
        
        if ($type & self::SEARCH_SHARED) {
            if ($this->owner == '') {
                $error = self::ERROR_OWNER_NOT_SET;
                return false;
            }
            $ownerQuery .= (($ownerQuery != '') ? 'OR ' : '') . 'nextant_share:' . $helper->escapePhrase($this->owner) . ' ';
        }
        
        if ($type & self::SEARCH_SHARED_GROUP) {
            $ownerGroups = '';
            $groups = array();
            foreach ($this->groups as $group)
                array_push($groups, ' nextant_sharegroup:' . $helper->escapePhrase($group));
            
            if (sizeof($groups) > 0)
                $ownerQuery .= (($ownerQuery != '') ? 'OR ' : '') . implode(' OR ', $groups);
        }
        
        return $ownerQuery;
    }

    public function message($line, $newline = true)
    {
        if ($this->output != null) {
            if ($newline)
                $this->output->writeln($line);
            else
                $this->output->write($line);
        } else
            $this->lastMessage = $line;
    }
}

