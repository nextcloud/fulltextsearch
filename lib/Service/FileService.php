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
use OC\Share\Share;
use OCP\Files\NotFoundException;

class FileService
{
    
    // private $root;
    private $solrService;

    private $solrTools;

    private $miscService;

    private $view;

    public function __construct($solrService, $solrTools, $miscService)
    {
        // $this->root = $root;
        $this->solrService = $solrService;
        $this->solrTools = $solrTools;
        $this->miscService = $miscService;
        
        $this->view = Filesystem::getView();
    }

    public function setView($view = null)
    {
        if ($view == null)
            $view = Filesystem::getView();
        $this->view = $view;
    }

    public function addFileFromPath($path, $forceExtract = false, &$status = 0)
    {
        if (! $this->view || $this->view == NULL)
            return false;
        
        $fileInfo = $this->view->getFileInfo($path);
        if ($fileInfo == null)
            return false;
        
        if (! $forceExtract && $this->solrTools->isDocumentUpToDate($fileInfo->getId(), $fileInfo->getMTime()))
            return true;
        
        $status = 1;
        return $this->solrService->extractFile($this->view->getLocalFile($path), $fileInfo->getId(), $fileInfo->getMTime(), $fileInfo->getMimeType());
    }

    public function updateFiles($files, $data = null)
    {
        if (! $this->view || $this->view == NULL)
            return false;
        
        $isRoot = false;
        if ($data == null)
            $isRoot = true;
        
        if (! is_array($files))
            $files = array(
                0 => array(
                    'fileid' => $files,
                    'path' => $this->view->getPath($files)
                )
            );
        
        $pack = array();
        foreach ($files as $file) {
            
            $fileInfo = $this->view->getFileInfo($file['path']);
            $data = $this->getData($file['path']);
            
            if ($fileInfo->getType() == \OCP\Files\FileInfo::TYPE_FOLDER) {
                $subfiles = $this->view->getDirectoryContent($file['path']);
                foreach ($subfiles as $subfile) {
                    $result = $this->updateFiles($subfile->getId(), $data);
                    $pack = array_merge($pack, $result);
                }
            } else {
                $data['id'] = $file['fileid'];
                array_push($pack, $data);
            }
        }
        
        if (! $isRoot)
            return $pack;
        
        $solrResult = $this->solrService->updateDocuments($pack);
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
            $solrResult = $this->solrTools->removeDocument($fileInfos->getId());
        }
        
        return $solrResult;
    }

    private function getData($path)
    {
        $data = array();
        
        $subpath = '';
        $subdirs = explode('/', $path);
        foreach ($subdirs as $subdir) {
            $subpath .= '/' . $subdir;
            if ($subpath != '/') {
                $subdirInfos = $this->view->getFileInfo($subpath);
                if (! $subdirInfos)
                    continue;
                $this->getDataFromFileId($subdirInfos->getId(), $data);
            }
        }
        
        return $data;
    }

    public static function getDataFromFileId($fileId, &$data)
    {
        if (! key_exists('share_users', $data))
            $data['share_users'] = array();
        if (! key_exists('share_groups', $data))
            $data['share_groups'] = array();
        if (! key_exists('deleted', $data))
            $data['deleted'] = false;
        
        $OCShares = Share::getAllSharesForFileId($fileId);
        foreach ($OCShares as $share) {
            if ($share['share_type'] == '0')
                array_push($data['share_users'], $share['share_with']);
            if ($share['share_type'] == '1')
                array_push($data['share_groups'], $share['share_with']);
        }
        
        return true;
    }

    public static function getId($path)
    {
        $fileId = 0;
        $info = Filesystem::getFileInfo($path);
        if ($info !== false)
            $fileId = (int) $info['fileid'];
        
        return $fileId;
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
    
    // public static function getAbsolutePath($path, $root = false)
    // {
    // $view = Filesystem::getView();
    // return $view->getAbsolutePath($path);
    // }
    public static function getBaseTypeFromMime($mimetype)
    {
        return substr($mimetype, 0, strpos($mimetype, '/'));
    }
}
