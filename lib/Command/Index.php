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

    private $queueService;

    private $solrService;

    private $solrTools;

    private $solrAdmin;

    private $configService;

    private $fileService;

    private $bookmarkService;

    private $miscService;

    private $currentIndexStatus = array();

    public function __construct(IUserManager $userManager, $rootFolder, $indexService, $queueService, $solrService, $solrTools, $solrAdmin, $configService, $fileService, $bookmarkService, $miscService)
    {
        parent::__construct();
        $this->userManager = $userManager;
        $this->rootFolder = $rootFolder;
        $this->indexService = $indexService;
        $this->queueService = $queueService;
        $this->solrService = $solrService;
        $this->solrTools = $solrTools;
        $this->solrAdmin = $solrAdmin;
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
            ->addOption('debug', null, InputOption::VALUE_NONE, 'display more text')
            ->addOption('debugall', null, InputOption::VALUE_NONE, 'display a lot more text')
            ->addOption('unlock', 'k', InputOption::VALUE_NONE, 'unlock on Solr')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'force extract and update of all your documents')
            ->addOption('user', 'u', InputArgument::OPTIONAL | InputArgument::IS_ARRAY, 'indexes file of the given user(s)')
            ->addOption('background', 'c', InputOption::VALUE_NONE, 'force index as a background process (cron)')
            ->addOption('bookmarks', 'b', InputOption::VALUE_NONE, 'only indexes bookmarks - requiert <comment>Bookmarks</comment> installed')
            ->addOption('files', 'i', InputOption::VALUE_NONE, 'only indexes files')
            ->addOption('files_extract', 'x', InputOption::VALUE_NONE, 'only extract files')
            ->addOption('files_update', 'r', InputOption::VALUE_NONE, 'only update files share rights');
    }

    public function interrupted()
    {
        if ($this->hasBeenInterrupted()) {
            $this->end(false);
            throw new \Exception('ctrl-c');
        }
    }

    public function end($exit = true)
    {
        $this->configService->lockIndex(false);
        if (key_exists('files', $this->currentIndexStatus))
            $this->configService->needIndexFiles($this->currentIndexStatus['files']);
        if (key_exists('bookmarks', $this->currentIndexStatus))
            $this->configService->needIndexBookmarks($this->currentIndexStatus['bookmarks']);
        
        if ($exit)
            exit();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<comment>nextant v' . $this->configService->getAppValue('installed_version') . ' (rc1)</comment>');
        // $output->writeln('<comment>discussion forum:</comment> https://help.nextcloud.com/c/apps/nextant');
        // $output->writeln('');
        
        if (! $this->solrService->configured(true)) {
            $output->writeln('Nextant is not yet configured');
            return;
        }
        
        $debug = 0;
        if ($input->getOption('debug'))
            $debug = 1;
        if ($input->getOption('debugall'))
            $debug = 2;
        
        $this->miscService->setDebug($debug);
        $this->fileService->setDebug($debug);
        $this->indexService->setDebug($debug);
        $this->indexService->setForcing($input->getOption('force'));
        
        $this->solrService->setOutput($output);
        $this->indexService->setOutput($output);
        $this->indexService->setParent($this);
        
        if ($input->getOption('background')) {
            if ($input->getOption('unlock')) {
                $this->configService->setAppValue('configured', '1');
                $this->configService->lockIndex(false);
            }
            $this->configService->needIndexFiles(true);
            $this->configService->needIndexBookmarks(true);
            $output->writeln('A background indexing process is scheduled');
            return;
        }
        
        if ($input->getOption('unlock')) {
            $this->configService->lockIndex(false);
            $output->writeln('Nextant is not locked anymore.');
            return;
        }
        
        $delay = 0;
        if ($this->configService->isLockedIndex($delay)) {
            $output->writeln('');
            $output->writeln('nextant is currently locked by another indexing script (command line or background job)');
            $output->writeln('last tick from this script was ' . $delay . ' second(s) ago');
            $output->writeln('');
            $output->writeln('If you think the other script exited improperly, you can use <info>./occ nextant:index --unlock</info> to unlock');
            $output->writeln('');
            
            return;
        }
        
        if (! ($this->solrAdmin->ping())) {
            $output->writeln('*** Solr seems down.');
            return false;
        }
        
        $this->indexService->init();
        $this->indexService->lockActive(true);
        $this->configService->lockIndex(true);
        
        $filtered = false;
        if ($input->getOption('bookmarks') || $input->getOption('files') || $input->getOption('files_extract') || $input->getOption('files_update'))
            $filtered = true;
        else
            $this->queueService->emptyQueue();
            
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
        
        $this->configService->lockIndex(false);
        $this->configService->setAppValue('configured', '1');
        
        $output->writeln('');
        $output->writeln('Time spent: ' . $this->indexService->getIndexDuration());
        $output->writeln('Your index now contains ' . $this->solrTools->getInfoCore()->index->segmentCount . ' segments');
    }

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
        
        $users = $this->getUsers($input->getOption('user'));
        
        $indexed = 0;
        $extracted = 0;
        $processed = 0;
        $removed = 0;
        $failed = 0;
        foreach ($users as $user) {
            
            $this->interrupted();
            
            if (! $this->userManager->userExists($user))
                continue;
            
            $this->fileService->initUser($user, true);
            $files = $this->fileService->getFilesPerUserId('/files', array());
            $files_trashbin = $this->fileService->getFilesPerUserId('/files_trashbin', array(
                'deleted'
            ));
            
            $files = array_merge($files, $files_trashbin);
            $solrDocs = null;
            $this->indexService->extract(ItemDocument::TYPE_FILE, $user, $files, $solrDocs);
            $this->indexService->removeOrphans(ItemDocument::TYPE_FILE, $user, $files, $solrDocs);
            
            $this->fileService->endUser();
            
            foreach ($files as $doc) {
                if ($doc->isIndexed())
                    $indexed ++;
                if ($doc->isExtracted())
                    $extracted ++;
                if ($doc->isProcessed())
                    $processed ++;
                if ($doc->isFailedExtract() || $doc->isFailedIndex())
                    $failed ++;
            }
            
            if (is_array($solrDocs)) {
                foreach ($solrDocs as $doc) {
                    if ($doc->isRemoved())
                        $removed ++;
                }
            }
            
            $output->writeln('');
        }
        
        $output->writeln('  ' . $processed . ' file(s) processed ; ' . $removed . ' orphan(s) removed');
        $output->writeln('  ' . $indexed . ' documents indexed ; ' . $extracted . ' fully extracted');
        
        if ($failed > 0)
            $output->writeln('  ' . $failed . ' file(s) were not processed (failure)');
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
        
        $users = $this->getUsers($input->getOption('user'));
        
        $updated = 0;
        $failed = 0;
        foreach ($users as $user) {
            
            $this->interrupted();
            
            if (! $this->userManager->userExists($user))
                continue;
            
            $this->fileService->initUser($user, true);
            $files = $this->fileService->getFilesPerUserId('/files', array());
            $files_trashbin = $this->fileService->getFilesPerUserId('/files_trashbin', array(
                'deleted'
            ));
            
            $files = array_merge($files, $files_trashbin);
            $this->indexService->updateDocuments(ItemDocument::TYPE_FILE, $user, $files);
            
            $this->fileService->endUser();
            
            $output->writeln('');
            foreach ($files as $doc) {
                if ($doc->isUpdated())
                    $updated ++;
                if ($doc->isFailedUpdate())
                    $failed ++;
            }
        }
        
        $output->writeln('  ' . $updated . ' document(s) updated ; ' . $failed . ' failure(s)');
        
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
        
        $users = $this->getUsers($input->getOption('user'));
        
        $indexed = 0;
        $extracted = 0;
        $processed = 0;
        $removed = 0;
        $failed = 0;
        foreach ($users as $user) {
            $this->interrupted();
            
            if (! $this->userManager->userExists($user))
                continue;
            
            $bm = $this->bookmarkService->getBookmarksPerUserId($user);
            
            $solrDocs = null;
            $this->indexService->extract(ItemDocument::TYPE_BOOKMARK, $user, $bm, $solrDocs);
            $this->indexService->removeOrphans(ItemDocument::TYPE_BOOKMARK, $user, $bm, $solrDocs);
            
            foreach ($bm as $doc) {
                if ($doc->isIndexed())
                    $indexed ++;
                if ($doc->isExtracted())
                    $extracted ++;
                if ($doc->isProcessed())
                    $processed ++;
                if ($doc->isFailedExtract() || $doc->isFailedIndex())
                    $failed ++;
            }
            
            if (is_array($solrDocs)) {
                foreach ($solrDocs as $doc) {
                    if ($doc->isRemoved())
                        $removed ++;
                }
            }
            
            $output->writeln('');
        }
        
        $output->writeln('  ' . $processed . ' bookmark(s) processed ; ' . $removed . ' orphan(s) removed');
        $output->writeln('  ' . $indexed . ' document indexed ; ' . $extracted . ' fully extracted');
        
        if ($failed > 0)
            $output->writeln('  ' . $failed . ' file(s) were not processed (failure)');
        
        return;
    }

    private function getUsers($option)
    {
        if (! $option) {
            $users = array();
            $userSearch = $this->userManager->search('');
            foreach ($userSearch as $user) {
                $users[] = $user->getUID();
            }
        } else {
            $users = $option;
            if (! is_array($users))
                $users = array(
                    $users
                );
        }
        
        return $users;
    }
}



