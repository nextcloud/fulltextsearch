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

    private $appConfig;

    private $userId;

    public function __construct($appName, IRequest $request, ConfigService $appConfig, $userId)
    {
        parent::__construct($appName, $request);
        $this->userId = $userId;
        $this->appConfig = $appConfig;
    }

    /**
     * @NoCSRFRequired
     */
    public function index()
    {
        $params = [
            'solr_url' => $this->appConfig->getAppValue('solr_url')
        ];
        return new TemplateResponse($this->appName, 'settings.admin', $params, 'blank');
    }

    public function setSettings($solr_url)
    {
        if (! is_null($solr_url)) {
            $this->appConfig->setAppValue('solr_url', $solr_url);
        }
        
        $response = array(
            'status' => 'success',
            'data' => array(
                'message' => 'Saved'
            )
        );
        
        return $response;
    }
}