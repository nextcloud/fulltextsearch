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
 * GNU Affero` General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\Nextant\Service;

use \OCA\Nextant\Events\FilesEvents;
use \OCA\Nextant\Items\ItemQueue;
use \OCA\Nextant\Items\ItemDocument;

class QueueService
{

    const QUEUE_ID = 19375;

    private $userId;

    private $indexService;

    private $fileService;

    private $miscService;

    private $queue = null;

    public function __construct($indexService, $fileService, $miscService)
    {
        // $this->configService = $configService;\
        $this->indexService = $indexService;
        $this->fileService = $fileService;
        $this->miscService = $miscService;
    }

    public function liveIndex($item)
    {
        $queue = msg_get_queue(self::QUEUE_ID);
        $msg = ItemQueue::toJson($item);
        
        if (! msg_send($queue, 1, $msg))
            $this->miscService->log('can\'t msg_send()');
    }
    
    // public function emptyQueue()
    // {
    // $queue = msg_get_queue(self::QUEUE_ID);
    
    // $msg_type = NULL;
    // $msg = NULL;
    
    // while (msg_receive($queue, 1, $msg_type, 512, $msg) !== false) {}
    // }
    public function readQueue($standby = false)
    {
        $queue = msg_get_queue(self::QUEUE_ID);
        
        $msg_type = NULL;
        $msg = NULL;
        $max_msg_size = 512;
        
        $infos = msg_stat_queue($queue);
        if (! $standby && $infos['msg_qnum'] == 0)
            return false;
        
        if (! msg_receive($queue, 1, $msg_type, $max_msg_size, $msg, true, 0, $error)) {
            return false;
        }
        
        return ItemQueue::fromJson($msg);
    }

    public function executeItem($item)
    {
        $options = array();
        switch ($item->getType()) {
            case FilesEvents::FILE_UPDATE:
                array_push($options, 'forceshared');
            
            case FilesEvents::FILE_CREATE:
                $files = $this->fileService->getFilesPerFileId($item->getUserId(), $item->getFileId(), $options);
                if ($files != false && sizeof($files) > 0) {
                    $this->indexService->extract(ItemDocument::TYPE_FILE, $item->getUserId(), $files);
                }
                break;
            
            case FilesEvents::FILE_TRASH:
                array_push($options, 'deleted');
            
            case FilesEvents::FILE_RENAME:
            case FilesEvents::FILE_RESTORE:
            case FilesEvents::FILE_SHARE:
            case FilesEvents::FILE_UNSHARE:
                array_push($options, 'forceshared');
                
                $files = $this->fileService->getFilesPerFileId($item->getUserId(), $item->getFileId(), $options);
                if (is_array($files) && sizeof($files) > 0)
                    $this->indexService->updateDocuments(ItemDocument::TYPE_FILE, $item->getUserId(), $files);
                break;
            
            case FilesEvents::FILE_DELETE:
                
                if ($item->getFolder()) {
                    
                    $files = $this->fileService->getFilesPerUserId($item->getUserId(), '/files', array());
                    $files_trashbin = $this->fileService->getFilesPerUserId($item->getUserId(), '/files_trashbin', array(
                        'deleted'
                    ));
                    
                    $files = array_merge($files, $files_trashbin);
                    $solrDocs = null;
                    $this->indexService->removeOrphans(ItemDocument::TYPE_FILE, $item->getUserId(), $files, $solrDocs);
                } else {
                    $doc[] = new ItemDocument(ItemDocument::TYPE_FILE, $item->getFileId());
                    $this->indexService->removeDocuments($doc);
                }
                break;
        }
    }
}