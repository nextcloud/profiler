const { spawn } = require('child_process')
const { Transform } = require('stream')
const { Buffer } = require('buffer')
const { rm } = require('fs/promises')

const core = require('@actions/core')

// Default shell invocation used by GitHub Action 'run:'
const shellArgs = ['--noprofile', '--norc', '-eo', 'pipefail', '-c']

class RecordStream extends Transform {
	constructor () {
		super()
		this._data = Buffer.from([])
	}

	get output () {
		return this._data
	}

	_transform (chunk, encoding, callback) {
		this._data = Buffer.concat([this._data, chunk])
		callback(null, chunk)
	}
}

function cmd(command) {
	return new Promise((resolve, reject) => {
		const outRec = new RecordStream()
		const errRec = new RecordStream()

		const cmd = spawn('bash', [...shellArgs, command])

		cmd.stdout.pipe(outRec)
		cmd.stderr.pipe(errRec)

		cmd.on('error', error => {
			reject(error)
		})

		cmd.on('close', code => {
			resolve({
				code: code,
				stdout: outRec.output.toString(),
				stderr: errRec.output.toString()
			})
		})
	})
}

async function occ(command) {
	return await cmd(`./occ ${command}`)
}

async function ensureApp() {
	let {stdout} = await occ('app:list');
	if (!stdout.includes('profiler')) {
		console.log('installing profiler')
		await cmd(`git clone -b cli https://github.com/nextcloud/profiler apps/profiler`)
		await occ(`app:enable --force profiler`)
		await occ(`profiler:enable`)
	}
}

async function run (command, output, compare) {
	await ensureApp()

	// warmup
	await cmd(command)

	try {
		await rm('data/profiler', {recursive: true})
	} catch (e) {}

	let {code} = await cmd(command)


	if (code !== 0) {
		throw new Error(`Process completed with exit code ${code}.`)
	}

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

run(core.getInput('run'), core.getInput('output'), core.getInput('compare-with'))
	.catch(error => core.setFailed(error.message))
