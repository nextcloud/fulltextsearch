<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

return [
	'ocs' => [
		/** @see OCA\FullTextSearch\Controller\CollectionController */
		['name' => 'Collection#resetCollection', 'url' => '/collection/{collection}/index', 'verb' => 'DELETE'],
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


