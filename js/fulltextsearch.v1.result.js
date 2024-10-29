/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/** global: OCA */
/** global: settings */
/** global: curr */
/** global: nav */

/** @namespace result.provider */
/** @namespace result.documents */

var result = {

	displayResult: function (res) {
		box_elements.searchError.hide();
		if (settings.resultContainer === null) {
			return;
		}

		var searchResult = res.result;
		if (searchResult.length === 0) {
			// result.displayNoResult();
			return;
		}

		settings.options = res.request.options;
		for (var i = 0; i < searchResult.length; i++) {
			result.displayProviderResult(res.request, searchResult[i]);
		}
	},


	displayError: function (res) {
		box_elements.searchError.text(res.message).show();
	},


	displayProviderResult: function (request, result) {

		// settings.divNoResult.fadeTo(settings.delay_result, 0);

		var current = curr.getProviderResult(result.provider.id);
		var divProvider = nav.getDivProvider(result.provider.id, result.provider.name);
		nav.manageDivProviderNavigation(divProvider.children('.provider_navigation'), request,
			result.meta);
		nav.manageDivProviderResult(divProvider.find('.provider_result'), result.documents,
			current.documents);

		divProvider.slideDown(settings.delay_provider, function () {
			$(this).fadeTo(settings.delay_provider, 1);
		});

		curr.setProviderResult(result.provider.id, result);
	},


	recalibrateResult: function (oldResult, newResult) {
		var tmpResult = [];
		for (var i = 0; i < oldResult.length; i++) {
			if (result.getResultIndex(oldResult[i].id, newResult) > -1) {
				tmpResult.push(oldResult[i]);
			}
		}

		return tmpResult;
	},


	getResultIndex: function (id, result) {
		if (!result) {
			return -1;
		}

		for (var i = 0; i < result.length; i++) {
			if (result[i].id === id) {
				return i;
			}
		}

		return -1;
	}


};
