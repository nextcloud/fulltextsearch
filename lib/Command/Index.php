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

use \OCA\Nextant\Service\SolrService;
use OC\Core\Command\Base;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use OCP\IUserManager;
use OC\Files\Filesystem;

class Index extends Base
{

    private $userManager;

    private $rootFolder;

    private $solrService;

    private $configService;

    private $fileService;

    public function __construct(IUserManager $userManager, $rootFolder, $solrService, $configService, $fileService)
    {
        parent::__construct();
        $this->userManager = $userManager;
        $this->rootFolder = $rootFolder;
        $this->solrService = $solrService;
        $this->configService = $configService;
        $this->fileService = $fileService;
    }

    protected function configure()
    {
        parent::configure();
        $this->setName('nextant:index')->setDescription('scan users\' files, generate and index Solr documents');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (! $this->solrService->configured()) {
            $output->writeln('Nextant is not yet configured');
            return;
        }
        
        $this->solrService->setOutput($output);
        
        // extract files
        $output->writeln('* Extracting new files to Solr:');
        
        $users = $this->userManager->search('');
        $usersTotal = sizeof($users);
        $usersCurrent = 0;
        $extractedDocuments = array();
        foreach ($users as $user) {
            $usersCurrent ++;
            
            if ($this->hasBeenInterrupted())
                break;
            
            $userId = $user->getUID();
            $this->solrService->setOwner($userId);
            
            $output->write('[' . $usersCurrent . '/' . $usersTotal . '] <info>' . $userId . '</info>: ');
            
            $result = $this->browseUserDirectory($userId, $output);
            
            if ($result['total'] > 0) {
                $output->writeln('  (processed: ' . $result['processed'] . ' - extracted: ' . $result['extracted'] . '/' . $result['total'] . ')');
                
                array_push($extractedDocuments, array(
                    'userid' => $userId,
                    'files' => $result['files']
                ));
            } else
                $output->writeln(' empty folder');
        }
        
        // update Documents
        $output->writeln('');
        $output->writeln('* Updating documents status');
        $usersTotal = sizeof($extractedDocuments);
        $usersCurrent = 0;
        foreach ($extractedDocuments as $doc) {
            $usersCurrent ++;
            
            $fileIds = $doc['files'];
            $userId = $doc['userid'];
            $this->solrService->setOwner($userId);
            
            $output->write('[' . $usersCurrent . '/' . $usersTotal . '] <info>' . $userId . '</info>: ');
            
            if ($this->updateUserDocuments($userId, $fileIds))
                $output->writeln(' ok');
            else
                $output->writeln(' fail');
            
            $usersCurrent ++;
        }
        
        Filesystem::tearDown();
        
        $this->configService->setAppValue('needed_index', '0');
    }

    private function browseUserDirectory($userId, $output)
    {
        Filesystem::tearDown();
        Filesystem::init($userId, '');
        $this->fileService->setView(Filesystem::getView());
        
        $userFolder = $this->rootFolder->getUserFolder($userId);
        
        $folder = $userFolder->get('/');
        $files = $folder->search('');
        
        $nb_tick = 30;
        $t = floor(sizeof($files) / $nb_tick);
        $size_tick = ($t > 0) ? $t : 1;
        
        $i = 0;
        
        $filesProcessed = 0;
        $fileIds = array();
        foreach ($files as $file) {
            if ($this->hasBeenInterrupted()) {
                $output->writeln('');
                $output->writeln('processed: ' . $filesProcessed . ' (' . sizeof($fileIds). '/' . sizeof($files) . ')');
                
                throw new \Exception('ctrl-c');
            }
            
            $i ++;
            if ($i % $size_tick == 0) {
                $output->write('.');
            }
            if (! $file->isShared() && $file->getType() == \OCP\Files\FileInfo::TYPE_FILE) {
                $forceExtract = false;
                $status = 0;
                if ($this->fileService->addFileFromPath($file->getPath(), $forceExtract, $status)) {
                    array_push($fileIds, array(
                        'fileid' => $file->getId(),
                        'path' => $file->getPath()
                    ));
                    $filesProcessed += $status;
                }
            }
            
            if ((($filesProcessed + 1) % SolrService::EXTRACT_CHUNK_SIZE) == 0) {
                $output->write('s');
                sleep(5);
            }
        }
        
        return array(
            'extracted' => sizeof($fileIds),
            'processed' => $filesProcessed,
            'total' => sizeof($files),
            'files' => $fileIds
        );
    }

    private function updateUserDocuments($userId, $fileIds)
    {
        Filesystem::tearDown();
        Filesystem::init($userId, '');
        $this->fileService->setView(Filesystem::getView());
        
        $result = $this->fileService->updateFiles($fileIds);
        
        if (! $result)
            return false;
        
        return true;
    }
}



