/*
 * FullTextSearch - Full text search your Nextcloud.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2017
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
 *
 */


/** global: OCA */
/** global: _ */

const nextSearch = OCA.NextSearch.api;


var elements = {
	searchTimeout: null,
	search_input: null,
	search_submit: null,
	search_json: null,
	divHeader: null
};

const Navigate = function () {
	this.init();
};

Navigate.prototype = {

	init: function () {
		var self = this;

		nextSearch.setEntryTemplateId($('#template_entry'), self);
		nextSearch.setResultContainerId($('#search_result'));

		elements.search_input = $('#search_input');
		elements.search_submit = $('#search_submit');
		elements.search_json = $('#search_json');
		elements.divHeader = $('#search_header');

		elements.search_input.on('input', function () {
			self.resetSearch();
			if (elements.searchTimeout === null && self.initSearch(false)) {
				elements.searchTimeout = _.delay(function () {
					self.initSearch(false);
					elements.searchTimeout = null;
				}, 3000);
			}
		});

		$(document).keypress(function (e) {
			if (e.which === 13) {
				self.initSearch(true);
			}
		});
	},


	initSearch: function (force) {
		var search = elements.search_input.val();

		if (!force && search.length < 3) {
			return false;
		}
		var request = {
			providers: 'all',
			search: search,
			page: curr.page
		};

		nextSearch.search(request, this.searchResult);

		return true;
	},


	resetSearch: function () {
		// if (elements.search_input.val() !== '') {
		// 	return;
		// }
	},


	searchResult: function (result) {

		if (elements.search_json !== null) {
			elements.search_json.text(JSON.stringify(result));
		}

		// console.log(JSON.stringify(result));
//			OCA.notification.onFail('Search returned no result');
//		OCA.notification.onSuccess('Search returned ' + res.meta.size + ' result(s)');

	},


	onEntryGenerated: function (entry) {
		this.deleteEmptyDiv(entry, '#line1');
		this.deleteEmptyDiv(entry, '#line2');
	},


	deleteEmptyDiv: function (entry, divId) {
		var div = entry.find(divId);
		if (div.text() === '') {
			div.remove();
		}
	}
};

OCA.NextSearch.Example = Navigate;


$(document).ready(function () {
	OCA.NextSearch.example = new Navigate();
});



