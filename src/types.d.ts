/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

export interface IPlatform {
	id: string
	name: string
}

export interface IAdminSettingsConfig {
	app_navigation: boolean
	search_platform: string
	platforms_all: Record<string, IPlatform>
	providers_all: Record<string, string>
}

/**
 * Detail payload of the `fulltextsearch:settings-admin-updated` window event.
 *
 * Platform and provider apps (e.g. fulltextsearch_elasticsearch, files_fulltextsearch) listen
 * for this event to decide whether to show or hide their own admin settings section.
 */
export interface ISettingsUpdatedEventDetail {
	/** `IFullTextSearchPlatform::getId()` of the currently selected platform, or '' if none is selected. */
	platform: string
	/** `IFullTextSearchProvider::getId()` of every registered provider, only populated once a platform is selected. */
	providers: string[]
}

/**
 * One `ISearchOption` as serialized by `OC\FullTextSearch\Model\SearchOption`, describing a
 * single filter control a Content Provider wants displayed in the navigation panel.
 */
export interface ISearchOption {
	name: string
	title: string
	type: 'checkbox' | 'input'
	size: string
	placeholder: string
}

/** One entry of the `GET /navigation/panels` response, keyed by the provider's app id. */
export interface INavigationPanel {
	provider: string
	title: string
	options: ISearchOption[]
	css: string
	icon: string
}

export type INavigationPanels = Record<string, INavigationPanel>

export interface ISearchExcerpt {
	source: string
	excerpt: string
}

/** `OC\FullTextSearch\Model\IndexDocument::jsonSerialize()`, trimmed to the fields the UI uses. */
export interface ISearchDocument {
	id: string
	providerId: string
	title: string
	link: string
	source: string
	hash: string
	tags: string[]
	excerpts: ISearchExcerpt[] | null
	score: string
}

export interface ISearchResultMeta {
	timedOut: boolean
	time: number
	count: number
	total: number
	maxScore: number
}

/** One entry of the `result` array, i.e. one provider's contribution to a search. */
export interface ISearchResult {
	provider: { id: string, name: string }
	documents: ISearchDocument[]
	meta: ISearchResultMeta
}

export interface ISearchResponse {
	result: ISearchResult[]
	status: number
	version: string
}

export interface ISearchErrorResponse {
	status: number
	exception: string
	message: string
}
