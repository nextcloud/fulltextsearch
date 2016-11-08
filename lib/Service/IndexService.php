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
use \OCA\Nextant\Items\ItemError;
use Symfony\Component\Console\Helper\ProgressBar;

class IndexService
{

    const GETALL_ROWS = 100;

    const REFRESH_LOCK = 20;

    const REFRESH_COMMIT = 900;

    const PROGRESS_TEMPLATE = "%job:1s%%message:-40s%%current:6s%/%max:6s% [%bar%] %percent:3s%% \n    %duration:-9s% %infos:12s% %jvm:-30s%      ";

    const PROGRESS_TEMPLATE_DEBUG = "\n %more%";
    // const PROGRESS_TEMPLATE_DEBUG = "";
    const REFRESH_INFO_SYSTEM = 20;

    private $solrService;

    private $solrTools;

    private $solrAdmin;

    private $configService;

    private $fileService;

    private $bookmarkService;

    private $miscService;

    private $parent;

    private $output;

    private $lastProgressTick;

    private $lastCommitTick;

    private $lastAverageTick;

    private $lastAverageTickCount;

    private $lastAverageTickValue;

    private $debug = false;

    private $force = false;

    private $last_lock = 0;

    private $active_lock = false;

    public function __construct($configService, $fileService, $bookmarkService, $solrService, $solrTools, $solrAdmin, $miscService)
    {
        $this->configService = $configService;
        $this->fileService = $fileService;
        $this->bookmarkService = $bookmarkService;
        
        $this->solrService = $solrService;
        $this->solrTools = $solrTools;
        $this->solrAdmin = $solrAdmin;
        
        $this->miscService = $miscService;
        
        $this->parent = null;
        $this->output = null;
    }

    public function setForcing($force)
    {
        $this->force = $force;
    }

