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
use \OCA\Nextant\AppInfo\Application;
use OC\Files\Filesystem;

class BackgroundIndex extends \OC\BackgroundJob\TimedJob
{

    private $configService;

    private $miscService;

    public function __construct()
    {
        $this->setInterval(60 * 60 * 2); // 2 hours
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
        $this->fileService = $c->query('FileService');
        $this->rootFolder = $c->query('RootFolder');
        
        // $this->setDebug(true);
        
        if (! $this->solrService->configured(false))
            return;
        
        if (! $this->configService->neededIndexFiles()) {
            $this->miscService->debug('Looks like there is no need to index');
            return;
        }
        
        $solr_locked = $this->configService->getAppValue('index_locked');
        if ($solr_locked > (time() - (3600 * 24))) {
            $this->miscService->log('The background index detected that your solr is locked by a running script. If it is not the case, you should start indexing manually using ./occ nextant:index --force');
            return;
        }
        
        $this->miscService->debug('Cron - Init');
        
        $this->configService->setAppValue('index_locked', time());
        if ($this->scanUsers()) {
            $this->configService->needIndexFiles(false);
            $this->configService->setAppValue('index_files_last', time());
        }
        $this->configService->setAppValue('index_locked', '0');
        
        $this->miscService->debug('Cron - End');
    }

    public function setDebug($debug)
    {
        $this->miscService->setDebug($debug);
        $this->fileService->setDebug($debug);
    }

    private function scanUsers()
    {
        $users = $this->userManager->search('');
        $extractedDocuments = array();
        
        $documentIds = array();
        $noFailure = true;
        foreach ($users as $user) {
            
            $userId = $user->getUID();
            $this->solrService->setOwner($userId);
            
            $result_files = $this->browseUserDirectory($userId, '/files', array(), $docIds_files);
            $result_trash = $this->browseUserDirectory($userId, '/files_trashbin', array(
                'deleted' => true
            ), $docIds_trash);
            
            if (! $result_files || ! $result_trash) {
                $this->miscService->log('Background index had some issue', 2);
                $noFailure = false;
            }
            $documentIds = array_merge($documentIds, $docIds_files, $docIds_trash);
        }
        
        // orphans
        $deleting = array();
        $page = 0;
        while (true) {
            
            $ids = $this->solrTools->getAll('files', $page, $lastPage, $error);
            if (! $ids)
                break;
            
            foreach ($ids as $id) {
                if (! in_array($id, $documentIds) && (! in_array($id, $deleting)))
                    array_push($deleting, $id);
            }
            
            if ($lastPage)
                break;
            
            $page ++;
            if ($page > 10000) {
                $this->miscService->log('Looks like we reached a 1,000,000 documents');
                break;
            }
        }
        
        foreach ($deleting as $docId)
            $this->solrTools->removeDocument($docId);
        
        return $noFailure;
    }

    private function browseUserDirectory($userId, $dir, $options, &$docIds)
    {
        $docIds = array();
        
        Filesystem::tearDown();
        Filesystem::init($userId, '');
        
        $this->fileService->setView(Filesystem::getView());
        
        $userFolder = FileService::getUserFolder($this->rootFolder, $userId, $dir);
        if ($userFolder == null || ! $userFolder)
            return true;
        
        $folder = $userFolder->get('/');
        
        $files = $folder->search('');
        
        sleep(5);
        
        $noFailure = true;
        $fileIds = array();
        foreach ($files as $file) {
            
            $this->miscService->debug('Cron - extract #' . $file->getId());
            if (! $file->isShared() && $file->getType() == \OCP\Files\FileInfo::TYPE_FILE) {
                if (($this->fileService->addFileFromPath($file->getPath(), false))) {
                    
                    array_push($docIds, (int) $file->getId());
                    array_push($fileIds, array(
                        'fileid' => $file->getId(),
                        'options' => $options,
                        'path' => $file->getPath()
                    ));
                }
            }
        }
        
        sleep(5);
        $i = 0;
        $currentIndex = time();
        foreach ($fileIds as $file) {
            $i ++;
            
            $this->miscService->debug('Cron update ' . $i . ' - file #' . $file['fileid']);
            $result = $this->fileService->updateFiles(array(
                $file
            ), $file['options']);
            
            if ($result === false) {
                $this->miscService->log('Failed to update files flag during background jobs (file #' . $file['fileid'] . ')', 3);
                $noFailure = false;
                sleep(10);
            }
            
            if (($i % 1000) == 0) {
                sleep(10);
            }
        }
        
        return $noFailure;
    }
}
