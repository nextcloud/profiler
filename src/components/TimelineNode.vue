<!--
SPDX-FileCopyrightText: 2022 Robin Appelman <robin@icewind.nl>

SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="node-group">
		<div class="node" :title="`${event.id} - ${event.description} ${duration}ms`" @click="$emit('click', event)">
			{{ event.id }} - {{ event.description }} {{ duration }}ms
		</div>
		<div v-if="children.length > 0" class="children" :style="`height: ${childHeight}px`">
			<div v-for="(event) in children"
				:key="event.id"
				:style="event.style"
				class="child">
				<TimelineNode :event="event" @click="(childEvent) => $emit('click', childEvent)" />
			</div>
		</div>
	</div>
</template>

<script>
export default {
	name: 'TimelineNode',
	props: {
		event: {
			type: Object,
			required: true,
		},
	},
	emits: ['click'],
	computed: {
		children() {
			return this.event.children.map(child => {
				const startRelative = (child.start - this.event.start) / this.event.duration
				const durationRelative = child.duration / this.event.duration
				child.style = `left: ${(startRelative) * 100}%; width: ${durationRelative * 100}%`
				return child
			})
		},
		childHeight() {
			return 24 * this.event.childDepth
		},
		duration() {
			return (this.event.duration * 1000).toFixed(1)
		},
	},
}
</script>

<style lang="scss" scoped>
div.node-group {
	position: relative;
	display: inline-block;
	vertical-align: top;
	width: 100%;
	overflow-x: hidden;
	margin: 0;
}

div.node, div.children {
	display: inline-block;
	vertical-align: top;
	width: 100%;
	position: relative;
	margin: 0;
}

div.node {
	background-color: #00000044;
	padding: 0 2px;
	white-space: nowrap;
	cursor: pointer;
}

div.child {
	position: absolute;
	display: inline-block;
	vertical-align: top;
	//top: 0;
	overflow-x: hidden;
	margin: 0;
}

span.duration {
	float: right;
}
</style>
