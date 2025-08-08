<!--
SPDX-FileCopyrightText: 2022 Carl Schwan <carl@carlschwan.eu>

SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div id="profiler-toolbar">
		<footer v-if="profile"
			class="bottom-bar"
			:class="{ 'bottom-bar-closed': !open }">
			<div v-if="open"
				role="button"
				class="toolbar-block"
				@click="openProfiler('http')">
				<div class="text-v-center px-3" :class="background">
					{{ profile.statusCode }}
				</div>
				<div class="text-v-center px-3">
					{{
						profile.collectors.router.controllerName
					}}::{{ profile.collectors.router.actionName }}
				</div>
				<div class="info">
					<div><b>HTTP Status:</b> {{ profile.statusCode }}</div>
					<div>
						<b>Controller:</b> {{
							profile.collectors.router.appName
						}}/{{
							profile.collectors.router.controllerName
						}}::{{ profile.collectors.router.actionName }}
					</div>
					<div><b>Profiled on:</b> {{ time }}</div>
					<div><b>Token:</b> {{ profile.token }}</div>
				</div>
			</div>
			<div v-if="open"
				role="button"
				class="toolbar-block text-v-center px-3"
				@click="openProfiler('event')">
				{{
					displayDuration(profile.collectors.event.runtime.duration + profile.collectors.event.autoloader.duration)
				}} ms
				<div class="info" style="width: 225px">
					<div>
						<b>Total time:</b> {{
							displayDuration(profile.collectors.event.runtime.duration + profile.collectors.event.autoloader.duration)
						}} ms
					</div>
					<div>
						<b>Boot:</b> {{
							displayDuration(profile.collectors.event.boot.duration)
						}} ms
					</div>
					<div v-if="profile.collectors.event.run_route">
						<b>Run route:</b> {{
							displayDuration(profile.collectors.event.run_route.duration)
						}} ms
					</div>
					<div v-if="profile.collectors.event.setup_fs">
						<b>Setup filesystem:</b> {{
							displayDuration(profile.collectors.event.setup_fs.duration)
						}} ms
					</div>
				</div>
			</div>

			<div v-if="open"
				role="button"
				class="toolbar-block text-v-center px-3"
				@click="openProfiler('db')">
				<Database :size="18" class="mr-3" />
				{{ queriesNumber }} in {{ queriesTime }} ms
				<div class="info" style="width: 225px">
					<div><b>Number of queries:</b> {{ queriesNumber }}</div>
					<div><b>Query time:</b> {{ queriesTime }} ms</div>
				</div>
			</div>

			<div v-if="profile.collectors.ldap && open"
				role="button"
				class="toolbar-block text-v-center px-3"
				@click="openProfiler('ldap')">
				<Account :size="18" class="mr-3" />
				{{ profile.collectors.ldap.length }} LDAP request
				<div v-if="profile.collectors.ldap.length > 0"
					class="info"
					style="width: 500px; max-height: 600px; overflow-x: scroll">
					<div>
						<b>Number of queries:</b>
						{{ profile.collectors.ldap.length }}
					</div>
					<div><b>Query time:</b> {{ ldapQueryTime }} ms</div>
				</div>
			</div>

			<div v-if="cacheTotal > 0 && open"
				role="button"
				class="toolbar-block text-v-center px-3"
				@click="openProfiler('cache')">
				<Server :size="18" class="mr-3" />
				{{ cacheHits }} / {{ cacheTotal }} cache hits
				<div class="info"
					style="width: 200px; max-height: 600px; overflow-x: scroll">
					<div>
						<b>Cache hits:</b> {{ cacheHits }} / {{ cacheTotal }}
					</div>
					<div>In {{ cacheTime }} ms</div>
				</div>
			</div>

			<div v-if="open"
				role="button"
				class="toolbar-block text-v-center px-3"
				:class="{open: xhrOpen, closed: !xhrOpen, hasError: stackElementHasError}"
				@click="xhrOpen = !xhrOpen">
				<ChevronUp v-if="xhrOpen" class="mr-3" :size="18" />
				<ChevronDown v-else class="mr-3" :size="18" />
				{{ stackElements.length }} XHR requests
				<table class="info"
					style="max-width: 900px; max-height: 600px; min-height: 600px; overflow: scroll;">
					<tr v-for="(stackElement, index) in validStackElements" :key="index" style="cursor: pointer">
						<td class="mr-3">
							<a :href="generateAjaxUrl(stackElement)">
								{{ stackElement.method }}
							</a>
						</td>
						<td class="mr-3">
							<a :href="generateAjaxUrl(stackElement)" :class="{error: stackElement.error }">
								{{ stackElement.statusCode }}
							</a>
						</td>
						<td>
							<a :href="generateAjaxUrl(stackElement)">
								{{ simplifiedUrl(stackElement.url) }}
							</a>
						</td>
						<td class="mr-3">
							<a :href="generateAjaxUrl(stackElement)">
								{{ (stackElement.duration).toFixed() }} ms
							</a>
						</td>
						<td class="lighter">
							<a :href="generateAjaxUrl(stackElement)">
								{{ stackElement.profile }}
							</a>
						</td>
					</tr>
				</table>
			</div>
			<div class="toggle-button toolbar-block text-v-center px-3"
				@click="open = !open">
				{{ open ? 'Close' : 'Open' }}
			</div>
		</footer>
	</div>
</template>

<script>
import { loadState } from '@nextcloud/initial-state'
import { mapState } from 'vuex'
import { generateUrl } from '@nextcloud/router'
import Database from 'vue-material-design-icons/Database.vue'
import Account from 'vue-material-design-icons/Account.vue'
import ChevronDown from 'vue-material-design-icons/ChevronDown.vue'
import ChevronUp from 'vue-material-design-icons/ChevronUp.vue'
import Server from 'vue-material-design-icons/Server.vue'

