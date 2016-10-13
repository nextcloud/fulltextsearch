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

class BookmarkService
{

    private $configService;

    private $solrService;

    private $solrTools;

    private $miscService;

    public function __construct($configService, $solrService, $solrTools, $miscService)
    {
        // $this->root = $root;
        $this->configService = $configService;
        $this->solrService = $solrService;
        $this->solrTools = $solrTools;
        $this->miscService = $miscService;
    }

    public function configured()
    {
        if (! \OCP\App::isEnabled('bookmarks'))
            return false;
            
            // if ($this->configService->getAppValue('index_bookmarks') != 1)
            // return false;
        
        return true;
    }

    public static function getSearchResult(&$data)
    {
        $data['link_main'] = $data['path'];
        $data['title'] = $data['path'];
        $data['icon'] = \OCP\Util::imagePath('nextant', 'bookmarks.svg');
        
        return true;
    }
}