<?php
/**
 * Nextcloud - nextant
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@pontapreta.net>
 * @copyright Maxence Lange 2016
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
return [
    'routes' => [
        [
            'name' => 'settings#setSettings',
            'url' => 'ajax/settings/admin.php',
            'verb' => 'POST'
        ],
        [
            'name' => 'settings#setOptionsFiles',
            'url' => 'ajax/settings/option_files.php',
            'verb' => 'POST'
        ],
        [
            'name' => 'settings#setOptionsFilesFilters',
            'url' => 'ajax/settings/option_files_filters.php',
            'verb' => 'POST'
        ],
        [
            'name' => 'settings#setOptionsBookmarks',
            'url' => 'ajax/settings/option_bookmarks.php',
            'verb' => 'POST'
        ],
        [
            'name' => 'settings#setOptionsStatus',
            'url' => 'ajax/settings/option_status.php',
            'verb' => 'POST'
        ],
        [
            'name' => 'settings#updateSubOptions',
            'url' => 'ajax/settings/updateSubOptions.php',
            'verb' => 'POST'
        ],
        [
            'name' => 'search#searchRequest',
            'url' => 'ajax/search.php',
            'verb' => 'POST'
        ],
        [
            'name' => 'search#getSuggestions',
            'url' => 'ajax/suggest.php',
            'verb' => 'POST'
        ],
        [
            'name' => 'search#searchRequestShareLink',
            'url' => 'ajax/search_sharelink.php',
            'verb' => 'POST'
        ],
        [
            'name' => 'search#getSuggestionsShareLink',
            'url' => 'ajax/suggest_sharelink.php',
            'verb' => 'POST'
        ]
    ]
]
;

/*
 * This route is now useless. It was used with navigate.js + SearchController
 * We are now using SearchProvider.
 */
