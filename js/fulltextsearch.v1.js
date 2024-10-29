/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/** global: OCA */
/** global: settings */
/** global: searchbar */
/** global: result */
/** global: nav */
/** global: api */


(function () {

	/**
	 * @constructs FullTextSearch
	 */
	var FullTextSearch = function () {
		$.extend(FullTextSearch.prototype, settings);
		$.extend(FullTextSearch.prototype, result);
		$.extend(FullTextSearch.prototype, searchbox);
		$.extend(FullTextSearch.prototype, nav);
		$.extend(FullTextSearch.prototype, api);

//		settings.generateNoResultDiv();
	};

	OCA.FullTextSearch = FullTextSearch;
	OCA.FullTextSearch.api = new FullTextSearch();

})();
