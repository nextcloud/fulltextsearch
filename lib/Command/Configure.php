<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FullTextSearch\Command;

use OC\Core\Command\Base;
use OCA\FullTextSearch\Service\ConfigService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Configure extends Base {
	public function __construct(
		private ConfigService $configService
	) {
		parent::__construct();
	}

	protected function configure() {
		parent::configure();
		$this->setName('fulltextsearch:configure')
			 ->addArgument('json', InputArgument::OPTIONAL, 'set config')
			 ->setDescription('Configure the installation');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		if ($input->getArgument('json')) {
			$this->configService->setConfig(json_decode($input->getArgument('json') ?? '', true) ?? []);
		}

		$output->writeln(json_encode($this->configService->getConfig(), JSON_PRETTY_PRINT));
		return self::SUCCESS;
	}
}