const token = loadState('profiler', 'request-token')

export default {
	name: 'ProfilerToolbar',
	components: {
		Database,
		Account,
		ChevronDown,
		ChevronUp,
		Server,
	},
	data() {
		return {
			token,
			open: true,
			xhrOpen: false,
		}
	},
	computed: {
		profile() {
			return this.profiles[this.token]
		},
		queriesNumber() {
			return Object.values(this.profile.collectors.db.queries).length
		},
		queriesTime() {
			return (Object.values(this.profile.collectors.db.queries).reduce((acc, query) => {
				return query.executionMS + acc
			}, 0) * 1000).toFixed(1)
		},
		ldapQueryTime() {
			return (Object.values(this.profile.collectors.ldap).reduce((acc, query) => {
				return query.end - query.start + acc
			}, 0) * 1000).toFixed(1)
		},
		cacheTotal() {
			let cacheTotal = 0
			Object.entries(this.profile.collectors).forEach(entry => {
				const [key, value] = entry
				if (key.includes('cache')) {
					cacheTotal += value.cacheMiss + value.cacheHit
				}
			})
			return cacheTotal
		},
		cacheHits() {
			let cacheTotal = 0
			Object.entries(this.profile.collectors).forEach(entry => {
				const [key, value] = entry
				if (key.includes('cache')) {
					cacheTotal += value.cacheHit
				}
			})
			return cacheTotal
		},
		cacheTime() {
			let cacheTime = 0
			Object.entries(this.profile.collectors).forEach(entry => {
				const [key, value] = entry
				if (key.includes('cache')) {
					cacheTime += (value.queries.reduce((acc, query) => {
						return query.end - query.start + acc
					}, 0))
				}
			})
			return (cacheTime * 1000).toFixed(1)
		},
		background() {
			if (!this.profile) {
				return ''
			}
			if (this.profile.statusCode === 200) {
				return 'status-success'
			}
			if (this.profile.statusCode === 500) {
				return 'status-error'
			}
			return 'status-warning'
		},
		time() {
			if (!this.profile) {
				return ''
			}
			return new Date(this.profile.time * 1000).toUTCString()
		},
		stackElementHasError() {
			if (this.stackElements === null) {
				return false
			}
			return this.stackElements.some(stackElement => stackElement.error)
		},
		validStackElements() {
			return this.stackElements.filter(stackElement => stackElement.profile)
		},
		...mapState(['profiles', 'stackElements']),
	},
	mounted() {
		this.$store.dispatch('loadProfile', { token })
	},
	methods: {
		displayDuration(time) {
			return (time * 1000.0).toFixed(2)
		},
		simplifiedUrl(url) {
			if (url.startsWith('http')) {
				const newUrl = new URL(url)
				return newUrl.pathname + newUrl.search
			} else {
				return url
			}
		},
		generateAjaxUrl(stackElement) {
			return generateUrl('/apps/profiler/profiler/db/{token}', {
				token: stackElement.profile,
			})
		},
		openProfiler(view) {
			document.location = generateUrl('/apps/profiler/profiler/{view}/{token}', {
				view,
				token: this.token,
			})
		},
	},
}
</script>

<style lang="scss" scoped>
.bottom-bar {
	position: fixed;
	width: 100%;
	bottom: 0;
	inset-inline-start: 0;
	display: flex;
	flex-direction: row;
	background-color: #222;
	justify-content: start;
	height: 36px;
	font-size: 12px;
	border-bottom: 1px solid var(--color-border);
	z-index: 99999;

	&.bottom-bar-closed {
		width: initial;
		inset-inline: initial 0;
	}

	& > .toolbar-block {
		height: 100%;

		&, & > .text-v-center {
			cursor: pointer;
		}
	}

	.pr-3, .px-3 {
		padding-inline-end: 1rem !important;
	}

	.pl-3, .px-3 {
		padding-inline-start: 1rem !important;
	}

	.mr-3 {
		margin-inline-end: 0.5rem !important;
	}

	.lighter {
		color: #ddd;
	}

	.text-v-center {
		height: 100%;
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-content: center;
	}

	.toolbar-block:hover:not(.closed), .toolbar-block.open {
		position: relative;
		background-color: #444;
		border-radius: 0;

		.info {
			display: block;
			padding: 10px;
			max-width: 480px;
			max-height: 480px;
			word-wrap: break-word;
			overflow: hidden;
		}
	}

	.toolbar-block {
		display: flex;
		flex-direction: row;
		justify-content: center;
		color: white;

		&.hasError {
			background-color: red;
		}

		.info {
			background-color: #444;
			bottom: 36px;
			color: #F5F5F5;
			display: none;
			padding: .5rem;
			position: absolute;

			a {
				color: #F5F5F5;
			}
			.error {
				color: red;
			}
		}
	}
}

.status-success {
	background-color: rgba(112, 196, 137, 0.75);
}

.status-error {
	background-color: rgba(231, 55, 51, 0.65);
}

.status-warning {
	background-color: rgba(213, 118, 41, 0.75);
}

.url {
	margin-inline-start: 48px;
}

.toggle-button {
	margin-inline-start: auto;
}
</style>

<style lang="scss">
:root {
	// overwrite body height for profiler toolbar
	--body-height: calc(100% - env(safe-area-inset-bottom) - 50px - var(--body-container-margin) - 36px);
}

.app-sidebar-tab__buttons {
	// add space for profiler toolbar
	bottom: calc(var(--body-container-margin) + 36px)!important;
}
</style>
