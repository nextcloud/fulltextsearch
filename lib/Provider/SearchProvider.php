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
namespace OCA\Nextant\Provider;

use OCP\Search\Provider;
use \OCA\Nextant\Service\FileService;

class SearchProvider extends \OCP\Search\Provider
{

    private $orig;

    private $app;

    private $miscService;

    private $solrService;

    private $userId;

    public function __construct()
    {
        $app = new \OCA\Nextant\AppInfo\Application();
        $container = $app->getContainer();
        $app->registerSearchEngine();
        
        $this->app = $app;
        $this->miscService = $container->query('MiscService');
        $this->solrService = $container->query('SolrService');
        $this->userId = $container->query('UserId');
        $this->solrService->setOwner($this->userId);
    }

    /**
     * performs a search
     *
     * @param string $query            
     * @return array
     *
     */
    public function search($query)
    {
        $results = array();
        if ($query !== null) {
            
            $solrResult = $this->solrService->search($query);
            foreach ($solrResult as $data) {
                
                // This is not clean, but right now it is the only descent way I found to display result
                $fileData = FileService::getFileInfo($data['id']);
                $result = new \OC\Search\Result\File($fileData);
                $result->type = 'nextant';
                $result->name = $result->path . ' (Score: ' . round($data['score'] * 100, 1) . ') ';
//                $this->miscService->log(">> " . var_export($result, true));
                $results[] = $result;
            }
        }
        
        return $results;
    }
}