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
namespace OCA\Nextant\Command;

use \OCA\Nextant\Service\FileService;
use \OCA\Nextant\Items\ItemDocument;
use OC\Core\Command\Base;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use OCP\IUserManager;
use OC\Files\Filesystem;

class Index extends Base
{

    const REFRESH_INFO_SYSTEM = 3;

    private $userManager;

    private $rootFolder;

    private $indexService;

    private $solrService;

    private $solrTools;

    private $configService;

    private $fileService;

    private $bookmarkService;

    private $miscService;

    private $currentIndexStatus = array();

    public function __construct(IUserManager $userManager, $rootFolder, $indexService, $solrService, $solrTools, $configService, $fileService, $bookmarkService, $miscService)
    {
        parent::__construct();
        $this->userManager = $userManager;
        $this->rootFolder = $rootFolder;
        $this->indexService = $indexService;
        $this->solrService = $solrService;
        $this->solrTools = $solrTools;
        $this->configService = $configService;
        $this->fileService = $fileService;
        $this->bookmarkService = $bookmarkService;
        $this->miscService = $miscService;
    }

    protected function configure()
    {
        parent::configure();
        $this->setName('nextant:index')
            ->setDescription('scan users\' files, generate and index Solr documents')
            ->addOption('debug', 'd', InputOption::VALUE_NONE, 'flood the log of debug messages')
            ->addOption('unlock', 'u', InputOption::VALUE_NONE, 'unlock on Solr')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'force extract and update of all your documents')
            ->addOption('background', 'c', InputOption::VALUE_NONE, 'force index as a background process (cron)')
            ->addOption('bookmarks', 'b', InputOption::VALUE_NONE, 'only indexes bookmarks - requiert <comment>Bookmarks</comment> installed')
            ->addOption('files', 'i', InputOption::VALUE_NONE, 'only indexes files')
            ->addOption('files_extract', 'x', InputOption::VALUE_NONE, 'only extract files')
            ->addOption('files_update', 'r', InputOption::VALUE_NONE, 'only update files share rights');
    }

    public function interrupted()
    {
        if ($this->hasBeenInterrupted()) {
            $this->configService->setAppValue('index_locked', '0');
            if (key_exists('files', $this->currentIndexStatus))
                $this->configService->needIndexFiles($this->currentIndexStatus['files']);
            if (key_exists('bookmarks', $this->currentIndexStatus))
                $this->configService->needIndexBookmarks($this->currentIndexStatus['bookmarks']);
            
            throw new \Exception('ctrl-c');
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<comment>nextant v' . $this->configService->getAppValue('installed_version') . ' (beta)</comment>');
        $output->writeln('<comment>discussion forum:</comment> https://help.nextcloud.com/c/apps/nextant');
        // $output->writeln('');
        
        if (! $this->solrService->configured(true)) {
            $output->writeln('Nextant is not yet configured');
            return;
        }
        
        $this->miscService->setDebug($input->getOption('debug'));
        $this->fileService->setDebug($input->getOption('debug'));
        $this->indexService->setDebug($input->getOption('debug'));
        
        $this->solrService->setOutput($output);
        $this->indexService->setOutput($output);
        $this->indexService->setParent($this);
        
        if ($input->getOption('background')) {
            if ($input->getOption('unlock')) {
                $this->configService->setAppValue('configured', '1');
                $this->configService->setAppValue('index_locked', '0');
            }
            $this->configService->needIndexFiles(true);
            $this->configService->needIndexBookmarks(true);
            $output->writeln('A background indexing process is scheduled');
            return;
        }
        
        if ($input->getOption('unlock')) {
            $this->configService->setAppValue('index_locked', '0');
            $output->writeln('Nextant is not locked anymore.');
            return;
        }
        
        $solr_locked = $this->configService->getAppValue('index_locked');
        if (! $input->getOption('unlock') && ($solr_locked > (time() - (3600 * 24)))) {
            $output->writeln('Your solr is locked by a running script like an index command or background jobs (cron)');
            $output->writeln('You can still use ./occ nextant:index --unlock');
            return;
        }
        
        $this->configService->setAppValue('index_locked', time());
        
        $filtered = false;
        if ($input->getOption('bookmarks') || $input->getOption('files') || $input->getOption('files_extract') || $input->getOption('files_update'))
            $filtered = true;
            
            // neededIndex
        $this->currentIndexStatus = array(
            'files' => $this->configService->neededIndexFiles(),
            'bookmarks' => $this->configService->neededIndexBookmarks()
        );
        
        if (! $filtered || $input->getOption('files'))
            $this->configService->needIndexFiles(false);
        if (! $filtered || $input->getOption('bookmarks'))
            $this->configService->needIndexBookmarks(false);
            
            // indexes
        if (! $filtered || $input->getOption('files') || $input->getOption('files_extract')) {
            $this->indexesFiles($input, $output);
            $this->configService->timeIndex('files');
        }
        
        if (! $filtered || $input->getOption('files') || $input->getOption('files_update')) {
            $this->updateFiles($input, $output);
            $this->configService->timeIndex('files');
        }
        
        if (! $filtered || $input->getOption('bookmarks')) {
            $this->indexesBookmarks($input, $output);
            $this->configService->timeIndex('bookmarks');
        }
        $this->configService->setAppValue('index_locked', '0');
        $this->configService->setAppValue('configured', '1');
        return;
        //
        
        // $documentIds = array();
        
        // //
        // // extract files
        // $output->writeln('');
        // $output->writeln('* Extracting files to Solr:');
        // $output->writeln('');
        
        // $users = $this->userManager->search('');
        // $usersTotal = sizeof($users);
        // $usersCurrent = 0;
        // $extractedDocuments = array();
        // $extractedTotal = 0;
        // $processedTotal = 0;
        // foreach ($users as $user) {
        // $usersCurrent ++;
        
        // if ($this->hasBeenInterrupted())
        // break;
        
        // $userId = $user->getUID();
        // $this->solrService->setOwner($userId);
        
        // $this->miscService->debug('Init Extracting new files for user ' . $userId);
        // $result_files = $this->browseUserDirectory($output, $userId, '/files', array(), $docIds_files);
        
        // $output->writeln('');
        // $result_trash = $this->browseUserDirectory($output, $userId, '/files_trashbin', array(
        // 'deleted' => true
        // ), $docIds_trash);
        
        // if (($result_files['total'] + $result_trash['total']) > 0) {
        // $documentIds = array_merge($documentIds, $docIds_files, $docIds_trash);
        // array_push($extractedDocuments, array(
        // 'userid' => $userId,
        // 'files' => array_merge($result_files['files'], $result_trash['files'])
        // ));
        
        // $extractedTotal += sizeof($result_files['files']) + sizeof($result_trash['files']);
        // $processedTotal += $result_files['processed'] + $result_trash['processed'];
        // }
        
        // $output->writeln('');
        // }
        
        // $output->writeln(' ' . $processedTotal . ' file(s) processed ; ' . $extractedTotal . ' extracted documents');
        
        // //
        // // update Documents
        // $output->writeln('');
        // $output->writeln('* Updating documents status');
        // $output->writeln('');
        
        // $noFailure = true;
        // $usersTotal = sizeof($extractedDocuments);
        // $usersCurrent = 0;
        // $processedFile = 0;
        // foreach ($extractedDocuments as $doc) {
        // $usersCurrent ++;
        
        // $fileIds = $doc['files'];
        // $userId = $doc['userid'];
        // $this->solrService->setOwner($userId);
        
        // $this->miscService->debug('Init Updating documents for user ' . $userId);
        
        // if (! $this->updateUserDocuments($userId, $fileIds, $output, $updateDocs))
        // $noFailure = false;
        
        // $processedFile += $updateDocs;
        // $output->writeln('');
        // }
        
        // $output->writeln(' ' . $processedFile . ' file(s) updated');
        
        // // $output->writeln(' - ' . $deleted . ' documents removed');
        
        // Filesystem::tearDown();
        
        // //
        // // removing orphan
        // $output->writeln('');
        // $output->writeln('* Removing orphan documents');
        // $output->writeln('');
        
        // $this->removeOrphans($output, $documentIds);
        
        // if ($noFailure)
        // $this->configService->setAppValue('index_files_last', time());
        // else
        // $this->configService->needIndexFiles(true);
        
        // $this->configService->setAppValue('configured', '1');
        // $this->configService->setAppValue('index_locked', '0');
        
        // $output->writeln('');
    }
    
    // private function browseUserDirectory($output, $userId, $dir, $options, &$docIds)
    // {
    // Filesystem::tearDown();
    // Filesystem::init($userId, '');
    // $this->fileService->setView(Filesystem::getView());
    // $this->miscService->debug('(' . $userId . ') - Init Filesystem');
    
    // $userFolder = FileService::getUserFolder($this->rootFolder, $userId, $dir);
    // if ($userFolder != null && $userFolder) {
    // $folder = $userFolder->get('/');
    
    // $this->miscService->debug('(' . $userId . '/' . $dir . ') - found root folder');
    // $files = $folder->search('');
    // } else
    // $files = array();
    
    // $this->miscService->debug('(' . $userId . ') - found ' . sizeof($files) . ' files');
    
    // $progress = new ProgressBar($output, sizeof($files));
    // $progress->setMessage('<info>' . $userId . '</info>' . $dir . ': ');
    // $progress->setMessage('', 'jvm');
    // $progress->setMessage('[preparing]', 'infos');
    // $progress->setFormat(" %message:-38s%%current:5s%/%max:5s% [%bar%] %percent:3s%% \n %infos:1s% %jvm:-30s% ");
    // $progress->start();
    
    // if (sizeof($files) > 10)
    // sleep(5);
    
    // $filesProcessed = 0;
    // $fileIds = array();
    // $docIds = array();
    // $lastProgressTick = 0;
    // foreach ($files as $file) {
    
    // $this->miscService->debug('(' . $userId . ') - scanning file #' . $file->getId() . ' (' . $file->getMimeType() . ') ' . $file->getPath());
    
    // if ($this->hasBeenInterrupted()) {
    // $this->configService->setAppValue('index_locked', '0');
    // throw new \Exception('ctrl-c');
    // }
    
    // $progress->setMessage('[scanning] -', 'infos');
    
    // if ((time() - self::REFRESH_INFO_SYSTEM) > $lastProgressTick) {
    // $infoSystem = $this->solrTools->getInfoSystem();
    // $progress->setMessage('Solr memory: ' . $infoSystem->jvm->memory->used, 'jvm');
    // $lastProgressTick = time();
    // }
    
    // if (! $file->isShared() && $file->getType() == \OCP\Files\FileInfo::TYPE_FILE) {
    
    // $forceExtract = false;
    // $status = 0;
    // if ($this->fileService->addFileFromPath($file->getPath(), $forceExtract, $status)) {
    // array_push($docIds, (int) $file->getId());
    // array_push($fileIds, array(
    // 'fileid' => $file->getId(),
    // 'options' => $options,
    // 'path' => $file->getPath()
    // ));
    // $filesProcessed += $status;
    // if ($status > 0) {
    // $progress->setMessage('[extracting] -', 'infos');
    // }
    // }
    // }
    
    // $progress->advance();
    // }
    
    // $progress->setMessage('', 'jvm');
    // $progress->setMessage('', 'infos');
    // $progress->finish();
    
    // return array(
    // 'extracted' => sizeof($fileIds),
    // 'processed' => $filesProcessed,
    // 'total' => sizeof($files),
    // 'files' => $fileIds
    // );
    // }
    
    // private function updateUserDocuments($userId, $fileIds, $output, &$processedfile)
    // {
    // Filesystem::tearDown();
    // Filesystem::init($userId, '');
    // $this->fileService->setView(Filesystem::getView());
    // $this->miscService->debug('Init filesystem for user ' . $userId);
    
    // // $cycle = array_chunk($fileIds, 5);
    
    // $progress = new ProgressBar($output, sizeof($fileIds));
    // $progress->setFormat(" %message:-38s%%current:5s%/%max:5s% [%bar%] %percent:3s%% \n %infos:1s% %jvm:-30s% %failures:1s% ");
    // $progress->setMessage('<info>' . $userId . '</info>: ');
    // $progress->setMessage('', 'jvm');
    // $progress->setMessage('', 'failures');
    // $progress->setMessage('[preparing]', 'infos');
    // $progress->start();
    
    // $this->miscService->debug('(' . $userId . ') - found ' . sizeof($fileIds) . ' files');
    
    // sleep(5);
    // $i = 0;
    // $lastProgressTick = 0;
    // $failureIds = array();
    // $processedfile = 0;
    // while ($file = array_shift($fileIds)) {
    
    // if ($this->hasBeenInterrupted()) {
    // $this->configService->setAppValue('index_locked', '0');
    // throw new \Exception('ctrl-c');
    // }
    
    // $count = $this->fileService->updateFiles(array(
    // $file
    // ), $file['options']);
    
    // // $progress->setMessage('failure(s): ' . $failure, 'failures');
    
    // if ($count === false) {
    // array_push($failureIds, $file);
    // $progress->setMessage('failure(s): ' . sizeof($failureIds), 'failures');
    // $this->miscService->debug('' . $userId . ' update failed');
    // } else {
    // $processedfile += $count;
    // $this->miscService->debug('' . $userId . ' update done');
    // }
    
    // $progress->setMessage('[updating] -', 'infos');
    
    // if ((time() - self::REFRESH_INFO_SYSTEM) > $lastProgressTick) {
    // $infoSystem = $this->solrTools->getInfoSystem();
    // $progress->setMessage($infoSystem->jvm->memory->used, 'jvm');
    // $lastProgressTick = time();
    // }
    
    // $progress->advance();
    
    // // if (! $result)
    // // return false;
    
    // $i ++;
    
    // // let's take a break every 500 files
    // if (($i % 500) == 0) {
    // $progress->setMessage('[standby] -', 'infos');
    // $progress->display();
    // sleep(2);
    // }
    // }
    
    // $progress->setMessage('', 'jvm');
    // $progress->setMessage('', 'infos');
    // $progress->finish();
    
    // return (sizeof($failureIds) == 0);
    // }
    
    // private function removeOrphans($output, $fileIds)
    // {
    // $progress = new ProgressBar($output, $this->solrTools->count('files'));
    
    // $progress->setMessage('<info>spoting orphans</info>:');
    // $progress->setFormat(" %message:-51s%[%bar%] %percent:3s%%");
    // $progress->start();
    
    // $deleting = array();
    // $page = 0;
    // while (true) {
    
    // if ($this->hasBeenInterrupted()) {
    // $this->configService->setAppValue('index_locked', '0');
    // throw new \Exception('ctrl-c');
    // }
    
    // // $ids = $this->solrTools->getAllDocuments('files', $page, $lastPage, $error);
    // if (! $ids)
    // return false;
    
    // foreach ($ids as $id) {
    
    // if (! in_array($id, $fileIds) && (! in_array($id, $deleting)))
    // array_push($deleting, $id);
    
    // $progress->advance();
    // }
    
    // if ($lastPage)
    // break;
    // $page ++;
    // }
    
    // $progress->finish();
    // $output->writeln('');
    
    // if (sizeof($deleting) > 0) {
    // $progress = new ProgressBar($output, sizeof($deleting));
    // $progress->setMessage('<info>removing orphans</info>:');
    // $progress->setFormat(" %message:-38s%%current:5s%/%max:5s% [%bar%] %percent:3s%%");
    // $progress->start();
    
    // foreach ($deleting as $docId) {
    // $this->solrTools->removeDocument($docId);
    // $progress->advance();
    // }
    
    // $progress->finish();
    // } else
    // $output->writeln(' <info>found no orphan</info>');
    // }
    
    /**
     * index Files
     *
     * @param OutputInterface $output            
     */
    private function indexesFiles($input, $output)
    {
        if (! $this->fileService->configured()) {
            if ($input->getOption('files') || $input->getOption('files_extract'))
                $output->writeln('Error while indexing Files: Nextant is not configured to extract your files.');
            return;
        }
        
        $output->writeln('');
        $output->writeln('* Extracting files:');
        $output->writeln('');
        
        $users = $this->userManager->search('');
        // $usersTotal = sizeof($users);
        // $usersCurrent = 0;
        
        $extracted = 0;
        $processed = 0;
        $removed = 0;
        foreach ($users as $user) {
            
            $this->interrupted();
            
            $files = $this->fileService->getFilesPerUserId($user->getUID(), '/files', array());
            $files_trashbin = $this->fileService->getFilesPerUserId($user->getUID(), '/files_trashbin', array(
                'deleted'
            ));
            
            $files = array_merge($files, $files_trashbin);
            $solrDocs = null;
            $this->indexService->extract(ItemDocument::TYPE_FILE, $user->getUID(), $files, $solrDocs);
            $this->indexService->removeOrphans(ItemDocument::TYPE_FILE, $user->getUID(), $files, $solrDocs);
            
            foreach ($files as $doc) {
                if ($doc->isExtracted())
                    $extracted ++;
                if ($doc->isProcessed())
                    $processed ++;
            }
            foreach ($solrDocs as $doc) {
                if ($doc->isRemoved())
                    $removed ++;
            }
            // $this->indexService->extract(IndexService::INDEX_FILES, $user->getUID(), $files_trashbin);
            // $this->indexService->removeOrphans(IndexService::INDEX_FILES, $user->getUID(), $files_trashbin);
            
            // $this->indexService->removeOrphans(IndexService::INDEX_BOOKMARKS, $user->getUID(), $docIds);
            
            // $userId = $user->getUID();
            // $this->solrService->setOwner($userId);
            
            // $this->miscService->debug('Init Extracting new files for user ' . $userId);
            // $result_files = $this->browseUserDirectory($output, $userId, '/files', array(), $docIds_files);
            
            // $output->writeln('');
            // $result_trash = $this->browseUserDirectory($output, $userId, '/files_trashbin', array(
            // 'deleted' => true
            // ), $docIds_trash);
            
            // if (($result_files['total'] + $result_trash['total']) > 0) {
            // $documentIds = array_merge($documentIds, $docIds_files, $docIds_trash);
            // array_push($extractedDocuments, array(
            // 'userid' => $userId,
            // 'files' => array_merge($result_files['files'], $result_trash['files'])
            // ));
            
            // $extractedTotal += sizeof($result_files['files']) + sizeof($result_trash['files']);
            // $processedTotal += $result_files['processed'] + $result_trash['processed'];
            // }
            
            $output->writeln('');
        }
        
        $output->writeln('  ' . $processed . ' file(s) processed ; ' . $extracted . ' extracted documents ; ' . $removed . ' orphan(s) removed');
        
        //
        //
        //
        //
        //
        //
        // $users = $this->userManager->search('');
        // foreach ($users as $user) {
        
        // $docIds = $this->indexService->extractBookmarks($user->getUID());
        // $this->indexService->removeOrphans(IndexService::INDEX_BOOKMARKS, $user->getUID(), $docIds);
        // $output->writeln('');
        // }
        
        // $this->configService->needIndexBookmarks(false);
        
        return;
    }

    /**
     * index Files
     *
     * @param OutputInterface $output            
     */
    private function updateFiles($input, $output)
    {
        if (! $this->fileService->configured()) {
            if ($input->getOption('files') || $input->getOption('files_update'))
                $output->writeln('Error while indexing Files: Nextant is not configured to update your files.');
            return;
        }
        
        $output->writeln('');
        $output->writeln('* Updating files:');
        $output->writeln('');
        
        $users = $this->userManager->search('');
        
        $updated = 0;
        foreach ($users as $user) {
            
            $this->interrupted();
            
            $files = $this->fileService->getFilesPerUserId($user->getUID(), '/files', array());
            $files_trashbin = $this->fileService->getFilesPerUserId($user->getUID(), '/files_trashbin', array(
                'deleted'
            ));
            
            $files = array_merge($files, $files_trashbin);
            $this->indexService->updateDocuments(ItemDocument::TYPE_FILE, $user->getUID(), $files);
            
            $output->writeln('');
            foreach ($files as $doc) {
                if ($doc->isUpdated())
                    $updated ++;
            }
        }
        
        $output->writeln('  ' . $updated . ' document(s) updated');
        
        return;
    }

    /**
     * index Bookmarks
     *
     * @param OutputInterface $output            
     */
    private function indexesBookmarks($input, OutputInterface $output)
    {
        if (! $this->bookmarkService->configured()) {
            if ($input->getOption('bookmarks'))
                $output->writeln('Error while indexing Bookmarks: Nextant is not configured to extract your bookmarks.');
            return;
        }
        
        $output->writeln('');
        $output->writeln('* Indexing bookmarks:');
        $output->writeln('');
        
        $users = $this->userManager->search('');
        foreach ($users as $user) {
            $bm = $this->bookmarkService->getBookmarksPerUserId($user->getUID());
            
            $solrDocs = null;
            $this->indexService->extract(ItemDocument::TYPE_BOOKMARK, $user->getUID(), $bm, $solrDocs);
            $this->indexService->removeOrphans(ItemDocument::TYPE_BOOKMARK, $user->getUID(), $bm, $solrDocs);
            $output->writeln('');
        }
        
        // $this->configService->needIndexBookmarks(false);
        
        $output->writeln('');
        return;
    }
}



