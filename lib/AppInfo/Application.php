<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FullTextSearch\AppInfo;

use Closure;
use OCA\FullTextSearch\Capabilities;
use OCA\FullTextSearch\ConfigLexicon;
use OCA\FullTextSearch\Search\UnifiedSearchProvider;
use OCA\FullTextSearch\Service\IndexService;
use OCA\FullTextSearch\Service\ProviderService;
use OCA\FullTextSearch\Service\SearchService;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\FullTextSearch\IFullTextSearchManager;
use OCP\IAppConfig;
use OCP\INavigationManager;
use OCP\IURLGenerator;
use OCP\L10N\IFactory;
use Psr\Container\ContainerInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Throwable;

if (file_exists($autoLoad = __DIR__ . '/../../vendor/autoload.php')) {
	include_once $autoLoad;
}

class Application extends App implements IBootstrap {
	public const APP_ID = 'fulltextsearch';
	public const APP_NAME = 'FullTextSearch';


	/**
	 * Application constructor.
	 *
	 * @param array $params
	 */
	public function __construct(array $params = []) {
		parent::__construct(self::APP_ID, $params);
	}


	/**
	 * @param IRegistrationContext $context
	 */
	public function register(IRegistrationContext $context): void {
		$context->registerCapability(Capabilities::class);
		$context->registerSearchProvider(UnifiedSearchProvider::class);
		$context->registerConfigLexicon(ConfigLexicon::class);
		$this->registerServices($this->getContainer());
	}

	/**
	 * @param IBootContext $context
	 *
	 * @throws Throwable
	 */
	public function boot(IBootContext $context): void {
		$context->injectFn(Closure::fromCallable([$this, 'registerNavigation']));
	}


	/**
	 * Register Navigation Tab
	 */
	protected function registerServices(ContainerInterface $container) {
		/** @var IFullTextSearchManager $fullTextSearchManager */
		$fullTextSearchManager = $container->get(IFullTextSearchManager::class);

		$providerService = $container->get(ProviderService::class);
		$indexService = $container->get(IndexService::class);
		$searchService = $container->get(SearchService::class);

		$fullTextSearchManager->registerProviderService($providerService);
		$fullTextSearchManager->registerIndexService($indexService);
		$fullTextSearchManager->registerSearchService($searchService);
	}


	/**
	 * Register Navigation Tab
	 */
	protected function registerNavigation(
		IAppConfig $appConfig,
		INavigationManager $navigationManager,
		IURLGenerator $urlGen,
		IFactory $l10nFactory,
	): void {
		if (!$appConfig->getValueBool(self::APP_ID, ConfigLexicon::APP_NAVIGATION)) {
			return;
		}

		try {
			$navigationManager->add(
				fn () => $this->fullTextSearchNavigation($urlGen, $l10nFactory)
			);
		} catch (RouteNotFoundException) {
			// Navigation route not found, do not add navigation entry
		}
	}


	/**
	 * @return array
	 */
	private function fullTextSearchNavigation(IURLGenerator $urlGen, IFactory $l10nFactory): array {
		return [
			'id' => self::APP_ID,
			'order' => 5,
			'href' => $urlGen->linkToRoute(self::APP_ID . '.Navigation.navigate'),
			'icon' => $urlGen->imagePath(self::APP_ID, 'fulltextsearch.svg'),
			'name' => $l10nFactory->get(self::APP_ID)->t('Search')
		];
	}
}
