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
						:options="recentProfiles.concat(store.importedProfiles)"
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

<script setup lang="ts">
import { loadState } from '@nextcloud/initial-state'
import NcAppNavigationCaption
	from '@nextcloud/vue/components/NcAppNavigationCaption'
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

import { watch, ref, onMounted } from 'vue'
import { useStore } from '../store'
import { useRouter, onBeforeRouteUpdate } from 'vue-router'

const router = useRouter()
const store = useStore()
const token = ref<string>(loadState('profiler', 'token'))
const recentProfiles = ref<string[]>(loadState('profiler', 'recentProfiles'))

const selectedCategory = ref('db')
const selectedProfile = ref(null)
const categoryInfo = [
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
]

onBeforeRouteUpdate(async (to) => {
	token.value = to.params.token
	selectedCategory.value = to.params.name
})

watch(selectedProfile, (newToken) => {
	store.loadProfile({ token: newToken.token })
	router.push({
		name: selectedCategory.value,
		params: { token: newToken.token },
	})
	token.value = newToken.token
})

watch(selectedCategory, (newCategory) => {
	router.push({
		name: newCategory,
		params: { token: router.params.token },
	})
})

onMounted(() => {
	store.loadProfile({ token: token.value })
})

/**
 *
 * @param event
 */
function importFiles(event) {
	for (const file of event.target.files) {
		store.importProfile({ file })
	}
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
