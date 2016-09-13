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

use Solarium\Core\Client\Request;

class SolrAdminService
{

    private $solariumClient;

    private $configService;

    private $miscService;

    private $output;

    private $lastMessage;

    public function __construct($solrClient, $configService, $miscService)
    {
        $this->solariumClient = $solrClient;
        $this->configService = $configService;
        $this->miscService = $miscService;
        $this->output = null;
    }

    public function setOutput(&$output)
    {
        $this->output = $output;
    }

    public function checkSchema($fix = false, &$error = '')
    {
        $fields = array();
        array_push($fields, array(
            'name' => 'nextant_owner',
            'type' => 'string',
            'indexed' => true,
            'stored' => true,
            'multiValued' => false
        ));
        array_push($fields, array(
            'name' => 'nextant_share',
            'type' => 'string',
            'indexed' => true,
            'stored' => true,
            'multiValued' => true
        ));
        array_push($fields, array(
            'name' => 'nextant_sharegroup',
            'type' => 'string',
            'indexed' => true,
            'stored' => true,
            'multiValued' => true
        ));
        array_push($fields, array(
            'name' => 'nextant_deleted',
            'type' => 'boolean',
            'indexed' => true,
            'stored' => false,
            'multiValued' => false
        ));
        
        $this->message('Checking Solr schema fields');
        
        while (true) {
            foreach ($fields as $field) {
                $this->message(' * field ' . $field['name'] . ': ', false);
                if ($this->checkFieldProperty($field, $curr))
                    $this->message('ok.');
                else {
                    $this->message('fail. ');
                    
                    if ($fix) {
                        $this->message('   -> Fixing field ' . $field['name']);
                        if ($curr)
                            $this->modifyField($field);
                        else
                            $this->createField($field);
                    } else
                        return false;
                }
            }
            
            break;
        }
        
        return true;
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
                $error = SolrClient::EXCEPTION_SOLRURI;
            else
                $error = SolrClient::EXCEPTION_HTTPEXCEPTION;
        } catch (\Solarium\Exception $e) {
            $error = SolrClient::EXCEPTION;
        }
        
        return false;
    }

    private function checkFieldProperty($field, &$property)
    {
        $property = $this->getFieldProperty($field['name']);
        if (! $property)
            return false;
        
        $k = array_keys($field);
        foreach ($k as $key) {
            if ($field[$key] != $property[$key])
                return false;
        }
        
        return true;
    }

    private function getFieldProperty($fieldName)
    {
        $client = $this->solariumClient;
        
        $query = $client->createSelect();
        $request = $client->createRequest($query);
        
        $request->setHandler('schema/fields/' . $fieldName);
        
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

    private function createField($field)
    {
        $data = array(
            'add-field' => $field
        );
        return $this->solariumPostSchemaRequest($data);
    }

    private function modifyField($field)
    {
        $data = array(
            'replace-field' => $field
        );
        return $this->solariumPostSchemaRequest($data);
    }

    private function deleteField($fieldName)
    {
        $data = array(
            'delete-field' => array(
                'name' => $fieldName
            )
        );
        return $this->solariumPostSchemaRequest($data);
    }

    private function solariumPostSchemaRequest($data)
    {
        try {
            $client = $this->solariumClient;
            
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
                $error = SolrClient::EXCEPTION_SOLRURI;
            else
                $error = SolrClient::EXCEPTION_HTTPEXCEPTION;
        } catch (\Solarium\Exception $e) {
            $error = SolrClient::EXCEPTION;
        }
        return false;
    }

    public function clear(&$error = '')
    {
        $client = $this->solariumClient;
        
        try {
            $update = $client->createUpdate();
            
            $update->addDeleteQuery('*:*');
            $update->addCommit();
            $result = $client->update($update);
            
            return true;
        } catch (\Solarium\Exception\HttpException $ehe) {
            if ($ehe->getStatusMessage() == 'OK')
                $error = SolrClient::EXCEPTION_SOLRURI;
            else
                $error = SolrClient::EXCEPTION_HTTPEXCEPTION;
        } catch (\Solarium\Exception $e) {
            $error = SolrClient::EXCEPTION;
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
                $error = SolrClient::EXCEPTION_SOLRURI;
            else
                $error = SolrClient::EXCEPTION_HTTPEXCEPTION;
        } catch (\Solarium\Exception $e) {
            $error = SolrClient::EXCEPTION;
        }
        
        return false;
    }

    private function message($line, $newline = true)
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
    