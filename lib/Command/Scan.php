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

use OC\Core\Command\Base;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use OCP\IUserManager;
use OC\Files\Filesystem;

class Scan extends Base
{

    private $userManager;
    
    // private $userFolder;
    private $solrService;

    private $fileService;

    private $filesExtracted;

    private $filesTotal;

    public function __construct(IUserManager $userManager, $userFolder, $solrService, $fileService)
    {
        parent::__construct();
        $this->userManager = $userManager;
        $this->solrService = $solrService;
        // $this->userFolder = $userFolder;
        $this->fileService = $fileService;
    }

    protected function configure()
    {
        parent::configure();
        $this->setName('nextant:scan')->setDescription('extract text files and generate solr documents');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $users = $this->userManager->search('');
        $usersTotal = sizeof($users);
        $usersCurrent = 0;
        foreach ($users as $user) {
            $usersCurrent ++;
            
            if ($this->hasBeenInterrupted())
                break;
            
            $userId = $user->getUID();
            $output->writeln('Scanning files from <info>' . $userId . '</info> (' . $usersCurrent . '/' . $usersTotal . ')');
            
            $this->solrService->setOwner($userId);
            $result = $this->scanFiles($userId, $output);
            $output->writeln(' > extracted files: ' . $result['extracted'] . '/' . $result['total'] . '');
        }
    }

    private function scanFiles($userId, $output)
    {
        /**
         * Right now, the easiest way to scan the files is using the Utils\Scanner ...
         */
        $scanner = new \OC\Files\Utils\Scanner($userId, \OC::$server->getDatabaseConnection(), \OC::$server->getLogger());
        
        $this->filesTotal = 0;
        $this->filesExtracted = 0;
        $scanner->listen('\OC\Files\Utils\Scanner', 'scanFile', function ($path) use ($output) {
            if ($this->hasBeenInterrupted())
                throw new \Exception('ctrl-c');
            
            $this->filesTotal ++;
            if ($this->fileService->addFiles($path, false) !== false)
                $this->filesExtracted++;
        });
        
        $path = '/' . $userId . '/files/';
        
        Filesystem::init($userId, '');
        $this->fileService->setView(Filesystem::getView());
        
        $scanner->scan($path);
        return array(
            'extracted' => $this->filesExtracted,
            'total' => $this->filesTotal
        );
    }
}



