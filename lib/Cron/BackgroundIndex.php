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
namespace OCA\Nextant\Cron;

use \OCA\Nextant\Service\FileService;
use \OCA\Nextant\Items\ItemDocument;
use \OCA\Nextant\AppInfo\Application;

class BackgroundIndex extends \OC\BackgroundJob\TimedJob
{

    private $configService;

    private $solrAdmin;

    private $miscService;

    public function __construct()
    {
        $this->setInterval(5); // 2 minutes
    }

    protected function run($argument)
    {
        $app = new Application();
        $c = $app->getContainer();
        
        $this->configService = $c->query('ConfigService');
        $this->miscService = $c->query('MiscService');
        $this->userManager = $c->query('UserManager');
        $this->solrService = $c->query('SolrService');
        $this->solrTools = $c->query('SolrToolsService');
        $this->solrAdmin = $c->query('SolrAdminService');
        $this->fileService = $c->query('FileService');
        $this->indexService = $c->query('IndexService');
        $this->bookmarkService = $c->query('BookmarkService');
        $this->queueService = $c->query('QueueService');
        $this->rootFolder = $c->query('RootFolder');
        
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
        $this->fileService->setDebug($debug);
    }

    private function liveIndex()
    {
        while (($item = $this->queueService->readQueue()) !== false) {
            $this->queueService->executeItem($item);
        }
    }

    private function cronIndex()
    {
        if (($this->configService->timeIndexDelay('files') && $this->configService->neededIndexFiles()) || $this->configService->timeIndexDelay('files', 24)) {
            // $this->miscService->log('___cronFiles');
            $this->configService->needIndexFiles(false);
            $this->cronIndexFiles();
            $this->cronUpdateFiles();
            $this->configService->timeIndex('files');
        }
        
        if (($this->configService->timeIndexDelay('bookmarks') && $this->configService->neededIndexBookmarks()) || $this->configService->timeIndexDelay('bookmarks', 24)) {
            // $this->miscService->log('___cronBookmarks');
            $this->configService->needIndexBookmarks(false);
            $this->cronIndexBookmarks();
            $this->configService->timeIndex('bookmarks');
        }
    }

    private function cronIndexFiles()
    {
        if (! $this->fileService->configured())
            return;
        
        $users = $this->userManager->search('');
        
        foreach ($users as $user) {
            
            $this->fileService->initUser($user->getUID());
            $files = $this->fileService->getFilesPerUserId('/files', array());
            $files_trashbin = $this->fileService->getFilesPerUserId('/files_trashbin', array(
                'deleted'
            ));
            
            $files = array_merge($files, $files_trashbin);
            $solrDocs = null;
            $this->indexService->extract(ItemDocument::TYPE_FILE, $user->getUID(), $files, $solrDocs);
            $this->indexService->removeOrphans(ItemDocument::TYPE_FILE, $user->getUID(), $files, $solrDocs);
            
            $this->fileService->endUser();
        }
    }

    private function cronUpdateFiles()
    {
        if (! $this->fileService->configured())
            return;
        
        $users = $this->userManager->search('');
        foreach ($users as $user) {
            
            $this->fileService->initUser($user->getUID());
            $files = $this->fileService->getFilesPerUserId('/files', array());
            $files_trashbin = $this->fileService->getFilesPerUserId('/files_trashbin', array(
                'deleted'
            ));
            
            $files = array_merge($files, $files_trashbin);
            $this->indexService->updateDocuments(ItemDocument::TYPE_FILE, $user->getUID(), $files);
            
            $this->fileService->endUser();
        }
    }

    private function cronIndexBookmarks()
    {
        if (! $this->bookmarkService->configured())
            return;
        
        $users = $this->userManager->search('');
        foreach ($users as $user) {
            $bm = $this->bookmarkService->getBookmarksPerUserId($user->getUID());
            
            $solrDocs = null;
            $this->indexService->extract(ItemDocument::TYPE_BOOKMARK, $user->getUID(), $bm, $solrDocs);
            $this->indexService->removeOrphans(ItemDocument::TYPE_BOOKMARK, $user->getUID(), $bm, $solrDocs);
        }
    }
}
