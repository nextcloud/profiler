// SPDX-FileCopyrightText: 2022 Carl Schwan <carl@carlschwan.eu>
// SPDX-License-Identifier: AGPL-3.0-or-later

import { createPinia } from 'pinia'
import { createApp } from 'vue'
import App from './views/Profiler.vue'
import router from './router/router.js'

const app = createApp(App)

app.use(createPinia())
app.use(router)
app.mount('#profiler')
