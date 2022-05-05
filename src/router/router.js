// SPDX-FileCopyrightText: 2019 Marco Ambrosini <marcoambrosini@pm.me>
// SPDX-FileCopyrightText: 2022 Carl Schwan <carl@carlschwan.eu>
// SPDX-License-Identifier: AGPL-3.0-or-later

import Vue from 'vue'
import VueRouter from 'vue-router'
import DatabaseProfilerView from '../views/DatabaseProfilerView'
import LoadingView from '../views/LoadingView'
import RequestView from '../views/RequestView'
import LdapView from '../views/LdapView'
import CacheView from '../views/CacheView'
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
		{
			path: '/apps/profiler/profiler/cache/:token/',
			name: 'cache',
			component: CacheView,
			props: true,
		},
	],
})
