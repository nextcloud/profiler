// SPDX-FileCopyrightText: 2022 Carl Schwan <carl@carlschwan.eu>
// SPDX-License-Identifier: AGPL-3.0-or-later

import { createStore } from 'vuex'
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

export default createStore({
	state() {
		return {
			profiles: {},
			stackElements: [],
			importedProfiles: [],
		}
	},
	mutations: {
		addProfile(state, { token, profile }) {
			state.profiles[token] = profile
		},
		addImportedProfiles(state, { profiles }) {
			for (const profile of profiles) {
				state.importedProfiles.push(profile)
				state.profiles[profile.token] = profile
			}
		},
		addStackElement(state, stackElement) {
			state.stackElements.push(stackElement)
		},
	},
	getters: {
		profile: (state) => (token) => {
			return state.profiles[token]
		},
	},
	actions: {
		loadProfile({ commit, state }, { token }) {
			if (state.profiles[token]) {
				return
			}
			axios.get(generateUrl('/apps/profiler/profile/{token}', { token }))
				.then((response) => {
					commit('addProfile', { token, profile: response.data.profile })
				})
		},
		importProfile({ commit, state }, { file }) {
			readFile(file).then(content => commit('addImportedProfiles', { profiles: JSON.parse(content) }))
		},
	},
})

/**
 * Read a file to string
 *
 * @param file
 * @return Promise<string>
 */
function readFile(file) {
	return new Promise((resolve, reject) => {
		const reader = new FileReader()
		reader.readAsText(file, 'UTF-8')
		reader.onload = function(evt) {
			resolve(evt.target.result)
		}
		reader.onerror = function(evt) {
			reject(new Error('error reading file'))
		}
	})
}
