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
