/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * Window CustomEvent dispatched whenever the selected search platform (or the set of registered
 * providers) changes on the admin settings page. See ISettingsUpdatedEventDetail for the payload.
 *
 * This is the public contract platform/provider apps use to show or hide their own admin settings
 * section.
 *
 * Since a platform/provider app's own script may attach its listener after this event has already
 * fired once (script load order across independently-registered settings sections is not
 * guaranteed), the same detail is also kept in sync on `window.OCA.FullTextSearch.settings` so
 * listeners can read the current state synchronously on init instead of only reacting to the event.
 */
export const SETTINGS_UPDATED_EVENT = 'fulltextsearch:settings-admin-updated'
