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

	searchRequestTimer: 4000,
	searchEntryTimer: 1500,
	parent: null,
	searchProviderId: '',
	searchProviderName: '',
	resultContainer: null,
	resultHeader: null,
	resultFooter: null,
	entryTemplate: null,
	entryTemplateDefault: null,
	// divNoResult: null,
	options: [],

	/**
	 * generate the default template to display search result entries
	 */
	generateDefaultTemplate: function () {

		var resultContent = $('<div>', {class: 'result_content'});
		resultContent.append($('<div>', {
			id: 'title',
			class: 'result_title'
		}));
		resultContent.append($('<div>', {
			id: 'extract',
			class: 'result_extract'
		}));

		var resultEntry = $('<div>', {class: 'result_entry'});
		resultEntry.append($('<div>', {class: 'result_div_checkbox'}));

		resultEntry.append($('<div>', {class: 'result_div result_div_content'}).append(resultContent));

		var resultRight = $('<div>', {class: 'result_div result_div_right'});
		resultRight.append($('<div>', {id: 'source'}));
		resultRight.append($('<div>', {id: 'info'}));
		resultEntry.append(resultRight);

		return $('<div>').append(resultEntry);
	},


	// 	var divLeft = $('<div>', {class: 'result_entry_left'});
	// 	divLeft.append($('<div>', {id: 'title'}));
	// 	divLeft.append($('<div>', {id: 'line1'}));
	// 	divLeft.append($('<div>', {id: 'line2'}));
	//
	// 	var divRight = $('<div>', {class: 'result_entry_right'});
	// 	//divRight.append($('<div>', {id: 'score'}));
	//
	// 	var div = $('<div>', {class: 'result_entry_default'});
	// 	div.append(divLeft);
	// 	div.append(divRight);
	//
	// 	return $('<div>').append(div);
	// },

	//
	// /**
	//  * generate a no result display
	//  */
	// generateNoResultDiv: function () {
	// 	var div = $('<div>', {id: 'noresult'});
	// 	div.html('no result');
	// 	div.hide();
	// 	settings.divNoResult = div;
	// },


	/**
	 * @param template
	 */
	setResultHeader: function (template) {
		settings.resultHeader = template;
	},

	/**
	 * @param template
	 */
	setResultFooter: function (template) {
		settings.resultFooter = template;
	},


	/**
	 * used to set the template to display search result entries
	 *
	 * @param template
	 */
	setEntryTemplate: function (template) {
		settings.entryTemplate = template;
	},


	/**
	 * used to set the container for the search result entries
	 *
	 * @param container
	 */
	setResultContainer: function (container) {
		settings.resultContainer = container;
		// settings.resultContainer.prepend(settings.divNoResult);
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
	 * @param context
	 * @returns {boolean}
	 */
	parentHasMethod: function (method, context) {
		if (settings.parent === null) {
			return false;
		}

		var functionName = 'settings.parent.' + method;
		var args = Array.prototype.slice.call(arguments, 2);
		var namespaces = functionName.split(".");
		for (var i = 0; i < namespaces.length; i++) {
			if (context[namespaces[i]] === undefined) {
				return false;
			}
			context = context[namespaces[i]];
		}
		return true;
	},


	executeFunction: function (functionName, context) {
		var args = Array.prototype.slice.call(arguments, 2);
		var namespaces = functionName.split(".");
		var func = namespaces.pop();
		for (var i = 0; i < namespaces.length; i++) {
			if (context[namespaces[i]] === undefined) {
				// console.log('Unknown function \'' + functionName + '\'');
				return false;
			}
			context = context[namespaces[i]];
		}

		return context[func].apply(context, args);
	}


};
