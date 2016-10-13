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
use \OCA\Nextant\Service\ConfigService;
use \OCA\Nextant\Service\MiscService;
use Solarium\Core\Client\Request;
use Solarium\Solarium;

/**
 *
 * Admin Tools to manage Solr Core
 *
 * @author Maxence Lange
 *        
 */
class SolrAdminService
{

    private $solrService;

    private $configService;

    private $miscService;

    private $lastMessage;

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
    }

    /**
     * Check the Schema of the Solr Core.
     *
     * @param boolean $fix            
     * @param number $error            
     * @return boolean
     */
    public function checkSchema($fix = false, &$error = 0)
    {
        if (! $this->solrService || ! $this->solrService->configured() || ! $this->solrService->getClient())
            return false;
        
        $client = $this->solrService->getClient();
        
        $fields = array();
        array_push($fields, array(
            'type' => 'dynamic-field',
            'data' => array(
                'name' => 'nextant_attr_*',
                'type' => 'text_general',
                'indexed' => true,
                'stored' => true,
                'multiValued' => true
            )
        ));
        array_push($fields, array(
            'type' => 'field',
            'data' => array(
                'name' => 'nextant_path',
                'type' => 'string',
                'indexed' => true,
                'stored' => true,
                'multiValued' => false
            )
        ));
        array_push($fields, array(
            'type' => 'field',
            'data' => array(
                'name' => 'text',
                'type' => 'text_general',
                'indexed' => true,
                'stored' => true,
                'multiValued' => false
            )
        ));
        array_push($fields, array(
            'type' => 'field',
            'data' => array(
                'name' => 'nextant_owner',
                'type' => 'string',
                'indexed' => true,
                'stored' => true,
                'multiValued' => false
            )
        ));
        array_push($fields, array(
            'type' => 'field',
            'data' => array(
                'name' => 'nextant_mtime',
                'type' => 'int',
                'indexed' => true,
                'stored' => true,
                'multiValued' => false
            )
        ));
        array_push($fields, array(
            'type' => 'field',
            'data' => array(
                'name' => 'nextant_share',
                'type' => 'string',
                'indexed' => true,
                'stored' => true,
                'multiValued' => true
            )
        ));
        array_push($fields, array(
            'type' => 'field',
            'data' => array(
                'name' => 'nextant_sharegroup',
                'type' => 'string',
                'indexed' => true,
                'stored' => true,
                'multiValued' => true
            )
        ));
        array_push($fields, array(
            'type' => 'field',
            'data' => array(
                'name' => 'nextant_deleted',
                'type' => 'boolean',
                'indexed' => true,
                'stored' => true,
                'multiValued' => false
            )
        ));
        array_push($fields, array(
            'type' => 'field',
            'data' => array(
                'name' => 'nextant_source',
                'type' => 'string',
                'indexed' => true,
                'stored' => true,
                'multiValued' => false
            )
        ));
        array_push($fields, array(
            'type' => 'field',
            'data' => array(
                'name' => 'nextant_tags',
                'type' => 'string',
                'indexed' => true,
                'stored' => true,
                'multiValued' => true
            )
        ));
        array_push($fields, array(
            'type' => 'field',
            'data' => array(
                'name' => 'nextant_ocr',
                'type' => 'int',
                'indexed' => true,
                'stored' => true,
                'multiValued' => false
            )
        ));
        
        $this->solrService->message('Checking Solr schema fields');
        
        $changed = false;
        while (true) {
            foreach ($fields as $field) {
                $this->solrService->message(' * Checking ' . $field['type'] . ' \'' . $field['data']['name'] . '\': ', false);
                if (self::checkFieldProperty($client, $field, $curr))
                    $this->solrService->message('ok.');
                else {
                    $this->solrService->message('fail. ');
                    
                    if ($fix) {
                        $changed = true;
                        $this->solrService->message('   -> Fixing ' . $field['type'] . ' \'' . $field['data']['name'] . '\'');
                        if ($curr)
                            self::modifyField($client, $field);
                        else
                            self::createField($client, $field);
                    } else
                        return false;
                }
            }
            
            break;
        }
        
        if ($changed)
            $this->configService->setAppValue('index_files_needed', '1');
        
      //  $this->configService->setAppValue('configured', '1');
        return true;
    }

    /**
     * Ping and test connection to the Solr Core
     *
     * @param number $error            
     * @return boolean
     */
    public function ping(&$error = 0)
    {
        if (! $this->solrService || ! $this->solrService->configured() || ! $this->solrService->getClient())
            return false;
        
        $client = $this->solrService->getClient();
        $ping = $client->createPing();
        
        try {
            $result = $client->ping($ping);
            return true;
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

    /**
     * Check a field property from a Solr core using Solarium Client
     *
     * @param \Solarium\Client $client            
     * @param array $field            
     * @param array $property            
     * @return boolean
     */
    private static function checkFieldProperty(\Solarium\Client $client, $field, &$property)
    {
        $property = self::getFieldProperty($client, $field['type'], $field['data']['name']);
        if (! $property)
            return false;
        
        $k = array_keys($field['data']);
        foreach ($k as $key) {
            if ($field['data'][$key] != $property[$key])
                return false;
        }
        
        return true;
    }

    /**
     * Get properties on a field based on its type and name
     *
     * @param \Solarium\Client $client            
     * @param string $fieldType            
     * @param string $fieldName            
     * @return boolean|mixed
     */
    private static function getFieldProperty(\Solarium\Client $client, $fieldType, $fieldName)
    {
        $url = '';
        if ($fieldType == 'field')
            $url = 'schema/fields/';
        if ($fieldType == 'dynamic-field')
            $url = 'schema/dynamicfields/';
        if ($url == '')
            return false;
        
        $query = $client->createSelect();
        $request = $client->createRequest($query);
        
        $request->setHandler($url . $fieldName);
        
        $response = $client->executeRequest($request);
        if ($response->getStatusCode() != 200)
            return false;
        
        $result = json_decode($response->getBody());
        foreach ($result as $data) {
            foreach ($data as $k => $v)
                $property[$k] = $v;
        }
        
        return $property;
    }

    /**
     * create field on the Solr Core
     *
     * @param \Solarium\Client $client            
     * @param array $field            
     */
    private static function createField(\Solarium\Client $client, $field)
    {
        $data = array(
            'add-' . $field['type'] => $field['data']
        );
        return self::solariumPostSchemaRequest($client, $data);
    }

    /**
     * modify field on the Solr Core
     *
     * @param \Solarium\Client $client            
     * @param array $field            
     */
    private static function modifyField(\Solarium\Client $client, $field)
    {
        $data = array(
            'replace-' . $field['type'] => $field['data']
        );
        return self::solariumPostSchemaRequest($client, $data);
    }

    /**
     * delete field on the Solr Core
     *
     * @param \Solarium\Client $client            
     * @param array $field            
     */
    private static function deleteField(\Solarium\Client $client, $field)
    {
        $data = array(
            'delete-' . $field['type'] => array(
                'name' => $field['data']['name']
            )
        );
        return self::solariumPostSchemaRequest($client, $data);
    }

    /**
     * send post request to the Solr core
     *
     * @param \Solarium\Client $client            
     * @param array $data            
     * @return boolean
     */
    private static function solariumPostSchemaRequest(\Solarium\Client $client, $data)
    {
        try {
            
            $query = $client->createSelect();
            $request = $client->createRequest($query);
            
            $request->setHandler('schema');
            $request->setMethod(Request::METHOD_POST);
            
            $request->setRawData(json_encode($data));
            $response = $client->executeRequest($request);
            
            if ($response->getStatusCode() != 200)
                return false;
            
            return true;
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

    /**
     * reset Solr Core
     *
     * @param number $error            
     * @return boolean
     */
    public function clear(&$error = 0)
    {
        if (! $this->solrService || ! $this->solrService->configured() || ! $this->solrService->getClient())
            return false;
        
        $client = $this->solrService->getClient();
        
        try {
            $update = $client->createUpdate();
            
            $update->addDeleteQuery('*:*');
            $update->addCommit();
            $result = $client->update($update);
            
            return true;
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
    