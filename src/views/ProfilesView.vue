<!--
SPDX-FileCopyrightText: 2026 Carl Schwan <carl@carlschwan.eu>

SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="profiles-view">
		<h2>All profiles</h2>
		<div class="filters">
			<NcTextField v-model="search.url"
				class="filters__url"
				label="Search by URL"
				placeholder="/apps/files/..."
				trailing-button-icon="close"
				:show-trailing-button="!!search.url"
				@trailing-button-click="search.url = ''">
				<template #icon>
					<Magnify :size="16" />
				</template>
			</NcTextField>
			<NcSelect v-model="search.method"
				class="filters__method"
				:options="methodOptions"
				:clearable="true"
				placeholder="Method" />
			<NcTextField v-model="search.statusCode"
				class="filters__status"
				label="Status code"
				placeholder="200"
				trailing-button-icon="close"
				:show-trailing-button="!!search.statusCode"
				@trailing-button-click="search.statusCode = ''" />
		</div>

		<NcEmptyContent v-if="!loading && profiles.length === 0"
			name="No profiles found"
			description="Try changing your search filters.">
			<template #icon>
				<Magnify />
			</template>
		</NcEmptyContent>

		<div v-else style="overflow-x:auto;">
			<table>
				<thead>
					<tr>
						<th class="nowrap">
							Method
						</th>
						<th style="width: 100%;">
							URL
						</th>
						<th class="nowrap">
							Status
						</th>
						<th class="nowrap">
							Time
						</th>
					</tr>
				</thead>
				<tbody>
					<tr v-for="profile in profiles"
						:key="profile.token"
						role="button"
						class="profile-row"
						@click="openProfile(profile)">
						<td class="nowrap">
							{{ profile.method }}
						</td>
						<td class="url-cell">
							{{ profile.url }}
						</td>
						<td class="nowrap" :class="statusClass(profile.status_code)">
							{{ profile.status_code }}
						</td>
						<td class="nowrap">
							{{ formatTime(profile.time) }}
						</td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>
</template>

<script lang="ts" setup>
import { ref, reactive, watch, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import Magnify from 'vue-material-design-icons/Magnify.vue'
import { useStore } from '../store'

interface FoundProfile {
	token: string,
	method: string,
	url: string,
	time: number,
	parent: string|null,
	status_code: string,
}

const router = useRouter()
const store = useStore()

const methodOptions = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE']

const search = reactive({
	url: '',
	method: null,
	statusCode: '',
})

const profiles = ref<FoundProfile[]>([])
const loading = ref(false)
let debounceTimeout: ReturnType<typeof setTimeout>|null = null

async function fetchProfiles(): Promise<void> {
	loading.value = true
	try {
		const response = await axios.get(generateUrl('/apps/profiler/profiles'), {
			params: {
				url: search.url || undefined,
				method: search.method || undefined,
				statusCode: search.statusCode || undefined,
			},
		})
		profiles.value = response.data.profiles
	} finally {
		loading.value = false
	}
}

function scheduleFetch(): void {
	if (debounceTimeout) {
		clearTimeout(debounceTimeout)
	}
	debounceTimeout = setTimeout(fetchProfiles, 300)
}

watch(() => [search.url, search.method, search.statusCode], scheduleFetch)

onMounted(fetchProfiles)

function openProfile(profile: FoundProfile): void {
	store.loadProfile({ token: profile.token })
	router.push({ name: 'db', params: { token: profile.token } })
}

function statusClass(statusCode: string): string {
	const code = parseInt(statusCode, 10)
	if (code >= 500) {
		return 'status-error'
	}
	if (code >= 400) {
		return 'status-warning'
	}
	return 'status-success'
}

function formatTime(time: number): string {
	return new Date(time * 1000).toLocaleString()
}
</script>

<style scoped lang="scss">
.profiles-view {
	max-width: 100%;
}

.filters {
	display: flex;
	flex-direction: row;
	align-items: flex-end;
	gap: 1rem;
	margin-block-end: 1rem;

	&__url {
		flex: 1 1 auto;
	}

	&__method {
		width: 160px;
	}

	&__status {
		width: 120px;
	}
}

table {
	background: var(--color-background-darker);
	border: var(--border-color-dark);
	box-shadow: rgba(32, 32, 32, 0.2) 0 0 1px 0;
	margin: 1em 0;
	width: 100%;
}

table, tr, th, td {
	background: var(--table-background);
	border-collapse: collapse;
	line-height: 1.5;
	vertical-align: top !important;
}

thead tr {
	background: var(--color-background-dark);
}

table th, table td {
	padding: 8px 10px;
}

table tbody th, table tbody td {
	border: 1px solid #ddd;
	border-width: 1px 0;
	font-family: monospace;
	font-size: 13px;
}

.nowrap {
	white-space: nowrap;
}

.url-cell {
	word-break: break-all;
}

.profile-row {
	cursor: pointer;
}

tbody tr:hover {
	background-color: var(--color-background-hover);
}

.status-success {
	color: var(--color-success);
}

.status-warning {
	color: var(--color-warning);
}

.status-error {
	color: var(--color-error);
}
</style>
