/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { createAppConfig } from '@nextcloud/vite-config'
import { readFileSync } from 'node:fs'
import { join } from 'node:path'

const isProduction = process.env.NODE_ENV === 'production'
const plyrIcons = readFileSync(
	join(__dirname, 'node_modules', 'plyr', 'dist', 'plyr.svg'),
	{ encoding: 'utf8' },
)

export default createAppConfig({
	main: 'src/main.js',
	toolbar: 'src/toolbar.js',
}, {
	replace: {
		PLYR_ICONS: JSON.stringify(plyrIcons),
	},
    resolve: {
      alias: {
        vue: '@vue/compat'
      }
	},
	minify: isProduction,
	// create REUSE compliant license information for compiled assets
	extractLicenseInformation: {
		includeSourceMaps: true,
	},
	// disable BOM because we already have the `.license` files
	thirdPartyLicense: false,
	// ensure that every JS entry point has a matching CSS file
	createEmptyCSSEntryPoints: true,
	// Make sure we also clear the CSS directory
	emptyOutputDirectory: {
		additionalDirectories: ['css'],
	},
})
