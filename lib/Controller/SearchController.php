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

/*****
 * This Controller is now useless
 ****/
/*
namespace OCA\Nextant\Controller;

use OCP\AppFramework\Controller;
use OCP\IRequest;
use \OCA\Nextant\Service\ConfigService;

class SearchController extends Controller
{

    private $appConfig;

    private $userId;

    private $miscService;

    public function __construct($appName, IRequest $request, AppConfig $appConfig, $userId, $miscService)
    {
        parent::__construct($appName, $request);
        $this->userId = $userId;
        $this->appConfig = $appConfig;
        $this->miscService = $miscService;
    }

    public function searchRequest($search)
    {
        $this->miscService->log('searchString() - ' . $search);
        
        $result = array();
                
        $response = array(
            'status' => 'success',
            'message' => 'OK_OK',
            'data' => $result
        );
        
        return $response;
    }
}
*/