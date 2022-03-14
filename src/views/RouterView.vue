<template>
	<div v-if="router">
		<div><b>AppName:</b> {{ router.appName }}</div>
		<div><b>Controller name:</b> {{ router.controllerName }}</div>
		<div><b>Action name:</b> {{ router.actionName }}</div>
	</div>
</template>

<script>
import { mapState } from 'vuex'

export default {
	name: 'RouterView',
	computed: {
		router() {
			return this.profiles[this.$route.params.token]?.collectors.router
		},
		...mapState(['profiles']),
	},
}
</script>
