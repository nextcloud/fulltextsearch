<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FullTextSearch\Search;

use Exception;
use OCA\FullTextSearch\Model\SearchRequest;
use OCA\FullTextSearch\Service\ConfigService;
use OCA\FullTextSearch\Service\SearchService;
use OCA\FullTextSearch\Tools\Traits\TArrayTools;
use OCP\FullTextSearch\Model\ISearchRequest;
use OCP\FullTextSearch\Model\ISearchResult;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\Search\IFilteringProvider;
use OCP\Search\ISearchQuery;
use OCP\Search\SearchResult;

/**
 * Class UnifiedSearchProvider
 *
 * @package OCA\FullTextSearch\Search
 */
class UnifiedSearchProvider implements IFilteringProvider {
	const PROVIDER_ID = 'fulltextsearch';
	const ORDER = 1;

	use TArrayTools;

	public function __construct(
		private IL10N $l10n,
		private IURLGenerator $urlGenerator,
		private SearchService $searchService,
		private ConfigService $configService
	) {
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

		$since = $query->getFilter('since')?->get();
		if ($since instanceof \DateTimeImmutable) {
			$searchRequest->addOption('since', (string)$since->getTimestamp());
		}
		$until = $query->getFilter('until')?->get();
		if ($until instanceof \DateTimeImmutable) {
			$searchRequest->addOption('until', (string)$until->getTimestamp());
		}

		$searchRequest->setProviders(['all']);
		$searchRequest->setSearch($query->getTerm());
		$searchRequest->setPage((int)floor(($query->getCursor() ?? 0) / $query->getLimit()) + 1);
		$searchRequest->setParts([]);
		$searchRequest->setSize($query->getLimit());
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
				$title = '(' . $document->getProviderId() . ') ';
				if (empty($excerpts)) {
					$title .= $document->getTitle();
					$subline = '';
				} else {
					$title .= (sizeof($excerpts) > 0) ? $excerpts[0]['excerpt'] : '';
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

	public function getSupportedFilters(): array {
		return [
			'term',
			'since',
			'until',
		];
	}

	public function getAlternateIds(): array {
		return [];
	}

	public function getCustomFilters(): array {
		return [];
	}
}

