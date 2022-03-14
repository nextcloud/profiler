<template>
	<div id="profiler-toolbar">
		<footer v-if="profile" class="bottom-bar" :class="{ 'bottom-bar-closed': !open }">
			<div role="button" class="toolbar-block" @click="openProfiler('http')" v-if="open">
				<div class="text-v-center px-3" :class="background">
					{{ profile.statusCode }}
				</div>
				<div class="text-v-center px-3">
					{{ profile.collectors.router.controllerName }}::{{ profile.collectors.router.actionName }}
				</div>
				<div class="info">
					<div><b>HTTP Status:</b> {{ profile.statusCode }} </div>
					<div><b>Controller:</b> {{ profile.collectors.router.appName }}/{{ profile.collectors.router.controllerName }}::{{ profile.collectors.router.actionName }} </div>
					<div><b>Profiled on:</b> {{ time }} </div>
					<div><b>Token:</b> {{ profile.token }}</div>
				</div>
			</div>
			<div role="button" class="toolbar-block text-v-center px-3" @click="openProfiler('event')" v-if="open">
				{{ displayDuration(profile.collectors.event.runtime.duration + profile.collectors.event.autoloader.duration) }} ms
				<div class="info" style="width: 225px">
					<div><b>Total time:</b> {{ displayDuration(profile.collectors.event.runtime.duration + profile.collectors.event.autoloader.duration) }} ms</div>
					<div><b>Boot:</b> {{ displayDuration(profile.collectors.event.boot.duration) }} ms</div>
					<div v-if="profile.collectors.event.run_route">
						<b>Run route:</b> {{ displayDuration(profile.collectors.event.run_route.duration) }} ms
					</div>
					<div v-if="profile.collectors.event.setup_fs">
						<b>Setup filesystem:</b> {{ displayDuration(profile.collectors.event.setup_fs.duration) }} ms
					</div>
				</div>
			</div>

			<div role="button" class="toolbar-block text-v-center px-3" @click="openProfiler('db')" v-if="open">
				{{ queriesNumber }} in {{ queriesTime }} ms
				<div class="info" style="width: 225px">
					<div><b>Number of queries:</b> {{ queriesNumber }}</div>
					<div><b>Query time:</b> {{ queriesTime }} ms</div>
				</div>
			</div>

			<div role="button" class="toolbar-block text-v-center px-3" v-if="profile.collectors.ldap && open" @click="openProfiler('ldap')">
				{{ profile.collectors.ldap.length }} LDAP request
				<div v-if="profile.collectors.ldap.length > 0" class="info" style="width: 500px; max-height: 600px; overflow-x: scroll">
					<div><b>Number of queries:</b> {{ profile.collectors.ldap.length }}</div>
					<div><b>Query time:</b> {{ ldapQueryTime }} ms</div>
				</div>
			</div>

			<div role="button" class="toolbar-block text-v-center px-3" v-if="cacheTotal > 0 && open" @click="openProfiler('cache')">
				{{ cacheHits }} / {{ cacheTotal }} cache hits
				<div class="info" style="width: 200px; max-height: 600px; overflow-x: scroll">
					<div><b>Cache hits:</b> {{ cacheHits }} / {{ cacheTotal }}</div>
					<div>In {{ cacheTime }} ms</div>
				</div>
			</div>

			<div role="button" class="toolbar-block text-v-center px-3" @click="openProfiler('db')" v-if="open">
				{{ stackElements.length }} XHR requests
				<div class="info" style="width: 500px; max-height: 600px; overflow-x: scroll">
					<div v-for="(stackElement, index) in stackElements" :key="index">
						<a :href="generateAjaxUrl(stackElement)">
							{{ stackElement.url }}
							in {{ (stackElement.duration).toFixed() }} ms
							<span class="lighter">({{ stackElement.profile }})</span>
						</a>
					</div>
				</div>
			</div>
			<div class="toggle-button toolbar-block text-v-center px-3" @click="open = !open">
			   {{ open ? 'Close' : 'Open' }}
			</div>
		</footer>
	</div>
</template>

<script>
import { loadState } from '@nextcloud/initial-state'
import { mapState } from 'vuex'
import { generateUrl } from '@nextcloud/router'

const token = loadState('profiler', 'request-token')

export default {
	name: 'ProfilerToolbar',
	data() {
		return {
			token,
			open: true,
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
		...mapState(['profiles', 'stackElements']),
	},
	mounted() {
		this.$store.dispatch('loadProfile', { token })
	},
	methods: {
		displayDuration(time) {
			return (time * 1000.0).toFixed(2)
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
	left: 0;
	display: flex;
	z-index: 10;
	flex-direction: row;
	background-color: #222;
	justify-content: start;
	height: 36px;
	font-size: 12px;
	border-bottom: 1px solid var(--color-border);
	z-index: 99999;

	&.bottom-bar-closed {
		width: initial;
		right: 0;
		left: initial;
	}

	& > .toolbar-block {
		height: 100%;
		&, & > .text-v-center {
			cursor: pointer;
		}
	}

	.pr-3, .px-3 {
		padding-right: 1rem !important;
	}

	.pl-3, .px-3 {
		padding-left: 1rem !important;
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

	.toolbar-block:hover {
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
	margin-left: 48px;
}

.toggle-button {
	margin-left: auto;
}
</style>
