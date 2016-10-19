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

class ItemDocument
{

    const TYPE_FILE = 'files';

    const TYPE_BOOKMARK = 'bookmarks';

    private $type;

    private $source;

    private $owner;

    private $path;

    private $mimetype;

    private $size;

    private $absolutePath;

    private $mtime;

    private $share;

    private $sharegroup;

    private $storage;

    private $extractable = false;

    private $extracted = false;

    private $processed = false;

    private $deleted = false;

    private $removed = false;

    private $updated = false;

    private $failed = false;

    private $synced = false;

    public function __construct($type = '', $id = 0)
    {
        $this->type = $type;
        $this->id = $id;
    }

    public function setType($type)
    {
        $this->type = $type;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return (int) $this->id;
    }

    public function setSource($source)
    {
        $this->source = $source;
    }

    public function getSource()
    {
        return $this->source;
    }

    public function setOwner($owner)
    {
        $this->owner = $owner;
    }

    public function getOwner()
    {
        return $this->owner;
    }

    public function setPath($path)
    {
        $this->path = $path;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function setMimetype($mimetype)
    {
        $this->mimetype = $mimetype;
    }

    public function getMimetype()
    {
        return $this->mimetype;
    }

    public function setSize($size)
    {
        $this->size = $size;
    }

    public function getSize()
    {
        return $this->size;
    }

    public function setAbsolutePath($absolutePath)
    {
        $this->absolutePath = $absolutePath;
    }

    public function getAbsolutePath()
    {
        return $this->absolutePath;
    }

    public function setMTime($mtime)
    {
        $this->mtime = $mtime;
    }

    public function getMTime()
    {
        return (int) $this->mtime;
    }

    public function setShare($share)
    {
        if (! is_array($share))
            $share = array();
        $this->share = $share;
    }

    public function getShare()
    {
        return $this->share;
    }

    public function setShareGroup($share)
    {
        if (! is_array($share))
            $share = array();
        $this->sharegroup = $share;
    }

    public function getShareGroup()
    {
        return $this->sharegroup;
    }

    public function setStorage($storage)
    {
        $this->storage = $storage;
    }

    public function getStorage()
    {
        return $this->storage;
    }

    public function extractable($extractable)
    {
        $this->extractable = $extractable;
    }

    public function isExtractable()
    {
        return $this->extractable;
    }

    public function extracted($extracted)
    {
        $this->extracted = $extracted;
    }

    public function isExtracted()
    {
        return $this->extracted;
    }

    public function processed($extracted)
    {
        $this->processed = $extracted;
    }

    public function isProcessed()
    {
        return $this->processed;
    }

    public function deleted($deleted)
    {
        $this->deleted = $deleted;
    }

    public function isDeleted()
    {
        return $this->deleted;
    }

    public function removed($removed)
    {
        $this->removed = $removed;
    }

    public function isRemoved()
    {
        return $this->removed;
    }

    public function updated($updated)
    {
        $this->updated = $updated;
    }

    public function isUpdated()
    {
        return $this->updated;
    }

    public function failed($failed)
    {
        $this->failed = $failed;
    }

    public function isFailed()
    {
        return $this->failed;
    }

    public function synced($synced)
    {
        $this->synced = $synced;
    }

    public function isSynced()
    {
        return $this->synced;
    }

    public static function fromCompleteId($str)
    {
        list ($type, $id) = explode('_', $str, 2);
        return new ItemDocument($type, $id);
    }

    public static function getItem(&$list, $type, $id)
    {
        foreach ($list as $entry)
            if ($entry->getId() == $id && $entry->getType() == $type)
                return $entry;
        return null;
    }
}