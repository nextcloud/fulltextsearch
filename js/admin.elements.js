/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/** global: OCA */
/** global: fts_admin_settings */



var fts_admin_elements = {
	fts_div: null,
	fts_navigation: null,
	fts_platforms: null,
	fts_providers: null,

	init: function () {
		fts_admin_elements.fts_div = $('#fns');
		fts_admin_elements.fts_navigation = $('#fts_navigation');
		fts_admin_elements.fts_platforms = $('#fts_platforms');

		fts_admin_elements.fts_navigation.on('change', fts_admin_settings.saveSettings);
		fts_admin_elements.fts_platforms.on('change', function () {
			fts_admin_settings.tagSettingsAsNotSaved($(this));
			fts_admin_settings.saveSettings();
		});
	}
};


