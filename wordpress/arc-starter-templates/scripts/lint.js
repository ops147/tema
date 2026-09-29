#!/usr/bin/env node
/* ARC Starter Templates — dependency-free PHP syntax check.
 *
 * Runs `php -l` over every PHP file in the plugin (node_modules excluded).
 * The PHP binary resolves from PHP_BINARY env var, then PATH, then common
 * Laragon locations on Windows.
 *
 * Usage: node scripts/lint.js   (npm run lint)
 */
'use strict';

const fs = require('fs');
const path = require('path');
const { execFileSync, spawnSync } = require('child_process');

const ROOT = path.resolve(__dirname, '..');

function* walk(dir) {
	for (const ent of fs.readdirSync(dir, { withFileTypes: true })) {
		if (ent.name === 'node_modules' || ent.name === '.git') continue;
		const p = path.join(dir, ent.name);
		if (ent.isDirectory()) yield* walk(p);
		else if (ent.name.endsWith('.php')) yield p;
	}
}

function findPhp() {
	const candidates = [];
	if (process.env.PHP_BINARY) candidates.push(process.env.PHP_BINARY);
	candidates.push('php');
	if (process.platform === 'win32') {
		const laragonPhp = 'C:\\laragon\\bin\\php';
		if (fs.existsSync(laragonPhp)) {
			for (const dir of fs.readdirSync(laragonPhp).sort().reverse()) {
				candidates.push(path.join(laragonPhp, dir, 'php.exe'));
			}
		}
	}
	for (const bin of candidates) {
		try {
			execFileSync(bin, ['-v'], { stdio: 'pipe' });
			return bin;
		} catch (e) { /* try next */ }
	}
	return null;
}

const php = findPhp();
if (!php) {
	console.error('PHP not found — set PHP_BINARY to your php executable.');
	process.exit(2);
}
console.log('Using ' + php);

let files = 0, failed = 0;
for (const file of walk(ROOT)) {
	files++;
	const res = spawnSync(php, ['-l', file], { encoding: 'utf8' });
	if (res.status !== 0) {
		failed++;
		console.error(path.relative(ROOT, file) + ':\n' + (res.stdout || '') + (res.stderr || ''));
	}
}

if (failed) {
	console.error(failed + ' of ' + files + ' files failed lint.');
	process.exit(1);
}
console.log('OK — ' + files + ' PHP files, no syntax errors.');
