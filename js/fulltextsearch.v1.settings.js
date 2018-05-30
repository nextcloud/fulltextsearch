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

/** global: searchbar */
/** global: api */

var settings = {

	delay_provider: 300,
	delay_result: 150,

	// 0.6.0
	searchTimer: 1000,
	parent: null,
	searchProviderId: '',
	searchProviderName: '',
	resultContainer: null,
	entryTemplate: null,
	entryTemplateDefault: null,
	divNoResult: null,
	options: [],

	/**
	 * generate the default template to dsplay search result entries
	 */
	generateDefaultTemplate: function () {

		var divLeft = $('<div>', {class: 'result_entry_left'});
		divLeft.append($('<div>', {id: 'title'}));
		divLeft.append($('<div>', {id: 'line1'}));
		divLeft.append($('<div>', {id: 'line2'}));

		var divRight = $('<div>', {class: 'result_entry_right'});
		//divRight.append($('<div>', {id: 'score'}));

		var div = $('<div>', {class: 'result_entry_default'});
		div.append(divLeft);
		div.append(divRight);

		return $('<div>').append(div);
	},


	/**
	 * generate a no result display
	 */
	generateNoResultDiv: function () {
		var div = $('<div>', {id: 'noresult'});
		div.html('no result');
		div.hide();
		settings.divNoResult = div;
	},


	/**
	 * used to set the template to display search result entries
	 *
	 * @param template
	 * @param parent
	 */
	setEntryTemplate: function (template, parent) {
		settings.entryTemplate = template;
		if (parent !== null) {
			settings.parent = parent;
		}
	},

	/**
	 * used to set the container for the search result entries
	 *
	 * @param container
	 */
	setResultContainer: function (container) {
		settings.resultContainer = container;
		settings.resultContainer.prepend(settings.divNoResult);
	},


	/**
	 *  initialize the full text search and assign a providerId
	 *
	 * @param providerId
	 * @param providerName
	 * @param parent
	 */
	initFullTextSearch: function (providerId, providerName, parent) {
		settings.searchProviderId = providerId;
		settings.searchProviderName = providerName;
		settings.parent = parent;
		searchbox.init();
	},


	/**
	 * check that the app that call the lib contains a specific method
	 *
	 * @param method
	 * @returns {boolean}
	 */
	parentHasMethod: function (method) {
		if (settings.parent === null) {
			return false;
		}
		return (typeof eval('settings.parent. ' + method) === "function");
	}
};
