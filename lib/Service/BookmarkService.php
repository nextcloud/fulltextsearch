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

use \OCA\Nextant\Items\ItemDocument;
use OCA\Bookmarks\Controller\Lib\Bookmarks;

class BookmarkService
{

    private $configService;
    
    // private $solrService;
    
    // private $solrTools;
    private $miscService;

    private $libBookmarks = null;

    public function __construct($configService, $miscService)
    {
        $this->configService = $configService;
        $this->miscService = $miscService;
    }

    public function configured()
    {
        if (! \OCP\App::isEnabled('bookmarks'))
            return false;
        
        if ($this->configService->getAppValue('index_bookmarks') === '1')
            return true;
        
        return false;
    }

    public function syncDocument(&$item)
    {
        $item->synced(true);
        $item->extractable(true);
        
        return true;
    }

    /**
     * get bookmarks from a specific user
     *
     * @param number $userId            
     * @return array
     */
    public function getBookmarksPerUserId($userId)
    {
        if (! $this->configured())
            return false;
            
            // if ($this->configService->getCloudVersion() >= 11) {
        if ($this->libBookmarks == null) {
            $bookmarkApp = new \OCA\Bookmarks\AppInfo\Application();
            $this->libBookmarks = $bookmarkApp->getContainer()->query(\OCA\Bookmarks\Controller\Lib\Bookmarks::class);
        }
        
        $bookmarks = $this->libBookmarks->findBookmarks($userId, 0, 'id', array(), false, - 1);
        // } else {
        // $db = \OC::$server->getDb();
        // $bookmarks = Bookmarks::findBookmarks($userId, $db, 0, 'id', array(), false, - 1);
        // }
        
        $data = array();
        foreach ($bookmarks as $bookmark) {
            $item = new ItemDocument(ItemDocument::TYPE_BOOKMARK, $bookmark['id']);
            $item->setMTime($bookmark['lastmodified']);
            $item->setAbsolutePath($bookmark['url']);
            $item->setPath($bookmark['url']);
            $data[] = $item;
        }
        
        return $data;
    }

    public static function getSearchResult(&$data)
    {
        $entry = array(
            'id' => $data->getId(),
            'mtime' => $data->getMTime() * 1000,
            'name' => $data->getPath(),
            'permissions' => 27,
            'type' => 'bookmark'
        );
        // 'mimetype' => 'text/html',
        
        $data->setEntry($entry);
        $data->valid(true);
        
        return true;
    }
}