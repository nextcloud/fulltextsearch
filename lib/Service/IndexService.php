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

use OCA\Bookmarks\Controller\Lib\Bookmarks;
use Symfony\Component\Console\Helper\ProgressBar;

class IndexService
{

    const INDEX_FILES = 'files';

    const INDEX_BOOKMARKS = 'bookmarks';

    private $bookmarkService;

    private $solrService;

    private $solrTools;

    private $miscService;

    private $parent;

    private $output;

    public function __construct($bookmarkService, $solrService, $solrTools, $miscService)
    {
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

    private function message($line)
    {
        if ($this->output != null)
            $this->output->writeln($line);
    }

    /**
     * extract bookmarks from a specific user and return the total list of Ids owned by this user
     *
     * @param number $userId            
     * @return array
     */
    public function extractBookmarks($userId)
    {
        if (! $this->bookmarkService->configured())
            return false;
        
        $db = \OC::$server->getDb();
        $bookmarks = Bookmarks::findBookmarks($userId, $db, 0, 'id', array(), false, - 1);
        
        $data = array();
        foreach ($bookmarks as $bookmark)
            array_push($data, array(
                'id' => $bookmark['id'],
                'mtime' => $bookmark['lastmodified'],
                'absolute' => $bookmark['url'],
                'path' => $bookmark['url']
            ));
        
        return $this->extract(self::INDEX_BOOKMARKS, $userId, $data);
    }

    private function extract($type, $userId, $data)
    {
        $this->solrService->setOwner($userId);
        
        if (sizeof($data) == 0)
            return array();
        
        $progress = null;
        if ($this->output != null)
            $progress = new ProgressBar($this->output, sizeof($data));
        
        if ($progress != null) {
            $progress->setMessage($type . '/<info>' . $userId . '</info>: ');
            $progress->setMessage('', 'jvm');
            $progress->setMessage('[preparing]', 'infos');
            $progress->setFormat(" %message:-38s%%current:5s%/%max:5s% [%bar%] %percent:3s%% \n    %infos:1s% %jvm:-30s%      ");
            $progress->start();
        }
        
        $forceExtract = false;
        
        $docIds = array();
        foreach ($data as $entry) {
            
            if ($this->parent != null)
                $this->parent->interrupted();
            
            if ($progress != null) {
                $progress->setMessage('[scanning]', 'infos');
                $progress->advance();
            }
            
            array_push($docIds, $entry['id']);
            if (! $forceExtract && $this->solrTools->isDocumentUpToDate($type, $entry['id'], $entry['mtime']))
                continue;
            
            if ($progress != null)
                $progress->setMessage('[extracting]', 'infos');
            
            if (! $this->solrService->extractFile($entry['absolute'], $type, $entry['id'], $entry['path'], $entry['mtime'], $error))
                continue;
        }
        
        if ($progress != null) {
            $progress->setMessage('', 'jvm');
            $progress->setMessage('', 'infos');
            $progress->finish();
        }
        
        return $docIds;
    }

    public function removeOrphans($type, $userId, $docIds)
    {
        if (! $this->bookmarkService->configured())
            return false;
        
        if (! is_array($docIds) || sizeof($docIds) == 0)
            return false;
        
        $this->solrService->setOwner($userId);
        
        $progress = null;
        if ($this->output != null)
            $progress = new ProgressBar($this->output, $this->solrTools->count($type));
        
        if ($progress != null) {
            // $progress->setFormat(" %message:-51s%[%bar%] %percent:3s%%");
            $progress->setFormat(" %message:-38s%%current:5s%/%max:5s% [%bar%] %percent:3s%% \n    %infos:1s% %jvm:-30s%      ");
            $progress->setMessage($type . '/<info>' . $userId . '</info>: ');
            $progress->setMessage('', 'jvm');
            $progress->setMessage('[spoting orphans]', 'infos');
            $progress->start();
        }
        
        $deleting = array();
        $page = 0;
        while (true) {
            
            if ($this->parent != null)
                $this->parent->interrupted();
            
            $ids = $this->solrTools->getAll($type, $page, $lastPage, $error);
            if (! $ids)
                return false;
            
            foreach ($ids as $id) {
                
                if (! in_array($id, $docIds) && (! in_array($id, $deleting)))
                    array_push($deleting, $id);
                
                if ($progress != null)
                    $progress->advance();
            }
            
            if ($lastPage)
                break;
            
            $page ++;
        }
        
        if ($progress != null) {
            $progress->setMessage('', 'jvm');
            $progress->setMessage('', 'infos');
            $progress->finish();
        }
        // $this->message('');
        
        if (sizeof($deleting) > 0) {
            
            $progress = null;
            if ($this->output != null) {
                $this->message('');
                $progress = new ProgressBar($this->output, sizeof($deleting));
            }
            
            if ($progress != null) {
                $progress->setFormat(" %message:-38s%%current:5s%/%max:5s% [%bar%] %percent:3s%% \n    %infos:1s% %jvm:-30s%      ");
                $progress->setMessage($type . '/<info>' . $userId . '</info>: ');
                $progress->setMessage('', 'jvm');
                $progress->setMessage('[deleting orphans]', 'infos');
                $progress->start();
            }
            foreach ($deleting as $docId) {
                
                if ($this->parent != null)
                    $this->parent->interrupted();
                
                $this->solrTools->removeDocument($type, $docId);
                if ($progress != null)
                    $progress->advance();
            }
            
            if ($progress != null) {
                $progress->setMessage('', 'jvm');
                $progress->setMessage('', 'infos');
                $progress->finish();
            }
        }
        // else
        // $this->message(' <info>found no orphan</info>');
    }
}




