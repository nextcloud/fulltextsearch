/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


/** global: OCA */
/** global: _ */

var fullTextSearch = OCA.FullTextSearch.api;


var elements = {
	searchTimeout: null,
	search_input: null,
	search_submit: null,
	search_result: null,
	search_json: null
};

var Navigate = function () {
	this.init();
};

Navigate.prototype = {

	currentTagsResult: {},
	selectedTags: {},

	init: function () {
		var self = this;

		elements.search_input = $('#search_input');
		elements.search_submit = $('#search_submit');
		elements.search_result = $('#search_result');
		elements.search_panels = $('#search_navigation');
//		elements.search_json = $('#search_json');
		elements.divHeader = $('#search_header');
		box_elements.searchError = $('#search_error');

		//	fullTextSearch.setEntryTemplate($('#template_entry'), self);
		fullTextSearch.setResultContainer(elements.search_result);

		elements.search_input.on('input', this.navigateTimedSearch);
		// function () {
		// 	self.resetSearch();
		// 	if (elements.searchTimeout === null && self.initSearch()) {
		// 		elements.searchTimeout = _.delay(function () {
		// 			self.initSearch();
		// 			elements.searchTimeout = null;
		// 		}, 3000);
		// 	}
		// });

		//
		// $(document).keypress(function (e) {
		// 	if (e.which === 13) {
		// 		self.initSearch(true);
		// 	}
		// });

		self.initPanels();
	},


	navigateTimedSearch: function () {

		if (curr.lastSearchTimer !== null) {
			window.clearTimeout(curr.lastSearchTimer);
		}

		curr.lastSearchTimer = window.setTimeout(function () {
			OCA.FullTextSearch.navigate.initSearch();
		}, settings.searchEntryTimer);

		if (curr.lastRequestTimer === null) {
			curr.lastRequestTimer = window.setTimeout(function () {
				OCA.FullTextSearch.navigate.initSearch();
			}, settings.searchRequestTimer);
		}
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
			var providerAppId = ak[i];
			var title = data[ak[i]]['title'];
			var options = data[ak[i]]['options'];
			var css = data[ak[i]]['css'];
			var icon = data[ak[i]]['icon'];
			var providerId = data[ak[i]]['provider'];

			if (css !== '') {
				OC.addStyle(providerAppId, css);
			}

			var li = $('<li>', {class: (nav.options !== undefined) ? 'collapsible open' : ''});
			var aIcon = $('<a>', {
				href: '#',
				class: (icon !== undefined) ? icon : 'search_icon'
			});
			aIcon.addClass('icon-fts').text(title);

			var ul = $('<ul>');
			// if (nav.options !== undefined) {

			aIcon.on('click', function () {
				var li = $(this).closest('li');
				if (li.hasClass('open')) {
					li.removeClass('open');
				} else {
					li.addClass('open');
				}
			});

			for (var j = 0; j < options.length; j++) {
				var sub = options[j];
				self.displayPanelCheckbox(ul, sub);
				self.displayPanelInput(ul, sub);
				self.displayPanelTags(ul, sub);
				self.displayPanelSearch(providerAppId, ul, sub);
				//
				// <p id="tag_filter" class="open">
				// 		<input value="" style="display: none;" type="text">
				// 		<ul class="tagit ui-widget ui-widget-content ui-corner-all">
				// 		<li class="tagit-new">
				// 		<input class="ui-widget-content ui-autocomplete-input"
				// placeholder="Filter by tag" autocomplete="off" type="text"> <span role="status"
				// aria-live="polite" class="ui-helper-hidden-accessible"> 1 result is available,
				// use up and down arrow keys to navigate.</span></li> <li class="tagit-choice
				// ui-widget-content ui-state-default ui-corner-all"> <span
				// class="tagit-label">test</span><a class="close"><span class="text-icon">×</span>
				// <span class="ui-icon ui-icon-close"></span></a></li> <li class="tagit-choice
				// ui-widget-content ui-state-default ui-corner-all"> <span
				// class="tagit-label">perdu</span><a class="close"><span class="text-icon">×</span>
				// <span class="ui-icon ui-icon-close"></span></a></li></ul> </p>


			}
			// }

			li.append(aIcon);
			var aInput = $('<input>', {
				class: 'search_checkbox',
				type: 'checkbox',
				'data-provider': ak[i],
				'data-provider-id': providerId
			});
			aInput.change(function () {
				self.initSearch();
			});

			li.append(aInput);
			li.append(ul);

			elements.search_panels.append(li);
		}

	},


	displayPanelOptionTitle: function (sub) {
		return $('<a>', {
			href: '#',
			class: 'ulsub',
			text: sub.title
		});
	},


	displayPanelCheckbox: function (ul, sub) {

		if (sub.type !== 'checkbox') {
			return;
		}

		var self = this;
		var subA = this.displayPanelOptionTitle(sub);
		var subAInput = $('<input>', {
			class: 'search_checkbox_sub',
			type: 'checkbox',
			'data-option': sub.name
		});
		subAInput.change(function () {
			self.initSearch();
		});

		ul.append($('<li>').append(subA).append(subAInput));
	},


	displayPanelInput: function (ul, sub) {
		if (sub.type !== 'input') {
			return;
		}

		var self = this;
		var subA = this.displayPanelOptionTitle(sub);
		var subAInput = $('<input>', {
			class: 'search_input_sub search_input_sub_' + sub.size,
			type: 'text',
			placeholder: sub.placeholder,
			'data-option': sub.name
		});
		subAInput.on('input', function () {
			self.initSearch();
		});

		ul.append($('<li>').append(subA).append(subAInput));
	},


	displayPanelTags: function (ul, sub) {
		if (sub.type !== 'tags') {
			return;
		}

		var self = this;
		var subAInput = $('<input>', {
			id: sub.name,
			class: 'search_tags_sub',
			type: 'text',
			placeholder: sub.title,
			list: sub.name + '_datalist',
			'data-option': sub.name
		});

		var subADataList = $('<datalist>', {
			id: sub.name + '_datalist'
		});

		sub.list.forEach(function (item) {
			subADataList.append($('<option>', {value: item}));
		});


		/**
		 * <div class="systemTagsInfoView">
		 *       <div class="systemTagsInputFieldContainer">
		 *           <div id="s2id_autogen15" class="select2-container select2-container-multi
		 * systemTagsInputField systemtags-select2-container">
		 *               <ul class="select2-choices">
		 *                   <li class="select2-search-choice">
		 *                       <div>
		 *                           <span class="label">dsfsdfds</span>
		 *                       </div>
		 *                       <a href="#" class="select2-search-choice-close" tabindex="-1"></a>
		 *                   </li>
		 *               </ul>
		 *           </div>
		 *           <input class="systemTagsInputField select2-offscreen" name="tags"
		 * value="5,4,3,1,2,6" tabindex="-1" type="hidden">
		 *       </div>
		 * </div>
		 */
		// subAInput.on('change', function (e) {
		// 	var div = $(this);
		// 	if (e.which === 13 && div.val() !== '') {
		// 		self.selectPanelTags($(this).attr('id'));
		// 	}
		//
		//
		// 	var url = '/apps/' + div.attr('data-provider');
		// 	var route = JSON.parse(div.attr('data-route'));
		//
		// 	route.url = url + route.url;
		// 	self.quickSearch(route, div.val(), function (res) {
		// 		self.resultTagsSearch(div, res);
		// 	});
		// });

		ul.append($('<li>').append(subAInput).append(subADataList));
	},

	displayPanelSearch: function (appId, ul, sub) {
		var self = this;

		if (sub.type !== 'search') {
			return;
		}

		var subAInput = $('<input>', {
			id: sub.name,
			class: 'search_tags_sub',
			type: 'text',
			placeholder: sub.title,
			list: sub.name + '_datalist',
			'data-option': sub.name,
			'data-provider': appId,
			'data-route': JSON.stringify(sub.route)
		});

		var subADataList = $('<datalist>', {
			id: sub.name + '_datalist'
		});


		subAInput.on('keypress', function (e) {
			var div = $(this);
			if (e.which === 13 && div.val() !== '') {
				self.selectPanelTags($(this).attr('id'));
			}


			var url = '/apps/' + div.attr('data-provider');
			var route = JSON.parse(div.attr('data-route'));

			route.url = url + route.url;
			self.quickSearch(route, div.val(), function (res) {
				self.resultTagsSearch(div, res);
			});
		});

		ul.append($('<li>').append(subAInput).append(subADataList));
	},


	// selectPanelTags: function (panelId) {
	// 	if (this.currentTagsResult === undefined) {
	// 		return;
	// 	}
	//
	// 	var tags = this.currentTagsResult[panelId];
	// 	if (tags.length === 0) {
	// 		return;
	// 	}
	//
	// 	if (this.selectedTags[panelId] === undefined)
	// 		this.selectedTags[panelId] = [];
	// 	this.selectedTags[panelId].push(tags[0]);
	//
	// 	console.log('etntree' + JSON.stringify(this.selectedTags[panelId]));
	// },
	//
	//
	// resultTagsSearch: function (div, res) {
	// 	this.currentTagsResult[div.attr('id')] = res;
	// 	var datalistId = div.attr('data-option') + '_datalist';
	// 	var datalist = $('#' + datalistId);
	//
	// 	datalist.empty();
	// 	res.forEach(function (item) {
	// 		datalist.append($('<option>', {value: item}));
	// 	});
	// },


	getProviders: function () {
		var providers = [];
		elements.search_panels.find('input').each(function () {
			if ($(this).hasClass('search_checkbox') && $(this).is(":checked")) {
				providers.push($(this).attr('data-provider-id'));
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

			if ($(this).hasClass('search_input_sub')) {
				options[$(this).attr('data-option')] = $(this).val();
			}
		});

		return options;
	},


	initSearch: function () {
		var search = elements.search_input.val();

		if (search.length < 1) {
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


	quickSearch: function (route, search, callback) {
		$.ajax({
			method: route.verb,
			url: OC.generateUrl(route.url),
			data: {
				search: search
			}
		}).done(function (res) {
			if (_.has(res, 'error')) {
				return;
			}

			callback(res);
		});
	},


	displayProviderResults: function (providers) {
		elements.search_result.children('DIV.provider_header').each(function () {
			if (providers === 'all' || providers.indexOf($(this).attr('data-id')) > -1) {
				$(this).stop().slideDown(100).fadeTo(settings.delay_provider, 1);
			} else if ($(this).css('display') !== 'none') {
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


	onError: function (message) {
		console.log('error while searching: ' + message);
	},


	// onEntryGenerated: function (entry) {
	// 	this.deleteEmptyDiv(entry, '#line1');
	// 	this.deleteEmptyDiv(entry, '#line2');
	// },


	deleteEmptyDiv: function (entry, divId) {
		var div = entry.find(divId);
		if (div.text() === '') {
			div.remove();
		}
	}


};

OCA.FullTextSearch.Navigate = Navigate;


$(document).ready(function () {
	OCA.FullTextSearch.navigate = new Navigate();
});



