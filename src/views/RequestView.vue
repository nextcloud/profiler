<!--
SPDX-FileCopyrightText: 2022 Carl Schwan <carl@carlschwan.eu>

SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div v-if="router || http">
		<div v-if="router">
			<h2>Router</h2>
			<p><b>AppName:</b> {{ router.appName }}</p>
			<p><b>Controller name:</b> {{ router.controllerName }}</p>
			<p><b>Action name:</b> {{ router.actionName }}</p>
			<p><b>Peak memory usage:</b> {{ memory.memory / 1024 / 1024 }} MiB (PHP Limit: {{ memory.memory_limit / 1024 / 1024 }} MiB)</p>
		</div>
		<div v-if="http">
			<h2>Request</h2>
			<p><b>Url:</b> {{ http.request.url }}</p>
			<p><b>Method:</b> {{ http.request.method }}</p>
			<p><b>User Agent:</b> {{ http.request.userAgent }}</p>
			<p><b>Http Protocol:</b> {{ http.request.httpProtocol }}</p>
			<p><b>Params:</b></p>
			<div v-for="(param, key) in http.request.params" :key="key">
				{{ key }}: <i>{{ param }}</i>
			</div>
			<h2>Response</h2>
			<p><b>Headers:</b></p>
			<p><b>Status code:</b> {{ http.response.statusCode }}</p>
			<p><b>Etag:</b> {{ http.response.ETag }}</p>
			<div v-for="(param, key) in http.response.headers" :key="key">
				{{ key }}: <i>{{ param }}</i>
			</div>
		</div>
	</div>
	<div v-else>
		Loading
	</div>
</template>

<script setup lang="ts">
import { useStore } from '../store'
import { useRoute } from 'vue-router'
import { computed } from 'vue'
import type { Http, Router, Memory } from '../store'

const store = useStore()
const route = useRoute()

const http = computed<Http>(() => {
	return store.profiles[route.params.token]?.collectors.http
})

const router = computed<Router>(() => {
	return store.profiles[route.params.token]?.collectors.router
})

const memory = computed<Memory>(() => {
	return store.profiles[route.params.token]?.collectors.memory
})
</script>

<style lang="scss" scoped>
h2 {
	margin-top: 1.25rem;
	margin-bottom: 0;
}
</style>
