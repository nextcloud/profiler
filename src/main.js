// SPDX-FileCopyrightText: 2022 Carl Schwan <carl@carlschwan.eu>
// SPDX-License-Identifier: AGPL-3.0-or-later

import { createApp } from 'vue'
import App from './views/Profiler.vue'
import router from './router/router.js'
import store from './store.js'

const app = createApp(App)

app.use(store)
app.use(router)
app.mount('#profiler')
