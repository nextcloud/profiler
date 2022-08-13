// SPDX-FileCopyrightText: 2022 Carl Schwan <carl@carlschwan.eu>
// SPDX-License-Identifier: AGPL-3.0-or-later

import Vue from 'vue'
import App from './views/Profiler.vue'
import VueRouter from 'vue-router'
import Vuex from 'vuex'
import router from './router/router.js'
import store from './store.js'

// bind to window
Vue.prototype.OC = OC
Vue.prototype.t = t

Vue.use(VueRouter)
Vue.use(Vuex)

const instance = new Vue({
	el: '#profiler',
	router,
	store,
	render: h => h(App),
})

export default instance
