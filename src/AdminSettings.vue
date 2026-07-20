<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcSettingsSection
		:name="t('fulltextsearch', 'General')"
		:description="t('fulltextsearch', 'Please check the wiki for documentation related to the installation and the configuration of the full text search within your Nextcloud.')"
		docUrl="https://github.com/nextcloud/fulltextsearch/wiki">
		<NcFormBox>
			<NcSelect
				:modelValue="selectedPlatform"
				:inputLabel="t('fulltextsearch', 'Search Platform')"
				:options="platformOptions"
				label="label"
				@update:modelValue="onPlatformChange" />

			<NcFormBoxSwitch
				:modelValue="config.app_navigation"
				:label="t('fulltextsearch', 'Navigation Icon')"
				:description="t('fulltextsearch', 'Enable global search within all your content.')"
				@update:modelValue="saveSettings({ app_navigation: $event })" />
		</NcFormBox>
	</NcSettingsSection>
</template>

<script setup lang="ts">
import type { IAdminSettingsConfig } from './types.d.ts'

import axios from '@nextcloud/axios'
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import { computed, ref } from 'vue'
import NcFormBox from '@nextcloud/vue/components/NcFormBox'
import NcFormBoxSwitch from '@nextcloud/vue/components/NcFormBoxSwitch'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'
import { SETTINGS_UPDATED_EVENT } from './constants.ts'
import { logger } from './logger.ts'

interface IPlatformOption {
	id: string
	label: string
}

const config = ref(loadState<IAdminSettingsConfig>('fulltextsearch', 'adminConfig'))

const platformOptions = computed<IPlatformOption[]>(() => Object.entries(config.value.platforms_all)
	.map(([className, platform]) => ({ id: className, label: platform.name })))

const selectedPlatform = computed<IPlatformOption | null>(() => platformOptions.value
	.find((option) => option.id === config.value.search_platform) ?? null)

/**
 * Let platform/provider apps know which platform is selected, so they can show or hide their own
 * admin settings section. See SETTINGS_UPDATED_EVENT for why this is both an event and a global.
 */
function broadcastSettings(): void {
	const platform = config.value.search_platform
		? (config.value.platforms_all[config.value.search_platform]?.id ?? '')
		: ''
	const providers = config.value.search_platform ? Object.keys(config.value.providers_all) : []
	const detail = { platform, providers }

	window.OCA = window.OCA || {}
	window.OCA.FullTextSearch = { ...window.OCA.FullTextSearch, settings: detail }
	window.dispatchEvent(new CustomEvent(SETTINGS_UPDATED_EVENT, { detail }))
}

/**
 * Persist a settings change on the backend, then refresh derived state and notify listeners.
 *
 * @param patch Partial config values to change before saving.
 */
async function saveSettings(patch: Partial<Pick<IAdminSettingsConfig, 'app_navigation' | 'search_platform'>>): Promise<void> {
	Object.assign(config.value, patch)

	try {
		const { data } = await axios.post<IAdminSettingsConfig>(generateUrl('/apps/fulltextsearch/admin/settings'), {
			data: {
				app_navigation: config.value.app_navigation ? 1 : 0,
				search_platform: config.value.search_platform,
			},
		})
		config.value = data
	} catch (error) {
		logger.error('Failed to save FullTextSearch settings', { error })
	}

	broadcastSettings()
}

/**
 * Handle a platform selection change from the dropdown.
 *
 * @param option The newly selected platform option, or null if cleared.
 */
function onPlatformChange(option: IPlatformOption | null): void {
	saveSettings({ search_platform: option?.id ?? '' })
}

broadcastSettings()
</script>
