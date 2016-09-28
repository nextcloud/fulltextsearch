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

class SolrAdminService
{

    private $solrService;

    private $configService;

    private $miscService;

    private $lastMessage;

    public function __construct($solrService, $configService, $miscService)
    {
        // $this->solariumClient = $solrClient;
        $this->solrService = $solrService;
        $this->configService = $configService;
        $this->miscService = $miscService;
    }

    public function checkSchema($fix = false, &$error = '')
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
            $this->configService->setAppValue('needed_index', '1');
        
        $this->configService->setAppValue('configured', '1');
        return true;
    }

    public function ping(&$error = '')
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

    private static function checkFieldProperty($client, $field, &$property)
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

    private static function getFieldProperty($client, $fieldType, $fieldName)
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

    private static function createField($client, $field)
    {
        $data = array(
            'add-' . $field['type'] => $field['data']
        );
        return self::solariumPostSchemaRequest($client, $data);
    }

    private static function modifyField($client, $field)
    {
        $data = array(
            'replace-' . $field['type'] => $field['data']
        );
        return self::solariumPostSchemaRequest($client, $data);
    }

    private static function deleteField($client, $field)
    {
        $data = array(
            'delete-' . $field['type'] => array(
                'name' => $field['data']['name']
            )
        );
        return self::solariumPostSchemaRequest($client, $data);
    }

    private static function solariumPostSchemaRequest($client, $data)
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

    public function clear(&$error = '')
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

    public function count(&$error = '')
    {
        if (! $this->solrService || ! $this->solrService->configured() || ! $this->solrService->getClient())
            return false;
        
        $client = $this->solrService->getClient();
        
        try {
            $query = $client->createSelect();
            $query->setQuery('*:*');
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
    