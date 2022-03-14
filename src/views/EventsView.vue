<template>
	<div>
		<h2>Events</h2>
		<div style="overflow-x:auto;">
			<table>
				<thead>
					<tr>
						<th class="nowrap" style="cursor: pointer;">
							#<span class="text-muted">▲</span>
						</th>
						<th class="nowrap" style="cursor: pointer;">
							Time<span />
						</th>
						<th style="width: 100%;">
							Description
						</th>
					</tr>
				</thead>
				<tbody>
					<tr v-for="(event, index) in events" :key="event.id">
						<td>
							{{ index }}
						</td>
						<td>
							{{ (event.duration * 1000).toFixed(1) }} ms
							(Start: {{ event.start * 1000 }}, End: {{ event.stop * 1000 }})
						</td>
						<td>
							{{ event.description }}
						</td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>
</template>

<script>
import { mapState } from 'vuex'

export default {
	name: 'EventsView',
	computed: {
		events() {
			return this.profiles[this.$route.params.token]?.collectors.event
		},
		...mapState(['profiles']),
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
