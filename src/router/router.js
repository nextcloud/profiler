/**
 * @copyright Copyright (c) 2019 Marco Ambrosini <marcoambrosini@pm.me>
 *
 * @author Marco Ambrosini <marcoambrosini@pm.me>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

import Vue from 'vue'
import VueRouter from 'vue-router'
import DatabaseProfilerView from '../views/DatabaseProfilerView'
import LoadingView from '../views/LoadingView'
import RequestView from '../views/RequestView'
import LdapView from '../views/LdapView'
import EventsView from '../views/EventsView'
import { getRootUrl, generateUrl } from '@nextcloud/router'

Vue.use(VueRouter)

const webRootWithIndexPHP = getRootUrl() + '/index.php'
const doesURLContainIndexPHP = window.location.pathname.startsWith(webRootWithIndexPHP)
const base = generateUrl('/', {}, {
	noRewrite: doesURLContainIndexPHP,
})

export default new VueRouter({
	mode: 'history',
	base,
	// if index.php is in the url AND we got this far, then it's working:
	// let's keep using index.php in the url
	linkActiveClass: 'active',
	routes: [
		{
			path: '/apps/profiler/',
			name: 'loading',
			component: LoadingView,
			props: true,
		},
		{
			path: '/apps/profiler/profiler/db/:token/',
			name: 'db',
			component: DatabaseProfilerView,
			props: true,
		},
		{
			path: '/apps/profiler/profiler/http/:token/',
			name: 'http',
			component: RequestView,
			props: true,
		},
		{
			path: '/apps/profiler/profiler/event/:token/',
			name: 'event',
			component: EventsView,
			props: true,
		},
		{
			path: '/apps/profiler/profiler/ldap/:token/',
			name: 'ldap',
			component: LdapView,
			props: true,
		},
	],
})
