/**
 * @copyright Copyright (c) 2022 Carl Schwan <carl@carlschwan.eu>
 *
 * @author Carl Schwan <carl@carlschwan.eu>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
import Vue from 'vue'
import App from './ProfilerToolbar.vue'
import Vuex from 'vuex'
import store from './store'

// bind to window
Vue.prototype.OC = OC
Vue.prototype.t = t

Vue.use(Vuex)

const instance = new Vue({
	el: '#profiler-toolbar',
	store,
	render: h => h(App),
})

// Hack into the fetch() and XMLHttpRequest to log related http requests
if (window.fetch && window.fetch.polyfill === undefined) {
	const oldFetch = window.fetch
	window.fetch = function() {
		console.debug('fetch')
		const promise = oldFetch.apply(this, arguments)
		let url = arguments[0]
		let params = arguments[1]
		const paramType = Object.prototype.toString.call(arguments[0])
		if (paramType === '[object Request]') {
			url = arguments[0].url
			params = {
				method: arguments[0].method,
				credentials: arguments[0].credentials,
				headers: arguments[0].headers,
				mode: arguments[0].mode,
				redirect: arguments[0].redirect,
			}
		} else {
			url = String(url)
		}
		if (!url.match(/profiler/)) {
			let method = 'GET'
			if (params && params.method !== undefined) {
				method = params.method
			}
			const stackElement = {
				error: false,
				url,
				method,
				type: 'fetch',
				start: new Date(),
			}
			// var idx = requestStack.push(stackElement) - 1;
			promise.then(function(r) {
				stackElement.duration = new Date() - stackElement.start
				stackElement.error = r.status < 200 || r.status >= 400
				stackElement.statusCode = r.status
				stackElement.profile = r.headers.get('x-debug-token')
				stackElement.profilerUrl = r.headers.get('x-debug-token-link')
				store.commit('addStackElement', stackElement)
			}, function(e) {
				store.commit('addStackElement', stackElement)
			})
		}
		return promise
	}
}

const extractHeaders = function(xhr, stackElement) {
	/* Here we avoid to call xhr.getResponseHeader in order to */
	/* prevent polluting the console with CORS security errors */
	const allHeaders = xhr.getAllResponseHeaders()
	let ret
	// eslint-disable-next-line
	if (ret = allHeaders.match(/^x-debug-token:\s+(.*)$/im)) {
		stackElement.profile = ret[1]
	}
	// eslint-disable-next-line
	if (ret = allHeaders.match(/^x-debug-token-link:\s+(.*)$/im)) {
		stackElement.profilerUrl = ret[1]
	}
}

if (window.XMLHttpRequest && XMLHttpRequest.prototype.addEventListener) {
	const proxied = XMLHttpRequest.prototype.open
	XMLHttpRequest.prototype.open = function(method, url, async, user, pass) {
		const self = this
		/* prevent logging AJAX calls to static and inline files, like templates */
		let path = url
		if (url.substr(0, 1) === '/') {
			if (url.indexOf('/index.php/') === 0) {
				path = url.substr('/index.php')
			}
		} else if (url.indexOf(`${location.protocol}//${location.hostname}/`.length) === 0) {
			path = url.substr(`${location.protocol}//${location.hostname}/`)
		}
		if (!path.match(/profiler/)) {
			const stackElement = {
				error: false,
				url,
				method,
				type: 'xhr',
				start: new Date(),
			}

			this.addEventListener('readystatechange', function() {
				if (self.readyState === 4) {
					stackElement.duration = new Date() - stackElement.start
					stackElement.error = self.status < 200 || self.status >= 400
					stackElement.statusCode = self.status
					extractHeaders(self, stackElement)
					store.commit('addStackElement', stackElement)
				}
			}, false)
		}
		proxied.apply(this, Array.prototype.slice.call(arguments))
	}
}

export default instance
