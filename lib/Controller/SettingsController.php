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
namespace OCA\Nextant\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use \OCA\Nextant\Service\ConfigService;

class SettingsController extends Controller
{

    private $configService;

    private $miscService;

    private $solrService;

    private $solr_url;

    public function __construct($appName, IRequest $request, ConfigService $configService, $solrService, $miscService)
    {
        parent::__construct($appName, $request);
        $this->configService = $configService;
        $this->solrService = $solrService;
        $this->miscService = $miscService;
    }

    /**
     * @NoCSRFRequired
     */
    public function index()
    {
        $params = [
            'solr_url' => $this->configService->getAppValue('solr_url')
        ];
        return new TemplateResponse($this->appName, 'settings.admin', $params, 'blank');
    }

    public function setSettings($solr_url, $command)
    {
        $this->solr_url = $solr_url;
        
        $tmpConfig = array(
            'solr_url' => $solr_url
        );
        $this->solrService->setClient($tmpConfig);
        $this->solrService->setOwner('__nextant_test_owner');
        
        $message = '';
        $result = false;
        switch ($command) {
            case 'ping':
                $result = $this->test_ping($message);
                break;
            
            case 'extract':
                $result = $this->test_extract($message);
                break;
            
            case 'save':
                $result = $this->save($message);
                break;
        }
        
        $response = array(
            'command' => $command,
            'status' => $result ? 'success' : 'failure',
            'data' => array(
                'message' => $message
            )
        );
        
        return $response;
    }
    
    // Wiki Error 9
    private function test_ping(&$message)
    {
        if ($this->solrService->ping($error)) {
            $message = 'Apache Solr is up, running and responding to our ping query';
            return true;
        } else {
            $message = 'Apache Solr is not responding to our ping query (Error #' . $error . ')';
            return false;
        }
    }

    private function test_extract(&$message)
    {
        $testFile = __DIR__ . '/../../LICENSE';
        if ($this->solrService->extractFile($testFile, '__nextant_test', 'text/plain', $error)) {
            $message = 'Text were successfully extracted';
            return true;
        } else {
            $message = 'Extract failed. Please check the configuration of your Solr server (Error #' . $error . ')';
            return false;
        }
    }

    private function save(&$message)
    {
        if (! is_null($this->solr_url)) {
            $this->configService->setAppValue('solr_url', $this->solr_url);
            $message = "Your configuration has been saved";
            return true;
        }
        $message = 'Configuration failed to be saved. Please reload this page.';
        return false;
    }
}