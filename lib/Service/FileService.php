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

class FileService
{

    private $root;

    public function __construct($root)
    {
        $this->root = $root;
    }

    public function getRoot()
    {
        return $this->root;
    }

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
            throw new NotFoundException('File with id ' . $id . ' not found');
        }
    }

    public static function getFileInfo($id)
    {
        try {
            $view = Filesystem::getView();
            $path = $view->getPath($id);
            return $view->getFileInfo($path);
        } catch (NotFoundException $e) {
            throw new NotFoundException('File with id ' . $id . ' not found');
        }        
    }
    
    
    public static function getAbsolutePath($path, $root = false)
    {
        $view = Filesystem::getView();
        return $view->getAbsolutePath($path);
    }
}