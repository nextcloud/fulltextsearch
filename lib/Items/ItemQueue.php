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
namespace OCA\Nextant\Items;

class ItemQueue
{

    private $type;

    private $userId;

    private $fileId;

    private $path;

    private $folder = false;

    public function __construct($type = '', $more = array())
    {
        $this->type = $type;
        
        if (is_array($more)) {
            if (key_exists('userid', $more))
                $this->setUserId($more['userid']);
            if (key_exists('fileid', $more))
                $this->setFileId($more['fileid']);
            if (key_exists('path', $more))
                $this->setPath($more['path']);
            if (key_exists('folder', $more))
                $this->setFolder($more['folder']);
        }
        
        if (is_object($more)) {
            if (isset($more->userid))
                $this->setUserId($more->userid);
            if (isset($more->fileid))
                $this->setFileId($more->fileid);
            if (isset($more->path))
                $this->setPath($more->path);
            if (isset($more->folder))
                $this->setFolder($more->folder);
        }
    }

    public function setType($type)
    {
        $this->type = $type;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setUserId($userId)
    {
        $this->userId = $userId;
    }

    public function getUserId()
    {
        return $this->userId;
    }

    public function setFileId($fileId)
    {
        $this->fileId = $fileId;
    }

    public function getFileId()
    {
        return $this->fileId;
    }

    public function setPath($path)
    {
        $this->path = $path;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function setFolder($folder)
    {
        $this->folder = $folder;
    }

    public function getFolder()
    {
        return $this->folder;
    }

    public static function toJson($item)
    {
        if ($item === null)
            return null;
        return json_encode(array(
            'type' => $item->getType(),
            'userid' => $item->getUserId(),
            'fileid' => $item->getFileId(),
            'path' => $item->getPath(),
            'folder' => $item->getFolder()
        ));
    }

    public static function fromJson($json)
    {
        $data = json_decode($json);
        $item = new ItemQueue($data->type, $data);
        return $item;
    }
}


