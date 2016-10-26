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
namespace OCA\Nextant\Events;

use \OCA\Nextant\Service\MiscService;

class BookmarksEvents
{

    private $configService;

    private $userId;

    private $solrService;

    private $miscService;

    public function __construct($configService, $userId, $solrService, $miscService)
    {
        $this->userId = $userId;
        $this->configService = $configService;
        $this->solrService = $solrService;
        $this->miscService = $miscService;
        
        $this->solrService->setOwner($this->userId);
    }

    /**
     * onBookmarkAdd()
     *
     * @param number $bookmarkId            
     */
    public function onBookmarkAdd($bookmarkId)
    {
        if ($this->configService->getAppValue('index_bookmarks') === '1')
            $this->configService->needIndexBookmarks(true);
    }

    /**
     * onBookmarkEdit()
     *
     * @param number $bookmarkId            
     */
    public function onBookmarkEdit($bookmarkId)
    {
        if ($this->configService->getAppValue('index_bookmarks') === '1')
            $this->configService->needIndexBookmarks(true);
    }

    /**
     * onBookmarkRemove()
     *
     * @param number $bookmarkId            
     */
    public function onBookmarkDelete($bookmarkId)
    {
        if ($this->configService->getAppValue('index_bookmarks') === '1')
            $this->configService->needIndexBookmarks(true);
    }
}

