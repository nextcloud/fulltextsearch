<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcContent appName="fulltextsearch">
		<NcAppNavigation :ariaLabel="t('fulltextsearch', 'Search filters')">
			<template #search>
				<NcAppNavigationSearch
					v-model="search"
					:label="t('fulltextsearch', 'Search on {theme}', { theme: themingName })"
					@update:modelValue="triggerSearch()"/>
			</template>

			<ul class="provider-filters">
				<li v-for="(panel, appId) in panels" :key="appId" class="provider-filters__provider">
					<NcCheckboxRadioSwitch
						type="switch"
						v-model="providerEnabled[appId]"
						style="width: 100%"
						@update:modelValue="onProviderEnabledChange(appId, $event)">
						{{ panel.title }}
					</NcCheckboxRadioSwitch>

					<ul v-if="panel.options.length > 0" class="provider-filters__options">
						<li v-for="option in panel.options" :key="option.name">
							<NcCheckboxRadioSwitch
								v-if="option.type === 'checkbox'"
								type="checkbox"
								:modelValue="isOptionChecked(appId, option.name)"
								:placeholder="option.placeholder"
								style="width: 100%"
								@update:modelValue="onOptionChange(appId, option.name, $event ? '1' : '0')">
								{{ option.title }}
							</NcCheckboxRadioSwitch>
							<NcTextField
								v-else-if="option.type === 'input'"
								:modelValue="getOptionValue(appId, option.name)"
								:label="option.title"
								:placeholder="option.placeholder"
								style="width: 100%"
								@update:modelValue="onOptionChange(appId, option.name, String($event))" />
						</li>
					</ul>
				</li>
			</ul>
		</NcAppNavigation>

		<NcAppContent>
			<NcEmptyContent v-if="error" :name="t('fulltextsearch', 'Error')" :description="error" />

			<NcEmptyContent v-else-if="!hasSearched" :name="t('fulltextsearch', 'Search for content')" />

			<NcEmptyContent v-else-if="loading && results.length === 0" :name="t('fulltextsearch', 'Searching …')">
				<template #icon>
					<NcLoadingIcon :size="64" />
				</template>
			</NcEmptyContent>

			<NcEmptyContent v-else-if="totalResultsCount === 0" :name="t('fulltextsearch', 'No results')" />

			<div v-else class="search-results">
				<section v-for="providerResult in results" :key="providerResult.provider.id" class="search-results__provider">
					<h3>
						{{ providerResultTitle(providerResult) }}
					</h3>

					<ul>
						<NcListItem
							v-for="doc in providerResult.documents"
							:key="doc.id"
							:name="doc.title"
							:href="doc.link || undefined"
							target="_blank"
							:details="doc.source">
							<template #subname>
								{{ firstExcerpt(doc) }}
							</template>
						</NcListItem>
					</ul>

					<div v-if="providerResult.meta.total > pageSize" class="search-results__pagination">
						<NcButton
							variant="tertiary"
							:disabled="(providerPage[providerResult.provider.id] ?? 1) <= 1"
							@click="changeProviderPage(providerResult, -1)">
							{{ t('fulltextsearch', 'Previous page') }}
						</NcButton>
						<span>{{ providerPage[providerResult.provider.id] ?? 1 }} / {{ maxPage(providerResult) }}</span>
						<NcButton
							variant="tertiary"
							:disabled="(providerPage[providerResult.provider.id] ?? 1) >= maxPage(providerResult)"
							@click="changeProviderPage(providerResult, 1)">
							{{ t('fulltextsearch', 'Next page') }}
						</NcButton>
					</div>
				</section>
			</div>
		</NcAppContent>
	</NcContent>
</template>

<script setup lang="ts">
import type { INavigationPanels, ISearchDocument, ISearchErrorResponse, ISearchResponse, ISearchResult } from './types.d.ts'

