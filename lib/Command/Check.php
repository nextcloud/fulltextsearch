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

class Check extends Base
{

    private $solrService;

    private $solrTools;

    private $solrAdmin;

    public function __construct($solrService, $solrTools, $solrAdmin)
    {
        parent::__construct();
        $this->solrService = $solrService;
        $this->solrTools = $solrTools;
        $this->solrAdmin = $solrAdmin;
    }

    protected function configure()
    {
        parent::configure();
        $this->setName('nextant:check')->setDescription('check, fix and optimise your current Solr configuration');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (! $this->solrService->configured(true)) {
            $output->writeln('Nextant is not yet configured');
            return;
        }
        
        $this->solrService->setOutput($output);
        
        $output->write('Ping: ');
        if ($this->solrAdmin->ping())
            $output->writeln('ok');
        else {
            $output->writeln('fail');
            return false;
        }
        
        if (! $this->solrAdmin->checkSchema(true, $error)) {
            $output->writeln('Error: ' . $error);
            return false;
        }
        
        $output->writeln('Your solr contains ' . $this->solrTools->count() . ' documents:');
        $output->writeln(' - ' . $this->solrTools->count('files') . ' files');
        $output->writeln(' - ' . $this->solrTools->count('bookmarks') . ' bookmarks');
    }
}



