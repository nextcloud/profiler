<!--
SPDX-FileCopyrightText: 2022 Carl Schwan <carl@carlschwan.eu>
SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div>
		<div class="sticky-menu">
			<a href="#db-queries"><span class="menu-label">Database queries: </span>{{ queriesNumber }}</a>
			<a href="#db-similars" class="menu-space"><span class="menu-label">Similar queries: </span>{{ duplicateQueries.length }}</a>
			<a href="#db-tables" class="menu-space"><span class="menu-label">Tables used: </span>{{ tableQueries.length }}</a>
		</div>
		<div id="db-queries" class="anchor-space" />
		<h2>Database queries</h2>
		<div style="overflow-x:auto;">
			<table>
				<thead>
					<tr>
						<th class="nowrap">
							#
						</th>
						<th class="nowrap">
							Time<span />
						</th>
						<th style="width: 100%;">
							Query
						</th>
					</tr>
				</thead>
				<tbody>
					<tr v-for="(query, index) in queries" :key="index">
						<td :id="'queries-' + index">
							{{ index }}
						</td>
						<td>
							{{ (query.executionMS * 1000).toFixed(1) }} ms
						</td>
						<td>
							<pre>
{{ query.sql }}
						</pre>
							<h4>Parameters:</h4>
							{{ query.params }}
							<button v-if="query.explainable && explainedQueries[index] === undefined" @click="explainQuery(index)">
								Explain query
							</button>
							<QueryExplanation v-else-if="explainedQueries[index]" :explanation="explainedQueries[index] ? explainedQueries[index] : ''" />
							<Backtrace v-if="query.backtrace" :backtrace="query.backtrace" />
						</td>
					</tr>
				</tbody>
			</table>
		</div>
		<div id="db-similars" class="anchor-space" />
		<h2>Database similar queries</h2>
		<div style="overflow-x:auto;">
			<table>
				<thead>
					<tr>
						<th class="nowrap">
							#
						</th>
						<th class="nowrap">
							Count
						</th>
						<th class="nowrap">
							Cumulated Time<span />
						</th>
						<th style="width: 100%;">
							Info
						</th>
					</tr>
				</thead>
				<tbody>
					<tr v-for="(duplicate, index) in duplicateQueries" :key="'dup-'+index">
						<td>
							{{ index }}
						</td>
						<td>
							{{ duplicate.count }}
						</td>
						<td>
							{{ (duplicate.time * 1000).toFixed(1) }} ms
						</td>
						<td>
							<pre>
{{ duplicate.sql }}
						</pre>
							<button v-if="similarQueries[index] === undefined" @click="openSimilarQuery(index)">
								Detail Similars
							</button>
							<template v-else>
								<template v-if="similarQueries[index].singles.length > 0">
									<h4>Unique combination of params:</h4>
									<div class="detail-see">
										See {{ similarQueries[index].singles.length }} in:
										<a v-for="(simIndex, i) in similarQueries[index].singles" :key="i" :href="anchor(simIndex)">
											{{ simIndex }}
										</a>
									</div>
								</template>
								<template v-if="similarQueries[index].multiples.length > 0">
									<h4>Duplicates (same parameters):</h4>
									<ul>
										<li v-for="(realDuplicate, i) in similarQueries[index].multiples" :key="i">
											<div class="detail-see">
												See {{ realDuplicate.count }} use of params "{{ JSON.parse(realDuplicate.params) }}" in:
												<a v-for="(dupIndex, j) in realDuplicate.indexes" :key="j" :href="anchor(dupIndex)">
													{{ dupIndex }}
												</a>
											</div>
										</li>
									</ul>
								</template>
							</template>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
		<div id="db-tables" class="anchor-space" />
		<h2>Database tables used</h2>
		<div style="overflow-x:auto;">
			<table>
				<thead>
					<tr>
						<th class="nowrap">
							#
						</th>
						<th class="nowrap">
							Count
						</th>
						<th class="nowrap">
							Cumulated Time<span />
						</th>
						<th style="width: 100%;">
							Table
						</th>
					</tr>
				</thead>
				<tbody>
					<tr v-for="(tableUsage, index) in tableQueries" :key="'tab-'+index">
						<td>
							{{ index }}
						</td>
						<td>
							{{ tableUsage.count }}
						</td>
						<td>
							{{ (tableUsage.time * 1000).toFixed(1) }} ms
						</td>
						<td>
							{{ tableUsage.table }}
							<button v-if="detailTablesQueries[index] === undefined" @click="openTableUseDetails(index)">
								Detail Usage
							</button>
							<template v-else>
								<h4>Usage:</h4>
								<div class="detail-see">
									{{ tableUse(tableUsage) }}
								</div>
								<h4>Occurrences:</h4>
								<div class="detail-see">
									See in :
									<a v-for="(useIndex, i) in tableUsage.indexes" :key="i" :href="anchor(useIndex)">
										{{ useIndex }}
									</a>
								</div>
							</template>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>
