<template>
	<div>
		<h2>LDAP queries</h2>
		<div style="overflow-x:auto;">
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
							Info
						</th>
					</tr>
				</thead>
				<tbody>
					<tr v-for="(request, index) in ldap" :key="index">
						<td>
							{{ index }}
						</td>
						<td>
							{{ ((request.end - request.start) * 1000).toFixed(1) }}
							ms
						</td>
						<td>
							<pre>
{{ request.query }}
							</pre>
							<h4>Parameters:</h4>
							{{ request.args }}
						</td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>
</template>

<script>
import { mapGetters, mapState } from 'vuex'

export default {
	name: 'LdapView',
	computed: {
		ldap() {
			return this.profiles[this.$route.params.token]?.collectors.ldap
		},
		...mapGetters(['profile']),
		...mapState(['profiles']),
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
</style>
