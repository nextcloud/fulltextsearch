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

use \OCA\Bookmarks\Controller\Lib\Bookmarks;
use \OCA\Nextant\Items\ItemDocument;
use Symfony\Component\Console\Helper\ProgressBar;

class IndexService
{

    const PROGRESS_TEMPLATE = "%job:1s%%message:-40s%%current:5s%/%max:5s% [%bar%] %percent:3s%% \n    %infos:1s% %jvm:-30s%      ";

    const REFRESH_INFO_SYSTEM = 5;

    private $solrService;

    private $solrTools;

    private $fileService;

    private $bookmarkService;

    private $miscService;

    private $parent;

    private $output;

    private $lastProgressTick;

    public function __construct($fileService, $bookmarkService, $solrService, $solrTools, $miscService)
    {
        $this->fileService = $fileService;
        $this->bookmarkService = $bookmarkService;
        
        $this->solrService = $solrService;
        $this->solrTools = $solrTools;
        
        $this->miscService = $miscService;
        
        $this->parent = null;
        $this->output = null;
    }

    public function setDebug($debug)
    {
        $this->miscService->setDebug($debug);
    }

    public function setParent($parent)
    {
        $this->parent = $parent;
    }

    public function setOutput($output)
    {
        $this->output = $output;
    }

    public function message($line)
    {
        if ($this->output != null)
            $this->output->writeln($line);
    }

    /**
     * Extract whatever is sent ($data)
     *
     * @param string $type            
     * @param string $userId            
     * @param array $data            
     * @return array
     */
    public function extract($type, $userId, &$data, $extract = true)
    {
        $this->solrService->setOwner($userId);
        
        // if (sizeof($data) == 0)
        // return true;
        
        $progress = null;
        if ($this->output != null)
            $progress = new ProgressBar($this->output, sizeof($data));
        
        if ($progress != null) {
            $progress->setMessage($type . '/<info>' . $userId . '</info>: ');
            $progress->setMessage('', 'jvm');
            $progress->setMessage('?', 'job');
            $progress->setMessage('[preparing]', 'infos');
            $progress->setFormat(self::PROGRESS_TEMPLATE);
            $progress->start();
        }
        
        $forceExtract = false;
        
        $allDocs = $this->solrTools->getAllDocuments($type, $userId, $error);        
        foreach ($data as $entry) {
            
            if ($this->parent != null)
                $this->parent->interrupted();
            
            if ($progress != null) {
                $progress->setMessage('/', 'job');
                $progress->setMessage('[scanning]', 'infos');
                
                if ((time() - self::REFRESH_INFO_SYSTEM) > $this->lastProgressTick) {
                    $infoSystem = $this->solrTools->getInfoSystem();
                    $progress->setMessage('Solr memory: ' . $infoSystem->jvm->memory->used, 'jvm');
                    $this->lastProgressTick = time();
                }
                $progress->advance();
            }
            
            if ($entry->getType() == ItemDocument::TYPE_FILE)
                $this->fileService->syncDocument($entry);
            if ($entry->getType() == ItemDocument::TYPE_BOOKMARK)
                $this->bookmarkService->syncDocument($entry);
            
            if (! $extract)
                continue;
                
                //
                // $doc = ItemDocument::getItem($allDocs, $type, $entry->getId());
                // $needed = $this->solrTools->updateDocument($entry, $doc, false);
                //
            
            if (! $forceExtract && $this->solrTools->isDocumentUpToDate($type, $entry, ItemDocument::getItem($allDocs, $type, $entry->getId())))
                continue;
            
            if ($progress != null) {
                $progress->setMessage('+', 'job');
                $progress->setMessage('[extracting]', 'infos');
                $progress->display();
            }
            
            if (! $this->solrService->extractDocument($entry, $error))
                continue;
        }
        
        if ($progress != null) {
            $progress->setMessage('', 'jvm');
            $progress->setMessage('', 'infos');
            $progress->finish();
        }
        
        return true;
    }

