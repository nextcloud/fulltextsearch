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

use \OCA\Nextant\Service\FileService;
use \OCA\Nextant\Service\MiscService;
use \OCA\Nextant\Events\FilesEvents;
use \OCA\Nextant\Items\ItemQueue;
use OC\Files\Filesystem;

class FilesEvents
{

    const FILE_CREATE = 'files_file_create';

    const FILE_UPDATE = 'files_file_update';

    const FILE_RENAME = 'files_file_rename';

    const FILE_TRASH = 'files_file_trash';

    const FILE_DELETE = 'files_file_delete';

    const FILE_RESTORE = 'files_file_restore';

    const FILE_SHARE = 'files_file_share';

    const FILE_UNSHARE = 'files_file_unshare';

    private $userId;

    private $configService;

    private $queueService;

    private $miscService;

    public function __construct($userId, $configService, $queueService, $miscService)
    {
        $this->userId = $userId;
        $this->configService = $configService;
        // $this->fileService = $fileService;
        // $this->solrService = $solrService;
        $this->queueService = $queueService;
        $this->miscService = $miscService;
        
        // $this->solrService->setOwner($this->userId);
    }

    /**
     * onFileCreate()
     *
     * @param string $path            
     */
    public function onFileCreate($path)
    {
        if ($this->configService->getAppValue('index_live') === '1') {
            $file = FileService::getFileInfoFromPath($path, Filesystem::getView());
            if ($file->getId() > 0)
                $this->queueService->liveIndex(new ItemQueue(FilesEvents::FILE_CREATE, array(
                    'userid' => $this->userId,
                    'fileid' => $file->getId()
                )));
        } else
            $this->configService->needIndexFiles(true);
    }

    /**
     * onFileUpdate()
     *
     * @param string $path            
     */
    public function onFileUpdate($path)
    {
        if ($this->configService->getAppValue('index_live') === '1') {
            $file = FileService::getFileInfoFromPath($path, Filesystem::getView());
            if ($file->getId() > 0)
                $this->queueService->liveIndex(new ItemQueue(FilesEvents::FILE_UPDATE, array(
                    'userid' => $this->userId,
                    'fileid' => $file->getId()
                )));
        } else
            $this->configService->needIndexFiles(true);
    }

    /**
     * onFileRename()
     *
     * @param string $target            
     */
    public function onFileRename($target)
    {
        if ($this->configService->getAppValue('index_live') === '1') {
            $file = FileService::getFileInfoFromPath($target, Filesystem::getView());
            if ($file->getId() > 0)
                $this->queueService->liveIndex(new ItemQueue(FilesEvents::FILE_RENAME, array(
                    'userid' => $this->userId,
                    'fileid' => $file->getId()
                )));
        } else
            $this->configService->needIndexFiles(true);
    }

    /**
     * onFileTrash()
     *
     * @param string $path            
     */
    public function onFileTrash($path)
    {
        if ($this->configService->getAppValue('index_live') === '1') {
            $file = FileService::getFileInfoFromPath($path, Filesystem::getView());
            if ($file->getId() > 0) {
                if (\OCP\App::isEnabled('files_trashbin'))
                    
                    $this->queueService->liveIndex(new ItemQueue(FilesEvents::FILE_TRASH, array(
                        'userid' => $this->userId,
                        'fileid' => $file->getId()
                    )));
                else
                    $this->queueService->liveIndex(new ItemQueue(FilesEvents::FILE_DELETE, array(
                        'userid' => $this->userId,
                        'fileid' => $file->getId()
                    )));
                return;
            }
        }
        $this->configService->needIndexFiles(true);
    }

    /**
     * onFileDelete()
     *
     * @param string $path            
     */
    public function onFileDelete($path)
    {
        if ($this->configService->getAppValue('index_live') === '1') {
            $file = FileService::getFileInfoFromPath($path, new \OC\Files\View('/' . $this->userId));
            if ($file->getId() > 0)
                $this->queueService->liveIndex(new ItemQueue(FilesEvents::FILE_DELETE, array(
                    'userid' => $this->userId,
                    'fileid' => $file->getId(),
                    'folder' => ($file->getType() === \OCP\Files\FileInfo::TYPE_FOLDER)
                )));
        } else
            $this->configService->needIndexFiles(true);
    }

    /**
     * onFileRestore
     *
     * @param string $path            
     */
    public function onFileRestore($path)
    {
        if ($this->configService->getAppValue('index_live') === '1') {
            $file = FileService::getFileInfoFromPath($path, Filesystem::getView());
            if ($file->getId() > 0)
                $this->queueService->liveIndex(new ItemQueue(FilesEvents::FILE_RESTORE, array(
                    'userid' => $this->userId,
                    'fileid' => $file->getId()
                )));
        } else
            $this->configService->needIndexFiles(true);
    }

    /**
     * onFileShare
     *
     * @param string $path            
     */
    public function onFileShare($fileId)
    {
        if ($this->configService->getAppValue('index_live') === '1') {
            if ($fileId > 0)
                $this->queueService->liveIndex(new ItemQueue(FilesEvents::FILE_SHARE, array(
                    'userid' => $this->userId,
                    'fileid' => $fileId
                )));
        } else
            $this->configService->needIndexFiles(true);
    }

    /**
     * onFileUnshare
     *
     * @param string $path            
     */
    public function onFileUnshare($fileId)
    {
        if ($this->configService->getAppValue('index_live') === '1') {
            if ($fileId > 0)
                $this->queueService->liveIndex(new ItemQueue(FilesEvents::FILE_UNSHARE, array(
                    'userid' => $this->userId,
                    'fileid' => $fileId
                )));
        } else
            $this->configService->needIndexFiles(true);
    }
}

