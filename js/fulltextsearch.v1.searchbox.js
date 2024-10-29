/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
	searchInput: null,
	searchOptions: null,
	searchTemplate: null,
	searchError: null,
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

		api.retrieveOptions(settings.searchProviderId);

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
		if (OCA.Theming === undefined || !OCA.Theming.inverted) {
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

		box_elements.searchInput = $('<input>', {
			id: 'fts-input',
			placeholder: t('fulltextsearch', 'Search') + ' ' + settings.searchProviderName
		}).on('keyup', searchbox.timedSearch);
		box_elements.searchOptions = $('<div>', {id: 'fts-options'});
		box_elements.searchTemplate = $('<div>', {id: 'fts-options-template'});
		box_elements.searchError = $('<div>', {id: 'fts-error'});

		var divHeader = $('<div>', {id: 'fts-header'});
		divHeader.append($('<div>').append(box_elements.searchInput));

		popup.append(divHeader);
		popup.append(box_elements.searchOptions);
		popup.append(box_elements.searchTemplate);
		popup.append(box_elements.searchError.hide());

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
		box_elements.searchError.hide();
		box_elements.searchInput.focus();
	},


	searching: function () {
		if (curr.lastRequestTimer !== null) {
			window.clearTimeout(curr.lastRequestTimer);
			curr.lastRequestTimer = null;
		}

		if (curr.lastSearchTimer !== null) {
			window.clearTimeout(curr.lastSearchTimer);
		}

		var search = box_elements.searchInput.val();
		if (search.length < 1) {
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
	},


	timedSearch: function () {

		if (curr.lastSearchTimer !== null) {
			window.clearTimeout(curr.lastSearchTimer);
		}

		curr.lastSearchTimer = window.setTimeout(function () {
			searchbox.searching();
		}, settings.searchEntryTimer);

		if (curr.lastRequestTimer === null) {
			curr.lastRequestTimer = window.setTimeout(function () {
				searchbox.searching();
			}, settings.searchRequestTimer);
		}
	},


	onOptionsLoaded: function (result) {
		if (!result[settings.searchProviderId]) {
			return;
		}

		if (result[settings.searchProviderId]['options']) {
			searchbox.generateOptionsHtml(result[settings.searchProviderId]['options']);
			box_elements.searchOptions.find('INPUT').each(function () {
				searchbox.eventOnOptionsLoadedInput($(this));
			});
		}

		if (result[settings.searchProviderId]['template']) {
			box_elements.searchTemplate.html(result[settings.searchProviderId]['template']);
			box_elements.searchTemplate.find('INPUT').each(function () {
				searchbox.eventOnOptionsLoadedInput($(this))
			});
		}
	},


	eventOnOptionsLoadedInput: function (div) {
		div.on('change keyup', function () {
//			console.log('eventOnOptionsLoadedInput');

			searchbox.searching();
		});
	},


	generateOptionsHtml: function (options) {
		var div = $('<div>', {class: 'div-table'});

		for (var j = 0; j < options.length; j++) {
			var sub = options[j];
			searchbox.displayPanelCheckbox(div, sub);
			searchbox.displayPanelInput(div, sub);
		}

		box_elements.searchOptions.append(div);
	},


	displayPanelOptionTitle: function (sub) {
		var subDiv = $('<div>', {
			class: 'div-table-row'
		});

		subDiv.append($('<div>',
			{
				class: 'div-table-col div-table-col-left'
			}).append($('<span>', {
			class: 'leftcol',
			text: sub.title
		})));

		subDiv.append($('<div>',
			{class: 'div-table-col div-table-col-right'}));

		return subDiv;
	},


	displayPanelCheckbox: function (div, sub) {
		if (sub.type !== 'checkbox') {
			return;
		}

		var subDiv = searchbox.displayPanelOptionTitle(sub);
		var subDivInput = $('<input>', {
			type: 'checkbox',
			id: sub.name,
			value: 1
		});
		subDiv.find('.div-table-col-right').append(subDivInput);
		div.append(subDiv);
	},


	displayPanelInput: function (div, sub) {
		if (sub.type !== 'input') {
			return;
		}

		var subDiv = searchbox.displayPanelOptionTitle(sub);
		var subDivInput = $('<input>', {
			class: 'fts_options_input fts_options_input_' + sub.size,
			type: 'text',
			placeholder: sub.placeholder,
			id: sub.name
		});
		subDiv.find('.div-table-col-right').append(subDivInput);
		div.append(subDiv);
	},


	getSearchOptions: function () {
		var options = {};

		if (box_elements.searchTemplate === null) {
			return options;
		}

		box_elements.searchOptions.find('INPUT').each(function () {
			searchbox.getSearchOptionsFromInput($(this), options);
		});
		box_elements.searchTemplate.find('INPUT').each(function () {
			searchbox.getSearchOptionsFromInput($(this), options);
		});

		return options;
	},


	getSearchOptionsFromInput: function (div, options) {
		var value = div.val();

		if (div.attr('type') === 'checkbox' && !div.is(':checked')) {
			value = '';
		}

		options[div.attr('id')] = value;
	}


};


