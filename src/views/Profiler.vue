<!--
SPDX-FileCopyrightText: 2022 Carl Schwan <carl@carlschwan.eu>

SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcContent app-name="profiler">
		<NcAppNavigation>
			<template #list>
				<NcAppNavigationCaption name="Categories" />
				<NcAppNavigationItem v-for="cat in categoryInfo"
					:key="cat.id"
					:to="{ name: cat.id, params: {token: token} }"
					:name="cat.name">
					<template #icon>
						<component :is="cat.icon" :size="20" />
					</template>
				</NcAppNavigationItem>

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
	</NcContent>
</template>

<script>
import { loadState } from '@nextcloud/initial-state'
import NcAppNavigationCaption from '@nextcloud/vue/components/NcAppNavigationCaption'
import NcContent from '@nextcloud/vue/components/NcContent'
import NcAppNavigation from '@nextcloud/vue/components/NcAppNavigation'
import NcAppNavigationItem from '@nextcloud/vue/components/NcAppNavigationItem'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import NcAppContent from '@nextcloud/vue/components/NcAppContent'
import ProfileHeader from '../components/ProfileHeader.vue'
import DatabaseOutline from 'vue-material-design-icons/DatabaseOutline.vue'
import ChartGantt from 'vue-material-design-icons/ChartGantt.vue'
import Cached from 'vue-material-design-icons/Cached.vue'
import Account from 'vue-material-design-icons/Account.vue'
import ServerNetwork from 'vue-material-design-icons/ServerNetwork.vue'

import { useStore } from '../store'
import { mapState, mapActions } from 'pinia'

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
		NcContent,
		DatabaseOutline,
		ChartGantt,
		Cached,
		ServerNetwork,
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
					icon: ServerNetwork,
				},
				{
					id: 'db',
					name: 'Database queries',
					icon: DatabaseOutline,
				},
				{
					id: 'event',
					name: 'Events',
					icon: ChartGantt,
				},
				{
					id: 'ldap',
					name: 'LDAP',
					icon: Account,
				},
				{
					id: 'cache',
					name: 'Cache',
					icon: Cached,
				},
			],
		}
	},
	computed: mapState(useStore, ['profiles', 'importedProfiles']),
	watch: {
		selectedProfile(newToken) {
			this.loadProfile({ token: newToken.token })
			this.$router.push({ name: this.selectedCategory, params: { token: newToken.token } })
			this.token = newToken.token
		},
		selectedCategory(newCategory) {
			this.$router.push({ name: newCategory, params: { token: this.$router.params.token } })
		},
	},
	mounted() {
		this.loadProfile({ token })
	},
	methods: {
		importFiles(event) {
			for (const file of event.target.files) {
				this.importProfile({ file })
			}
		},
		...mapActions(useStore, ['loadProfile', 'importProfile']),
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

.router-wrapper {
	padding: 2rem;
}

</style>
