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
/** global: _ */

const fullTextSearch = OCA.FullTextSearch.api;


var elements = {
	searchTimeout: null,
	search_input: null,
	search_submit: null,
	search_result: null,
	search_json: null
};

const Navigate = function () {
	this.init();
};

Navigate.prototype = {

	init: function () {
		var self = this;

		elements.search_input = $('#search_input');
		elements.search_submit = $('#search_submit');
		elements.search_result = $('#search_result');
		elements.search_panels = $('#search_navigation');
//		elements.search_json = $('#search_json');
		elements.divHeader = $('#search_header');

		fullTextSearch.setEntryTemplate($('#template_entry'), self);
		fullTextSearch.setResultContainer(elements.search_result);

		elements.search_input.on('input', function () {
			self.resetSearch();
			if (elements.searchTimeout === null && self.initSearch()) {
				elements.searchTimeout = _.delay(function () {
					self.initSearch();
					elements.searchTimeout = null;
				}, 3000);
			}
		});

		//
		// $(document).keypress(function (e) {
		// 	if (e.which === 13) {
		// 		self.initSearch(true);
		// 	}
		// });

		self.initPanels();
	},


	initPanels: function () {
		var self = this;

		$.ajax({
			method: 'GET',
			url: OC.generateUrl('/apps/fulltextsearch/navigation/panels')
		}).done(function (res) {
			self.displayPanels(res);
		});
	},


	displayPanels: function (data) {
		var self = this;

		var ak = Object.keys(data);
		for (var i = 0; i < ak.length; i++) {
			var title = data[ak[i]]['title'];
			var nav = data[ak[i]]['navigation'];

			var li = $('<li>', {class: (nav.options !== undefined) ? 'collapsible open' : ''});
			var aIcon = $('<a>', {
				href: '#',
				class: 'search_icon'
			});
			aIcon.text(title);

			var ul = $('<ul>');
			if (nav.options !== undefined) {

				aIcon.on('click', function () {
					var li = $(this).closest('li');
					if (li.hasClass('open')) {
						li.removeClass('open');
					} else {
						li.addClass('open');
					}
				});

				for (var j = 0; j < nav.options.length; j++) {
					var sub = nav.options[j];

					var subA = $('<a>', {
						href: '#',
						class: 'ulsub',
						text: sub.title
					});

					var subAInput;
					if (sub.type === 'checkbox') {
						subAInput = $('<input>', {
							class: 'search_checkbox_sub',
							type: 'checkbox',
							'data-option': sub.name
						});
						subAInput.change(function () {
							self.initSearch();
						});
						ul.append($('<li>').append(subA).append(subAInput));
					}
				}
			}

			li.append(aIcon);
			var aInput = $('<input>', {
				class: 'search_checkbox',
				type: 'checkbox',
				'data-provider': ak[i]
			});
			aInput.change(function () {
				self.initSearch();
			});

			li.append(aInput);
			li.append(ul);

			elements.search_panels.append(li);
		}

	},


	getProviders: function () {
		var providers = [];
		elements.search_panels.find('input').each(function () {
			if ($(this).hasClass('search_checkbox') && $(this).is(":checked")) {
				providers.push($(this).attr('data-provider'));
			}
		});

		if (providers.length === 0) {
			return 'all';
		}

		return providers;
	},


	getOptions: function () {
		var options = {};
		elements.search_panels.find('input').each(function () {
			if ($(this).hasClass('search_checkbox_sub')) {
				options[$(this).attr('data-option')] = (($(this).is(':checked')) ? '1' : '0');
			}
		});

		return options;
	},


	initSearch: function () {
		var search = elements.search_input.val();

		if (search.length < 3) {
			return false;
		}

		var providers = this.getProviders();
		var options = this.getOptions();

		this.displayProviderResults(providers);

		var request = {
			providers: providers,
			options: options,
			search: search,
			page: curr.page
		};

		fullTextSearch.search(request, this.searchResult);
		return true;
	},


	displayProviderResults: function (providers) {
		elements.search_result.children('DIV.provider_header').each(function () {
			if (providers === 'all' || providers.indexOf($(this).attr('data-id')) > -1) {
				$(this).stop().slideDown(100).fadeTo(settings.delay_provider, 1);
			} else {
				$(this).stop().fadeTo(settings.delay_provider, 0).slideUp(100);
			}
		});
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

OCA.FullTextSearch.Example = Navigate;


$(document).ready(function () {
	OCA.FullTextSearch.example = new Navigate();
});



