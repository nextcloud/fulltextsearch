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
use \OCA\Nextant\Service\ConfigService;

class SolrService
{
    
    // Owner is not set - mostly a developper mistake
    const ERROR_OWNER_NOT_SET = 4;
    
    // Type of document is not set
    const ERROR_TYPE_NOT_SET = 6;

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

    const EXCEPTION_SUGGEST_FAILED = 85;

    const EXCEPTION_REMOVE_FAILED = 101;

    const EXCEPTION_OPTIMIZE_FAILED = 121;
    
    // undocumented exception
    const EXCEPTION = 9;

    const SEARCH_OWNER = 1;

    const SEARCH_SHARED = 2;

    const SEARCH_SHARED_GROUP = 4;

    const SEARCH_EXTERNAL = 8;

    const SEARCH_ALL = 15;

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

    public function configured($first = false)
    {
        if (! $this->configured) {
            $isIt = $this->configService->getAppValue('configured');
            if ($isIt === '1')
                $this->configured = true;
            if ($first && $isIt > 0)
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

    public function getClientConfig()
    {
        return $this->solariumClient->getOptions();
    }

    public function setOwner($owner, $groups = array())
    {
        $this->owner = $owner;
        $this->groups = $groups;
    }

    public static function extractableFile($mimetype, $path = '')
    {
        switch (FileService::getBaseTypeFromMime($mimetype)) {
            case 'text':
                return \OCP\Util::imagePath('core', 'filetypes/text.svg');
        }
        
        switch ($mimetype) {
            case 'image/jpeg':
                return \OCP\Util::imagePath('core', 'filetypes/image.svg');
            
            case 'image/tiff':
                return \OCP\Util::imagePath('core', 'filetypes/image.svg');
            
            case 'application/epub+zip':
                return \OCP\Util::imagePath('core', 'filetypes/text.svg');
            
            case 'application/pdf':
                return \OCP\Util::imagePath('core', 'filetypes/application-pdf.svg');
            
            case 'application/rtf':
                return \OCP\Util::imagePath('core', 'filetypes/text.svg');
            
            case 'application/msword':
                return \OCP\Util::imagePath('core', 'filetypes/text.svg');
            
            case 'audio/mpeg':
                return \OCP\Util::imagePath('core', 'filetypes/audio.svg');
            
            case 'audio/flac':
                return \OCP\Util::imagePath('core', 'filetypes/audio.svg');
            
            case 'application/octet-stream':
                if ($path === '')
                    return false;
                
                $pinfo = pathinfo($path);
                if (key_exists('extension', $pinfo) && substr($pinfo['extension'], 0, 1) == 'd' && ((int) (substr($pinfo['extension'], 1)) > 0)) {
                    $tmppath = substr($path, 0, strrpos($path, '.'));
                    $tmpmime = \OC::$server->getMimeTypeDetector()->detectPath($tmppath);
                    
                    if ($tmpmime === 'application/octet-stream')
                        return false;
                    return self::extractableFile($tmpmime);
                }
                return false;
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
                return \OCP\Util::imagePath('core', 'filetypes/text.svg');
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
    public function indexDocument(&$document, &$error = '')
    {
        if (! $this->configured())
            return false;
        
        if ($document->getAbsolutePath() == null)
            return false;
        
        if ($document->getType() == null || $document->getType() == '') {
            $error = self::ERROR_TYPE_NOT_SET;
            return false;
        }
        
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
            
            if ($document->isExtractable()) {
                $query = $client->createExtract();
                $query->setUprefix('nextant_attr_');
                $query->addFieldMapping('content', 'text');
                
                $query->addFieldMapping('div', 'ignored_');
                $query->addFieldMapping('html', 'ignored_');
                $query->addFieldMapping('link', 'ignored_');
                $query->addFieldMapping('style', 'ignored_');
                $query->addFieldMapping('script', 'ignored_');
                $query->addFieldMapping('input', 'ignored_');
                $query->addFieldMapping('form', 'ignored_');
                $query->addFieldMapping('img', 'ignored_');
                $query->addFieldMapping('a', 'ignored_');
                $query->addFieldMapping('p', 'ignored_');
                $query->addFieldMapping('span', 'ignored_');
                $query->addFieldMapping('h1', 'ignored_');
                $query->addFieldMapping('h2', 'ignored_');
                $query->addFieldMapping('h3', 'ignored_');
                $query->addFieldMapping('table', 'ignored_');
                $query->addFieldMapping('tr', 'ignored_');
                $query->addFieldMapping('td', 'ignored_');
                $query->addFieldMapping('b', 'ignored_');
                $query->addFieldMapping('i', 'ignored_');
                $query->addFieldMapping('ul', 'ignored_');
                $query->addFieldMapping('li', 'ignored_');
                
                $query->addFieldMapping('media_black_point', 'ignored_');
                $query->addFieldMapping('media_white_point', 'ignored_');
                
                $query->setFile($document->getAbsolutePath());
                $query->setOmitHeader(true);
            } else
                $query = $client->createUpdate();
                
                // add document
            $doc = $query->createDocument();
            $doc->id = $document->getType() . '_' . $document->getId();
            $doc->nextant_source = $document->getType();
            
            $doc->nextant_mtime = $document->getMTime();
            $doc->nextant_owner = $this->owner;
            $doc->nextant_path = $document->getPath();
            $doc->nextant_share = $document->getShare();
            $doc->nextant_sharegroup = $document->getShareGroup();
            $doc->nextant_deleted = $document->isDeleted();
            
            if ($document->isExtractable()) {
                $query->setCommit(true);
                $query->setDocument($doc);
                
                // custom options
                $request = $client->createRequest($query);
                $request->addParam('captureAttr', true);
                $request->addParam('ignoreTikaException', true);
                
                $response = $client->executeRequest($request);
                $ret = $client->createResult($query, $response);
                
                if ($ret) {
                    $document->extracted(true);
                    $document->processed(true);
                    $document->indexed(true);
                    return true;
                }
            } else {
                $query->addCommit();
                $query->addDocuments(array(
                    $doc
                ));
                
                $ret = $client->update($query);
                
                if ($ret) {
                    $document->processed(true);
                    $document->indexed(true);
                    return true;
                }
            }
        } catch (\Solarium\Exception\HttpException $ehe) {
            if ($ehe->getStatusMessage() == 'OK')
                $error = self::EXCEPTION_EXTRACT_FAILED;
            else
                $error = self::EXCEPTION_HTTPEXCEPTION;
        } catch (\Solarium\Exception $e) {
            $error = self::EXCEPTION;
        }
        
        $document->failedExtract(true);
        
        return false;
    }

    public function search($string, $options = array(), &$error = '')
    {
        if (! $this->configured())
            return false;
        
        if ($this->getClient() == false)
            return false;
        
        $string = str_replace('  ', ' ', trim($string));
        
        if ($string == '')
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
            
            $query->setRows(25);
            
            // $query->setQuery('text:' . ((! in_array('complete_words', $options)) ? '*' : '') . $helper->escapePhrase($string));
            
            array_push($options, 'complete_words');
            $q = 'text:' . $helper->escapeTerm($string) . "\n";
            $words = explode(' ', $string);
            foreach ($words as $word)
                $q .= 'nextant_path:*' . $helper->escapeTerm($word) . '*' . "\n";
                
                // $this->miscService->log($q);
            $query->setQuery($q);
            
            $query->createFilterQuery('owner')->setQuery($ownerQuery);
            
            $query->setFields(array(
                'id',
                'nextant_deleted',
                'nextant_path',
                'nextant_source',
                'nextant_owner'
            ));
            
            // if (key_exists('current_directory', $options))
            // $query->setQuery('nextant_path:' . $helper->escapePhrase($options['current_directory']));
            
            $hl = $query->getHighlighting();
            $hl->setFields(array(
                'text'
            ));
            
            if ($this->configService->getAppValue('display_result') == ConfigService::SEARCH_DISPLAY_NEXTANT) {
                $hl->setSimplePrefix('<span class="nextant_hl">');
                $hl->setSimplePostfix('</span>');
            } else {
                $hl->setSimplePrefix('');
                $hl->setSimplePostfix('');
            }
            $hl->setSnippets(3);
            
            $resultset = $client->select($query);
            $highlighting = $resultset->getHighlighting();
            
            $return = array();
            foreach ($resultset as $document) {
                
                // highlight
                $hlDoc = $highlighting->getResult($document->id);
                list ($type, $docid) = explode('_', $document->id, 2);
                array_push($return, array(
                    'id' => $docid,
                    'type' => $type,
                    'path' => $document->nextant_path,
                    'source' => $document->nextant_source,
                    'shared' => ($document->nextant_owner != $this->owner),
                    'deleted' => $document->nextant_deleted,
                    'owner' => $document->nextant_owner,
                    'highlight' => $hlDoc->getField('text'),
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

    public function suggest($string, &$error = 0)
    {
        $error = 0;
        
        if (! $this->configured())
            return false;
        
        if ($this->getClient() == false)
            return false;
        
        try {
            $client = $this->getClient();
            $query = $client->createSuggester();
            
            $query->setQuery($string);
            
            $query->setDictionary('suggest');
            $query->setOnlyMorePopular(true);
            $query->setCount(5);
            $query->setCollate(true);
            
            $resultset = $client->suggester($query);
            
            $t = 0;
            $suggTotal = sizeof($resultset);
            $suggestions = array();
            foreach ($resultset as $term => $termResult) {
                
                $t ++;
                if ($t == $suggTotal) {
                    foreach ($termResult as $result)
                        $suggestions[] = array(
                            'suggestion' => '<b>' . $string . '</b>' . (($termResult->getEndOffset() >= strlen($string)) ? substr($result, strlen($term)) : '')
                        );
                }
            }
            
            return $suggestions;
        } catch (\Solarium\Exception\HttpException $ehe) {
            if ($ehe->getStatusMessage() == 'OK')
                $error = self::EXCEPTION_SUGGEST_FAILED;
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
        
        // if ($type & self::SEARCH_EXTERNAL) {
        // $ownerQuery .= (($ownerQuery != '') ? 'OR ' : '') . 'nextant_share:"__all" ';
        // }
        
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

