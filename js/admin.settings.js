/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/** global: OC */
/** global: fts_admin_elements */

/** @namespace result.platforms_all */
/** @namespace result.search_platform */
/** @namespace result.providers_all */




var fts_admin_settings = {

	config: null,

	refreshSettingPage: function () {

		fts_admin_settings.request('GET', '/apps/fulltextsearch/admin/settings').then(function (res) {
			fts_admin_settings.updateSettingPage(res);
		});

	},


	updateSettingPage: function (result) {

		fts_admin_elements.fts_navigation.checked = (result.app_navigation === true);

		fts_admin_settings.updateSettingPagePlatforms(result);

		fts_admin_settings.updateCurrentPlatform(result);
		fts_admin_settings.updateEnabledProviders(result);

		fts_admin_settings.tagSettingsAsSaved(fts_admin_elements.fts_div);
	},


	updateSettingPagePlatforms: function (result) {
		fts_admin_elements.fts_platforms.replaceChildren(new Option('', ''));

		var platforms = result.platforms_all;
		var classes = Object.keys(platforms);
		for (var i = 0; i < classes.length; i++) {
			var platformClass = classes[i];
			var option = new Option(platforms[platformClass].name, platformClass);
			option.selected = (result.search_platform === platformClass);
			fts_admin_elements.fts_platforms.appendChild(option);
			fts_admin_settings.hideElement(document.getElementById(platforms[platformClass].id));
		}

		fts_admin_elements.fts_platforms.style.opacity = '1';
	},


	updateCurrentPlatform: function (result) {
		if (result.search_platform.length === 0) {
			return;
		}

		fts_admin_settings.showElement(document.getElementById(result.platforms_all[result.search_platform].id));
	},


	updateEnabledProviders: function (result) {

		var providers = result.providers_all;
		var providerIds = Object.keys(providers);
		for (var i = 0; i < providerIds.length; i++) {
			fts_admin_settings.fadeElement(document.getElementById(providerIds[i]), 0);
		}

		document.querySelectorAll('.subprovider').forEach(function (element) {
			fts_admin_settings.fadeElement(element, 0);
		});

		// we only check that a search_platform is valid. we don't manage a list of enabled provider as
		// of right now
		if (result.search_platform.length === 0) {
			return;
		}

		for (i = 0; i < providerIds.length; i++) {
			fts_admin_settings.showElement(document.getElementById(providerIds[i]));
		}
	},


	updateEnabledSubProviders: function () {
		document.querySelectorAll('body .subprovider').forEach(function (element) {
			var top = element.id.split('-', 2);

			if (top.length < 2) {
				return;
			}

			var topOption = top[0];
			var topOptionElement = document.getElementById(topOption);
			if (topOptionElement && topOptionElement.checked) {
				fts_admin_settings.showElement(element);
			} else {
				fts_admin_settings.hideElement(element);
			}
		});
	},


	tagSettingsAsNotSaved: function (div) {
		fts_admin_settings.toElements(div).forEach(function (element) {
			element.style.backgroundColor = 'rgba(255, 180, 0, 0.18)';
		});
	},


	tagSettingsAsSaved: function (div) {
		fts_admin_settings.toElements(div).forEach(function (element) {
			element.querySelectorAll('input').forEach(function (input) {
				input.style.backgroundColor = 'rgba(255, 255, 255, 0.18)';
			});
			element.querySelectorAll('select').forEach(function (select) {
				select.style.backgroundColor = '#fff';
			});
		});

		fts_admin_settings.updateEnabledSubProviders();
	},


	saveSettings: function () {

		var data = {
			app_navigation: fts_admin_elements.fts_navigation.checked ? 1 : 0,
			search_platform: fts_admin_elements.fts_platforms.value
		};

		fts_admin_settings.request('POST', '/apps/fulltextsearch/admin/settings', data).then(function (res) {
			fts_admin_settings.updateSettingPage(res);
		});

	},


	request: function (method, route, data) {
		var options = {
			method: method,
			credentials: 'same-origin',
			headers: {
				'Accept': 'application/json',
				'requesttoken': window.OC ? window.OC.requestToken : ''
			}
		};

		if (method === 'POST') {
			options.headers['Content-Type'] = 'application/x-www-form-urlencoded;charset=UTF-8';
			options.body = fts_admin_settings.encodeData(data);
		}

		return fetch(window.OC.generateUrl(route), options).then(function (response) {
			if (!response.ok) {
				throw new Error('Request failed: ' + response.status);
			}

			return response.json();
		});
	},


	encodeData: function (data) {
		var params = new URLSearchParams();
		Object.keys(data).forEach(function (key) {
			params.append('data[' + key + ']', data[key]);
		});

		return params.toString();
	},


	toElements: function (value) {
		if (!value) {
			return [];
		}
		if (value instanceof Element) {
			return [value];
		}
		if (value instanceof NodeList || Array.isArray(value)) {
			return Array.prototype.slice.call(value);
		}

		return [];
	},


	fadeElement: function (element, opacity) {
		if (!element) {
			return;
		}
		element.style.opacity = opacity;
	},


	hideElement: function (element) {
		if (!element) {
			return;
		}
		element.style.opacity = '0';
		element.style.display = 'none';
	},


	showElement: function (element) {
		if (!element) {
			return;
		}
		element.style.display = '';
		element.style.opacity = '1';
	}


};
