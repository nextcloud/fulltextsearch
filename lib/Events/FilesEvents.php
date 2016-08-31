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
use Solarium\Solarium;

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
        // $this->miscService->log('A file has been created: ' . $absolutePath . ' (' . FileService::getId($path) . ')', 2);
        
        $solrResult = $this->solrService->extractSimpleTextFile($absolutePath, FileService::getId($path));
        // $this->miscService->log('Solr Status: ' . $solrResult->getStatus() . ', Time: ' . $solrResult->getQueryTime(), 2);
    }

    /**
     * onFileUpdate()
     *
     * @param string $path            
     */
    public function onFileUpdate($path)
    {
        $absolutePath = $this->fileService->getRoot() . FileService::getAbsolutePath($path);
        // $this->miscService->log('A file has been updated: ' . $absolutePath . ' (' . FileService::getId($path) . ')', 2);
        
        $solrResult = $this->solrService->extractSimpleTextFile($absolutePath, FileService::getId($path));
        // $this->miscService->log('Solr Status: ' . $solrResult['status'] . ', Time: ' . $solrResult['time'], 2);
    }

    /**
     * onFileRename()
     *
     * @param string $path            
     */
    public function onFileRename($source, $target)
    {
        // $this->miscService->log('A file has been renamed: ' . FileService::getId($target), 2);
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

