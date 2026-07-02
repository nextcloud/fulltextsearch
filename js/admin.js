/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/** global: OCA */
/** global: fts_admin_settings */
/** global: fts_admin_elements */
function ready(callback) {
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', callback);
	} else {
		callback();
	}
}


ready(function () {


	/**
	 * @constructs FullTextSearchAdmin
	 */
	var FullTextSearchAdmin = function () {
		Object.assign(FullTextSearchAdmin.prototype, fts_admin_elements, fts_admin_settings);

		fts_admin_elements.init();
		fts_admin_settings.refreshSettingPage();
	};

	window.OCA = window.OCA || {};
	window.OCA.FullTextSearchAdmin = FullTextSearchAdmin;
	window.OCA.FullTextSearchAdmin.settings = new FullTextSearchAdmin();

});
