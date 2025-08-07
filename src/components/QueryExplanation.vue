<!--
SPDX-FileCopyrightText: 2022 Carl Schwan <carl@carlschwan.eu>

SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div>
		<div v-if="true || explanation.data[0].length > 0">
			<table>
				<thead>
					<tr>
						<th v-for="(key, label) in explanation.data[0]" :key="label" style="padding: 1rem;">
							{{ label }}
						</th>
					</tr>
				</thead>
				<tbody>
					<tr v-for="row in explanation.data" :key="row">
						<td v-for="(item, key) in row" :key="key" style="padding: 1rem">
							{{ item }}
						</td>
					</tr>
				</tbody>
			</table>
		</div>
		<div v-else>
			<pre v-for="row in explanation.data" :key="row">{{ row }}</pre>
		</div>
	</div>
</template>

<script>
export default {
	name: 'QueryExplanation',
	props: {
		explanation: {
			type: Object,
			required: true,
		},
	},
}
</script>
