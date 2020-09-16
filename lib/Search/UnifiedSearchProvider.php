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


namespace OCA\FullTextSearch\Search;


use daita\MySmallPhpTools\Traits\TArrayTools;
use Exception;
use OCA\FullTextSearch\Model\SearchRequest;
use OCA\FullTextSearch\Service\ConfigService;
use OCA\FullTextSearch\Service\MiscService;
use OCA\FullTextSearch\Service\SearchService;
use OCP\FullTextSearch\Model\ISearchRequest;
use OCP\FullTextSearch\Model\ISearchResult;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\Search\IProvider;
use OCP\Search\ISearchQuery;
use OCP\Search\SearchResult;

/**
 * Class UnifiedSearchProvider
 *
 * @package OCA\FullTextSearch\Search
 */
class UnifiedSearchProvider implements IProvider {


	const PROVIDER_ID = 'fulltextsearch';
	const ORDER = 1;


	use TArrayTools;


	/** @var IL10N */
	private $l10n;

	/** @var IURLGenerator */
	private $urlGenerator;

	/** @var SearchService */
	private $searchService;

	/** @var ConfigService */
	private $configService;

	/** @var MiscService */
	private $miscService;


	/**
	 * UnifiedSearchProvider constructor.
	 *
	 * @param IL10N $l10n
	 * @param IURLGenerator $urlGenerator
	 * @param SearchService $searchService
	 * @param ConfigService $configService
	 * @param MiscService $miscService
	 */
	public function __construct(
		IL10N $l10n, IURLGenerator $urlGenerator, SearchService $searchService, ConfigService $configService,
		MiscService $miscService
	) {
		$this->l10n = $l10n;
		$this->urlGenerator = $urlGenerator;
		$this->searchService = $searchService;
		$this->configService = $configService;
		$this->miscService = $miscService;
	}


	/**
	 * return unique id of the provider
	 */
	public function getId(): string {
		return self::PROVIDER_ID;
	}


	/**
	 * @return string
	 */
	public function getName(): string {
		return $this->l10n->t('Full Text Search');
	}


	/**
	 * @param string $route
	 * @param array $routeParameters
	 *
	 * @return int
	 */
	public function getOrder(string $route, array $routeParameters): int {
		return self::ORDER;
	}


	/**
	 * @param IUser $user
	 * @param ISearchQuery $query
	 *
	 * @return SearchResult
	 */
	public function search(IUser $user, ISearchQuery $query): SearchResult {
		$result = [];

		$searchRequest = $this->generateSearchRequest($query);
		try {
			$ftsResult = $this->searchService->search($user->getUID(), $searchRequest);
			$result = $this->convertSearchResult($ftsResult);
		} catch (Exception $e) {
		}

		return SearchResult::paginated(
			$this->l10n->t('Full Text Search'), $result, ($query->getCursor() ?? 0) + $query->getLimit()
		);
	}


	/**
	 * @param $query
	 *
	 * @return ISearchRequest
	 */
	private function generateSearchRequest(ISearchQuery $query): ISearchRequest {
		$searchRequest = new SearchRequest();

		list($app, $controller, $method) = explode('.', $query->getRoute());

		$searchRequest->setProviders([$app]);
		$searchRequest->setSearch($query->getTerm());
		$searchRequest->setPage((int)floor(($query->getCursor() ?? 0) / $query->getLimit()) + 1);
		$searchRequest->setParts([]);
		$searchRequest->setSize($query->getLimit());
		$searchRequest->setOptions([]);
		$searchRequest->setTags([]);
		$searchRequest->setSubTags([]);
		$searchRequest->setSize($query->getLimit());

		return $searchRequest;
	}


	/**
	 * @param ISearchResult[] $searchResult
	 *
	 * @return UnifiedSearchResult[]
	 */
	private function convertSearchResult(array $searchResult): array {
		$result = [];
		foreach ($searchResult as $ftsSearch) {
			foreach ($ftsSearch->getDocuments() as $document) {
				$excerpts = $document->getExcerpts();
				if (empty($excerpts)) {
					$title = $document->getTitle();
					$subline = '';
				} else {
					$title = (sizeof($excerpts) > 0) ? $excerpts[0]['excerpt'] : '';
					$subline = $document->getTitle();
				}

				$unified = $document->getInfoArray('unified');
				$result[] = new UnifiedSearchResult(
					$this->get('thumbUrl', $unified, ''),
					$this->get('title', $unified, $title),
					$this->get('subline', $unified, $subline),
					$this->get('link', $unified, $document->getLink()),
					$this->get('icon', $unified, '')
				);
			}
		}

		return $result;
	}

}

