<template>
	<div>
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
							Info
						</th>
					</tr>
				</thead>
				<tbody>
					<tr v-for="(query, index) in queries" :key="index">
						<td>
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
						</td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>
</template>

<script>
import { mapGetters, mapState } from 'vuex'
import QueryExplanation from '../components/QueryExplanation'
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

export default {
	name: 'DatabaseProfilerView',
	components: {
		QueryExplanation,
	},
	data() {
		return {
			explainedQueries: {},
		}
	},
	computed: {
		queries() {
			return this.profiles[this.$route.params.token]?.collectors.db.queries
		},
		...mapGetters(['profile']),
		...mapState(['profiles']),
	},
	methods: {
		explainQuery(index) {
			axios.get(generateUrl('/apps/profiler/explain/{token}/{index}', { token: this.$route.params.token, index }))
				.then((response) => {
					this.$set(this.explainedQueries, index, response.data)
				})
		},
	},
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
</style>
