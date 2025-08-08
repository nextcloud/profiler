// SPDX-FileCopyrightText: 2019 Marco Ambrosini <marcoambrosini@pm.me>
// SPDX-FileCopyrightText: 2022 Carl Schwan <carl@carlschwan.eu>
// SPDX-License-Identifier: AGPL-3.0-or-later

import { createRouter, createWebHistory } from 'vue-router'
import DatabaseProfilerView from '../views/DatabaseProfilerView.vue'
import LoadingView from '../views/LoadingView.vue'
import RequestView from '../views/RequestView.vue'
import LdapView from '../views/LdapView.vue'
import CacheView from '../views/CacheView.vue'
import EventsView from '../views/EventsView.vue'
import { getRootUrl, generateUrl } from '@nextcloud/router'

const webRootWithIndexPHP = getRootUrl() + '/index.php'
const doesURLContainIndexPHP = window.location.pathname.startsWith(webRootWithIndexPHP)
const base = generateUrl('/', {}, {
	noRewrite: doesURLContainIndexPHP,
})

const routes = [
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
]

export default createRouter({
	history: createWebHistory(base),
	routes,
	// if index.php is in the url AND we got this far, then it's working:
	// let's keep using index.php in the url
	linkActiveClass: 'active',
})
