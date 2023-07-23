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


return [
	'ocs' => [
		/** @see OCA\FullTextSearch\Controller\CollectionController */
		['name' => 'Collection#getQueue', 'url' => '/collection/{collection}/index', 'verb' => 'GET'],
		[
			'name' => 'Collection#indexDocument',
			'url' => '/collection/{collection}/document/{providerId}/{documentId}',
			'verb' => 'GET'
		],
		[
			'name' => 'Collection#updateStatusDone',
			'url' => '/collection/{collection}/document/{providerId}/{documentId}/done',
			'verb' => 'POST'
		]
	],

	'routes' => [
		['name' => 'Navigation#navigate', 'url' => '/', 'verb' => 'GET'],
		['name' => 'Settings#getSettingsAdmin', 'url' => '/admin/settings', 'verb' => 'GET'],
		['name' => 'Settings#setSettingsAdmin', 'url' => '/admin/settings', 'verb' => 'POST'],
		['name' => 'Api#search', 'url' => '/v1/search', 'verb' => 'GET'],
		['name' => 'Api#searchFromRemote', 'url' => '/v1/remote', 'verb' => 'GET'],
		['name' => 'Templates#getOptionsPanel', 'url' => '/options/{providerId}/', 'verb' => 'GET'],
		['name' => 'Templates#getNavigationPanels', 'url' => '/navigation/panels', 'verb' => 'GET']
	]
];
