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
/** global: nav */
/** global: _ */
/** global: api */
/** global: search */
/** global: result */
/** global: fullTextSearch */
/** global: settings */


var box_elements = {
	searchTimeout: null,

	searchBoxInitialized: false,
	search_form: null,
	moreDisplayed: false,
	iconSearchOptions: 'options.svg',
	iconSearch: 'fulltextsearch.svg',
	currentBackgroundImage: '',

	// v0.6.0
	searchInput: null,
	searchMore: null,
	delayAnimation: 500,
	divFullTextSearchIcon: null,
	divFullTextSearchPopup: null
};


var searchbox = {

	init: function () {

		var self = this;

		// we remove old search
		var search_form = $('FORM.searchbox');
		if (search_form.length > 0) {
			search_form.remove();
		}


		var divHeaderRight = $('DIV.header-right');
		var divFullTextSearch = $('<div>', {id: 'fulltextsearch'});
		divHeaderRight.prepend(divFullTextSearch);

		box_elements.divFullTextSearchIcon = searchbox.generateFullTextSearchIcon();
		box_elements.divFullTextSearchPopup = searchbox.generateFullTextSearchPopup();
		divFullTextSearch.append(box_elements.divFullTextSearchIcon);
		divFullTextSearch.append(box_elements.divFullTextSearchPopup);

		OC.registerMenu(box_elements.divFullTextSearchIcon, box_elements.divFullTextSearchPopup,
			searchbox.displayedSearchPopup);

		fullTextSearch.retrieveOptions(settings.searchProviderId);

		$(window).bind('keydown', function (event) {
			if (event.ctrlKey || event.metaKey) {
				if (String.fromCharCode(event.which).toLowerCase() === 'f') {
					event.preventDefault();
					searchbox.displaySearchPopup(true);
				}

				return;
			}

			if (event.which === 27) {
				searchbox.displaySearchPopup(false);
			}
		});


	},


	generateFullTextSearchIcon: function () {
		var className = 'icon-fulltextsearch';
		if (!OCA.Theming.inverted) {
			className = 'icon-fulltextsearch-white';
		}

		var icon = $('<div>', {
			id: 'fts-icon',
			tabindex: 0,
			role: 'link',
			class: className + ' menutoggle'
		});

		icon.fadeTo(0, 0.6);

		return icon;
	},


	generateFullTextSearchPopup: function () {
		var popup = $('<div>', {
			id: 'fts-popup'
		});

		var self = this;
		box_elements.searchInput = $('<input>', {
			id: 'fts-input',
			placeholder: 'Search ' + settings.searchProviderName
		}).on('keyup', self.searching);
		box_elements.searchMore = $('<div>', {id: 'fts-more'});

		var divHeader = $('<div>', {id: 'fts-header'});
		divHeader.append($('<div>').append(box_elements.searchInput));

		popup.append(divHeader);
		popup.append(box_elements.searchMore);

		return popup;
	},


	displaySearchPopup: function (display) {
		if (display) {
			OC.showMenu(box_elements.divFullTextSearchIcon, box_elements.divFullTextSearchPopup,
				searchbox.displayedSearchPopup);
		} else {
			OC.hideMenus(null);
		}
	},


	displayedSearchPopup: function () {
		box_elements.searchInput.focus();
	},


	searching: function (force) {
		var search = box_elements.searchInput.val();
		if (force === undefined) {
			force = false;
		}

		if (!force && search.length < 3) {
			return;
		}

		if (search === '' || (!force && curr.lastRequest === search)) {
			return;
		}
		curr.lastRequest = search;

		api.search({
			providers: settings.searchProviderId,
			search: search,
			page: curr.page,
			options: searchbox.getSearchOptions(),
			size: 20
		});

//		return;
		// if (settings.lockSearchbox === true) {
		// 	return;
		// }
		// settings.lockSearchbox = true;
		// searchbox.search_icon.stop().fadeTo(100, 0);
		// searchbox.search_form.stop().fadeTo(100, 0.8);
		// searchbox.search_input.focus();
		// searchbox.search_icon_close.stop().fadeTo(200, 1);
		// if (settings.noMoreOptions) {
		// 	searchbox.search_icon_more.stop().fadeTo(200, 1);
		// }
	},


	onOptionsLoaded: function (result) {
		if (!result[settings.searchProviderId]) {
			return;
		}

		box_elements.searchMore.html(result[settings.searchProviderId]);
		box_elements.searchMore.find('INPUT').each(function () {
			$(this).on('change', function () {
				searchbox.searching(true);
			});
		})
	},


	/**
	 *
	 * 0.6.0
	 *
	 *
	 */
	//
	//
	// initFullTextSearchBox: function () {
	// 	if (box_elements.searchBoxInitialized) {
	// 		return;
	// 	}
	// 	var self = this;
	//
	// 	box_elements.search_input.unbind('keyup');
	// 	box_elements.search_input.bind('keyup blur change', function () {
	// 		if ($(this).val() === '') {
	// 			self.displaySearchOptionsIcon(false);
	// 		} else {
	// 			self.displaySearchOptionsIcon(true);
	// 		}
	//
	// 		self.searching();
	// 	});
	//
	// 	box_elements.searchBoxInitialized = true;
	// },
	//


	getSearchOptions: function () {
		var options = {};

		if (box_elements.searchMore === null) {
			return options;
		}

		box_elements.searchMore.find('INPUT').each(function () {
			var value = $(this).val();

			if ($(this).attr('type') === 'checkbox' && !$(this).is(':checked')) {
				value = '';
			}

			options[$(this).attr('id')] = value;
		});

		return options;
	},


	// TODO: do we really need this initSearch, or should we use the one from fulltextsearch.js !?
	initSearch: function (force) {
		// var search = searchbox.search_input.val();
		//
		// if (!force && search.length < 3) {
		// 	return false;
		// }
		//
		// if (curr.lastRequest === search) {
		// 	return true;
		// }
		//
		// curr.lastRequest = search;
		//
		// fullTextSearch.search({
		// 	providers: settings.searchProviderId,
		// 	search: search,
		// 	page: curr.page,
		// 	options: searchbar.getSearchOptions(),
		// 	size: 20
		// });
		//
		// return true;
	}


};


