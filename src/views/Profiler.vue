<!--
SPDX-FileCopyrightText: 2022 Carl Schwan <carl@carlschwan.eu>

SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div id="profiler" class="content">
		<NcAppNavigation>
			<template #list>
				<NcAppNavigationCaption name="Categories" />
				<NcAppNavigationItem v-for="cat in categoryInfo"
					:key="cat.id"
					:to="{ name: cat.id, params: {token: token} }"
					:name="cat.name" />

				<NcAppNavigationCaption name="Requests" />
				<div class="select-container">
					<NcSelect v-model="selectedProfile"
						:options="recentProfiles.concat(importedProfiles)"
						class="select"
						label="url"
						track-by="token" />
				</div>

				<NcAppNavigationCaption name="Import requests" />
				<input type="file" multiple @change="importFiles">
			</template>
		</NcAppNavigation>
		<NcAppContent>
			<ProfileHeader />
			<div class="router-wrapper">
				<router-view />
			</div>
		</NcAppContent>
	</div>
</template>

<script>
import { loadState } from '@nextcloud/initial-state'
import NcAppNavigationCaption from '@nextcloud/vue/dist/Components/NcAppNavigationCaption.js'
import NcAppNavigation from '@nextcloud/vue/dist/Components/NcAppNavigation.js'
import NcAppNavigationItem from '@nextcloud/vue/dist/Components/NcAppNavigationItem.js'
import NcSelect from '@nextcloud/vue/dist/Components/NcSelect.js'
import NcAppContent from '@nextcloud/vue/dist/Components/NcAppContent.js'
import ProfileHeader from '../components/ProfileHeader.vue'
import { mapState } from 'vuex'

const token = loadState('profiler', 'token')
const recentProfiles = loadState('profiler', 'recentProfiles')

export default {
	name: 'Profiler',
	components: {
		NcAppNavigation,
		NcAppNavigationItem,
		NcAppContent,
		NcAppNavigationCaption,
		ProfileHeader,
		NcSelect,
	},
	beforeRouteUpdate(to, from, next) {
		next(vm => {
			vm.selectedCategory = to.params.name
			vm.token = to.params.token
		})
	},
	data() {
		return {
			selectedCategory: 'db',
			selectedProfile: null,
			token,
			profile: null,
			recentProfiles,
			categoryInfo: [
				{
					id: 'http',
					name: 'Request and Response',
				},
				{
					id: 'db',
					name: 'Database queries',
				},
				{
					id: 'event',
					name: 'Events',
				},
				{
					id: 'ldap',
					name: 'LDAP',
				},
				{
					id: 'cache',
					name: 'Cache',
				},
			],
		}
	},
	computed: mapState(['profiles', 'importedProfiles']),
	watch: {
		selectedProfile(newToken) {
			this.$store.dispatch('loadProfile', { token: newToken.token })
			this.$router.push({ name: this.selectedCategory, params: { token: newToken.token } })
			this.token = newToken.token
		},
		selectedCategory(newCategory) {
			this.$router.push({ name: newCategory, params: { token: this.$router.params.token } })
		},
	},
	mounted() {
		this.$store.dispatch('loadProfile', { token })
	},
	methods: {
		importFiles(event) {
			for (const file of event.target.files) {
				this.$store.dispatch('importProfile', { file })
			}
		},
	},
}
</script>

<style lang="scss" scoped>
.select-container {
	padding: 0 .5rem;

	.multiselect {
		width: 100%
	}
}

.content {
	height: 100%;
	box-sizing: border-box;
	display: flex;
	min-height: 100%;
	width: 100%;
}

.router-wrapper {
	padding: 2rem;
}

</style>
