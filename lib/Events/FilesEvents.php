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
        $absolutePath = $this->fileService->getRoot() . FileService::getAbsolutePath($path);
        $fileInfos = FileService::getFileInfo($path);
        $this->miscService->log('>> ' . $fileInfos->getMimeType());
        
        $solrResult = $this->solrService->extractFile($absolutePath, FileService::getId($path), $fileInfos->getMimeType());
    }

    /**
     * onFileUpdate()
     *
     * @param string $path            
     */
    public function onFileUpdate($path)
    {
        $absolutePath = $this->fileService->getRoot() . FileService::getAbsolutePath($path);        
        $fileInfos = FileService::getFileInfo($path);         
        
        $solrResult = $this->solrService->extractFile($absolutePath, FileService::getId($path), $fileInfos->getMimeType());
    }

    /**
     * onFileRename()
     *
     * @param string $path            
     */
    public function onFileRename($source, $target)
    {
    }

    /**
     * onFileDelete()
     *
     * @param string $path            
     */
    public function onFileDelete($path)
    {
        // $path incorrect
        $this->miscService->log('A file has been deleted: ' . FileService::getId($path), 2);
    }

    /**
     * onFileRestore
     *
     * @param string $path            
     */
    public function onFileRestore($path)
    {
        $this->miscService->log('A file has been restored: ' . FileService::getId($path), 2);
    }

    /**
     * onFileShare
     *
     * @param string $path            
     */
    public function onFileShare($item, $file)
    {
        $this->miscService->log('A file has been shared: ' . $item . ', ' . $file, 2);
    }

    /**
     * onFileUnshare
     *
     * @param string $path            
     */
    public function onFileUnshare($path)
    {
        $this->miscService->log('A file has been unshared: ' . $path, 2);
    }

    /**
     * onFileScan
     *
     * @param string $path            
     */
    public function onFileScan($path)
    {
        $this->miscService->log('A file has been scanned: ' . $path, 2);
    }
}

