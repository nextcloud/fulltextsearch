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

/**
 * ***
 * This Controller is now useless
 * **
 */
namespace OCA\Nextant\Controller;

use OCP\AppFramework\Controller;
use OCP\IRequest;

class SearchController extends Controller
{

    private $userId;

    private $groupManager;

    private $solrService;

    private $miscService;

    public function __construct($appName, IRequest $request, $userId, $groupManager, $solrService, $miscService)
    {
        parent::__construct($appName, $request);
        
        $this->userId = $userId;
        $this->groupManager = $groupManager;
        $this->solrService = $solrService;
        $this->miscService = $miscService;
    }

    public function searchRequest($query, $current_dir)
    {
        $results = array();
        
        if ($this->solrService == false)
            return $results;
        
        if ($query !== null) {
            
            // $groups
            $groups = array_map(function ($value) {
                return (string) $value;
            }, array_keys($this->groupManager->getUserIdGroups($this->userId)));
            $this->solrService->setOwner($this->userId, $groups);
            
            $solrResult = $this->solrService->search($query, array(
                'current_directory' => $current_dir
            ));
            
            if ($solrResult == false)
                return $results;
            
            foreach ($solrResult as $data) {
                
                // $fileData = FileService::getFileInfo($data['id']);
                // if ($fileData === false)
                // continue;
                
                $response = array(
                    'id' => $data['id'],
                    'type' => 'file',
                    'shared' => ($data['owner'] != $this->userId),
                    'deleted' => ($data['deleted']),
                    'highlight' => '... ' . $data['highlight'] . ' ...'
                );
                
                array_push($results, $response);
            }
        }
        
        return $results;
    }
}
