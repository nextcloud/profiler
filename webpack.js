const fs = require('fs')
const path = require('path')
const webpack = require('webpack')

const webpackConfig = require('@nextcloud/webpack-vue-config')
const webpackRules = require('@nextcloud/webpack-vue-config/rules')

const BabelLoaderExcludeNodeModulesExcept = require('babel-loader-exclude-node-modules-except')

const isTesting = !!process.env.TESTING

if (isTesting) {
	console.debug('TESTING MODE ENABLED')
}

// vue-plyr uses .mjs file
webpackRules.RULE_JS.test = /\.m?js$/
webpackRules.RULE_JS.exclude = BabelLoaderExcludeNodeModulesExcept([
	'camelcase',
	'fast-xml-parser',
	'hot-patcher',
	'semver',
	'vue-plyr',
	'webdav',
	'toastify-js',
])

// Replaces rules array
webpackConfig.module.rules = Object.values(webpackRules)

webpackConfig.entry.main = path.resolve(path.join('src', 'main.js'))
webpackConfig.entry.toolbar = path.resolve(path.join('src', 'toolbar.js'))

// Add custom plugins
webpackConfig.plugins.push(...[
	new webpack.DefinePlugin({
		isTesting,
	}),
])

module.exports = webpackConfig
