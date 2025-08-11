<!--
SPDX-FileCopyrightText: 2022 Carl Schwan <carl@carlschwan.eu>

SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div>
		<h2>Cache Overview</h2>
		<div v-if="cacheTotal > 0">
			<b>Cache hits:</b> {{ cacheHits }} / {{ cacheTotal }} cache hits
			<div>In {{ cacheTime }} ms</div>
		</div>

		<h2>Cache Profiles</h2>
		<div v-for="(cacheProfile) in cacheProfiles" :key="cacheProfile[0]">
			<h3>{{ cacheProfile[0] }}</h3>
			<b>Cache hits:</b> {{ cacheProfile[1].cacheHit }} / {{ cacheProfile[1].cacheHit + cacheProfile[1].cacheMiss }} cache hits
			<div v-if="cacheProfile[1].queries.length > 0" style="overflow-x:auto;">
				<table>
					<thead>
						<tr>
							<th class="nowrap">
								#
							</th>
							<th class="nowrap">
								Time
							</th>
							<th style="width: 100%;">
								Operation
							</th>
						</tr>
					</thead>
					<tbody>
						<tr v-for="(request, index) in cacheProfile[1].queries" :key="index">
							<td>
								{{ index }}
							</td>
							<td>
								{{
									((request.end - request.start) * 1000).toFixed(1)
								}}
								ms
							</td>
							<td class="d-flex">
								<pre>{{ request.op }}</pre>
								<div v-if="request.hit === true" class="rectangle-green" />
								<div v-else-if="request.hit === false" class="rectangle-red" />
							</td>
						</tr>
					</tbody>
				</table>
			</div>
			<p v-else>
				No cache operation
			</p>
		</div>
	</div>
</template>

<script>
import { useStore } from '../store'
import { mapState } from 'pinia'

export default {
	name: 'CacheView',
	computed: {
		cacheProfiles() {
			if (!this.profiles[this.$route.params.token]?.collectors) {
				return []
			}
			return Object.entries(this.profiles[this.$route.params.token].collectors).filter(entry => {
				const [key] = entry
				console.debug(entry)
				return key.includes('cache')
			})
		},
		...mapState(useStore, ['profiles']),
		cacheTotal() {
			let cacheTotal = 0
			this.cacheProfiles.forEach(entry => {
				const [, value] = entry
				cacheTotal += value.cacheMiss + value.cacheHit
			})
			return cacheTotal
		},
		cacheHits() {
			let cacheTotal = 0
			this.cacheProfiles.forEach(entry => {
				const [, value] = entry
				cacheTotal += value.cacheHit
			})
			return cacheTotal
		},
		cacheTime() {
			let cacheTime = 0
			this.cacheProfiles.forEach(entry => {
				const [, value] = entry
				cacheTime += (value.queries.reduce((acc, query) => {
					return query.end - query.start + acc
				}, 0))
			})
			return (cacheTime * 1000).toFixed(1)
		},
	},
}
</script>

<style scoped>
table {
	background: var(--color-background-darker);
	border: var(--border-color-dark);
	box-shadow: rgba(32, 32, 32, 0.2) 0px 0px 1px 0px;
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

tbody tr:hover, tbody tr:focus, tbody tr:active {
	background-color: inherit;
}

.rectangle-red {
	display: inline-block;
	background-color: red;
	height: 10px;
	width: 10px;
	margin-inline-start: 5px;
}

.rectangle-green {
	display: inline-block;
	background-color: green;
	height: 10px;
	width: 10px;
	margin-inline-start: 5px;
}

.d-flex {
	display: flex;
	align-items: center;
}
</style>
