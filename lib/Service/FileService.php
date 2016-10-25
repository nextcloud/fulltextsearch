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

use \OCA\Nextant\Service\SolrService;
use \OCA\Nextant\Service\SolrToolsService;
use \OCA\Nextant\Items\ItemDocument;
use OC\Files\Filesystem;
use OC\Files\View;
use OC\Share\Share;
use OCP\Files\NotFoundException;

class FileService
{

    const UPDATE_MAXIMUM_FILES = 1000;
    
    // private $root;
    private $configService;

    private $rootFolder;

    private $solrService;

    private $solrTools;

    private $miscService;

    private $view;

    public function __construct($configService, $rootFolder, $solrService, $solrTools, $miscService)
    {
        // $this->root = $root;
        $this->configService = $configService;
        $this->rootFolder = $rootFolder;
        $this->solrService = $solrService;
        $this->solrTools = $solrTools;
        $this->miscService = $miscService;
        
        // $this->view = Filesystem::getView();
    }

    public function setDebug($debug)
    {
        $this->miscService->setDebug($debug);
        $this->solrService->setDebug($debug);
        $this->solrTools->setDebug($debug);
    }

    public function configured()
    {
        if (! \OCP\App::isEnabled('files'))
            return false;
        
        if ($this->configService->getAppValue('index_files') == 1)
            return true;
        
        return false;
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

    public static function getUserFolder($rootFolder, $userId, $path)
    {
        \OC\Files\Filesystem::initMountPoints($userId);
        $dir = '/' . $userId;
        $folder = null;
        
        try {
            return $rootFolder->get($dir)->get($path);
        } catch (NotFoundException $e) {}
        
        return false;
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

    /**
     * add some information to the itemDocument
     *
     * @param ItemDocument $item            
     * @return boolean
     */
    public function syncDocument(&$item)
    {
        $item->synced(true);
        
        // && $this->configService->getAppValue('index_files_external_index') != 1)
        if (! $item->getStorage()->isLocal())
            return false;
        
        $size = round($item->getSize() / 1024 / 1024, 1);
        if ($size > $this->configService->getAppValue('index_files_max_size')) {
            $this->miscService->debug('File is too big (' . $size . ' > ' . $this->configService->getAppValue('index_files_max_size') . ')');
            return false;
        }
        
        if (! SolrService::extractableFile($item->getMimeType(), $item->getPath()))
            return false;
        
        $item->extractable(true);
        $this->setShareRights($item);
        
        if ($item->isDeleted()) {
            $item->setShare();
            $item->setShareGroup();
        }
        
        return true;
    }

    /**
     * convert FileInfo to ItemDocument
     *
     * @param FileInfo $file            
     * @return boolean|\OCA\Nextant\Items\ItemDocument
     */
    public function getDocumentFromFile($file)
    {
        if ($file == null)
            return false;
        
        $item = new ItemDocument(ItemDocument::TYPE_FILE, $file->getId());
        $item->setMTime($file->getMTime());
        $item->setMimetype($file->getMimeType());
        $item->setAbsolutePath($file->getPath());
        $item->setPath($file->getPath());
        $item->setSize($file->getSize());
        $item->setStorage($file->getStorage());
        
        return $item;
    }

    /**
     * get files from a specific user
     *
     * @param number $userId            
     * @return array
     */
    public function getFilesPerUserId($userId, $dir, $options)
    {
        if (! $this->configured())
            return false;
        
        $data = array();
        
        Filesystem::tearDown();
        Filesystem::init($userId, '');
        
        $userFolder = FileService::getUserFolder($this->rootFolder, $userId, $dir);
        if (! $userFolder || $userFolder == null)
            return $data;
        
        $folder = $userFolder->get('/');
        $files = $folder->search('');
        
        foreach ($files as $file) {
            if ($file->getType() == \OCP\Files\FileInfo::TYPE_FOLDER)
                continue;
            
            if (! $file->getStorage()->isLocal())
                continue;
            
            if ($file->isShared() && ! in_array('forceshared', $options))
                continue;
            
            $item = $this->getDocumentFromFile($file);
            $item->setAbsolutePath(Filesystem::getView()->getLocalFile($item->getPath()));
            $item->setOwner($userId);
            $item->deleted(in_array('deleted', $options));
            
            if ($item && $item != false && $item != null)
                $data[] = $item;
        }
        
        return $data;
    }

    /**
     * get files from a userid+fileid
     *
     * @param number $userId            
     * @param number $fileId            
     * @param array $options            
     * @return array
     */
    public function getFilesPerFileId($userId, $fileId, $options)
    {
        if (! $this->configured())
            return false;
        
        if ($userId == '')
            return false;
        
        if ($fileId == '')
            return false;
        
        Filesystem::init($userId, '');
        $view = Filesystem::getView();
        
        $data = array();
        $file = self::getFileInfoFromFileId($fileId, $view, $this->miscService);
        
        if ($file == null) {
            $trashview = new View('/' . $userId . '/files_trashbin/files');
            $file = self::getFileInfoFromFileId($fileId, $trashview, $this->miscService);
            array_push($options, 'deleted');
        }
        
        if ($file == null)
            return false;
        
        if ($file->getType() == \OCP\Files\FileInfo::TYPE_FOLDER) {
            $result = $this->getFilesPerPath($userId, $file->getPath(), $options);
            if (is_array($result) && sizeof($result) > 0)
                $data = array_merge($data, $result);
            
            return $data;
        }
        
        if (! $file->getStorage()->isLocal())
            return $data;
        
        if ($file->isShared() && ! in_array('forceshared', $options))
            return $data;
        
        $item = $this->getDocumentFromFile($file);
        $item->setAbsolutePath($view->getLocalFile($item->getPath()));
        $item->setOwner($userId);
        
        $item->deleted(in_array('deleted', $options));
        
        $data[] = $item;
        
        return $data;
    }

    /**
     * get files/subdir from a userid+fileid
     *
     * @param number $userId            
     * @param number $fileId            
     * @param array $options            
     * @return array
     */
    private function getFilesPerPath($userId, $path, $options)
    {
        if (! $this->configured())
            return false;
        
        if ($userId == '')
            return false;
            
            // Filesystem::tearDown();
        Filesystem::init($userId, '');
        $view = Filesystem::getView();
        
        $data = array();
        $file = $view->getFileInfo($path);
        if ($file == false | $file == null)
            return false;
        
        if ($file->getType() == \OCP\Files\FileInfo::TYPE_FOLDER) {
            
            $subfiles = $view->getDirectoryContent($file->getPath());
            foreach ($subfiles as $subfile) {
                $result = $this->getFilesPerPath($userId, $subfile->getPath(), $options);
                if (is_array($result) && sizeof($result) > 0)
                    $data = array_merge($data, $result);
            }
            return $data;
        }
        
        if (! $file->getStorage()->isLocal())
            return $data;
        
        if ($file->isShared() && ! in_array('forceshared', $options))
            return $data;
        
        $item = $this->getDocumentFromFile($file);
        $item->setAbsolutePath($view->getLocalFile($item->getPath()));
        $item->setOwner($userId);
        $item->deleted(in_array('deleted', $options));
        
        $data[] = $item;
        
        return $data;
    }

    /**
     * update ItemDocument share rights
     *
     * @param ItemDocument $entry            
     * @return boolean
     */
    private function setShareRights(&$entry)
    {
        $data = array();
        
        $subpath = '';
        $subdirs = explode('/', $entry->getPath());
        foreach ($subdirs as $subdir) {
            $subpath .= '/' . $subdir;
            if ($subpath != '/') {
                $subdirInfos = self::getFileInfoFromPath($subpath);
                if (! $subdirInfos)
                    continue;
                self::getShareRightsFromFileId($subdirInfos->getId(), $data);
            }
        }
        
        $entry->setShare($data['share_users']);
        $entry->setShareGroup($data['share_groups']);
        
        return true;
    }

    /**
     * update ItemDocument share rights from a specific fileid / subfolder
     *
     * @param number $fileId            
     * @param ItemDocument $data            
     * @return boolean
     */
    private static function getShareRightsFromFileId($fileId, &$data)
    {
        if (! key_exists('share_users', $data))
            $data['share_users'] = array();
        if (! key_exists('share_groups', $data))
            $data['share_groups'] = array();
        if (! key_exists('deleted', $data))
            $data['deleted'] = false;
        
        $OCShares = Share::getAllSharesForFileId($fileId);
        foreach ($OCShares as $share) {
            
            if ($share['share_type'] == '0' && ! in_array($share['share_with'], $data['share_users']))
                array_push($data['share_users'], $share['share_with']);
            if ($share['share_type'] == '1' && ! in_array($share['share_with'], $data['share_groups']))
                array_push($data['share_groups'], $share['share_with']);
        }
        
        return true;
    }

    /**
     * complete data from a search result with more details about the file itself
     *
     * @param array $data            
     * @param number $userid            
     * @return array[]
     */
    public static function getSearchResult(&$data)
    {
        Filesystem::init($data['userid'], '');
        $view = Filesystem::getView();
        
        $path = '';
        $deleted = false;
        $fileData = null;
        try {
            $path = $view->getPath($data['id']);
            $fileData = $view->getFileInfo($path);
        } catch (NotFoundException $e) {
            $fileData = null;
        }
        
        if ($fileData == null) {
            try {
                $trashview = new View('/' . $data['userid'] . '/files_trashbin/files');
                $path = $trashview->getPath($data['id']);
                $fileData = $trashview->getFileInfo($path);
                $deleted = true;
            } catch (NotFoundException $e) {
                return false;
            }
        }
        
        if ($fileData == null || $fileData === false)
            return false;
        
        $pathParts = pathinfo($path);
        $basepath = str_replace('//', '/', '/' . $pathParts['dirname'] . '/');
        
        if (substr($path, - 1) == '/')
            $path = substr($path, 0, - 1);
            
            // 'extension' => ($pathParts['extension'] != '') ? '.' . $pathParts['extension'] : '',
            // 'webdav' =>
            // 'trashbin' => ($deleted) ? '?view=trashbin&dir=' . $basepath . '&scrollto=' . $pathParts['filename'] : '',
        
        $data = array_merge($data, array(
            'size' => $fileData->getSize(),
            'title' => $path,
            'icon' => SolrService::extractableFile($fileData->getMimeType(), $path),
            'filename' => $pathParts['filename'],
            'dirpath' => $pathParts['dirname'],
            'mimetype' => $fileData->getMimeType(),
            'deleted' => $deleted,
            'link_main' => (! $deleted) ? str_replace('//', '/', parse_url(\OCP\Util::linkToRemote('webdav') . $path, PHP_URL_PATH)) : '?view=trashbin&dir=' . $basepath . '&scrollto=' . $pathParts['filename'],
            'link_sub' => '',
            'mtime' => $fileData->getMTime()
        ));
        
        return true;
    }

    /**
     * returns fileId from a path
     *
     * @param string $path            
     * @param View $view            
     * @return boolean|number
     */
    public static function getFileInfoFromPath($path, $view = null)
    {
        if ($view == null)
            $view = Filesystem::getView();
        if ($view == null)
            return null;
        
        try {
            return $view->getFileInfo($path);
        } catch (NotFoundException $e) {
            return false;
        }
    }

    public static function getFileInfoFromFileId($fileId, $view = null, $misc)
    {
        try {
            if ($view == null)
                $view = Filesystem::getView();
            if ($view == null)
                return null;
            
            $path = $view->getPath($fileId);
            if ($path == null)
                return null;
            
            $file = $view->getFileInfo($path);
            if ($file == null)
                return null;
            
            return $file;
        } catch (NotFoundException $e) {
            return null;
        }
    }
}
