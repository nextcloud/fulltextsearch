<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FullTextSearch\Command;

use Exception;
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


	/**
	 *
	 */
	protected function configure() {
		parent::configure();
		$this->setName('fulltextsearch:configure')
			 ->addArgument('json', InputArgument::REQUIRED, 'set config')
			 ->setDescription('Configure the installation');
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @return int
	 * @throws Exception
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$json = $input->getArgument('json');

		$config = json_decode($json, true);

		if ($config === null) {
			$output->writeln('Invalid JSON');

			return 0;
		}

		$ak = array_keys($config);
		foreach ($ak as $k) {
			if (array_key_exists($k, $this->configService->defaults)) {
				$this->configService->setAppValue($k, $config[$k]);
			}
		}

		$output->writeln(json_encode($this->configService->getConfig(), JSON_PRETTY_PRINT));

		return 0;
	}


}