    public function setDebug($debug)
    {
        $this->debug = $debug;
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

    public function lockActive($active)
    {
        $this->active_lock = $active;
    }

    public function lockIndex($lock)
    {
        if (! $this->active_lock)
            return;
        
        if ($lock && $this->last_lock > (time() - self::REFRESH_LOCK))
            return false;
        
        if ($lock)
            $this->last_lock = time();
        else
            $this->last_lock = 0;
        
        $this->configService->lockIndex($lock);
    }

    /**
     * Extract whatever is sent ($data)
     *
     * @param string $type            
     * @param string $userId            
     * @param array $data            
     * @return array
     */
    public function extract($type, $userId, &$data, &$solrDocs, $extract = true, &$ierror = '')
    {
        $this->solrService->setOwner($userId);
        
        if ($solrDocs === null)
            $solrDocs = $this->getDocuments($type, $userId, 0, $ierror);
        else 
            if ($solrDocs === false)
                $solrDocs = $this->getDocuments($type, $userId, $data[0]->getId(), $ierror);
        
        $progress = null;
        if ($this->output !== null)
            $progress = new ProgressBar($this->output, sizeof($data));
        
        if ($progress != null) {
            $progress->setMessage('<info>' . $userId . '</info>');
            $progress->setMessage('', 'jvm');
            $progress->setMessage('', 'duration');
            $progress->setMessage('', 'more');
            $progress->setMessage('/', 'job');
            $progress->setMessage('', 'infos');
            $progress->setFormat(self::PROGRESS_TEMPLATE . (($this->debug) ? self::PROGRESS_TEMPLATE_DEBUG : ''));
            $progress->start();
        }
        
        $this->lastCommitTick = time();
        foreach ($data as $entry) {
            
            if ($this->parent != null)
                $this->parent->interrupted();
            
            $this->lockIndex(true);
            
            if ($progress != null) {
                $progress->setMessage('<info>' . $userId . '</info>/' . $entry->getType());
                $progress->setMessage('/', 'job');
                $progress->setMessage('[scanning]', 'infos');
                
                if ($this->parent != null)
                    $progress->setMessage('(' . $this->parent->getIndexDuration() . ')', 'duration');
                
                if ((time() - self::REFRESH_INFO_SYSTEM) > $this->lastProgressTick) {
                    if (! $infoSystem = $this->solrTools->getInfoSystem($ierror))
                        $this->manageFailure($ierror, $progress, 'Failed to retreive Info System');
                    $progress->setMessage('Solr memory: ' . $infoSystem->jvm->memory->used, 'jvm');
                    $this->lastProgressTick = time();
                }
                $progress->advance();
            }
            
            if ($entry->getType() == ItemDocument::TYPE_FILE)
                $this->fileService->syncDocument($entry);
            if ($entry->getType() == ItemDocument::TYPE_BOOKMARK)
                $this->bookmarkService->syncDocument($entry);
            if ($entry->getType() == ItemDocument::TYPE_TEST) {
                $entry->synced(true);
                $entry->extractable(true);
            }
            
            if (! $entry->isExtractable() && $this->configService->getAppValue('index_files_tree') !== '1')
                continue;
            
            if (! $extract)
                continue;
            
            if (! $this->force && $this->solrTools->isDocumentUpToDate($entry, ItemDocument::getItem($solrDocs, $entry)))
                continue;
            
            if ($progress != null) {
                $progress->setMessage('+', 'job');
                $progress->setMessage('[extracting]', 'infos');
                $progress->display();
            }
            
            $atick = $this->generateAverageTick();
            if ($atick > - 1)
                $progress->setMessage($atick . ' documents extracted in the last minute.', 'more');
            
            if ($entry->getType() == ItemDocument::TYPE_FILE)
                $this->fileService->generateTempDocument($entry);
            
            $this->solrService->indexDocument($entry, $ierror);
            
            if ((time() - self::REFRESH_COMMIT) > $this->lastCommitTick) {
                $progress->setMessage('@', 'job');
                $progress->setMessage('[commiting]', 'infos');
                $progress->display();
                if ($this->output != null && $this->debug) {
                    $this->output->writeln('');
                    $this->output->writeln('');
                    $this->output->writeln('Commiting ...');
                }
                
                $commit = $this->solrTools->commit(false, $ierror);
                if (! $commit)
                    $this->manageFailure($ierror, $progress, 'Failed to commit');
                else 
                    if ($this->output != null && $this->debug) {
                        $this->output->writeln('Last commit took ' . $commit->getQueryTime() . ' ms');
                        $this->output->writeln('');
                        $this->output->writeln('');
                    }
                
                $this->lastCommitTick = time();
            }
            
            // fail at extract, let's try just index
            if ($entry->isFailedExtract()) {
                if ($this->configService->getAppValue('index_files_tree') !== '1')
                    $entry->invalid(true);
                
                if (! $this->manageFailure($ierror, $progress, 'Failed to extract document #' . $entry->getId() . ' (' . $entry->getPath() . ')'))
                    return false;
                
                if ($this->configService->getAppValue('index_files_tree') === '1') {
                    $entry->extractable(false);
                    
                    if (! $this->force && $this->solrTools->isDocumentUpToDate($entry, ItemDocument::getItem($solrDocs, $entry)))
                        continue;
                    
                    $this->solrService->indexDocument($entry, $ierror);
                }
            }
            
            if ($entry->isFailedIndex())
                $entry->invalid(true);
            
            if ($entry->getType() == ItemDocument::TYPE_FILE)
                $this->fileService->destroyTempDocument($entry);
        }
        
        $this->resetAverageTick();
        
        if (! $this->solrTools->commit(false, $ierror))
            return false;
        
        if ($progress != null) {
            $progress->setMessage('', 'jvm');
            $progress->setMessage('', 'infos');
            $progress->finish();
        }
        
        return true;
    }

    /**
     * update Documents (sharing rights, trash, ...)
     *
     * @param string $type            
     * @param string $userId            
     * @param ItemDocument[] $data            
     * @param ItemDocument[] $solrDocs            
     * @return boolean
     */
    public function updateDocuments($type, $userId, &$data, &$solrDocs = null, &$ierror = '')
    {
        $this->solrService->setOwner($userId);
        
        if (sizeof($data) > 0 && ! $data[0]->isSynced())
            $this->extract($type, $userId, $data, $solrDocs, false);
        
        if ($solrDocs === null)
            $solrDocs = $this->getDocuments($type, $userId, 0, $ierror);
        else 
            if ($solrDocs === false)
                $solrDocs = $this->getDocuments($type, $userId, $data[0]->getId(), $ierror);
        
        $progress = null;
        if ($this->output !== null)
            $progress = new ProgressBar($this->output, sizeof($data));
        
        if ($progress !== null) {
            $progress->setMessage('<info>' . $userId . '</info>');
            $progress->setMessage('', 'jvm');
            $progress->setMessage('', 'duration');
            $progress->setMessage('', 'more');
            $progress->setMessage('/', 'job');
            $progress->setMessage('[comparing]', 'infos');
            $progress->setFormat(self::PROGRESS_TEMPLATE . (($this->debug) ? self::PROGRESS_TEMPLATE_DEBUG : ''));
            $progress->start();
        }
        
        foreach ($data as $entry) {
            
            if ($this->parent != null)
                $this->parent->interrupted();
            
            $this->lockIndex(true);
            
            if ($progress !== null) {
                $progress->setMessage('<info>' . $userId . '</info>/' . $entry->getType());
                
                if ($this->parent != null)
                    $progress->setMessage('(' . $this->parent->getIndexDuration() . ')', 'duration');
                
                if ((time() - self::REFRESH_INFO_SYSTEM) > $this->lastProgressTick) {
                    if (! $infoSystem = $this->solrTools->getInfoSystem($ierror))
                        $this->manageFailure($ierror, $progress, 'Failed to retreive Info System');
                    $progress->setMessage('Solr memory: ' . $infoSystem->jvm->memory->used, 'jvm');
                    $this->lastProgressTick = time();
                }
                $progress->advance();
            }
            
            $current = ItemDocument::getItem($solrDocs, $entry);
            $continue = false;
            
            $this->solrTools->updateDocument($entry, $current, false, $ierror);
            
            if ($progress != null) {
                if ($entry->neededUpdate()) {
                    $progress->setMessage('!', 'job');
                    $progress->setMessage('[updating]', 'infos');
                } else {
                    $progress->setMessage('/', 'job');
                    $progress->setMessage('[comparing]', 'infos');
                }
                $progress->display();
            }
            
            if ($entry->neededUpdate()) {
                
                $atick = $this->generateAverageTick();
                if ($atick > - 1)
                    $progress->setMessage($atick . ' documents updated in the last minute.', 'more');
                
                $this->solrTools->updateDocument($entry, $current, true, $ierror);
                
                if ($entry->isFailedUpdate() && ! $this->manageFailure($ierror, $progress, 'Failed to update document #' . $entry->getId() . ' (' . $entry->getPath() . ')'))
                    return false;
            }
        }
        
        $this->resetAverageTick();
        
        if (! $this->solrTools->commit(false, $ierror))
            return false;
        
        if ($progress != null) {
            $progress->setMessage('', 'jvm');
            $progress->setMessage('', 'infos');
            $progress->finish();
        }
        
        return true;
    }

    /**
     * remove documents
     *
     * @param string $type            
     * @param ItemDocument[] $data            
     * @return boolean
     */
    public function removeDocuments(&$data, &$ierror)
    {
        if (sizeof($data) == 0)
            return false;
        
        $forceExtract = false;
        foreach ($data as $entry) {
            $this->lockIndex(true);
            $this->solrTools->removeDocument($entry);
        }
        
        if (! $this->solrTools->commit(false, $ierror))
            return false;
        
        return true;
    }

    /**
     * remove documents with no more file associated.
     *
     * @param string $type            
     * @param string $userId            
     * @param ItemDocument[] $data            
     * @param ItemDocument[] $solrDocs            
     */
    public function removeOrphans($type, $userId, &$data, &$solrDocs)
    {
        $this->solrService->setOwner($userId);
        if (sizeof($data) > 0 && ! $data[0]->isSynced())
            $this->extract($type, $userId, $data, $solrDocs, false);
        
        if ($solrDocs == null || $solrDocs == '' || ! is_array($solrDocs))
            $solrDocs = $this->getDocuments($type, $userId, 0, $ierror);
        
        if (! is_array($solrDocs))
            return false;
        
        $progress = null;
        if ($this->output != null)
            $progress = new ProgressBar($this->output, sizeof($data));
        
        if ($progress != null) {
            $progress->setFormat(self::PROGRESS_TEMPLATE . (($this->debug) ? self::PROGRESS_TEMPLATE_DEBUG : ''));
            $progress->setMessage('<info>' . $userId . '</info>');
            $progress->setMessage('/', 'job');
            $progress->setMessage('', 'duration');
            $progress->setMessage('', 'more');
            $progress->setMessage('', 'jvm');
            $progress->setMessage('[spoting orphans]', 'infos');
            $progress->start();
        }
        
        $docIds = array();
        foreach ($data as $entry) {
            if (! $entry->isInvalid())
                array_push($docIds, (int) $entry->getId());
        }
        
        $deleting = array();
        foreach ($solrDocs as $doc) {
            
            if ($this->parent != null)
                $this->parent->interrupted();
            
            $this->lockIndex(true);
            
            if (! in_array($doc->getId(), $docIds) && (! in_array($doc->getId(), $deleting))) {
                array_push($deleting, $doc->getId());
                $item = ItemDocument::getItem($solrDocs, $doc);
                $item->removed(true);
            }
            
            if ($progress != null) {
                $progress->setMessage('<info>' . $userId . '</info>/' . $doc->getType());
                
                if ($this->parent != null)
                    $progress->setMessage('(' . $this->parent->getIndexDuration() . ')', 'duration');
                
                if ((time() - self::REFRESH_INFO_SYSTEM) > $this->lastProgressTick) {
                    if (! $infoSystem = $this->solrTools->getInfoSystem($ierror))
                        $this->manageFailure($ierror, $progress, 'Failed to retreive Info System');
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
                $progress->setFormat(self::PROGRESS_TEMPLATE . (($this->debug) ? self::PROGRESS_TEMPLATE_DEBUG : ''));
                $progress->setMessage('<info>' . $userId . '</info>/' . $type);
                $progress->setMessage('-', 'job');
                $progress->setMessage('', 'jvm');
                $progress->setMessage('[deleting orphans]', 'infos');
                $progress->start();
            }
            
            foreach ($deleting as $docId) {
                
                if ($this->parent != null)
                    $this->parent->interrupted();
                
                $this->lockIndex(true);
                
                $del = new ItemDocument($type, $docId);
                $this->solrTools->removeDocument($del);
                
                foreach ($data as $entry) {
                    if ($entry->getId() == $docId) {
                        $entry->removed(true);
                        break;
                    }
                }
                
                if ($progress != null) {
                    if ((time() - self::REFRESH_INFO_SYSTEM) > $this->lastProgressTick) {
                        if (! $infoSystem = $this->solrTools->getInfoSystem($ierror))
                            $this->manageFailure($ierror, $progress, 'Failed to retreive Info System');
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

    /**
     * return ids of all documents
     *
     * @param string $type            
     * @param string $userId            
     * @param number $page            
     * @param boolean $lastpage            
     * @param ItemDocument $ierror            
     * @return boolean
     */
    public function getDocuments($type = '', $userId = '', $fileId = 0, &$ierror = '')
    {
        if (! $this->solrService || ! $this->solrService->configured() || ! $this->solrService->getClient()) {
            $ierror = new ItemError(SolrService::ERROR_SOLRSERVICE_DOWN);
            return false;
        }
        
        $fileId = (int) $fileId;
        $client = $this->solrService->getClient();
        $data = array();
        try {
            
            $progress = null;
            if ($this->output != null) {
                if ($fileId > 0)
                    $progress = new ProgressBar($this->output, 1);
                else
                    $progress = new ProgressBar($this->output, $this->solrTools->count($type, $userId));
            }
            
            if ($progress != null) {
                $progress->setMessage('<info>' . $userId . '</info>/' . $type);
                $progress->setMessage('', 'jvm');
                $progress->setMessage('', 'duration');
                $progress->setMessage('', 'more');
                $progress->setMessage('%', 'job');
                $progress->setMessage('[preparing]', 'infos');
                $progress->setFormat(self::PROGRESS_TEMPLATE . (($this->debug) ? self::PROGRESS_TEMPLATE_DEBUG : ''));
                $progress->start();
            }
            
            if ($type != '')
                $type .= '_';
            
            $page = 0;
            $docIds = array();
            while (true) {
                
                if ($this->parent != null)
                    $this->parent->interrupted();
                
                $query = $client->createSelect();
                $helper = $query->getHelper();
                
                $query->setQuery('id:' . $type . (($fileId > 0) ? $fileId : '*'));
                
                if ($userId != '')
                    $query->createFilterQuery('owner')->setQuery('nextant_owner:' . $helper->escapePhrase($userId));
                
                $query->addSort('id', $query::SORT_ASC);
                $query->setStart($page * self::GETALL_ROWS);
                $query->setRows(self::GETALL_ROWS);
                $query->setFields(array(
                    'id',
                    'nextant_owner',
                    'nextant_path',
                    'nextant_mtime',
                    'nextant_share',
                    'nextant_sharegroup',
                    'nextant_deleted',
                    'nextant_extracted',
                    'nextant_source'
                ));
                
                $resultset = $client->execute($query);
                
                foreach ($resultset as $document) {
                    
                    if ($this->parent != null)
                        $this->parent->interrupted();
                    
                    $this->lockIndex(true);
                    
                    $doc = ItemDocument::fromCompleteId($document->id);
                    $doc->setOwner($document->nextant_owner);
                    $doc->setPath($document->nextant_path);
                    $doc->setMTime($document->nextant_mtime);
                    $doc->setShare($document->nextant_share);
                    $doc->setShareGroup($document->nextant_sharegroup);
                    $doc->deleted($document->nextant_deleted);
                    $doc->setSource($document->nextant_source);
                    $doc->extracted($document->nextant_extracted);
                    
                    $tick = false;
                    if (! in_array($doc->getId(), $docIds)) {
                        array_push($docIds, $doc->getId());
                        $data[] = $doc;
                        $tick = true;
                    }
                    
                    if ($progress != null) {
                        
                        if ($this->parent != null)
                            $progress->setMessage('(' . $this->parent->getIndexDuration() . ')', 'duration');
                        
                        if ((time() - self::REFRESH_INFO_SYSTEM) > $this->lastProgressTick) {
                            if (! $infoSystem = $this->solrTools->getInfoSystem($ierror))
                                $this->manageFailure($ierror, $progress, 'Failed to retreive Info System');
                            $progress->setMessage('Solr memory: ' . $infoSystem->jvm->memory->used, 'jvm');
                            $this->lastProgressTick = time();
                        }
                        if ($tick)
                            $progress->advance();
                    }
                }
                
                if ((($page + 1) * self::GETALL_ROWS) >= $resultset->getNumFound())
                    break;
                
                $page ++;
            }
            
            if ($progress != null) {
                $progress->setMessage('', 'jvm');
                $progress->setMessage('', 'infos');
                $progress->finish();
            }
            
            return $data;
        } catch (\Solarium\Exception\HttpException $ehe) {
            $ierror = new ItemError(SolrService::EXCEPTION_HTTPEXCEPTION, $ehe->getStatusMessage());
        } catch (\Solarium\Exception\RuntimeException $re) {
            $ierror = new ItemError(SolrService::EXCEPTION_RUNTIME, $re->getMessage());
        } catch (\Solarium\Exception $e) {
            $ierror = new ItemError(SolrService::EXCEPTION, $e->getMessage());
        }
        return false;
    }

    private function manageFailure($ierror, $progress = null, $message = '')
    {
        if ($ierror == null || $ierror === '')
            $ierror = new ItemError();
        
        if ($this->output != null && $this->debug) {
            $this->output->writeln('');
            $this->output->writeln('');
            if ($ierror->getCode() > 0)
                $this->output->writeln('*** Error #' . $ierror->getCode() . ' (' . $ierror->getMessage() . ')');
            $this->output->writeln('*** ' . $message);
            
            if ($ierror->getCode() == SolrService::EXCEPTION_HTTPEXCEPTION)
                $this->output->writeln('Note: we will wait here for few seconds and check if Solr is still running');
            $this->output->writeln('');
            $this->output->writeln('');
        }
        
        if ($ierror->getCode() == SolrService::EXCEPTION_HTTPEXCEPTION) {
            if ($progress != null) {
                $progress->setMessage('|', 'job');
                $progress->setMessage('[standby]', 'infos');
                $progress->display();
            }
            sleep(30);
            
            if (! $this->solrAdmin->ping($ierror)) {
                if ($this->output != null) {
                    $this->output->writeln('');
                    $this->output->writeln('');
                    $this->output->writeln('Error #' . $ierror->getCode());
                    $this->output->writeln('Is Solr Up and Running ?');
                    $this->output->writeln('');
                    $this->output->writeln('');
                }
                
                if ($this->parent != null)
                    $this->parent->end();
                else
                    exit();
                return false;
            }
        }
        
        return true;
    }

    private function generateAverageTick()
    {
        if (! $this->debug)
            return - 1;
        
        if ($this->lastAverageTick == 0) {
            $this->resetAverageTick();
            return - 1;
        }
        
        $this->lastAverageTickCount ++;
        
        if ((time() - $this->lastAverageTick) > 60) {
            $this->lastAverageTickValue = $this->lastAverageTickCount;
            $this->resetAverageTick();
            return $this->lastAverageTickValue;
        }
    }

    private function resetAverageTick()
    {
        $this->lastAverageTick = time();
        $this->lastAverageTickCount = 0;
    }
}





