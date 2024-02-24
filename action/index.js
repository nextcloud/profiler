// SPDX-FileCopyrightText: 2022 Robin Appelman <robin@icewind.nl>
// SPDX-License-Identifier: AGPL-3.0-or-later

const { spawn } = require('child_process')
const { rm } = require('fs/promises')

const core = require('@actions/core')

// Default shell invocation used by GitHub Action 'run:'
const shellArgs = ['--noprofile', '--norc', '-eo', 'pipefail', '-c']

const cmd = function(command) {
	return new Promise((resolve, reject) => {
		let stdout = ''
		const cmd = spawn('bash', [...shellArgs, command])

		cmd.stdout.on('data', (data) => {
			stdout = stdout + data
		})
		cmd.stderr.on('data', (data) => {
			console.error(data.toString('utf8'))
		})

		cmd.on('error', error => {
			reject(error)
		})

		cmd.on('close', code => {
			resolve({
				code,
				stdout,
			})
		})
	})
}

const occ = async function(command) {
	return await cmd(`./occ ${command}`)
}

const ensureApp = async function(profilerBranch) {
	const { stdout } = await occ('app:list')
	if (!stdout.includes('profiler')) {
		console.log('installing profiler')
		if (profilerBranch) {
			await cmd('git clone -b ' + profilerBranch + ' https://github.com/nextcloud/profiler apps/profiler')
		} else {
			await cmd('git clone https://github.com/nextcloud/profiler apps/profiler')
		}

		await occ('app:enable --force profiler')
		const { code, stdout } = await occ('profiler:enable')
		if (code !== 0) {
			console.log(stdout)
			throw new Error('Failed to enable profiler')
		}
	}
}

const run = async function(command, output, compare, profilerBranch) {
	await ensureApp(profilerBranch)

	// warmup
	await cmd(command)

	try {
		await rm('data/__profiler', { recursive: true })
	} catch (e) {}

	console.log('running command')
	const cmdOut = await cmd(command)

	console.log(cmdOut.stdout.toString('utf8'))

	if (cmdOut.code !== 0) {
		throw new Error(`Process completed with exit code ${cmdOut.code}.`)
	}
	console.log('processing result')

	const { stdout } = await occ('profiler:list')
	console.log(stdout.toString('utf8'))

	await occ(`profiler:export > ${output}`)

	if (compare) {
		const { code, stdout } = await occ(`profiler:compare ${compare} ${output}`)
		core.setOutput('compare', stdout)

		if (code !== 0) {
			throw new Error('Possible performance regression detected')
		}
	}
}

run(core.getInput('run'), core.getInput('output'), core.getInput('compare-with'), core.getInput('profiler-branch'))
	.catch(error => core.setFailed(error.message))
