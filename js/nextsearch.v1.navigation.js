/*
 * FullNextSearch - Full Text Search your Nextcloud.
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
/** global: api */
/** global: search */
/** global: result */
/** global: settings */


var curr = {
	providerResult: [],


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

		failedToAjax: function () {
			// OCA.notification.onFail(
			// 	'Failed to connect to cloud, page will refresh within few seconds');
			// window.setTimeout(function () {
			// 	window.location.reload(true);
			// }, 4000);
		},


		managerDivProviderResult: function (divProvider, newResult, oldResult) {
			//replaceWith();
			nav.divProviderResultAddItems(divProvider, newResult, oldResult);
			if (oldResult) {
				nav.divProviderResultRemoveItems(divProvider, newResult, oldResult);
				nav.divProviderResultMoveItems(divProvider, newResult, oldResult);
			}
		},


		divProviderResultAddItems: function (divProviderResult, newResult, oldResult) {

			var precItem = null;
			for (var i = 0; i < newResult.length; i++) {
				var entry = newResult[i];
				if (result.getResultIndex(entry.id, oldResult) > -1) {
					precItem = nav.getDivResult(entry.id, divProviderResult);
					continue;
				}

				var divResult = nav.generateDivResult(entry, nav.generateTemplateEntry(entry));
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
					var divResult = nav.getDivResult(entry.id, divProviderResult);
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

			var divResult = nav.getDivResult(entryId, divProviderResult);

			if (precId === '') {
				divResult.fadeTo(settings.delay_result, 0.35, function () {
					$(this).prependTo(divProviderResult).fadeTo(100, 1);
				});
			} else {
				var precItem = nav.getDivResult(precId, divProviderResult);
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


		getDivResult: function (resultId, divProviderResult) {
			var ret = null;
			divProviderResult.children('.result_entry').each(function () {
				if ($(this).attr('data-id') === resultId) {
					ret = $(this);
				}
			});

			return ret;
		},


		fillDivResult: function (divResult, entry) {
			divResult.find('#title').text(entry.title);
			divResult.find('#score').text(entry.score);

			nav.fillDivResultExcepts(divResult, entry);

			if (entry.link !== '') {
				divResult.on('click', function () {
					window.open(entry.link, '_self');
				});
				divResult.find('div').each(function () {
					$(this).css('cursor', 'pointer');
				});
			}
		},


		fillDivResultExcepts: function (divResult, entry) {
			if (entry.excerpts === null) {
				return;
			}

			if (entry.excerpts.length > 0) {
				divResult.find('#line1').text(entry.excerpts[0]);
			}

			if (entry.excerpts.length > 1) {
				divResult.find('#line2').text(entry.excerpts[1]);
			}

		},

		onEntryGenerated: function (divResult) {

			if (settings.parentHasMethod('onEntryGenerated')) {
				settings.parent.onEntryGenerated(divResult);
			}

		},


		generateTemplateEntry: function (document) {
			var divTemplate = settings.entryTemplate;
			if (divTemplate === null) {
				divTemplate = settings.entryTemplateDefault;
			}

			if (!divTemplate.length) {
				console.log('FullNextSearch Error: template_entry is not defined');
				return;
			}

			var tmpl = divTemplate.html();
			tmpl = tmpl.replace(/%%id%%/g, escapeHTML(document.id));

			var div = $('<div>', {class: 'result_template'});
			div.html(tmpl).fadeTo(0);

			return div;
		},


		generateDivResult: function (entry, divResultContent) {
			var divResult = $('<div>', {class: 'result_entry'});
			divResult.hide();
			divResult.attr('data-id', entry.id);
			divResult.attr('data-result', JSON.stringify(entry));
			divResult.append(divResultContent);

			nav.fillDivResult(divResult, entry);
			nav.onEntryGenerated(divResult);

			return divResult;
		},


		generateDivProvider: function (providerId, providerName) {
			var divProviderName = $('<div>', {class: 'provider_name'});
			divProviderName.text(providerName);

			var divProviderResult = $('<div>', {class: 'provider_result'});
			var divProvider = $('<div>', {class: 'provider_header'});
			divProvider.hide();
			divProvider.attr('data-id', providerId);
			divProvider.append(divProviderName);
			divProvider.append(divProviderResult);

			return divProvider;
		}

	}
;
