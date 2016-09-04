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

use OC\Files\Filesystem;
use OC\Files\View;
use OCP\Files\NotFoundException;

class FileService
{

    private $root;

    private $solrService;

    private $miscService;

    private $view;

    public function __construct($root, $solrService, $miscService)
    {
        $this->root = $root;
        $this->solrService = $solrService;
        $this->miscService = $miscService;
        
        $this->view = Filesystem::getView();
    }

    public function getRoot()
    {
        return $this->root;
    }

    public function setView($view)
    {
        $this->view = $view;
    }

    /**
     * restore file or files if directory
     *
     * @param string $path            
     */
    public function addFiles($path, $recursive = false)
    {
        $solrResult = false;
        
        $fileInfos = $this->view->getFileInfo($path);
        if ($fileInfos->getMimeType() == 'httpd/unix-directory' && $recursive) {
            $files = $this->view->getDirectoryContent($path);
            foreach ($files as $file)
                $this->addFiles($this->view->getPath($file->getId()), true);
        } else {
            $absolutePath = $this->getRoot() . $this->view->getAbsolutePath($path);
            // $this->miscService->log('[Nextant] Add file ' . $absolutePath . ' (' . $fileInfos->getId() . ', ' . $fileInfos->getMimeType() . ')');
            
            $solrResult = $this->solrService->extractFile($absolutePath, $fileInfos->getId(), $fileInfos->getMimeType(), $error);
        }
        
        return $solrResult;
    }

    /**
     * delete file or files if directory
     *
     * @param string $path            
     */
    public function removeFiles($path, $recursive = false)
    {
        $solrResult = false;
        
        $fileInfos = $this->view->getFileInfo($path);
        if ($fileInfos->getMimeType() == 'httpd/unix-directory' && $recursive) {
            $files = $this->view->getDirectoryContent($path);
            foreach ($files as $file)
                $this->removeFiles($this->view->getPath($file->getId()), true);
        } else {
            // $this->miscService->log('[Nextant] Remove file ' . $fileInfos->getId());
            
            $solrResult = $this->solrService->removeDocument($fileInfos->getId());
        }
        
        return $solrResult;
    }

    public function shareDocument($path, $shareWith, $isGroup, $sharing)
    {}

    public static function getId($path)
    {
        $fileId = 0;
        $info = Filesystem::getFileInfo($path);
        if ($info !== false)
            $fileId = (int) $info['fileid'];
        return $fileId;
    }

    public static function getPath($id, $absolute = false)
    {
        try {
            $view = Filesystem::getView();
            if ($absolute)
                return $view->getAbsolutePath($view->getPath($id));
            else
                return $view->getPath($id);
        } catch (NotFoundException $e) {
            return false;
        }
    }

    public static function getFileInfo($pathorid)
    {
        try {
            $view = Filesystem::getView();
            if (intval($pathorid) != 0)
                $path = $view->getPath($pathorid);
            else
                $path = $pathorid;
            
            return $view->getFileInfo($path);
        } catch (NotFoundException $e) {
            return false;
        }
    }

    public static function getAbsolutePath($path, $root = false)
    {
        $view = Filesystem::getView();
        return $view->getAbsolutePath($path);
    }

    public static function getBaseTypeFromMime($mimetype)
    {
        return substr($mimetype, 0, strpos($mimetype, '/'));
    }
}