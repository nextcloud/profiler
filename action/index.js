// SPDX-FileCopyrightText: 2022 Robin Appelman <robin@icewind.nl>
// SPDX-License-Identifier: AGPL-3.0-or-later

const { spawn } = require('child_process')
const { rm } = require('fs/promises')

const core = require('@actions/core')

// Default shell invocation used by GitHub Action 'run:'
const shellArgs = ['--noprofile', '--norc', '-eo', 'pipefail', '-c']

function cmd(command) {
	return new Promise((resolve, reject) => {
		let stdout = ""
		const cmd = spawn('bash', [...shellArgs, command])

		cmd.stdout.on('data', (data) => {
			stdout = stdout + data
		})
		cmd.stderr.on('data', (data) => {
			console.error(data);
		})

		cmd.on('error', error => {
			reject(error)
		})

		cmd.on('close', code => {
			resolve({
				code: code,
				stdout,
			})
		})
	})
}

async function occ(command) {
	return await cmd(`./occ ${command}`)
}

async function ensureApp(profilerBranch) {
	let {stdout} = await occ('app:list');
	if (!stdout.includes('profiler')) {
		console.log('installing profiler')
		if (profilerBranch) {
			await cmd(`git clone -b ` + profilerBranch + ` https://github.com/nextcloud/profiler apps/profiler`)
		} else {
			await cmd(`git clone https://github.com/nextcloud/profiler apps/profiler`)
		}
		await occ(`app:enable --force profiler`)
		let {code, stdout} = await occ(`profiler:enable`);
		if (code !== 0) {
			console.log(stdout);
			throw new Error('Failed to enable profiler');
		}
	}
}

async function run (command, output, compare, profilerBranch) {
	await ensureApp(profilerBranch)

	// warmup
	await cmd(command)

	try {
		await rm('data/profiler', {recursive: true})
	} catch (e) {}

	console.log('running command')
	let cmdOut = await cmd(command)

	console.log(cmdOut.stdout);


	if (cmdOut.code !== 0) {
		throw new Error(`Process completed with exit code ${cmdOut.code}.`)
	}
	console.log('processing result')

	let {stdout} = await occ('profiler:list');
	console.log(stdout);

	await occ(`profiler:export > ${output}`)

	if (compare) {
		let {code, stdout} = await occ(`profiler:compare ${compare} ${output}`)
		core.setOutput('compare', stdout)

		if (code !== 0) {
			throw new Error(`Possible performance regression detected`)
		}
	}
}

run(core.getInput('run'), core.getInput('output'), core.getInput('compare-with'), core.getInput('profiler-branch'))
	.catch(error => core.setFailed(error.message))