import axios from '@nextcloud/axios'
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import { computed, onMounted, reactive, ref } from 'vue'
import NcAppContent from '@nextcloud/vue/components/NcAppContent'
import NcAppNavigation from '@nextcloud/vue/components/NcAppNavigation'
import NcAppNavigationSearch from '@nextcloud/vue/components/NcAppNavigationSearch'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcContent from '@nextcloud/vue/components/NcContent'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import NcListItem from '@nextcloud/vue/components/NcListItem'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import { logger } from './logger.ts'

/** How many documents the server returns for a single provider on a single page. */
const pageSize = 10
/** Debounce, in ms, before a keystroke in the search field triggers a new search. */
const searchDebounce = 500

const themingName = loadState<string>('fulltextsearch', 'themingName')

const panels = ref<INavigationPanels>({})
const providerEnabled = reactive<Record<string, boolean>>({})
const providerOptions = reactive<Record<string, Record<string, string>>>({})

const search = ref('')
const results = ref<ISearchResult[]>([])
const providerPage = reactive<Record<string, number>>({})
const loading = ref(false)
const hasSearched = ref(false)
const error = ref('')

let searchTimer: ReturnType<typeof window.setTimeout> | null = null

const totalResultsCount = computed(() => results.value.reduce((total, providerResult) => total + providerResult.documents.length, 0))

/**
 * Load the list of Content Providers and the filter options they want displayed in this
 * navigation page, and initialize their default filter values.
 */
async function loadPanels(): Promise<void> {
	const { data } = await axios.get<INavigationPanels>(generateUrl('/apps/fulltextsearch/navigation/panels'))
	panels.value = data

	for (const [appId, panel] of Object.entries(data)) {
		providerEnabled[appId] = true
		providerOptions[appId] = {}

		for (const option of panel.options) {
			providerOptions[appId][option.name] = option.type === 'checkbox' ? '0' : ''
		}
	}
}

/**
 * @param appId app id of the Content Provider, as returned by `GET /navigation/panels`
 * @param optionName name/key of the option to read
 */
function getOptionValue(appId: string, optionName: string): string {
	return providerOptions[appId]?.[optionName] ?? ''
}

/**
 * @param appId app id of the Content Provider, as returned by `GET /navigation/panels`
 * @param optionName name/key of the option to read
 */
function isOptionChecked(appId: string, optionName: string): boolean {
	return getOptionValue(appId, optionName) === '1'
}

/**
 * @param appId app id of the Content Provider whose enabled state changed
 * @param enabled whether the provider should now be included in searches
 */
function onProviderEnabledChange(appId: string, enabled: boolean): void {
	providerEnabled[appId] = enabled
	triggerSearch(true)
}

/**
 * @param appId app id of the Content Provider the option belongs to
 * @param optionName name/key of the option, as sent back to the search request
 * @param value new value, '1'/'0' for checkboxes or free text for inputs
 */
function onOptionChange(appId: string, optionName: string, value: string): void {
	providerOptions[appId][optionName] = value
	triggerSearch(true)
}

/**
 * @returns the `provider` id (not app id) of every enabled Content Provider, or 'all' if none was
 * explicitly disabled - matching what `Api#search` expects.
 */
function selectedProviders(): string[] | 'all' {
	const entries = Object.entries(panels.value)
	const selected = entries.filter(([appId]) => providerEnabled[appId]).map(([, panel]) => panel.provider)

	return selected.length === entries.length ? 'all' : selected
}

/** @returns the merged option values of every enabled Content Provider, as the search API expects them. */
function selectedOptions(): Record<string, string> {
	return Object.entries(panels.value)
		.filter(([appId]) => providerEnabled[appId])
		.reduce((options, [appId]) => ({ ...options, ...providerOptions[appId] }), {})
}

/**
 * Run a search request, either across every enabled provider (`providerId` omitted) or scoped to
 * a single one when paginating its results.
 *
 * @param providerId `provider` id (not app id) to scope the request to, or undefined for all enabled providers
 * @param page page number to request, 1-indexed
 */
