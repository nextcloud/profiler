// SPDX-FileCopyrightText: 2022 Carl Schwan <carl@carlschwan.eu>
// SPDX-License-Identifier: AGPL-3.0-or-later

import { defineStore } from 'pinia'
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { ref } from 'vue'

interface BacktraceRow {
	file: string,
	line: number,
	class: string|null,
	type: string,
	function: string,
}

interface Memory {
	memory: number,
	memory_limit: number,
}

interface Router {
	appName: string,
	controllerName: string,
	actionName: string,
}

interface DbQuery {
	executionMS: number,
	start: number,
	end: number,
}

interface Db {
	queries: DbQuery[],
}

interface Headers {
	[index: string]: string
}

interface Parameters {
	[index: string]: string
}

interface Request {
	url: string,
	method: string,
	userAgent: string,
	httpProtocol: string,
	params: Parameters,
}

interface Response {
	statusCode: number,
	params: object,
	ETag: string,
	headers: Headers,
}

interface Http {
	request: Request,
	response: Response,
}

interface CacheEntry {
	end: number,
	start: number,
	op: string,
	hit: boolean,
}

interface Cache {
	queries: CacheEntry[]
}

interface LdapQuery {
	start: number,
	end: number
	query: string,
	args: string[],
	backtrace: BacktraceRow[],
}

interface Collector {
	memory: Memory,
	ldap: LdapQuery[],
	router: Router,
	http: Http,
	db: Db,
	[index: string]: Cache
}

interface Profile {
	collectors: Collector,
}

interface Profiles {
	[index: string]: Profile;
}

interface StackElement {
	profile: Profile,
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
	const profiles = ref<Profiles>({})
	const stackElements = ref<StackElement[]>([])
	const importedProfiles = ref<Profile[]>([])

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
	function addImportedProfiles({ profiles: profilesToImport }) {
		for (const profile of profilesToImport) {
			importedProfiles.value.push(profile)
			profiles.value[profile.token] = profile
		}
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

	return { profiles, stackElements, importedProfiles, addProfile, addImportedProfiles, loadProfile, importProfile }
})
