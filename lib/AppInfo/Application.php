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


namespace OCA\FullTextSearch\AppInfo;


use Closure;
use OC;
use OCA\FullTextSearch\Capabilities;
use OCA\FullTextSearch\Search\UnifiedSearchProvider;
use OCA\FullTextSearch\Service\ConfigService;
use OCA\FullTextSearch\Service\IndexService;
use OCA\FullTextSearch\Service\ProviderService;
use OCA\FullTextSearch\Service\SearchService;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\FullTextSearch\IFullTextSearchManager;
use OCP\INavigationManager;
use OCP\IServerContainer;
use OCP\IURLGenerator;
use Psr\Container\ContainerInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Throwable;


class Application extends App implements IBootstrap {


	const APP_ID = 'fulltextsearch';
	const APP_NAME = 'FullTextSearch';


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
	 *
	 * @param ContainerInterface $container
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
	 *
	 * @param IServerContainer $container
	 */
	protected function registerNavigation(IServerContainer $container) {
		/** @var ConfigService $configService */
		$configService = $container->get(ConfigService::class);
		if ($configService->getAppValue(ConfigService::APP_NAVIGATION) !== '1') {
			return;
		}

		try {
			$container->get(INavigationManager::class)
					  ->add($this->fullTextSearchNavigation());
		} catch (RouteNotFoundException $e) {
		}
	}


	/**
	 * @return array
	 */
	private function fullTextSearchNavigation(): array {
		/** @var IURLGenerator $urlGen */
		$urlGen = OC::$server->get(IURLGenerator::class);

		return [
			'id' => self::APP_ID,
			'order' => 5,
			'href' => $urlGen->linkToRoute(self::APP_ID . '.Navigation.navigate'),
			'icon' => $urlGen->imagePath(self::APP_ID, 'fulltextsearch.svg'),
			'name' => 'Search'
		];
	}

}

