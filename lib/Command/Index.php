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
use Symfony\Component\Console\Helper\ProgressBar;
use OCP\IUserManager;
use OC\Files\Filesystem;

class Index extends Base
{

    const REFRESH_INFO_SYSTEM = 25;

    private $userManager;

    private $rootFolder;

    private $solrService;

    private $solrTools;

    private $configService;

    private $fileService;

    public function __construct(IUserManager $userManager, $rootFolder, $solrService, $solrTools, $configService, $fileService)
    {
        parent::__construct();
        $this->userManager = $userManager;
        $this->rootFolder = $rootFolder;
        $this->solrService = $solrService;
        $this->solrTools = $solrTools;
        $this->configService = $configService;
        $this->fileService = $fileService;
    }

    protected function configure()
    {
        parent::configure();
        $this->setName('nextant:index')->setDescription('scan users\' files, generate and index Solr documents');
        // ->addOption('debug', 'd', InputOption::VALUE_REQUIRED, 'Generate a complete log file named nextant.txt at the root of your cloud. You will need to specify a userId (ie. --debug=admin)');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('> <comment>This is an alpha release, please report any issue to </comment>');
        $output->writeln('   <comment> https://help.nextcloud.com/t/nextant-navigate-through-your-cloud-using-solr/2954/ </comment>');
        $output->writeln('');
        if (! $this->solrService->configured()) {
            $output->writeln('Nextant is not yet configured');
            return;
        }
        
        $this->solrService->setOutput($output);
        
        //
        // extract files
        $output->writeln('');
        $output->writeln('* Extracting new files to Solr:');
        
        $users = $this->userManager->search('');
        $usersTotal = sizeof($users);
        $usersCurrent = 0;
        $extractedDocuments = array();
        $extractedTotal = 0;
        $processedTotal = 0;
        foreach ($users as $user) {
            $usersCurrent ++;
            
            if ($this->hasBeenInterrupted())
                break;
            
            $userId = $user->getUID();
            $this->solrService->setOwner($userId);
            
            $result = $this->browseUserDirectory(array(
                'userid' => $userId,
                'usersCurrent' => $usersCurrent,
                'usersTotal' => $usersTotal
            ), $output);
            
            if ($result['total'] > 0) {
                
                array_push($extractedDocuments, array(
                    'userid' => $userId,
                    'files' => $result['files']
                ));
                
                $extractedTotal += sizeof($result['files']);
                $processedTotal += $result['processed'];
            }
            
            $output->writeln('');
        }
        
        $output->writeln('       ' . $processedTotal . ' file(s) processed ; ' . $extractedTotal . ' extracted documents');
        
        //
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
            
            if (! $this->updateUserDocuments(array(
                'userid' => $userId,
                'usersCurrent' => $usersCurrent,
                'usersTotal' => $usersTotal
            ), $fileIds, $output))
                $output->writeln('  fail ');
            
            $output->writeln('');
        }
        
        Filesystem::tearDown();
        
        $this->configService->setAppValue('needed_index', '0');
    }

    private function browseUserDirectory($info, $output)
    {
        $userId = $info['userid'];
        Filesystem::tearDown();
        Filesystem::init($userId, '');
        $this->fileService->setView(Filesystem::getView());
        
        $userFolder = $this->rootFolder->getUserFolder($userId);
        $folder = $userFolder->get('/');
        $files = $folder->search('');
        
        $progress = new ProgressBar($output, sizeof($files));
        $progress->setMessage('[' . $info['usersCurrent'] . '/' . $info['usersTotal'] . '] <info>' . $userId . '</info>: ');
        $progress->setMessage('', 'jvm');
        $progress->setMessage('(loading)', 'infos');
        $progress->setFormat(' %message:-30s%%current:5s%/%max:5s% [%bar%] %percent:3s%% - Solr memory: %jvm:-18s% %infos:-12s%');
        $progress->start();
        
        $filesProcessed = 0;
        $fileIds = array();
        $i = 0;
        foreach ($files as $file) {
            if ($this->hasBeenInterrupted()) {
                throw new \Exception('ctrl-c');
            }
            
            if (($i % self::REFRESH_INFO_SYSTEM) == 0) {
                $infoSystem = $this->solrTools->getInfoSystem();
                $progress->setMessage($infoSystem->jvm->memory->used, 'jvm');
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
                    if ($status > 0) {
                        $progress->setMessage('(extracting)', 'infos');
                        $progress->display();
                    } else {
                        $progress->setMessage('(scanning)', 'infos');
                        $progress->display();
                    }
                } else {
                    $progress->setMessage('(scanning)', 'infos');
                    $progress->display();
                }
            }
            
            $progress->advance();
            $i ++;
        }
        
        $progress->finish();
        
        return array(
            'extracted' => sizeof($fileIds),
            'processed' => $filesProcessed,
            'total' => sizeof($files),
            'files' => $fileIds
        );
    }

    private function updateUserDocuments($info, $fileIds, $output)
    {
        $userId = $info['userid'];
        
        Filesystem::tearDown();
        Filesystem::init($userId, '');
        $this->fileService->setView(Filesystem::getView());
        
        $cycle = array_chunk($fileIds, SolrService::UPDATE_CHUNK_SIZE);
        
        $progress = new ProgressBar($output, sizeof($fileIds));
        $progress->setFormat(' %message:-30s% [%bar%] %percent:3s%% - Solr memory: %jvm:-10s%  ');
        $progress->setMessage('[' . $info['usersCurrent'] . '/' . $info['usersTotal'] . '] <info>' . $userId . '</info>: ');
        $progress->setMessage('', 'jvm');
        $progress->start();
        
        $i = 0;
        foreach ($cycle as $batch) {
            if ($this->hasBeenInterrupted()) {
                throw new \Exception('ctrl-c');
            }
            
            $result = $this->fileService->updateFiles($batch, $error);
            
            $infoSystem = $this->solrTools->getInfoSystem();
            $progress->setMessage($infoSystem->jvm->memory->used, 'jvm');
            $progress->advance(SolrService::UPDATE_CHUNK_SIZE);
            
            if (! $result) {
                $output->writeln('  fail ' . $error);
                return false;
            }
            
            $i ++;
            if (($i % 10) == 0) {
                sleep(3);
            }
        }
        
        $progress->finish();
        
        return true;
    }
}



