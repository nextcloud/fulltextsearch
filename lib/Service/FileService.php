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

    private $userId;

    private $externalMountPoint;

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

    public static function getBaseTypeFromMime($mimetype)
    {
        return substr($mimetype, 0, strpos($mimetype, '/'));
    }

    public function initUser($userId)
    {
        $this->userId = $userId;
        Filesystem::init($this->userId, '');
        
        $this->initUserExternalMountPoints();
    }

    public function endUser()
    {
        $this->userId = '';
        // $this->externalMountPoint = array();
    }

    private function initUserExternalMountPoints()
    {
        if ($this->configService->getAppValue('index_files_external') !== '1')
            return false;
        
        $data = array();
        $mounts = \OC_Mount_Config::getAbsoluteMountPoints($this->userId);
        foreach ($mounts as $mountPoint => $mount) {
            $data[] = array(
                'id' => $mount['id'],
                'path' => $mountPoint,
                'shares' => $mount['applicable'],
                'personal' => $mount['personal']
            );
        }
        
        $this->externalMountPoint = $data;
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
        
        if ($item->isRemote() && $this->configService->getAppValue('index_files_external') !== '1') {
            $item->invalid(true);
            return false;
        }
        
        if ($item->isEncrypted() && $this->configService->getAppValue('index_files_encrypted') !== '1') {
            $item->invalid(true);
            return false;
        }
        
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
     * generate a local file and set its path in the item/AbsolutePath
     *
     * @param ItemDocument $item            
     */
    public function generateTempDocument(&$item)
    {
        // We generate a local tmp file from the remote one
        if ($item->isRemote() && $this->configService->getAppValue('index_files_external') === '1')
            $item->setAbsolutePath(Filesystem::getView()->toTmpFile($item->getPath()), true);
            
            // We generate a local tmp file from the remote one
        if ($item->isEncrypted() && $this->configService->getAppValue('index_files_encrypted') === '1')
            $item->setAbsolutePath(Filesystem::getView()->toTmpFile($item->getPath()), true);
    }

    /**
     * destroy local temp file
     *
     * @param unknown $item            
     */
    public function destroyTempDocument(&$item)
    {
        if ($item->isTemp())
            unlink($item->getAbsolutePath());
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
        $item->setOwner($this->userId);
        $item->setMTime($file->getMTime());
        $item->setMimetype($file->getMimeType());
        $item->setPath(str_replace('//', '/', $file->getPath()));
        $item->setSize($file->getSize());
        $item->setStorage($file->getStorage());
        
        if ($file->getStorage()->isLocal())
            $item->setAbsolutePath(Filesystem::getView()->getLocalFile($item->getPath()));
        else
            $item->remote(true);
        
        if ($file->isEncrypted())
            $item->encrypted(true);
        
        return $item;
    }

    /**
     * get files from a specific user
     *
     * @param number $userId            
     * @return array
     */
    public function getFilesPerUserId($dir, $options)
    {
        if (! $this->configured())
            return false;
        
        if ($this->userId === '')
            return false;
        
        $data = array();
        
        // Filesystem::tearDown();
        
        $userFolder = FileService::getUserFolder($this->rootFolder, $this->userId, $dir);
        if (! $userFolder || $userFolder == null)
            return $data;
        
        $folder = $userFolder->get('/');
        $files = $folder->search('');
        
        foreach ($files as $file) {
            if ($file->getType() == \OCP\Files\FileInfo::TYPE_FOLDER)
                continue;
            
            if ($file->isShared() && ! in_array('forceshared', $options))
                continue;
            
            $item = $this->getDocumentFromFile($file);
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
    public function getFilesPerFileId($fileId, $options)
    {
        if (! $this->configured())
            return false;
        
        if ($this->userId === '')
            return false;
        
        if ($fileId == '')
            return false;
        
        $view = Filesystem::getView();
        
        $data = array();
        $file = self::getFileInfoFromFileId($fileId, $view, $this->miscService);
        
        if ($file == null) {
            $trashview = new View('/' . $this->userId . '/files_trashbin/files');
            $file = self::getFileInfoFromFileId($fileId, $trashview, $this->miscService);
            array_push($options, 'deleted');
        }
        
        if ($file == null)
            return false;
        
        if ($file->getType() == \OCP\Files\FileInfo::TYPE_FOLDER) {
            $result = $this->getFilesPerPath($file->getPath(), $options);
            if (is_array($result) && sizeof($result) > 0)
                $data = array_merge($data, $result);
            
            return $data;
        }
        
        if ($file->isShared() && ! in_array('forceshared', $options))
            return $data;
        
        $item = $this->getDocumentFromFile($file);
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
    private function getFilesPerPath($path, $options)
    {
        if (! $this->configured())
            return false;
        
        if ($this->userId === '')
            return false;
            
            // Filesystem::tearDown();
        $view = Filesystem::getView();
        
        $data = array();
        $file = $view->getFileInfo($path);
        if ($file == false | $file == null)
            return false;
        
        if ($file->getType() == \OCP\Files\FileInfo::TYPE_FOLDER) {
            
            $subfiles = $view->getDirectoryContent($file->getPath());
            foreach ($subfiles as $subfile) {
                $result = $this->getFilesPerPath($subfile->getPath(), $options);
                if (is_array($result) && sizeof($result) > 0)
                    $data = array_merge($data, $result);
            }
            return $data;
        }
        
        if ($file->isShared() && ! in_array('forceshared', $options))
            return $data;
        
        $item = $this->getDocumentFromFile($file);
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
            
            if ($subdir == '')
                continue;
            
            $subpath .= '/' . $subdir;
            if (strlen($subpath) > 0 && $subpath != '/') {
                
                self::getShareRightsFromExternalMountPoint($this->externalMountPoint, $subpath, $data, $entry);
                
                $subdirInfos = self::getFileInfoFromPath($subpath);
                
                if (! $subdirInfos)
                    continue;
                self::getShareRightsFromFileId($subdirInfos->getId(), $data);
            }
        }
        
        if (key_exists('share_users', $data))
            $entry->setShare($data['share_users']);
        if (key_exists('share_groups', $data))
            $entry->setShareGroup($data['share_groups']);
        
        return true;
    }

    private static function getShareRightsFromExternalMountPoint($mountPoints, $path, &$data, &$entry)
    {
        if (! $entry->isRemote())
            return false;
        
        if (! key_exists('share_users', $data))
            $data['share_users'] = array();
        if (! key_exists('share_groups', $data))
            $data['share_groups'] = array();
        
        $edited = false;
        foreach ($mountPoints as $mount) {
            if ($mount['path'] !== $path)
                continue;
            
            $edited = true;
            if (! $mount['personal']) {
                $entry->setOwner('__global');
                if (sizeof($mount['shares']['users']) == 1 && sizeof($mount['shares']['groups']) == 0 && $mount['shares']['users'][0] == 'all' && (! in_array('__all', $data['share_users']))) {
                    array_push($data['share_users'], '__all');
                    continue;
                }
            }
            
            foreach ($mount['shares']['users'] as $share_user) {
                if ($share_user != $entry->getOwner() && ! in_array($share_user, $data['share_users']))
                    array_push($data['share_users'], $share_user);
            }
            
            foreach ($mount['shares']['groups'] as $share_group) {
                if (! in_array($share_group, $data['share_groups']))
                    array_push($data['share_groups'], $share_group);
            }
        }
        
        return $edited;
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
            if ($share['share_type'] == \OC\Share\Constants::SHARE_TYPE_USER && ! in_array($share['share_with'], $data['share_users']))
                array_push($data['share_users'], $share['share_with']);
            if ($share['share_type'] == \OC\Share\Constants::SHARE_TYPE_GROUP && ! in_array($share['share_with'], $data['share_groups']))
                array_push($data['share_groups'], $share['share_with']);
            if ($share['share_type'] == \OC\Share\Constants::SHARE_TYPE_LINK && ! in_array('__link', $data['share_users']))
                array_push($data['share_users'], '__link_' . $share['id']);
        }
        
        return true;
    }

    /**
     * complete data from a search result with more details about the file itself
     *
     * @param array $data            
     * @param string $base            
     * @param boolean $trashbin            
     * @return array[]
     */
    public static function getSearchResult(&$data, $base = '', $trashbin = true)
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
        
        if ($fileData == null && $trash) {
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
            'title' => substr($path, strpos($path, $base) + strlen($base)),
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
