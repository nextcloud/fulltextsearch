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
use Symfony\Component\Console\Question\ConfirmationQuestion;

class Pick extends Base
{

    private $configService;

    private $solrService;

    private $solrTools;

    public function __construct($configService, $solrService, $solrTools)
    {
        parent::__construct();
        $this->configService = $configService;
        $this->solrService = $solrService;
        $this->solrTools = $solrTools;
    }

    protected function configure()
    {
        parent::configure();
        $this->setName('nextant:pick')
            ->setDescription('pick a result from your index')
            ->addArgument('document_id', InputArgument::OPTIONAL, 'id of the document to scan')
            ->addOption('type', 't', InputArgument::OPTIONAL, 'type of the document to search (default: files)')
            ->addOption('search', 's', InputArgument::OPTIONAL, 'keyword to search in the document');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (! $this->solrService->configured(true)) {
            $output->writeln('Nextant is not yet configured');
            return;
        }
        
        if (! $id = $input->getArgument('document_id')) {
            $output->writeln('You need to specify the document id');
            return;
        }
        
        switch ($input->getOption('type')) {
            case 'bookmarks':
                $type = 'bookmarks';
                break;
            
            default:
                $type = 'files';
                break;
        }
        
        $result = $this->solrTools->pick($type, $id);
        if ($result->getNumFound() === 0) {
            $output->writeln("Can't find document corresponding that id");
            return;
        }
        
        foreach ($result as $document) {
            
            foreach ($document as $field => $value) {
                
                if ($field === 'text_edge')
                    continue;
                
                if (is_array($value))
                    $value = implode(', ', $value);
                
                $output->writeln($field . ' -> ' . $value);
            }
            
            if ($kw = $input->getOption('search')) {
                $search = $this->solrService->search($kw, array(
                    'no_owner_check',
                    'limit_document_id' => $type . '_' . $id
                ));
                
                $output->writeln('');
                $output->write("* Searching '" . $kw . "' in that document: ");
                if (sizeof($search) === 0)
                    $output->writeln('<error>fail</error>');
                else
                    $output->writeln('<info>OK</info>');
            }
            
            $output->writeln('');
        }
    }
}



