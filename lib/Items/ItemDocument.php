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

    const TYPE_TEST = 'test';

    const TYPE_FILE = 'files';

    const TYPE_BOOKMARK = 'bookmarks';

    private $test = false;

    private $type;

    private $source;

    private $owner;

    private $path;

    private $mimetype;

    private $size;

    private $absolutePath = null;

    private $mtime;

    private $share;

    private $sharegroup;

    private $storage_local;

    private $content_type;

    private $content_type_mime;

    private $content_type_charset;

    private $score;

    private $lines = array();

    private $highlighting;

    private $needExtract = false;

    private $needUpdate = false;

    private $public = false;

    private $external = false;

    private $federated = false;

    private $shared = false;

    private $sharedpublic = false;

    private $encrypted = false;

    private $extractable = false;

    private $indexed = false;

    private $extracted = false;

    private $processed = false;

    private $deleted = false;

    private $removed = false;

    private $updated = false;

    private $noindex = false;

    private $failedIndex = false;

    private $failedExtract = false;

    private $failedUpdate = false;

    private $entry = null;

    private $temp = false;

    private $valid = false;

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

    public function test($test)
    {
        $this->test = $test;
    }

    public function isTest()
    {
        return $this->test;
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

    public function setEntry($entry)
    {
        $this->entry = $entry;
    }

    public function getEntry()
    {
        return $this->entry;
    }

    public function setAbsolutePath($absolutePath, $temp = false)
    {
        $this->absolutePath = $absolutePath;
        $this->temp = $temp;
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

    public function setShare($share = array())
    {
        if (! is_array($share))
            $share = array();
        $this->share = $share;
    }

    public function getShare()
    {
        return $this->share;
    }

    public function setShareGroup($share = array())
    {
        if (! is_array($share))
            $share = array();
        $this->sharegroup = $share;
    }

    public function getShareGroup()
    {
        return $this->sharegroup;
    }

    public function setHighlighting($high)
    {
        $this->highlighting = $high;
    }

    public function getHighlighting()
    {
        return $this->highlighting;
    }

    public function setContentType($type)
    {
        $this->content_type = $type;
        
        if (strpos($type, ';') === false) {
            $this->content_type_mime = $type;
            return;
        }
        
        list ($this->content_type_mime, $this->content_type_charset) = explode(';', $type);
    }

    public function getContentType()
    {
        return $this->content_type;
    }

    public function getContentMime()
    {
        return $this->content_type_mime;
    }

    public function getContentCharset()
    {
        return $this->content_type_charset;
    }

    public function setScore($score)
    {
        $this->score = $score;
    }

    public function getScore()
    {
        return $this->score;
    }

    public function setLine($nb, $html)
    {
        $this->lines[$nb] = $html;
    }

    public function getLine($nb)
    {
        if (key_exists($nb, $this->lines))
            return $this->lines[$nb];
        
        return '';
    }

    public function storageLocal($local)
    {
        $this->storage_local = $local;
    }

    public function isStorageLocal()
    {
        return $this->storage_local;
    }

    public function federated($fed)
    {
        $this->federated = $fed;
    }

    public function isFederated()
    {
        return $this->federated;
    }

    public function needExtract($need)
    {
        $this->needExtract = $need;
    }

    public function neededExtract()
    {
        return $this->needExtract;
    }

    public function needUpdate($need)
    {
        $this->needUpdate = $need;
    }

    public function neededUpdate()
    {
        return $this->needUpdate;
    }

    public function external($external)
    {
        $this->external = $external;
    }

    public function isExternal()
    {
        return $this->external;
    }

    public function encrypted($encrypted)
    {
        $this->encrypted = $encrypted;
    }

    public function isEncrypted()
    {
        return $this->encrypted;
    }

    public function shared($shared)
    {
        $this->shared = $shared;
    }

    public function isShared()
    {
        return $this->shared;
    }

    public function extractable($extractable)
    {
        $this->extractable = $extractable;
    }

    public function isExtractable()
    {
        return $this->extractable;
    }

    public function indexed($indexed)
    {
        $this->indexed = $indexed;
    }

    public function isIndexed()
    {
        return $this->indexed;
    }

    public function extracted($extracted)
    {
        $this->extracted = $extracted;
    }

    public function isExtracted()
    {
        return $this->extracted;
    }

    public function processed($processed)
    {
        $this->processed = $processed;
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

    public function noIndex($noindex)
    {
        $this->noindex = $noindex;
    }

    public function isNoIndex()
    {
        return $this->noindex;
    }

    public function failedIndex($index)
    {
        $this->failedIndex = $index;
    }

    public function isFailedIndex()
    {
        return $this->failedIndex;
    }

    public function failedExtract($failed)
    {
        $this->failedExtract = $failed;
    }

    public function isFailedExtract()
    {
        return $this->failedExtract;
    }

    public function failedUpdate($failed)
    {
        $this->failedUpdate = $failed;
    }

    public function isFailedUpdate()
    {
        return $this->failedUpdate;
    }

    public function synced($synced)
    {
        $this->synced = $synced;
    }

    public function isSynced()
    {
        return $this->synced;
    }

    public function isTemp()
    {
        return $this->temp;
    }

    public function sharedPublic($pub)
    {
        $this->sharedpublic = $pub;
    }

    public function isSharedPublic()
    {
        return $this->sharedpublic;
    }

    public function valid($valid)
    {
        $this->valid = $valid;
    }

    public function isValid()
    {
        return $this->valid;
    }

    public static function fromCompleteId($str)
    {
        list ($type, $id) = explode('_', $str, 2);
        return new ItemDocument($type, $id);
    }

    public static function fromSolr($document)
    {
        $item = self::fromCompleteId($document->id);
        $item->setPath($document->nextant_path);
        $item->setMTime($document->nextant_mtime);
        $item->setSource($document->nextant_source);
        $item->isDeleted($document->nextant_deleted);
        $item->setOwner($document->nextant_owner);
        $item->setScore($document->score);
        if (is_array($document->nextant_attr_content_type) && sizeof($document->nextant_attr_content_type) > 0)
            $item->setContentType($document->nextant_attr_content_type[0]);
        
        return $item;
    }

    public static function fromItemQueue($type, $queue)
    {
        $item = new ItemDocument($type, $queue->getFileId());
        $item->setOwner($queue->getUserId());
        
        return $item;
    }

    public static function getItem($list, $item)
    {
        if ($list == null || $item == null || ! is_array($list))
            return null;
        
        if (isset($list[$item->getType() . '_' . $item->getId()]))
            return $list[$item->getType() . '_' . $item->getId()];
            
            // $neededObject = array_filter(
            // $arrayOfObjects,
            // function ($e) {
            // return $e->id == $searchedValue;
            // }
            // );
            
        // foreach ($list as $entry)
            // if ($entry->getId() === $item->getId() && $entry->getType() === $item->getType())
            // return $entry;
        
        return null;
    }

    public function toArray($complete = false)
    {
        $arr = array(
            'entry' => $this->getEntry(),
            'data' => array(
                'id' => $this->getId(),
                'type' => $this->getType(),
                'path' => $this->getPath(),
                'deleted' => $this->isDeleted(),
                'shared' => $this->isShared(),
                'score' => $this->getScore(),
                'lines' => $this->lines
            )
        );
        
        if ($complete)
            $arr['complete'] = $complete = array(
                'type' => $this->getType(),
                'source' => $this->getSource(),
                'owner' => $this->getOwner(),
                'path' => $this->getPath(),
                'mimetype' => $this->getMimetype(),
                'size' => $this->getSize(),
                'absolutepath' => $this->getAbsolutePath(),
                'mtime' => $this->getMTime(),
                'share' => $this->getShare(),
                'sharegroup' => $this->getSharegroup(),
                'storage_local' => (($this->isStorageLocal()) ? 'y' : 'n'),
                'needextract' => $this->neededExtract(),
                'needUpdate' => $this->neededUpdate(),
                'external' => $this->isExternal(),
                'encrypted' => $this->isEncrypted(),
                'extractable' => $this->isExtractable(),
                'indexed' => $this->isIndexed(),
                'extracted' => $this->isExtracted(),
                'processed' => $this->isProcessed(),
                'deleted' => $this->isDeleted(),
                'removed' => $this->isRemoved(),
                'updated' => $this->isUpdated(),
                'failedIndex' => $this->isFailedIndex(),
                'failedExtract' => $this->isFailedExtract(),
                'failedUpdate' => $this->isFailedUpdate(),
                'temp' => $this->isTemp(),
                'valid' => $this->isValid(),
                'synced' => $this->isSynced()
            );
        
        return $arr;
    }

    public function toString($complete = false)
    {
        return json_encode($this->toArray($complete));
    }
}