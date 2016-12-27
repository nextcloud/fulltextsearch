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
use \OCA\Nextant\Db\LiveQueue;

class QueueService
{

    private $userId;

    private $configService;

    private $indexService;

    private $sourceService;

    private $miscService;

    private $queue = null;

    private $parent = null;

    public function __construct($liveQueueMapper, $configService, $indexService, $sourceService, $miscService)
    {
        $this->liveQueueMapper = $liveQueueMapper;
        $this->configService = $configService;
        $this->indexService = $indexService;
        $this->sourceService = $sourceService;
        $this->miscService = $miscService;
    }

    public function setParent($parent)
    {
        $this->parent = $parent;
    }

    public function liveIndex($item)
    {
        switch ($this->configService->getAppValue('index_live')) {
            case '0':
                return;
            
            case '1':
                $queue = msg_get_queue($this->configService->getAppValue('index_live_queuekey'));
                if (! msg_send($queue, 1, ItemQueue::toJson($item)))
                    $this->miscService->log('can\'t msg_send()');
                break;
            
            case '2':
                $this->liveQueueMapper->insert(new LiveQueue($item));
                break;
        }
    }

    public function emptyQueue()
    {
        switch ($this->configService->getAppValue('index_live')) {
            case '0':
                return;
            
            case '1':
                msg_remove_queue(msg_get_queue($this->configService->getAppValue('index_live_queuekey')));
                break;
            
            case '2':
                $this->liveQueueMapper->clear();
                break;
        }
    }

    public function readQueue($standby = false)
    {
        switch ($this->configService->getAppValue('index_live')) {
            case '0':
                return;
            
            case '1':
                $queue = msg_get_queue($this->configService->getAppValue('index_live_queuekey'));
                
                $msg_type = null;
                $msg = null;
                $max_msg_size = 512;
                
                $infos = msg_stat_queue($queue);
                
                if (! $standby && $infos['msg_qnum'] === 0)
                    return false;
                
                if (! msg_receive($queue, 1, $msg_type, $max_msg_size, $msg, true, 0, $error))
                    return false;
                
                return ItemQueue::fromJson($msg);
            
            case '2':
                $msg = null;
                while (true) {
                    if ($this->parent != null)
                        $this->parent->interrupted();
                    
                    $queue = $this->liveQueueMapper->next();
                    if ($queue)
                        break;
                    if (! $standby && ! $queue)
                        return false;
                    
                    sleep(15);
                }
                
                if ($queue)
                    $msg = $queue->getItem();
                
                return ItemQueue::fromJson($msg);
        }
    }

    public function executeItem($item)
    {
        if ($item == null)
            return false;
        
        $options = array();
        
        if (! $item->getUserId())
            return false;
        
        switch ($item->getType()) {
            
            case FilesEvents::FILE_UPDATE:
                array_push($options, 'forceshared');
            
            case FilesEvents::FILE_CREATE:
                $this->sourceService->file()->initUser($item->getUserId(), true);
                $files = $this->sourceService->file()->getFilesPerFileId($item->getFileId(), $options);
                if ($files != false && sizeof($files) > 0) {
                    $ispack = (sizeof($files) != 1);
                    $this->indexService->extract(ItemDocument::TYPE_FILE, $item->getUserId(), $files, $ispack);
                }
                $this->sourceService->file()->endUser();
                break;
            
            case FilesEvents::FILE_TRASH:
                array_push($options, 'deleted');
            
            case FilesEvents::FILE_RENAME:
            case FilesEvents::FILE_RESTORE:
            case FilesEvents::FILE_SHARE:
            case FilesEvents::FILE_UNSHARE:
                array_push($options, 'forceshared');
                
                $this->sourceService->file()->initUser($item->getUserId(), true);
                $files = $this->sourceService->file()->getFilesPerFileId($item->getFileId(), $options);
                if (is_array($files) && sizeof($files) > 0) {
                    $ispack = (sizeof($files) != 1);
                    $this->indexService->updateDocuments(ItemDocument::TYPE_FILE, $item->getUserId(), $files, $ispack);
                }
                $this->sourceService->file()->endUser();
                break;
            
            case FilesEvents::FILE_DELETE:
                if ($item->getFolder()) {
                    
                    $this->sourceService->file()->initUser($item->getUserId(), true);
                    $files = $this->sourceService->file()->getFilesPerUserId('/files', array());
                    $files_trashbin = $this->sourceService->file()->getFilesPerUserId('/files_trashbin', array(
                        'deleted'
                    ));
                    
                    $files = array_merge($files, $files_trashbin);
                    $solrDocs = null;
                    
                    $this->indexService->removeOrphans(ItemDocument::TYPE_FILE, $item->getUserId(), $files, $solrDocs);
                    
                    $this->sourceService->file()->endUser();
                } else {
                    
                    $doc[] = ItemDocument::fromItemQueue(ItemDocument::TYPE_FILE, $item);
                    $this->indexService->removeDocuments($doc, $ierror);
                }
                break;
        }
    }
}