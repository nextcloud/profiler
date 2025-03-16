/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
const babelConfig = require('@nextcloud/babel-config')

babelConfig.plugins.push('@babel/plugin-proposal-class-properties')

module.exports = babelConfig