    public function updateDocuments($type, $userId, &$data)
    {
        $this->solrService->setOwner($userId);
        if (sizeof($data) > 0 && ! $data[0]->isSynced())
            $this->extract($type, $userId, $data, false);
        
        $progress = null;
        if ($this->output != null)
            $progress = new ProgressBar($this->output, sizeof($data));
        
        if ($progress != null) {
            $progress->setMessage($type . '/<info>' . $userId . '</info>: ');
            $progress->setMessage('', 'jvm');
            $progress->setMessage('/', 'job');
            $progress->setMessage('[preparing]', 'infos');
            $progress->setFormat(self::PROGRESS_TEMPLATE);
            $progress->start();
        }
        
        $forceExtract = false;
        $allDocs = $this->solrTools->getAllDocuments($type, $userId, $error);
        foreach ($data as $entry) {
            
            if ($this->parent != null)
                $this->parent->interrupted();
            
            if ($progress != null) {
                if ((time() - self::REFRESH_INFO_SYSTEM) > $this->lastProgressTick) {
                    $infoSystem = $this->solrTools->getInfoSystem();
                    $progress->setMessage('Solr memory: ' . $infoSystem->jvm->memory->used, 'jvm');
                    $this->lastProgressTick = time();
                }
                $progress->advance();
            }
            
            $doc = ItemDocument::getItem($allDocs, $type, $entry->getId());
            $continue = false;
            $needed = $this->solrTools->updateDocument($entry, $doc, false);
            
            if ($progress != null) {
                if (! $needed) {
                    $progress->setMessage('/', 'job');
                    $progress->setMessage('[comparing]', 'infos');
                } else {
                    $progress->setMessage('!', 'job');
                    $progress->setMessage('[updating]', 'infos');
                }
                $progress->display();
            }
            
            if ($needed)
                $this->solrTools->updateDocument($entry, $doc);
        }
        
        if ($progress != null) {
            $progress->setMessage('', 'jvm');
            $progress->setMessage('', 'infos');
            $progress->finish();
        }
        
        return true;
    }

    public function removeOrphans($type, $userId, &$data)
    {
        $this->solrService->setOwner($userId);
        if (sizeof($data) > 0 && ! $data[0]->isSynced())
            $this->extract($type, $userId, $data, false);
        
        $progress = null;
        if ($this->output != null)
            $progress = new ProgressBar($this->output, sizeof($data));
        
        if ($progress != null) {
            $progress->setFormat(self::PROGRESS_TEMPLATE);
            $progress->setMessage($type . '/<info>' . $userId . '</info>: ');
            $progress->setMessage('/', 'job');
            $progress->setMessage('', 'jvm');
            $progress->setMessage('[spoting orphans]', 'infos');
            $progress->start();
        }
        
        $docIds = array();
        foreach ($data as $entry)
            array_push($docIds, (int) $entry->getId());
        
        $deleting = array();
        $allDocs = $this->solrTools->getAllDocuments($type, $userId, $error);
        foreach ($allDocs as $doc) {
            
            if ($this->parent != null)
                $this->parent->interrupted();
            
            if (! in_array($doc->getId(), $docIds) && (! in_array($doc->getId(), $deleting)))
                array_push($deleting, $doc->getId());
            
            if ($progress != null) {
                if ((time() - self::REFRESH_INFO_SYSTEM) > $this->lastProgressTick) {
                    $infoSystem = $this->solrTools->getInfoSystem();
                    $progress->setMessage('Solr memory: ' . $infoSystem->jvm->memory->used, 'jvm');
                    $this->lastProgressTick = time();
                }
                
                $progress->advance();
            }
        }
        
        if ($progress != null) {
            $progress->setMessage('', 'jvm');
            $progress->setMessage('', 'infos');
            $progress->finish();
        }
        
        if (sizeof($deleting) > 0) {
            
            $progress = null;
            if ($this->output != null) {
                $progress = new ProgressBar($this->output, sizeof($deleting));
            }
            
            if ($progress != null) {
                $progress->setFormat(self::PROGRESS_TEMPLATE);
                $progress->setMessage($type . '/<info>' . $userId . '</info>: ');
                $progress->setMessage('-', 'job');
                $progress->setMessage('', 'jvm');
                $progress->setMessage('[deleting orphans]', 'infos');
                $progress->start();
            }
            
            foreach ($deleting as $docId) {
                
                if ($this->parent != null)
                    $this->parent->interrupted();
                
                $this->solrTools->removeDocument($type, $docId);
                
                foreach ($data as $entry) {
                    if ($entry->getId() == $docId) {
                        $entry->removed(true);
                        break;
                    }
                }
                
                if ($progress != null) {
                    if ((time() - self::REFRESH_INFO_SYSTEM) > $this->lastProgressTick) {
                        $infoSystem = $this->solrTools->getInfoSystem();
                        $progress->setMessage('Solr memory: ' . $infoSystem->jvm->memory->used, 'jvm');
                        $this->lastProgressTick = time();
                    }
                    
                    $progress->advance();
                }
            }
        }
        
        if ($progress != null) {
            $progress->setMessage('', 'jvm');
            $progress->setMessage('', 'infos');
            $progress->finish();
        }
    }
}