async function runSearch(providerId: string | undefined, page: number): Promise<ISearchResult[]> {
	const request = {
		providers: providerId ?? selectedProviders(),
		options: selectedOptions(),
		search: search.value,
		page,
		size: pageSize,
	}

	const { data } = await axios.get<ISearchResponse>(generateUrl('/apps/fulltextsearch/v1/search'), {
		params: { request: JSON.stringify(request) },
	})

	return data.result
}

/**
 * @param e error caught from an axios request against the search API
 */
function errorMessage(e: unknown): string {
	return (e as { response?: { data?: ISearchErrorResponse } }).response?.data?.message
		?? t('fulltextsearch', 'Failed to reach server. Try reloading the page')
}

/**
 * Debounce and (re-)run a full search across every enabled provider, replacing the current results.
 *
 * @param immediate skip the debounce, used when a filter (rather than the search field) changed
 */
function triggerSearch(immediate = false): void {
	if (searchTimer !== null) {
		window.clearTimeout(searchTimer)
		searchTimer = null
	}

	if (search.value.trim().length < 1) {
		results.value = []
		hasSearched.value = false
		error.value = ''
		return
	}

	const run = async () => {
		loading.value = true
		error.value = ''
		hasSearched.value = true

		try {
			for (const panel of Object.values(panels.value)) {
				providerPage[panel.provider] = 1
			}

			results.value = await runSearch(undefined, 1)
		} catch (e) {
			error.value = errorMessage(e)
			results.value = []
			logger.error('FullTextSearch request failed', { error: e })
		} finally {
			loading.value = false
		}
	}

	if (immediate) {
		run()
	} else {
		searchTimer = window.setTimeout(run, searchDebounce)
	}
}

/**
 * @param providerResult section whose page is changing
 * @param direction -1 for the previous page, 1 for the next page
 */
async function changeProviderPage(providerResult: ISearchResult, direction: 1 | -1): Promise<void> {
	const providerId = providerResult.provider.id
	const nextPage = (providerPage[providerId] ?? 1) + direction
	providerPage[providerId] = nextPage

	try {
		const [updated] = await runSearch(providerId, nextPage)
		const index = results.value.findIndex((result) => result.provider.id === providerId)
		if (updated && index > -1) {
			results.value[index] = updated
		}
	} catch (e) {
		error.value = errorMessage(e)
		logger.error('FullTextSearch pagination request failed', { error: e })
	}
}

/**
 * @param providerResult result section to compute the last page number for
 */
function maxPage(providerResult: ISearchResult): number {
	return Math.max(1, Math.ceil(providerResult.meta.total / pageSize))
}

/**
 * @param doc search result document to read the first excerpt from
 */
function firstExcerpt(doc: ISearchDocument): string {
	return doc.excerpts?.[0]?.excerpt ?? ''
}

/**
 * @param providerResult result section to build the translated header for
 */
function providerResultTitle(providerResult: ISearchResult): string {
	return t(
		'fulltextsearch',
		'{title} — {total} results in {time} ms',
		{
			title: providerResult.provider.name,
			total: providerResult.meta.total,
			time: providerResult.meta.time,
		},
	)
}

onMounted(() => {
	loadPanels()
})
</script>

<style scoped lang="scss">
.provider-filters {
	padding: 0 calc(var(--default-grid-baseline) * 2);

	&__options {
		display: flex;
		flex-direction: column;
		padding-inline-start: calc(var(--default-grid-baseline) * 4);
	}
}

.search-results {
	padding: calc(var(--default-grid-baseline) * 2) calc(var(--default-grid-baseline) * 4);

	&__provider {
		margin-bottom: calc(var(--default-grid-baseline) * 6);
	}

	&__pagination {
		display: flex;
		align-items: center;
		justify-content: center;
		gap: calc(var(--default-grid-baseline) * 2);
		margin-top: calc(var(--default-grid-baseline) * 2);
	}
}
</style>
