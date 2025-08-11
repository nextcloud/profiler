<!--
SPDX-FileCopyrightText: 2022 Robin Appelman <robin@icewind.nl>

SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div>
		<div>
			<button @click="expanded = !expanded">
				Toggle Backtrace
			</button>
		</div>
		<div v-if="expanded">
			<table>
				<thead>
					<tr>
						<th class="line">
							Line
						</th>
						<th class="method">
							Method
						</th>
					</tr>
				</thead>
				<tbody>
					<tr v-for="(row, key) in trace" :key="key">
						<td class="line">
							{{ row.line }}
						</td>
						<td class="method">
							{{ row.call }}
						</td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>
</template>

<script lang="ts" setup>
import { computed, defineProps, ref } from 'vue'
import type { BacktraceRow } from '../store'

const { backtrace } = defineProps<{
	backtrace: BacktraceRow[]
}>()
const expanded = ref(true)

const filePrefix = computed((): string => {
	const files = backtrace.map(line => line.file || '')
	if (!files[0] || files.length === 1) {
		return files[0] || ''
	}
	let i = 0
	while (files[0][i] && files.every(w => w[i] === files[0][i])) {
		i++
	}
	return files[0].substring(0, i)
})

const trace = computed(() => {
	const prefixLength = filePrefix.value.length
	return backtrace.map(line => {
		return {
			line: line.file ? (line.file.substring(prefixLength) + ' ' + line.line) : '--',
			call: line.class ? (line.class + line.type + line.function) : line.function,
		}
	})
})

</script>

<style lang="scss" scoped>
th {
	font-weight: bold;
}

td.line {
	padding-inline-end: 2em;
}
</style>
