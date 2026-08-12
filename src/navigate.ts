/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createApp } from 'vue'
import NavigateView from './NavigateView.vue'

const app = createApp(NavigateView)
app.mount('#content')
