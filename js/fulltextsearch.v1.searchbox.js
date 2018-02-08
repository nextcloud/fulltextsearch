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

	// v0.6.0

	searchBoxInitialized: false,
	search_form: null,
	search_input: null,
	moreOptions: false,
	moreDisplayed: false,
	search_more: null,
	iconSearchOptions: 'options.svg',
	iconSearch: 'fulltextsearch.svg',
	currentBackgroundImage: ''
};


var searchbox = {

	init: function () {

		var self = this;
		box_elements.search_form = $('FORM.searchbox');
		box_elements.search_input = $('INPUT#searchbox');


		// on focus of the searchbox, we remove all keyup event
		box_elements.search_input.bind('keydown', function () {
			self.initFullTextSearchBox();
		});

		// options
		if (OCA.Theming.inverted) {
			box_elements.iconSearchOptions = 'options_black.svg';
			box_elements.iconSearch = 'fulltextsearch_black.svg';
		}

		box_elements.currentBackgroundImage = box_elements.iconSearch;
		box_elements.search_input.css({
			'background-image': 'url(/apps/fulltextsearch/img/' + box_elements.iconSearch + ')'
		});

		box_elements.search_input.click(function (e) {
			var elm = $(this);
			var xPos = e.pageX - elm.offset().left;
			if (xPos < 26) {
				self.switchSearchOptions();
			}
		});

		fullTextSearch.retreiveOptions(settings.searchProviderId);

		box_elements.search_more =
			$('<div>', {class: 'search_more'}).css({'border-color': OCA.Theming.color});

		box_elements.search_form.append(box_elements.search_more);
		box_elements.search_more.fadeTo(0, 0).hide();
	},


	initFullTextSearchBox: function () {
		if (box_elements.searchBoxInitialized) {
			return;
		}
		var self = this;

		box_elements.search_input.unbind('keyup');
		box_elements.search_input.bind('keyup blur change', function () {
			if ($(this).val() === '') {
				self.displaySearchOptionsIcon(false);
			} else {
				self.displaySearchOptionsIcon(true);
			}

			self.searching();
		});

		box_elements.searchBoxInitialized = true;
	},


	switchSearchOptions: function () {
		this.displaySearchOptions(!box_elements.moreDisplayed);
	},

	displaySearchOptions: function (display) {
		if (!box_elements.moreOptions) {
			return;
		}

		if (display) {
			box_elements.search_more.stop().show().fadeTo(100, 1);
		} else {
			box_elements.search_more.stop().fadeTo(100, 0, function () {
				$(this).hide();
			});
		}
		box_elements.moreDisplayed = display;
	},


	displaySearchOptionsIcon: function (display) {

		if (!box_elements.moreOptions) {
			return;
		}

		if (display) {
			this.switchInputBackgroundImage(box_elements.iconSearchOptions);
		} else {
			if (box_elements.search_input.val() !== '') {
				return;
			}

			this.displaySearchOptions(false);
			this.switchInputBackgroundImage(box_elements.iconSearch);
		}
	},


	switchInputBackgroundImage: function (image) {

		if (image === box_elements.currentBackgroundImage) {
			return;
		}
		box_elements.currentBackgroundImage = image;

		box_elements.search_input.stop().animate({'background-position-x': '-70px'}, 150,
			function () {
				$(this).css({
					'background-image': 'url(/apps/fulltextsearch/img/' + image + ')'
				}).animate({'background-position-x': '6px'}, 150);
			});
	},


	searching: function () {

		var search = box_elements.search_input.val();
		console.log('searching ' + search);

		// 	fullTextSearch.search({
		// 		providers: settings.searchProviderId,
		// 		search: search,
		// 		page: curr.page,
		// 		options: searchbox.getSearchOptions(),
		// 		size: 20
		// 	});
		// });

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
			box_elements.moreOptions = false;
			return;
		}

		box_elements.moreOptions = true;
		box_elements.search_input.find('background-image').on('click', function () {
			console.log('___');
		});
		box_elements.search_more.html(result[settings.searchProviderId]);
		box_elements.search_more.find('INPUT').each(function () {
			$(this).on('change', searchbox.searching);
		})
	},


	getSearchOptions: function () {
		var options = {};
		// searchbox.search_more.find('INPUT').each(function () {
		// 	var value = $(this).val();
		//
		// 	if ($(this).attr('type') === 'checkbox' && !$(this).is(':checked')) {
		// 		value = '';
		// 	}
		//
		// 	options[$(this).attr('id')] = value;
		// });

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


