<!--
SPDX-FileCopyrightText: 2022 Carl Schwan <carl@carlschwan.eu>

SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<header v-if="profile" class="top-bar" :class="background">
		<h2 class="url">
			{{ profile.url }}
			<button class="download" title="Export profile" @click="exportProfile">
				Export
			</button>
		</h2>
		<div>
			<div><b>Method:</b> {{ profile.method }} </div>
			<div><b>HTTP Status:</b> {{ profile.statusCode }} </div>
			<div><b>Profiled on:</b> {{ time }} </div>
			<div><b>Token:</b> {{ profile.token }}</div>
			<div><b>DB query:</b> {{ queriesNumber }}</div>
		</div>
	</header>
</template>

<script>
import { useStore } from '../store'
import { mapState } from 'pinia'

/**
 * Download a string as textfile
 *
 * @param {string} filename name to save the file as
 * @param {string} text content to save
 */
function download(filename, text) {
	const blob = new Blob([text], { type: 'text/plain' })
	const element = window.document.createElement('a')
	element.href = URL.createObjectURL(blob)
	element.download = filename
	element.style.display = 'none'
	document.body.appendChild(element)
	element.click()
	document.body.removeChild(element)
}

export default {
	name: 'ProfileHeader',
	computed: {
		profile() {
			return this.profiles[this.$route.params.token]
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
		queriesNumber() {
			return Object.values(this.profile.collectors.db.queries).length
		},
		...mapState(useStore, ['profiles']),
	},
	methods: {
		exportProfile() {
			const profile = this.profile
			download(`${this.profile.token}.json`, JSON.stringify([profile]))
		},
	},
}
</script>

<style lang="scss" scoped>
.top-bar {
	inset-inline-end: 12px;
	display: flex;
	z-index: 10;
	flex-direction: column;
	padding: 8px;
	border-bottom: 1px solid var(--color-border);
	& > div {
		display: flex;
		flex-direction: row;
		& > div {
			margin-inline-start: 20px;
		}
	}

	button.download {
		float: inline-end;
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
</style>
