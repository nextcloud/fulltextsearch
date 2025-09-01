<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FullTextSearch\Cron;

use Exception;
use OCA\FullTextSearch\ConfigLexicon;
use OCA\FullTextSearch\Exceptions\PlatformTemporaryException;
use OCA\FullTextSearch\Exceptions\RunnerAlreadyUpException;
use OCA\FullTextSearch\Model\Runner;
use OCA\FullTextSearch\Service\ConfigService;
use OCA\FullTextSearch\Service\IndexService;
use OCA\FullTextSearch\Service\PlatformService;
use OCA\FullTextSearch\Service\ProviderService;
use OCA\FullTextSearch\Service\RunningService;
use OCP\AppFramework\QueryException;
use OCP\AppFramework\Services\IAppConfig;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJob;
use OCP\BackgroundJob\TimedJob;
use Psr\Log\LoggerInterface;
use Throwable;

class Index extends TimedJob {
	const HOUR_ERR_RESET = 240;

	private Runner $runner;

	public function __construct(
		ITimeFactory $timeFactory,
		private RunningService $runningService,
		private IndexService $indexService,
		private PlatformService $platformService,
		private ProviderService $providerService,
		private IAppConfig $appConfig,
		private LoggerInterface $logger,
	) {
		parent::__construct($timeFactory);
		$this->setInterval(12 * 60); // 12 minutes
		$this->setTimeSensitivity(IJob::TIME_SENSITIVE);
	}


	/**
	 * @param mixed $argument
	 *
	 * @throws QueryException
	 */
	protected function run($argument) {
		$this->runner = new Runner($this->runningService, 'cronIndex');

		try {
			$this->runner->start();
			$this->liveCycle();
			$this->runner->stop();
		} catch (RunnerAlreadyUpException $e) {
		} catch (Exception $e) {
			$this->logger->notice(
				'exception encountered while running fulltextsearch/lib/Cron/Index.php',
				['exception' => $e]
			);
			$this->runner->exception($e->getMessage(), true);
		}

	}


	/**
	 * @throws Exception
	 */
	private function liveCycle() {

		$wrapper = $this->platformService->getPlatform(true);
		$platform = $wrapper->getPlatform();

		$all = $this->shouldWeGetAllIndex();
		$indexes = $this->indexService->getQueuedIndexes('', $all);

		foreach ($indexes as $index) {
			$this->runner->updateAction('indexing');

			try {
				$providerWrapper = $this->providerService->getProvider($index->getProviderId());
				$provider = $providerWrapper->getProvider();

				$this->indexService->updateDocument($platform, $provider, $index);
			} catch (PlatformTemporaryException $e) {
				$this->logger->warning('platform seems down. we will update index next cron tick');
				return;
			} catch (Throwable|Exception $e) {
				$this->runner->exception(get_class($e) . ' - ' . $e->getMessage(), false);
				$this->logger->notice(
					'exception encountered while running fulltextsearch/lib/Cron/Index.php',
					['exception' => $e]
				);
				// TODO - upgrade error number - after too many errors, delete index
				// TODO - do not count error if elasticsearch is down.
			}
		}

	}


	/**
	 * @return bool
	 */
	private function shouldWeGetAllIndex(): bool {
		$lastErrReset = $this->appConfig->getAppValueInt(ConfigLexicon::CRON_LAST_ERR_RESET);
		if ($lastErrReset === 0) {
			$this->setLastErrReset();

			return false;
		}

		if ($lastErrReset < (time() - (self::HOUR_ERR_RESET * 3600))) {
			$this->setLastErrReset();

			return true;
		}

		return false;
	}

	/**
	 *
	 */
	private function setLastErrReset() {
		$this->appConfig->setAppValueInt(ConfigLexicon::CRON_LAST_ERR_RESET, time());
	}
}
