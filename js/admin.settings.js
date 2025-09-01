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

		$.ajax({
			method: 'GET',
			url: OC.generateUrl('/apps/fulltextsearch/admin/settings')
		}).done(function (res) {
			fts_admin_settings.updateSettingPage(res);
		});

	},


	updateSettingPage: function (result) {

		fts_admin_elements.fts_navigation.prop('checked', (result.app_navigation === true));

		fts_admin_settings.updateSettingPagePlatforms(result);

		fts_admin_settings.updateCurrentPlatform(result);
		fts_admin_settings.updateEnabledProviders(result);

		fts_admin_settings.tagSettingsAsSaved(fts_admin_elements.fts_div);
	},


	updateSettingPagePlatforms: function (result) {
		fts_admin_elements.fts_platforms.empty();
		fts_admin_elements.fts_platforms.append($('<option>', {
			value: '',
			text: ''
		}));

		var platforms = result.platforms_all;
		var classes = Object.keys(platforms);
		for (var i = 0; i < classes.length; i++) {
			var platformClass = classes[i];
			fts_admin_elements.fts_platforms.append($('<option>', {
				value: platformClass,
				selected: (result.search_platform === platformClass),
				text: platforms[platformClass].name
			}));
			$('#' + platforms[platformClass].id).fadeTo(300, 0, function () {
				$(this).hide();
			});
		}

		fts_admin_elements.fts_platforms.fadeTo(300, 1);
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

		$('.subprovider').stop().fadeTo(300, 0);

		// we only check that a search_platform is valid. we don't manage a list of enabled provider as
		// of right now
		if (result.search_platform.length === 0) {
			return;
		}

		for (i = 0; i < providerIds.length; i++) {
			$('#' + providerIds[i]).stop().fadeTo(300, 1);
		}
	},


	updateEnabledSubProviders: function () {
		$('body').find('.subprovider').each(function () {
			var top = $(this).attr('id').split('-', 2);

			if (top.length < 2) {
				return;
			}

			var topOption = top[0];
			if ($('#' + topOption).is(':checked')) {
				$(this).stop().fadeTo(300, 1).slideDown();
			} else {
				$(this).stop().fadeTo(300, 0).slideUp();
			}
		});
	},


	tagSettingsAsNotSaved: function (div) {
		div.animate({
			'backgroundColor': 'rgba(255, 180, 0, 0.18)'
		}, 300);
	},


	tagSettingsAsSaved: function (div) {
		div.find('INPUT').animate({'backgroundColor': 'rgba(255, 255, 255, 0.18)'}, 300);
		div.find('SELECT').animate({'backgroundColor': '#fff'}, 300);

		fts_admin_settings.updateEnabledSubProviders();
	},


	saveSettings: function () {

		var data = {
			app_navigation: (fts_admin_elements.fts_navigation.is(':checked')) ? 1 : 0,
			search_platform: fts_admin_elements.fts_platforms.val()
		};

		$.ajax({
			method: 'POST',
			url: OC.generateUrl('/apps/fulltextsearch/admin/settings'),
			data: {
				data: data
			}
		}).done(function (res) {
			fts_admin_settings.updateSettingPage(res);
		});

	}


};
