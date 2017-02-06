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

class BackgroundService
{

    private $userManager;

    private $rootFolder;

    private $configService;

    private $solrService;

    private $solrToolsService;

    private $solrAdminService;

    private $sourceService;

    private $indexService;

    private $queueService;

    private $miscService;

    public function __construct($userManager, $rootFolder, $configService, $solrService, $solrToolsService, $solrAdminService, $sourceService, $indexService, $queueService, $miscService)
    {
        $this->miscService = $miscService;
        $this->userManager = $userManager;
        $this->configService = $configService;
        $this->solrService = $solrService;
        $this->solrTools = $solrToolsService;
        $this->solrAdmin = $solrAdminService;
        $this->sourceService = $sourceService;
        $this->indexService = $indexService;
        $this->queueService = $queueService;
        $this->rootFolder = $rootFolder;
    }

    public function run()
    {
        if (! $this->solrService->configured(false))
            return;
        
        if (! $this->solrAdmin->ping())
            return;
        
        if ($this->configService->isLockedIndex())
            return;
        
        $this->indexService->lockActive(true);
        $this->configService->lockIndex(true);
        
        $this->liveIndex();
        $this->cronIndex();
        
        $this->configService->lockIndex(false);
        
        return;
    }

    public function setDebug($debug)
    {
        $this->miscService->setDebug($debug);
        $this->sourceService->file()->setDebug($debug);
    }

    private function liveIndex()
    {
        if ($this->configService->getAppValue('index_live') === '0')
            return;
        
        while (($item = $this->queueService->readQueue()) !== false) {
            $this->queueService->executeItem($item);
        }
    }

    private function cronIndex()
    {
        if (($this->configService->timeIndexDelay('index_live') !== '1' && $this->configService->timeIndexDelay('files') && $this->configService->neededIndexFiles()) || $this->configService->timeIndexDelay('files', 24 * $this->configService->getAppValue('index_delay_max'))) {
            $this->configService->needIndexFiles(false);
            $this->cronIndexFiles();
            $this->cronUpdateFiles();
            $this->configService->timeIndex('files');
        }
        
        if (($this->configService->timeIndexDelay('index_live') !== '1' && $this->configService->timeIndexDelay('bookmarks') && $this->configService->neededIndexBookmarks()) || $this->configService->timeIndexDelay('bookmarks', 24 * $this->configService->getAppValue('index_delay_max'))) {
            $this->configService->needIndexBookmarks(false);
            $this->cronIndexBookmarks();
            $this->configService->timeIndex('bookmarks');
        }
    }

    private function cronIndexFiles()
    {
        if (! $this->sourceService->file()->configured())
            return;
        
        $users = $this->userManager->search('');
        
        foreach ($users as $user) {
            
            $this->sourceService->file()->initUser($user->getUID(), true);
            $files = $this->sourceService->file()->getFilesPerUserId('/files', array());
            
            if ($this->configService->getAppValue('index_files_trash') === '1') {
                $files_trashbin = $this->sourceService->file()->getFilesPerUserId('/files_trashbin', array(
                    'deleted'
                ));
                $files = array_merge($files, $files_trashbin);
            }
            $solrDocs = null;
            $this->indexService->extract(ItemDocument::TYPE_FILE, $user->getUID(), $files, $solrDocs);
            $this->indexService->removeOrphans(ItemDocument::TYPE_FILE, $user->getUID(), $files, $solrDocs);
            
            $this->sourceService->file()->endUser();
        }
    }

    private function cronUpdateFiles()
    {
        if (! $this->sourceService->file()->configured())
            return;
        
        $users = $this->userManager->search('');
        foreach ($users as $user) {
            
            $this->sourceService->file()->initUser($user->getUID(), true);
            $files = $this->sourceService->file()->getFilesPerUserId('/files', array());
            
            if ($this->configService->getAppValue('index_files_trash') === '1') {
                $files_trashbin = $this->sourceService->file()->getFilesPerUserId('/files_trashbin', array(
                    'deleted'
                ));
                $files = array_merge($files, $files_trashbin);
            }
            
            $this->indexService->updateDocuments(ItemDocument::TYPE_FILE, $user->getUID(), $files);
            
            $this->sourceService->file()->endUser();
        }
    }

    private function cronIndexBookmarks()
    {
        if (! $this->sourceService->bookmark()->configured())
            return;
        
        $users = $this->userManager->search('');
        foreach ($users as $user) {
            $bm = $this->sourceService->bookmark()->getBookmarksPerUserId($user->getUID());
            
            $solrDocs = null;
            $this->indexService->extract(ItemDocument::TYPE_BOOKMARK, $user->getUID(), $bm, $solrDocs);
            $this->indexService->removeOrphans(ItemDocument::TYPE_BOOKMARK, $user->getUID(), $bm, $solrDocs);
        }
    }
}