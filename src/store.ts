// SPDX-FileCopyrightText: 2022 Carl Schwan <carl@carlschwan.eu>
// SPDX-License-Identifier: AGPL-3.0-or-later

import { defineStore } from 'pinia'
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { computed, ref } from 'vue'
import type { Ref } from 'vue'

interface StackElement {

}

interface Profile {

}

interface Profiles {
	[index: string]: Profile;
}

/**
 * Read a file to string
 * @param file
 */
function readFile(file: Blob): Promise<string> {
	return new Promise((resolve, reject): void => {
		const reader: FileReader = new FileReader()
		reader.readAsText(file, 'UTF-8')
		reader.onload = function(): void {
			if (reader.result) {
				resolve(reader.result.toString())
			} else {
				reject(new Error('File output is null'))
			}
		}
		reader.onerror = function(): void {
			reject(new Error('error reading file'))
		}
	})
}

export const useStore = defineStore('main', () => {
	const profiles: Ref<Profiles> = ref({})
	const stackElements: Ref<StackElement[]> = ref([])
	const importedProfiles: Ref<Profile[]> = ref([])

	const profile = computed(() => (token: string): Profile => {
		return profiles.value[token]
	})

	/**
	 *
	 * @param root0
	 * @param root0.token
	 * @param root0.profile
	 */
	function addProfile({ token, profile }) {
		profiles.value[token] = profile
	}

	/**
	 *
	 * @param root0
	 * @param root0.profiles
	 */
	function addImportedProfiles({ profiles }) {
		for (const profile of profiles) {
			importedProfiles.value.push(profile)
			profiles.value[profile.token] = profile
		}
	}

	/**
	 *
	 * @param stackElement
	 */
	function addStackElement(stackElement: StackElement) {
		stackElements.value.push(stackElement)
	}

	/**
	 *
	 * @param root0
	 * @param root0.token
	 */
	function loadProfile({ token }) {
		if (profiles.value[token]) {
			return
		}
		axios.get(generateUrl('/apps/profiler/profile/{token}', { token }))
			.then((response) => {
				addProfile({ token, profile: response.data.profile })
			})
	}

	/**
	 *
	 * @param root0
	 * @param root0.file
	 */
	function importProfile({ file }) {
		readFile(file).then(content => addImportedProfiles({ profiles: JSON.parse(content) }))
	}

	return { profiles, stackElements, importedProfiles, profile, addProfile, addImportedProfiles, addStackElement, loadProfile, importProfile }
})
