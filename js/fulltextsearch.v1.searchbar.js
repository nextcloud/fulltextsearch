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


var searchbox = {
	searchTimeout: null,
	search_more: null,
	search_icon_more: null,
	search_icon_close: null,
	search_icon: null,
	search_input: null,
	search_form: null
};


var searchbar = {

	init: function () {
		var divHeaderRight = $('div.header-right');

		searchbox.search_div = $('<div>', {class: 'next_search_div'});
		divHeaderRight.prepend(searchbox.search_div);

		searchbox.search_icon = $('<div>', {class: 'icon-fulltextsearch'});
		searchbox.search_icon.css('background-image',
			"url('/apps/fulltextsearch/img/fulltextsearch.svg')");
		searchbox.search_icon.fadeTo(0, 0.7);
		searchbox.search_div.append(searchbox.search_icon);

		searchbox.search_form = $('<div>');
		searchbox.search_form.fadeTo(0, 0);

		searchbox.search_input = $('<input>', {
			id: 'next_search_input',
			placeholder: 'Search'
		});
		searchbox.search_form.append(searchbox.search_input);

		searchbox.search_more = $('<div>', {class: 'search_more'});
		searchbox.search_more.fadeTo(0, 0);

		searchbox.search_icon_more = $('<div>', {class: 'icon-more-white icon-more-fulltextsearch'});
		searchbox.search_icon_more.fadeTo(0, 0);
		searchbox.search_icon_more.on('click', function () {
			if (curr.moreDisplayed) {
				searchbox.search_more.stop().fadeTo(100, 0);
				curr.moreDisplayed = false;
			} else {
				searchbox.search_more.stop().fadeTo(100, 1);
				curr.moreDisplayed = true;
			}
		});
		searchbox.search_form.append(searchbox.search_icon_more);

		searchbox.search_icon_close = $('<div>', {class: 'icon-close-white icon-close-fulltextsearch'});
		searchbox.search_icon_close.fadeTo(0, 0);
		searchbox.search_icon_close.on('click', function () {
			settings.lockSearchbox = false;
			searchbox.search_icon_more.stop().fadeTo(100, 0);
			searchbox.search_icon_close.stop().fadeTo(100, 0);
			searchbox.search_more.stop().fadeTo(100, 0);
			curr.moreDisplayed = false;
			searchbox.search_input.val('');
			nav.onSearchReset();
		});
		searchbox.search_form.append(searchbox.search_icon_close);

		searchbox.search_form.hover(function () {
			searchbox.search_icon.stop().fadeTo(100, 0);
			searchbox.search_form.stop().fadeTo(100, 0.8);
		}, function () {
			if (settings.lockSearchbox === true) {
				return;
			}
			searchbox.search_form.stop().fadeTo(500, 0);
			searchbox.search_icon.stop().fadeTo(800, 0.7);
		});
		searchbox.search_div.append(searchbox.search_form);
		searchbox.search_div.append(searchbox.search_more);

		searchbox.search_input.on('focus', function () {
			settings.lockSearchbox = true;
			searchbox.search_icon_more.stop().fadeTo(200, 1);
			searchbox.search_icon_close.stop().fadeTo(200, 1);
		});

		searchbox.search_input.on('input', function () {

			if ($(this).val() === '') {
				nav.onSearchReset();
			}

			if (settings.parentHasMethod('onEntryGenerated')) {
				settings.parent.onEntryGenerated();
			}

			if (searchbox.searchTimeout === null && searchbar.initSearch(false)) {
				searchbox.searchTimeout = _.delay(function () {
					searchbar.initSearch(false);
					searchbox.searchTimeout = null;
				}, 2000);
			}
		});

		fullTextSearch.options(settings.searchProviderId);
	},


	onOptionsLoaded: function (result) {
		searchbox.search_more.html(result[settings.searchProviderId]);
		searchbox.search_more.find('INPUT').each(function () {
			$(this).on('change', function () {
				var search = searchbox.search_input.val();
				fullTextSearch.search({
					providers: settings.searchProviderId,
					search: search,
					page: curr.page,
					options: searchbar.getSearchOptions(),
					size: 20
				});
			});
		})
	},


	getSearchOptions: function () {
		var options = {};
		searchbox.search_more.find('INPUT').each(function () {
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
		var search = searchbox.search_input.val();

		if (!force && search.length < 3) {
			return false;
		}

		if (curr.lastRequest === search) {
			return true;
		}

		curr.lastRequest = search;

		fullTextSearch.search({
			providers: settings.searchProviderId,
			search: search,
			page: curr.page,
			options: searchbar.getSearchOptions(),
			size: 20
		});

		return true;
	}


};


