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

use \OCA\Nextant\Service\FileService;
use OCP\AppFramework\Controller;
use OCP\IRequest;
use OC\Files\Filesystem;
use OCP\Files\NotFoundException;

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
        Filesystem::init($this->userId, '');
        $view = Filesystem::getView();
        
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
                
                try {
                    $path = $view->getPath($data['id']);
                    $fileData = $view->getFileInfo($path);
                    
                    if ($fileData === false)
                        continue;
                    
                    $pathParts = pathinfo($path);
                    $basepath = str_replace('//', '/', '/' . $pathParts['dirname'] . '/');
                    
                    $hl1 = '';
                    $hl2 = '';
                    if (key_exists('highlight', $data) && is_array($data['highlight'])) {
                        if (sizeof($data['highlight']) == 1)
                            $hl1 = '... ' . $data['highlight'][0] . ' ...';
                        if (sizeof($data['highlight']) > 1)
                            $hl2 = '... ' . $data['highlight'][1] . ' ...';
                    }
                    
                    $this->miscService->log('$$' . $hl1 . '$$' . $hl2 . '$$');
                    
                    if ($hl1 == '' || $hl1 == null)
                        $hl1 = '';
                    if ($hl2 == '' || $hl2 == null)
                        $hl2 = '';
                    
                    $response = array(
                        'id' => $data['id'],
                        'type' => 'file',
                        'shared' => ($data['owner'] != $this->userId),
                        'deleted' => ($data['deleted']),
                        'size' => $fileData->getSize(),
                        'path' => $path,
                        'basepath' => $basepath,
                        'filename' => $pathParts['basename'],
                        'basefile' => $pathParts['filename'],
                        'highlight1' => $hl1,
                        'highlight2' => $hl2,
                        'extension' => ($pathParts['extension'] != '') ? '.' . $pathParts['extension'] : '',
                        'mime' => $fileData->getMimetype(),
                        'webdav' => \OCP\Util::linkToRemote('webdav') . $path,
                        'mtime' => $fileData->getMTime()
                    );
                    
                    array_push($results, $response);
                } catch (NotFoundException $e) {
                    continue;
                }
            }
        }
        
        return $results;
    }
}
