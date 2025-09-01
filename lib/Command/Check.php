<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FullTextSearch\Command;

use Exception;
use OC\Core\Command\Base;
use OCA\FullTextSearch\ConfigLexicon;
use OCA\FullTextSearch\Service\ConfigService;
use OCA\FullTextSearch\Service\PlatformService;
use OCA\FullTextSearch\Service\ProviderService;
use OCP\AppFramework\Services\IAppConfig;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Check extends Base {
	public function __construct(
		private ConfigService $configService,
		private PlatformService $platformService,
		private ProviderService $providerService,
		private readonly IAppConfig $appConfig,
	) {
		parent::__construct();
	}


	/**
	 *
	 */
	protected function configure() {
		parent::configure();
		$this->setName('fulltextsearch:check')
			 ->addOption('json', 'j', InputOption::VALUE_NONE, 'return result as JSON')
			 ->setDescription('Check the installation');
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @return int
	 * @throws Exception
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		if ($input->getOption('json') === true) {
			$output->writeln(json_encode($this->displayAsJson(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

			return 0;
		}

		$output->writeln('Full text search ' . $this->appConfig->getAppValueString('installed_version'));
		$output->writeln(json_encode($this->configService->getConfig(), JSON_PRETTY_PRINT));
		$output->writeln('');

		$this->displayPlatform($output);
		$this->displayProviders($output);

		return 0;
	}


	/**
	 * @return array
	 */
	private function displayAsJson(): array {

		$resultPlatform = [];

		try {
			$platforms = $this->platformService->getPlatforms();
			foreach ($platforms as $platformWrapper) {
				$platform = $platformWrapper->getPlatform();
				$platform->loadPlatform();
				$resultPlatform[] = [
					'class'   => $platformWrapper->getClass(),
					'version' => $platformWrapper->getVersion(),
					'config'  => $platform->getConfiguration()
				];
			}

		} catch (Exception $e) {
			$resultPlatform = ['error' => $e->getMessage()];
		}

		$resultProviders = [];
		try {
			$providers = $this->providerService->getProviders();
			foreach ($providers as $providerWrapper) {
				$provider = $providerWrapper->getProvider();
				$resultProviders[$provider->getId()] = [
					'version' => $providerWrapper->getVersion(),
					'config'  => $provider->getConfiguration()
				];
			}
		} catch (Exception $e) {
			$resultProviders[] = ['error' => $e->getMessage()];
		}

		return [
			'fulltextsearch' => [
				'version' => $this->appConfig->getAppValueString('installed_version'),
				'config'  => $this->configService->getConfig()
			],

			'platform'  => $resultPlatform,
			'providers' => $resultProviders
		];

	}


	/**
	 * @param OutputInterface $output
	 *
	 * @throws Exception
	 */
	private function displayPlatform(OutputInterface $output) {
		$platforms = $this->platformService->getPlatforms();

		if (empty($platforms)) {
			$output->writeln('No Search Platform available');

			return;
		}

		$select = $this->appConfig->getAppValueString(ConfigLexicon::SEARCH_PLATFORM);
		$output->writeln('- Search Platform:' . (($select === '') ? ' (none selected)' : ''));

		foreach ($platforms as $platformWrapper) {
			$platform = $platformWrapper->getPlatform();
			$selected = ($platformWrapper->getClass() === $select) ? '(Selected)' : '';
			$output->writeln($platform->getName() . ' ' . $platformWrapper->getVersion() . ' ' . $selected);
			try {
				echo json_encode($platform->getConfiguration(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
			} catch (Exception $e) {
				echo '(not configured)';
			}
			$output->writeln(' ');
		}

		$output->writeln('');
	}


	/**
	 * @param OutputInterface $output
	 *
	 * @throws Exception
	 */
	private function displayProviders(OutputInterface $output) {
		$providers = $this->providerService->getProviders();

		if (sizeof($providers) === 0) {
			$output->writeln('No Content Provider available');

			return;
		}

		$output->writeln('- Content Providers:');

		foreach ($providers as $providerWrapper) {
			$provider = $providerWrapper->getProvider();
			$output->writeln($provider->getName() . ' ' . $providerWrapper->getVersion());
			echo json_encode($provider->getConfiguration(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
			$output->writeln('');
		}
	}

}

