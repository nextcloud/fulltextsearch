/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


/** global: OC */
/** global: settings */
/** global: result */
/** global: search */
/** global: nav */


var api = {


	search: function (request, callback, callbackError) {
		var res = {
			status: -1,
			error: 'Failed to reach server. Try reloading the page'
		};

		nav.onSearchRequest(request);

		$.ajax({
			method: 'GET',
			url: OC.generateUrl('/apps/fulltextsearch/v1/search'),
			data: {
				request: JSON.stringify(request)
			}
		}).done(function (res) {
			if (res.status === -1) {
				result.displayError(res);
				nav.onError(res.message);
				api.onCallback(callbackError, res);
				return;
			}

			result.displayResult(res);
			nav.onResultDisplayed(res);
			api.onCallback(callback, res);
		}).fail(function () {
			if (!api.onCallback(callbackError, res)) {
				nav.onError(res.message);
			}
		});
	},


	retrieveOptions: function (providerId, callback) {
		var res = {status: -1};

		$.ajax({
			method: 'GET',
			url: OC.generateUrl('/apps/fulltextsearch/options/' + providerId)
		}).done(function (res) {
			searchbox.onOptionsLoaded(res);
			api.onCallback(callback, res);
		}).fail(function () {
			nav.failedToAjax();
			api.onCallback(callback, res);
		});
	},


	onCallback: function (callback, result) {
		if (callback && (typeof callback === 'function')) {
			if (typeof result === 'object') {
				callback(result);
			} else {
				callback({status: -1});
			}

			return true;
		}

		return false;
	}

};