</template>

<script lang="ts" setup>
import QueryExplanation from '../components/QueryExplanation.vue'
import Backtrace from '../components/Backtrace.vue'
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { useStore } from '../store'
import { ref, computed } from 'vue'
import { useRoute } from 'vue-router'

const store = useStore()
const route = useRoute()

const explainedQueries = ref({})
const similarQueries = ref({})
const detailTablesQueries = ref({})

const queries = computed(() => {
	return store.profiles[route.params.token]?.collectors.db.queries
})

const queriesNumber = computed(() => {
	const queries = store.profiles[route.params.token]?.collectors.db.queries
	if (queries === undefined) {
		return 0
	}
	return Object.values(queries).length
})

const duplicateQueries = computed(() => {
	const queries = store.profiles[route.params.token]?.collectors.db.queries
	if (queries === undefined) {
		return []
	}
	const querySql = []
	Object.entries(queries).forEach(entry => {
		const [index, query] = entry
		if (querySql[query.sql] === undefined) {
			querySql[query.sql] = {
				count: 1,
				time: query.executionMS,
				indexes: [Number(index)],
				sql: query.sql,
			}
		} else {
			querySql[query.sql].count++
			querySql[query.sql].time += query.executionMS
			querySql[query.sql].indexes.push(Number(index))
		}
	})
	return Object.values(querySql).filter(query => {
		return query.count > 1
	}).sort((a, b) => b.time - a.time)
})

const tableQueries = computed(() => {
	const queries = store.profiles[route.params.token]?.collectors.db.queries
	if (queries === undefined) {
		return []
	}
	const tableQueries = []
	Object.entries(queries).forEach(entry => {
		const [index, query] = entry
		const matches = query.sql.matchAll(/(from|join|into|update)\s+["`](\w+\.?\w+\s*)["`]/gi)
		if (matches === null) {
			return
		}
		matches.forEach(match => {
			const typeFrom = match[1].toLowerCase()
			const table = match[2]
			if (tableQueries[table] === undefined) {
				tableQueries[table] = {
					count: 1,
					time: query.executionMS,
					indexes: [index],
					types: { from: 0, join: 0, into: 0, update: 0 },
				}
			} else {
				tableQueries[table].count++
				tableQueries[table].time += query.executionMS
				tableQueries[table].indexes.push(index)
			}
			tableQueries[table].types[typeFrom]++
		})
	})
	return Object.entries(tableQueries).map(entry => {
		const [table, query] = entry
		query.table = table
		return query
	}).sort((a, b) => b.time - a.time)
})

/**
 *
 * @param index
 */
function explainQuery(index: number): void {
	axios.get(generateUrl('/apps/profiler/explain/{token}/{index}', { token: route.params.token, index }))
		.then((response) => {
			explainedQueries.value[index] = response.data
		})
}

/**
 *
 * @param tableUsage
 */
function tableUse(tableUsage): void {
	const types = []
	Object.entries(tableUsage.types).forEach(entry => {
		const [type, count] = entry
		if (count > 0) {
			types.push(type + ': ' + count)
		}
	})
	return types.join(', ')
}

/**
 *
 * @param index
 */
function openSimilarQuery(index: number): void {
	const paramReferences = {}
	duplicateQueries.value[index].indexes.forEach(indexDuplicate => {
		const paramStr = JSON.stringify(queries.value[indexDuplicate].params)
		if (paramReferences[paramStr] === undefined) {
			paramReferences[paramStr] = {
				count: 1,
				indexes: [indexDuplicate],
			}
		} else {
			paramReferences[paramStr].count++
			paramReferences[paramStr].indexes.push(indexDuplicate)
		}
	})
	const singles = Object.entries(paramReferences).filter(entry => {
		return entry[1].count === 1
	}).map(entry => {
		return entry[1].indexes[0]
	})
	const multiples = Object.entries(paramReferences).filter(entry => {
		return entry[1].count > 1
	}).map(entry => {
		const [paramStr, param] = entry
		return {
			count: param.count,
			indexes: param.indexes,
			params: paramStr,
		}
	}).sort((a, b) => b.count - a.count)
	similarQueries.value[index] = {
		singles,
		multiples,
	}
}

/**
 *
 * @param index
 */
function openTableUseDetails(index): void {
	detailTablesQueries.value[index] = {}
}

/**
 *
 * @param index
 */
function anchor(index: number): string {
	return '#queries-' + index
}
</script>

<style lang="scss" scoped>
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

.sticky-menu {
	position: sticky;
	top: 0;
	z-index: 1;
	background: var(--color-background-dark);
	padding: 8px 10px;
}

.anchor-space {
	height: 48px;
}

.menu-label {
	font-size: 1.2em;
	font-weight: bold;
}

.menu-space {
	margin-inline-start: 20px;
}

.detail-see {
	margin-inline-start: 20px;
	font-size: 0.8em;

	a {
		margin-inline-end: 0.5rem;
	}
}

</style>
