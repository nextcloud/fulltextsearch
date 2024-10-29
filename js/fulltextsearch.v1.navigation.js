/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/** global: OCA */
/** global: api */
/** global: search */
/** global: result */
/** global: settings */



var curr = {
	providerResult: [],
	page: 1,
	lastRequest: '',
	lastRequestTimer: null,
	lastSearchTimer: null,

	setProviderResult: function (id, value) {
		curr.providerResult[id] = value;
	},

	getProviderResult: function (id) {
		var current = curr.providerResult[id];
		if (!current) {
			current = [];
		}

		return current;
	}

};


var nav = {
		manageDivProviderNavigation: function (divProviderNavigation, request, meta) {

			var maxPage = Math.ceil(meta.total / request.size);

			divProviderNavigation.attr('data-time', meta.time);
			divProviderNavigation.attr('data-page', request.page);
			divProviderNavigation.attr('data-options', JSON.stringify(request.options));
			divProviderNavigation.attr('data-search', request.search);
			divProviderNavigation.attr('data-empty-search', request.empty_search);
			divProviderNavigation.attr('data-max-page', maxPage);
			divProviderNavigation.attr('data-size', request.size);
			divProviderNavigation.attr('data-total', meta.total);

			var providerTitle = divProviderNavigation.attr('data-provider-title');
			var left = '';
			var data =
				{
					title: providerTitle,
					search: request.search,
					total: meta.total,
					time: meta.time
				};

			if (request.search === ':null' || request.search === '') {
				left = t('fulltextsearch', 'the search returned {total} results in {time} ms', data);
			} else {
				left = t('fulltextsearch',
					"the search in {title} for \"{search}\" returned {total} results in {time} ms", data);
			}

			divProviderNavigation.find('.provider_navigation_left').text(left);

			if (maxPage > 1) {
				divProviderNavigation.find('.provider_navigation_curr').text(request.page + ' / ' +
					maxPage).stop().fadeTo(200, 1);

				divProviderNavigation.find('.provider_navigation_prev').stop().fadeTo(200,
					(request.page > 1) ? 1 : 0);
				divProviderNavigation.find('.provider_navigation_next').stop().fadeTo(200,
					(request.page < maxPage) ? 1 : 0);
			} else {
				divProviderNavigation.find('.provider_navigation_prev').stop().fadeTo(200, 0);
				divProviderNavigation.find('.provider_navigation_curr').stop().fadeTo(200, 0);
				divProviderNavigation.find('.provider_navigation_next').stop().fadeTo(200, 0);
			}
		},


		manageDivProviderResult: function (divProviderResult, newResult, oldResult) {
			//replaceWith();
			nav.divProviderResultAddItems(divProviderResult, newResult, oldResult);
			if (oldResult) {
				nav.divProviderResultRemoveItems(divProviderResult, newResult, oldResult);
				nav.divProviderResultMoveItems(divProviderResult, newResult, oldResult);
			}
		},


		divProviderResultAddItems: function (divProviderResult, newResult, oldResult) {

			var precItem = null;
			for (var i = 0; i < newResult.length; i++) {
				var entry = newResult[i];
				if (result.getResultIndex(entry.id, oldResult) > -1) {
					precItem = nav.getDivEntry(entry.id, divProviderResult);
					nav.fillDivEntry(precItem, entry);
					continue;
				}

				var divResult = nav.generateDivEntry(entry, nav.generateTemplateEntry(entry));
				if (precItem === null) {
					divProviderResult.prepend(divResult);
				} else {
					precItem.after(divResult);
				}

				divResult.slideDown(settings.delay_result, function () {
					$(this).children('.result_template').fadeTo(settings.delay_result, 1);
				});

				precItem = divResult;
			}

		},


		divProviderResultRemoveItems: function (divProviderResult, newResult, oldResult) {
			for (var i = 0; i < oldResult.length; i++) {
				var entry = oldResult[i];
				if (result.getResultIndex(entry.id, newResult) === -1) {
					var divResult = nav.getDivEntry(entry.id, divProviderResult);
					divResult.fadeTo(settings.delay_result, 0, function () {
						$(this).slideUp(settings.delay_result, function () {
							$(this).remove();
						});
					});
				}
			}
		},


		divProviderResultMoveItems: function (divProviderResult, newResult, oldResult) {

			var precId = '';

			oldResult = result.recalibrateResult(oldResult, newResult);
			newResult = result.recalibrateResult(newResult, oldResult);
			for (var i = 0; i < newResult.length; i++) {
				var entry = newResult[i];

				var pos = result.getResultIndex(entry.id, oldResult);
				if (pos > -1 && pos !== i) {
					nav.animateMoveDivResult(entry.id, divProviderResult, precId);
				}

				precId = newResult[i].id;
			}
		},


		animateMoveDivResult: function (entryId, divProviderResult, precId) {

			var divResult = nav.getDivEntry(entryId, divProviderResult);

			if (precId === '') {
				divResult.fadeTo(settings.delay_result, 0.35, function () {
					$(this).prependTo(divProviderResult).fadeTo(100, 1);
				});
			} else {
				var precItem = nav.getDivEntry(precId, divProviderResult);
				divResult.fadeTo(settings.delay_result, 0.35, function () {
					$(this).insertAfter(precItem).fadeTo(100, 1);
				});
			}

		},


		getDivProvider: function (providerId, providerName) {
			var ret = null;
			settings.resultContainer.children('.provider_header').each(function () {
				if ($(this).attr('data-id') === providerId) {
					ret = $(this);
				}
			});

			if (ret === null) {
				ret = nav.generateDivProvider(providerId, providerName);
				settings.resultContainer.append(ret);
			}

			return ret;
		},


		getDivEntry: function (resultId, divProviderResult) {
			var ret = null;

			divProviderResult.children('.result_entry').each(function () {
				if ($(this).attr('data-id') === resultId) {
					ret = $(this);
				}
			});

			return ret;
		},


		fillDivEntry: function (divEntry, entry) {
			divEntry.find('#title').text(entry.title);

			divEntry.find('#info').text('');
			if (settings.options.show_hash === '1') {
				divEntry.find('#info').text(entry.hash);
			}

			nav.fillDivResultExcepts(divEntry, entry);

			if (entry.link !== '') {
				divEntry.off('click').on('click', function (event) {
					if (nav.onEntrySelected($(this), event)) {
						return;
					}

					window.open(entry.link);
				});
				divEntry.find('div').each(function () {
					$(this).css('cursor', 'pointer');
				});
			}

			nav.onEntryGenerated(divEntry, entry);
		},


		/**
		 * @namespace entry.excerpts
		 */
		fillDivResultExcepts: function (divResult, entry) {
			if (entry.excerpts === null) {
				return;
			}

			if (entry.excerpts.length > 0) {
				divResult.find('#extract').text(entry.excerpts[0]['excerpt']);
			} else {
				divResult.find('#extract').text('');
			}
		},


		onEntryGenerated: function (divEntry, entry) {
			if (settings.parentHasMethod('onEntryGenerated', window)) {
				settings.parent.onEntryGenerated(divEntry, entry);
			}
		},


		onEntrySelected: function (divEntry, event) {
			return !!(settings.parentHasMethod('onEntrySelected', window) &&
				settings.parent.onEntrySelected(divEntry, event));
		},


		onSearchRequest: function (data) {
			if (settings.parentHasMethod('onSearchRequest', window)) {
				settings.parent.onSearchRequest(data);
			}
		},


		onSearchReset: function () {
			if (settings.parentHasMethod('onSearchReset', window)) {
				settings.parent.onSearchReset();
			}
		},


		onResultDisplayed: function (data) {
			if (settings.parentHasMethod('onResultDisplayed', window)) {
				settings.parent.onResultDisplayed(data);
			}
		},

		onResultClose: function () {
			if (settings.parentHasMethod('onResultClose', window)) {
				settings.parent.onResultClose();
			}
		},

		onError: function (data) {
			if (settings.parentHasMethod('onError', window)) {
				settings.parent.onError(data);
			}
		},

		// deleteEmptyDiv: function (entry, divId) {
		// 	var div = entry.find(divId);
		// 	if (div.text() === '') {
		// 		div.remove();
		// 	}
		// },


		generateTemplateEntry: function () {
			var divTemplate = settings.entryTemplate;
			if (divTemplate === null) {
				divTemplate = settings.generateDefaultTemplate();
			}

			if (!divTemplate.length) {
				console.log('FullTextSearch Error: entryTemplate is not defined');
				return;
			}

			var tmpl = divTemplate.html();
			// var divNavToggle = $('<div>', {
			// 	id: 'app-navigation-toggle',
			// 	class: 'icon-menu'
			// });
			//
			var div = $('<div>', {class: 'result_template'});
			div.html(tmpl).fadeTo(0);

			return div;
		},


		generateDivEntry: function (entry, divResultContent) {
			var divEntry = $('<div>', {class: 'result_entry'});

			divEntry.hide();
			divEntry.attr('data-id', entry.id);
			divEntry.attr('data-link', entry.link);
			divEntry.attr('data-source', entry.source);
			divEntry.attr('data-info', JSON.stringify(entry.info));
			divEntry.attr('data-result', JSON.stringify(entry));
			divEntry.append(divResultContent);

			nav.fillDivEntry(divEntry, entry);

			return divEntry;
		},


		generateDivProvider: function (providerId, providerName) {

			var divProviderNavigation = $('<div>', {class: 'provider_navigation'});
			divProviderNavigation.attr('data-provider-id', providerId);
			divProviderNavigation.attr('data-provider-title', providerName);

			var divProviderLeftNav = $('<div>', {class: 'provider_navigation_left'});
			if (settings.searchProviderId !== '') {
				var divProviderPaginationClose = $('<div>',
					{class: 'icon-close provider_navigation_close'});
				divProviderPaginationClose.on('click', function () {
					nav.onResultClose();
				});
				divProviderNavigation.append(divProviderPaginationClose);
			}

			divProviderNavigation.append(divProviderLeftNav);


			var divProviderPagination = $('<div>', {class: 'provider_navigation_right'});
			var divProviderPaginationPrev = $('<div>', {class: 'provider_navigation_prev'}).append(
				$('<div>', {class: 'provider_navigation_page'}).text('previous page'));

			divProviderPaginationPrev.on('click', function () {
				var prevPage = Number(divProviderNavigation.attr('data-page')) - 1;
				if (prevPage < 1) {
					return;
				}

				fullTextSearch.search({
					providers: providerId,
					options: JSON.parse(divProviderNavigation.attr('data-options')),
					search: divProviderNavigation.attr('data-search'),
					empty_search: divProviderNavigation.attr('data-empty-search'),
					page: prevPage,
					size: divProviderNavigation.attr('data-size')
				});
			});
			divProviderPagination.append(divProviderPaginationPrev);

			divProviderPagination.append($('<div>', {class: 'provider_navigation_curr'}));

			var divProviderPaginationNext = $('<div>',
				{class: 'provider_navigation_next'}).append(
				$('<div>', {class: 'provider_navigation_page'}).text('next page'));

			divProviderPaginationNext.on('click', function () {
				var nextPage = Number(divProviderNavigation.attr('data-page')) + 1;
				if (nextPage > Number(divProviderNavigation.attr('data-max-page'))) {
					return;
				}

				fullTextSearch.search({
					providers: providerId,
					options: JSON.parse(divProviderNavigation.attr('data-options')),
					search: divProviderNavigation.attr('data-search'),
					empty_search: divProviderNavigation.attr('data-empty-search'),
					page: nextPage,
					size: divProviderNavigation.attr('data-size')
				});
			});
			divProviderPagination.append(divProviderPaginationNext);

			divProviderNavigation.append(divProviderPagination);

			var divProviderResult = $('<div>', {class: 'provider_result'});

			var divProvider = $('<div>', {class: 'provider_header'});
			divProvider.hide();
			divProvider.attr('data-id', providerId);
			divProvider.append(divProviderNavigation);

			if (settings.resultHeader !== null) {
				divProvider.append(settings.resultHeader);
			}

			divProvider.append(divProviderResult);

			if (settings.resultFooter !== null) {
				divProvider.append(settings.resultFooter);
			}

			return divProvider;
		}

	}
;
