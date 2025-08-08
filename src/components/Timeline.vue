<!--
SPDX-FileCopyrightText: 2022 Robin Appelman <robin@icewind.nl>

SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="timeline">
		<div class="slider">
			<Slider v-model="range"
				:min="0"
				:max="duration"
				:step="0.1" />
		</div>
		<div class="zoom-container" :style="zoomStyle">
			<TimelineNode :event="eventTree" @click="(event) => range = [toMs(event.start, true), toMs(event.stop)]" />
		</div>
	</div>
</template>

<script>
import TimelineNode from './TimelineNode.vue'
import '@vueform/slider/themes/default.css'
import Slider from '@vueform/slider'

/**
 *
 * @param time
 * @param low
 */
function toMs(time, low) {
	if (low) {
		return Math.floor(time * 10_000) / 10
	} else {
		return Math.ceil(time * 10_000) / 10
	}
}

export default {
	name: 'Timeline',
	components: {
		TimelineNode,
		Slider,
	},
	props: {
		events: {
			type: Object,
			required: true,
		},
		queries: {
			type: Object,
			required: true,
		},
	},
	data() {
		return {
			range: [0, toMs(this.events.runtime.stop - this.events.init.start)],
		}
	},
	computed: {
		zoomStyle() {
			const duration = (this.events.runtime.stop - this.events.init.start) * 1000
			const width = this.range[1] - this.range[0]
			const zoom = duration / width
			const left = this.range[0] / width
			return `margin-left: ${-left * 100}%; width: ${zoom * 100}%`
		},
		duration() {
			return toMs(this.events.runtime.stop - this.events.init.start)
		},
		eventTree() {
			const runtimeStop = this.events.runtime.stop
			const events = Object.values(this.events)
			const queries = Object.values(this.queries)
			events.forEach(event => {
				if (!event.stop) {
					event.stop = runtimeStop
				}
			})
			events.sort((a, b) => {
				if (a.start < b.start) {
					return -1
				} else if (a.start > b.start) {
					return 1
				} else {
					// if 2 events have the same start, the one that ends last should be sorted first as it is the parent
					return b.stop - a.stop
				}
			})
			const startTime = events[0].start
			const stopTime = Math.max(...events.map(event => event.stop))
			let current = {
				id: 'root',
				duration: stopTime - startTime,
				start: 0,
				stop: stopTime - startTime,
				children: [],
				childDepth: 0,
				parents: [],
				queries,
			}
			const stack = [current]

			for (let event of events) {
				event = {
					id: event.id,
					duration: event.duration,
					start: event.start - startTime,
					stop: event.stop - startTime,
					description: event.description,
					children: [],
					childDepth: 0,
					parents: [],
					queries: queries.filter(query => (query.start >= event.start && query.start < event.stop)),
				}
				while (event.stop > current.stop) {
					current = stack.pop()
				}

				current.children.push(event)
				event.parents = [current].concat(current.parents)

				if (current.childDepth === 0) {
					let depth = 0
					for (const parent of event.parents) {
						depth += 1
						parent.childDepth = Math.max(depth, parent.childDepth)
					}
				}

				stack.push(current)
				current = event
			}

			return stack[0]
		},
	},
	methods: {
		toMs,
	},
}
</script>

<style lang="scss" scoped>
#content div.slider * {
	box-sizing: content-box;
}

div.zoom-container {
	position: relative;
	width: auto;
	transition: width 0.5s, margin-left 0.5s;
}
</style>
