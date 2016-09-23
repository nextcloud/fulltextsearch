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

class FilesEvents
{

    private $fileService;

    private $solrService;

    private $miscService;

    public function __construct($userId, $fileService, $solrService, $miscService)
    {
        $this->userId = $userId;
        $this->fileService = $fileService;
        $this->solrService = $solrService;
        $this->miscService = $miscService;
        
        $this->solrService->setOwner($this->userId);
    }

    /**
     * onFileCreate()
     *
     * @param string $path            
     */
    public function onFileCreate($path)
    {
        if ($this->fileService->addFileFromPath($path, true))
            $this->fileService->updateFiles(FileService::getId($path));
    }

    /**
     * onFileUpdate()
     *
     * @param string $path            
     */
    public function onFileUpdate($path)
    {
        if ($this->fileService->addFileFromPath($path, true))
            $this->fileService->updateFiles(FileService::getId($path));
    }

    /**
     * onFileRename()
     *
     * @param string $source            
     * @param string $target            
     */
    public function onFileRename($target)
    {
        $this->fileService->updateFiles(FileService::getId($target));
    }

    /**
     * onFileTrash()
     *
     * @param string $path            
     */
    public function onFileTrash($path)
    {
        if (\OCP\App::isEnabled('files_trashbin')) {
            $this->fileService->updateFiles(FileService::getId($path), array(
                'deleted' => true
            ));
        } else
            $this->fileService->removeFiles($path);
    }

    /**
     * onFileDelete()
     *
     * @param string $path            
     */
    public function onFileDelete($path)
    {
        // fast way to bypass files_trashbin/
        $this->fileService->setView(new \OC\Files\View('/' . $this->userId));
        $this->fileService->removeFiles($path);
    }

    /**
     * onFileRestore
     *
     * @param string $path            
     */
    public function onFileRestore($path)
    {
        $this->fileService->updateFiles(FileService::getId($path), array(
            'deleted' => false
        ));
        
        // $this->fileService->addFileFromPath($path);
    }

    /**
     * onFileShare
     *
     * @param string $path            
     */
    public function onFileShare($fileId)
    {
        $this->fileService->updateFiles($fileId);
    }

    /**
     * onFileUnshare
     *
     * @param string $path            
     */
    public function onFileUnshare($fileId)
    {
        $this->fileService->updateFiles($fileId);
    }
}

