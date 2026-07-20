/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { createAppConfig } from '@nextcloud/vite-config'

export default createAppConfig({
	'settings-admin': 'src/settings-admin.ts',
}, {
	// Setup REUSE information extraction
	extractLicenseInformation: {
		// Also create .license files for source maps
		includeSourceMaps: true,
	},
	thirdPartyLicense: false,
	// js/ and css/ also hold hand-written legacy runtime assets
	emptyOutputDirectory: false,
	// Make sure we have one cache-able CSS entry point per JS entry
	createEmptyCSSEntryPoints: true,
	// Enable CSS code splitting to create correct CSS files per JS entry
	config: {
		build: {
			cssCodeSplit: true,
		},
	},
})
