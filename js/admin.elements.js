/*
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


