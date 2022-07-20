<!--
SPDX-FileCopyrightText: 2022 Carl Schwan <carl@carlschwan.eu>

SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div id="profiler" class="content">
		<AppNavigation>
			<template #list>
				<AppNavigationCaption title="Categories" />
				<li v-for="cat in categoryInfo"
					:key="cat.id">
					<router-link class="app-navigation-entry-link"
						:to="{ name: cat.id, params: {token: token} }">
						<span :title="cat.name" class="app-navigation-entry__title">
							{{ cat.name }}
						</span>
					</router-link>
				</li>

				<AppNavigationCaption title="Requests" />
				<div class="select-container">
					<Multiselect v-model="selectedProfile"
						:options="recentProfiles.concat(importedProfiles)"
						class="select"
						label="url"
						track-by="token" />
				</div>

				<AppNavigationCaption title="Import requests" />
				<input type="file" multiple @change="importFiles">
			</template>
		</AppNavigation>
		<AppContent>
			<ProfileHeader />
			<div class="router-wrapper">
				<router-view />
			</div>
		</AppContent>
	</div>
</template>

<script>
import { loadState } from '@nextcloud/initial-state'
import { AppNavigation, AppContent, AppNavigationCaption, Multiselect } from '@nextcloud/vue'
import ProfileHeader from '../components/ProfileHeader'
import { mapState } from 'vuex'

const token = loadState('profiler', 'token')
const recentProfiles = loadState('profiler', 'recentProfiles')

export default {
	name: 'Profiler',
	components: {
		AppNavigation,
		AppContent,
		AppNavigationCaption,
		ProfileHeader,
		Multiselect,
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

.app-navigation-entry-link {
	background-size: 16px 16px;
	background-position: 14px center;
	background-repeat: no-repeat;
	display: block;
	justify-content: space-between;
	line-height: 44px;
	min-height: 44px;
	padding: 0 12px 0 14px;
	overflow: hidden;
	box-sizing: border-box;
	white-space: nowrap;
	text-overflow: ellipsis;
	color: var(--color-main-text);
	opacity: 0.8;
	flex: 1 1 0;
	z-index: 100;
}
</style>
