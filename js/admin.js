/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/** global: OCA */
/** global: fts_admin_settings */
/** global: fts_admin_elements */



$(document).ready(function () {


	/**
	 * @constructs FullTextSearchAdmin
	 */
	var FullTextSearchAdmin = function () {
		$.extend(FullTextSearchAdmin.prototype, fts_admin_elements);
		$.extend(FullTextSearchAdmin.prototype, fts_admin_settings);

		fts_admin_elements.init();
		fts_admin_settings.refreshSettingPage();
	};

	OCA.FullTextSearchAdmin = FullTextSearchAdmin;
	OCA.FullTextSearchAdmin.settings = new FullTextSearchAdmin();

});
