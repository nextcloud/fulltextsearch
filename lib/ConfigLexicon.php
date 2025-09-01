<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FullTextSearch;

use OCP\Config\Lexicon\Entry;
use OCP\Config\Lexicon\ILexicon;
use OCP\Config\Lexicon\Strictness;
use OCP\Config\ValueType;

/**
 * Config Lexicon for fulltextsearch.
 *
 * Please Add & Manage your Config Keys in that file and keep the Lexicon up to date!
 */
class ConfigLexicon implements ILexicon {
	public const APP_NAVIGATION = 'app_navigation';
	public const SEARCH_PLATFORM = 'search_platform';
	public const CRON_LAST_ERR_RESET = 'cron_err_reset';
	public const TICK_TTL = 'tick_ttl';
	public const COLLECTION_INDEXING_LIST = 'collection_indexing_list';
	public const COLLECTION_INTERNAL = 'collection_internal';
	public const COLLECTION_LINKS = 'collection_links';

	public function getStrictness(): Strictness {
		return Strictness::NOTICE;
	}

	public function getAppConfigs(): array {
		return [
			new Entry(key: self::APP_NAVIGATION, type: ValueType::BOOL, defaultRaw: false, definition: 'add an app navigation page for search'),
			new Entry(key: self::SEARCH_PLATFORM, type: ValueType::STRING, defaultRaw: '', definition: 'configured search platform'),
			new Entry(key: self::CRON_LAST_ERR_RESET, type: ValueType::INT, defaultRaw: 0, definition: '(internal) estimated time before retrying failed indexes'),
			new Entry(key: self::TICK_TTL, type: ValueType::INT, defaultRaw: 1800, definition: 'Time to Live before indexing is estimated as blocked'),
			new Entry(key: self::COLLECTION_INDEXING_LIST, type: ValueType::INT, defaultRaw: 50, definition: 'size of chunks of async documents on collection queue request'),
			new Entry(key: self::COLLECTION_INTERNAL, type: ValueType::STRING, defaultRaw: 'local', definition: 'name of the local collection'),
			new Entry(key: self::COLLECTION_LINKS, type: ValueType::ARRAY, defaultRaw: [], definition: '(internal) data relative to collections'),
		];
	}

	public function getUserConfigs(): array {
		return [];
	}
}
