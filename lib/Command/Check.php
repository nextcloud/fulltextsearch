<?php
declare(strict_types=1);


/**
 * FullTextSearch - Full text search framework for Nextcloud
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2018
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


namespace OCA\FullTextSearch\Command;


use Exception;
use OC\Core\Command\Base;
use OCA\FullTextSearch\Service\ConfigService;
use OCA\FullTextSearch\Service\MiscService;
use OCA\FullTextSearch\Service\PlatformService;
use OCA\FullTextSearch\Service\ProviderService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


class Check extends Base {

	/** @var ConfigService */
	private $configService;

	/** @var PlatformService */
	private $platformService;

	/** @var ProviderService */
	private $providerService;

	/** @var MiscService */
	private $miscService;


	/**
	 * Index constructor.
	 *
	 * @param ConfigService $configService
	 * @param PlatformService $platformService
	 * @param ProviderService $providerService
	 * @param MiscService $miscService
	 */
	public function __construct(
		ConfigService $configService, PlatformService $platformService,
		ProviderService $providerService, MiscService $miscService
	) {
		parent::__construct();

		$this->configService = $configService;
		$this->platformService = $platformService;
		$this->providerService = $providerService;
		$this->miscService = $miscService;
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

		$output->writeln(
			'Full text search ' . $this->configService->getAppValue('installed_version')
		);
		$output->writeln(' ');

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
				'version' => $this->configService->getAppValue('installed_version'),
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

		$select = $this->configService->getAppValue(ConfigService::SEARCH_PLATFORM);
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

