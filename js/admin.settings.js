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
 */

/** global: OC */
/** global: fns_admin_elements */

/** @namespace result.index_chunk */
/** @namespace result.platforms_all */
/** @namespace result.search_platform */
/** @namespace result.providers_all */




var fns_admin_settings = {

	config: null,

	refreshSettingPage: function () {

		$.ajax({
			method: 'GET',
			url: OC.generateUrl('/apps/fulltextsearch/admin/settings')
		}).done(function (res) {
			fns_admin_settings.updateSettingPage(res);
		});

	},


	updateSettingPage: function (result) {

		fns_admin_settings.updateSettingPagePlatforms(result);
		fns_admin_settings.updateSettingPageChunkSize(result);

		fns_admin_settings.updateCurrentPlatform(result);
		fns_admin_settings.updateEnabledProviders(result);

		fns_admin_settings.tagSettingsAsSaved(fns_admin_elements.fns_div);
	},


	updateSettingPagePlatforms: function (result) {
		fns_admin_elements.fns_platforms.empty();
		fns_admin_elements.fns_platforms.append($('<option>', {
			value: '',
			text: ''
		}));

		var platforms = result.platforms_all;
		var classes = Object.keys(platforms);
		for (var i = 0; i < classes.length; i++) {
			var platformClass = classes[i];
			fns_admin_elements.fns_platforms.append($('<option>', {
				value: platformClass,
				selected: (result.search_platform === platformClass),
				text: platforms[platformClass].name
			}));
			$('#' + platforms[platformClass].id).fadeTo(300, 0, function () {
				$(this).hide();
			});
		}

		fns_admin_elements.fns_platforms.fadeTo(300, 1);
	},


	updateSettingPageChunkSize: function (result) {
		fns_admin_elements.fns_chunkSize.val(result.index_chunk);
		fns_admin_elements.fns_chunkSize.fadeTo(300, 1);
	},


	updateCurrentPlatform: function (result) {
		if (result.search_platform.length === 0) {
			return;
		}

		$('#' + result.platforms_all[result.search_platform].id).stop().show().fadeTo(300, 1);
	},


	updateEnabledProviders: function (result) {

		var providers = result.providers_all;
		var providerIds = Object.keys(providers);
		for (var i = 0; i < providerIds.length; i++) {
			$('#' + providerIds[i]).stop().fadeTo(300, 0);
		}

		// we only check that a search_platform is valid. we don't manage a list of enabled provider as
		// of right now
		if (result.search_platform.length === 0) {
			return;
		}

		for (i = 0; i < providerIds.length; i++) {
			$('#' + providerIds[i]).stop().fadeTo(300, 1);
		}
	},


	tagSettingsAsNotSaved: function (div) {
		div.animate({
			'backgroundColor': 'rgba(255, 180, 0, 0.18)'
		}, 300);
	},


	tagSettingsAsSaved: function (div) {
		div.find('INPUT').animate({'backgroundColor': 'rgba(255, 255, 255, 0.18)'}, 300);
		div.find('SELECT').animate({'backgroundColor': '#fff'}, 300);
	},


	saveSettings: function () {

		var data = {
			search_platform: fns_admin_elements.fns_platforms.val(),
			index_chunk: fns_admin_elements.fns_chunkSize.val()
		};

		$.ajax({
			method: 'POST',
			url: OC.generateUrl('/apps/fulltextsearch/admin/settings'),
			data: {
				data: data
			}
		}).done(function (res) {
			fns_admin_settings.updateSettingPage(res);
		});

	}


};
