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

class Live extends Base
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

    public function __construct($queueService, $indexService, $solrService, $solrTools, $configService, $fileService, $bookmarkService, $miscService)
    {
        parent::__construct();
        $this->queueService = $queueService;
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
        $this->setName('nextant:live')
            ->setDescription('[ALPHA] Real Live Extract -- do not use')
            ->addOption('instant', 'i', InputOption::VALUE_NONE, 'Instant indexes');
    }

    public function interrupted()
    {
        if ($this->hasBeenInterrupted())
            throw new \Exception('ctrl-c');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<comment>nextant v' . $this->configService->getAppValue('installed_version') . ' (beta)</comment>');
        $output->writeln('');
        
        if (! $this->solrService->configured(true)) {
            $output->writeln('Nextant is not yet configured');
            return;
        }
        
        // $this->miscService->setDebug($input->getOption('debug'));
        // $this->fileService->setDebug($input->getOption('debug'));
        // $this->indexService->setDebug($input->getOption('debug'));
        
        $this->solrService->setOutput($output);
        $this->indexService->setOutput($output);
        $this->indexService->setParent($this);
        
        $output->writeln('');
        
        $stack = array();
        $lasttick = 0;
        
        while (true) {
            $this->interrupted();
            $item = $this->queueService->readQueue(true);
            if ($item !== false) {
                if ($input->getOption('instant'))
                    $this->queueService->executeItem($item);
                else {
                    
                    $this->queueService->executeItem($item);
                }
            }
            $output->writeln('');
        }
    }
}



