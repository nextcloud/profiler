<template>
	<div>
		<div v-if="true || explanation.data[0].length > 0">
			<table>
				<thead>
					<tr>
						<th v-for="(key, label) in explanation.data[0]" :key="key">
							<td style="padding: 1rem;">
								{{ label }}
							</td>
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
			<pre v-for="row in explanation.data">{{ row }}</pre>
		</div>
	</div>
</template>

<script>
export default {
	name: 'QueryExplaination',
	props: {
		explanation: {
			type: Object,
			required: true,
		},
	},
}
</script>
