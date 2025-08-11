<!--
SPDX-FileCopyrightText: 2022 Carl Schwan <carl@carlschwan.eu>

SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div v-if="router">
		<div><b>AppName:</b> {{ router.appName }}</div>
		<div><b>Controller name:</b> {{ router.controllerName }}</div>
		<div><b>Action name:</b> {{ router.actionName }}</div>
	</div>
</template>

<script>
import { useStore } from '../store'
import { mapState } from 'pinia'

export default {
	name: 'RouterView',
	computed: {
		router() {
			return this.profiles[this.$route.params.token]?.collectors.router
		},
		...mapState(useStore, ['profiles']),
	},
}
</script>
