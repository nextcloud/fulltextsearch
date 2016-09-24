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

    private $configService;

    private $userId;

    private $fileService;

    private $solrService;

    private $miscService;

    public function __construct($configService, $userId, $fileService, $solrService, $miscService)
    {
        $this->userId = $userId;
        $this->configService = $configService;
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
        if ($this->configService->getAppValue('live_extract') == '1') {
            if ($this->fileService->addFileFromPath($path, true))
                $this->fileService->updateFiles(FileService::getId($path));
        } else
            $this->configService->needIndex(true);
    }

    /**
     * onFileUpdate()
     *
     * @param string $path            
     */
    public function onFileUpdate($path)
    {
        if ($this->configService->getAppValue('live_extract') == '1') {
            if ($this->fileService->addFileFromPath($path, true))
                $this->fileService->updateFiles(FileService::getId($path));
        } else
            $this->configService->needIndex(true);
    }

    /**
     * onFileRename()
     *
     * @param string $source            
     * @param string $target            
     */
    public function onFileRename($target)
    {
        if ($this->configService->getAppValue('live_docupdate') == '1')
            $this->fileService->updateFiles(FileService::getId($target));
        else
            $this->configService->needIndex(true);
    }

    /**
     * onFileTrash()
     *
     * @param string $path            
     */
    public function onFileTrash($path)
    {
        if ($this->configService->getAppValue('live_docupdate') == '1') {
            if (\OCP\App::isEnabled('files_trashbin')) {
                $this->fileService->updateFiles(FileService::getId($path), array(
                    'deleted' => true
                ));
            } else
                $this->fileService->removeFiles($path);
        } else
            $this->configService->needIndex(true);
    }

    /**
     * onFileDelete()
     *
     * @param string $path            
     */
    public function onFileDelete($path)
    {
        // fast way to bypass files_trashbin/
        if ($this->configService->getAppValue('live_docupdate') == '1') {
            $this->fileService->setView(new \OC\Files\View('/' . $this->userId));
            $this->fileService->removeFiles($path);
        } else
            $this->configService->needIndex(true);
    }

    /**
     * onFileRestore
     *
     * @param string $path            
     */
    public function onFileRestore($path)
    {
        if ($this->configService->getAppValue('live_docupdate') == '1') {
            $this->fileService->updateFiles(FileService::getId($path), array(
                'deleted' => false
            ));
        } else
            $this->configService->needIndex(true);
    }

    /**
     * onFileShare
     *
     * @param string $path            
     */
    public function onFileShare($fileId)
    {
        if ($this->configService->getAppValue('live_docupdate') == '1')
            $this->fileService->updateFiles($fileId);
        else
            $this->configService->needIndex(true);
    }

    /**
     * onFileUnshare
     *
     * @param string $path            
     */
    public function onFileUnshare($fileId)
    {
        if ($this->configService->getAppValue('live_docupdate') == '1')
            $this->fileService->updateFiles($fileId);
        else
            $this->configService->needIndex(true);
    }
}

