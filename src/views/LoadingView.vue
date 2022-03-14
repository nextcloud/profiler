<template>
	<div class="grid">
		<div>Loading</div>
	</div>
</template>

<script>
export default {
	name: 'LoadingView',
}
</script>

<style scoped lang="scss">
.grid {
	display: grid;
	height: 100%;
	div {
		align-self: center;
		justify-self: center;
	}
}

</style>
