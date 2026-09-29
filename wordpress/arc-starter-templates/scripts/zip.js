#!/usr/bin/env node
/* ARC Starter Templates — dependency-free distributable ZIP builder.
 *
 * Packs the plugin into dist/arc-starter-templates-<version>.zip using only
 * node core (zlib deflate + hand-rolled ZIP headers). Files are stored
 * under the top-level `arc-starter-templates/` folder as WordPress expects.
 *
 * Exclusions come from .distignore (one pattern per line: `name`, `dir/`,
 * `*.ext`, `path/to/file`). Pass --slim to also drop the bundled payload
 * (templates/*.html + assets/img/) for installs that pull templates from a
 * configured remote repository (ARC_ST_REMOTE_BASE).
 *
 * Usage: node scripts/zip.js [--slim]
 */
'use strict';

const fs = require('fs');
const path = require('path');
const zlib = require('zlib');

const ROOT = path.resolve(__dirname, '..');
const SLUG = path.basename(ROOT);
const SLIM = process.argv.includes('--slim');
const VERSION = require('../package.json').version;
const OUT_DIR = path.join(ROOT, 'dist');
const OUT = path.join(OUT_DIR, SLUG + '-' + VERSION + (SLIM ? '-slim' : '') + '.zip');

/* ------------------------------------------------ .distignore -------- */

function loadIgnore() {
	const file = path.join(ROOT, '.distignore');
	const pats = fs.existsSync(file)
		? fs.readFileSync(file, 'utf8').split(/\r?\n/)
		: [];
	return pats
		.map((l) => l.trim())
		.filter((l) => l && !l.startsWith('#'))
		.map((l) => l.replace(/^\/+/, ''));
}

function matcher(pats) {
	return (rel) => {
		rel = rel.replace(/\\/g, '/');
		const segments = rel.split('/');
		const base = segments[segments.length - 1];
		for (let p of pats) {
			if (p.endsWith('/')) {
				p = p.slice(0, -1);
				if (rel === p || rel.startsWith(p + '/') || rel.includes('/' + p + '/')) return true;
			} else if (p.startsWith('*')) {
				if (base.endsWith(p.slice(1))) return true;
			} else if (p.includes('/')) {
				if (rel === p || rel.startsWith(p + '/')) return true;
			} else if (segments.includes(p)) {
				return true;
			}
		}
		return false;
	};
}

/* ------------------------------------------------ file list ---------- */

function* walk(dir) {
	for (const ent of fs.readdirSync(dir, { withFileTypes: true })) {
		if (ent.name === 'node_modules' || ent.name === '.git' || ent.name === 'dist') continue;
		const p = path.join(dir, ent.name);
		if (ent.isDirectory()) yield* walk(p);
		else yield p;
	}
}

/* ------------------------------------------------ zip primitives ----- */

const CRC_TABLE = (() => {
	const t = new Uint32Array(256);
	for (let n = 0; n < 256; n++) {
		let c = n;
		for (let k = 0; k < 8; k++) c = c & 1 ? 0xedb88320 ^ (c >>> 1) : c >>> 1;
		t[n] = c >>> 0;
	}
	return t;
})();

function crc32(buf) {
	let c = 0xffffffff;
	for (const b of buf) c = CRC_TABLE[(c ^ b) & 0xff] ^ (c >>> 8);
	return (c ^ 0xffffffff) >>> 0;
}

function dosDateTime(d) {
	const time = ((d.getHours() << 11) | (d.getMinutes() << 5) | (d.getSeconds() >> 1)) & 0xffff;
	const date = (((d.getFullYear() - 1980) << 9) | ((d.getMonth() + 1) << 5) | d.getDate()) & 0xffff;
	return { time, date };
}

/* ------------------------------------------------ build -------------- */

const ignored = matcher(loadIgnore());
const slimSkip = (rel) =>
	SLIM && (rel.startsWith('templates/') || rel.startsWith('assets/img/'));

const entries = [];
for (const abs of walk(ROOT)) {
	const rel = path.relative(ROOT, abs).replace(/\\/g, '/');
	if (ignored(rel) || slimSkip(rel)) continue;
	entries.push({ abs, zip: SLUG + '/' + rel });
}
entries.sort((a, b) => (a.zip < b.zip ? -1 : 1));

const chunks = [];
const central = [];
let offset = 0;

for (const e of entries) {
	const nameBuf = Buffer.from(e.zip, 'utf8');
	const data = fs.readFileSync(e.abs);
	const crc = crc32(data);
	const packed = zlib.deflateRawSync(data, { level: 9 });
	const { time, date } = dosDateTime(fs.statSync(e.abs).mtime);

	const local = Buffer.alloc(30);
	local.writeUInt32LE(0x04034b50, 0);
	local.writeUInt16LE(20, 4);            // version needed
	local.writeUInt16LE(0x0800, 6);        // UTF-8 names
	local.writeUInt16LE(8, 8);             // deflate
	local.writeUInt16LE(time, 10);
	local.writeUInt16LE(date, 12);
	local.writeUInt32LE(crc, 14);
	local.writeUInt32LE(packed.length, 18);
	local.writeUInt32LE(data.length, 22);
	local.writeUInt16LE(nameBuf.length, 26);
	local.writeUInt16LE(0, 28);

	chunks.push(local, nameBuf, packed);

	// Buffer.alloc zero-fills: extra len, comment len, disk number, internal
	// and external attrs all stay 0 — only the populated fields are written.
	const cd = Buffer.alloc(46);
	cd.writeUInt32LE(0x02014b50, 0);
	cd.writeUInt16LE(20, 4);               // version made by
	cd.writeUInt16LE(20, 6);               // version needed
	cd.writeUInt16LE(0x0800, 8);           // UTF-8 names
	cd.writeUInt16LE(8, 10);               // deflate
	cd.writeUInt16LE(time, 12);
	cd.writeUInt16LE(date, 14);
	cd.writeUInt32LE(crc, 16);
	cd.writeUInt32LE(packed.length, 20);
	cd.writeUInt32LE(data.length, 24);
	cd.writeUInt16LE(nameBuf.length, 28);
	cd.writeUInt32LE(offset, 42);          // local header offset
	central.push(Buffer.concat([cd, nameBuf]));

	offset += local.length + nameBuf.length + packed.length;
}

const cdBuf = Buffer.concat(central);
const eocd = Buffer.alloc(22);
eocd.writeUInt32LE(0x06054b50, 0);
eocd.writeUInt16LE(entries.length, 8);
eocd.writeUInt16LE(entries.length, 10);
eocd.writeUInt32LE(cdBuf.length, 12);
eocd.writeUInt32LE(offset, 16);
eocd.writeUInt16LE(0, 20);

fs.mkdirSync(OUT_DIR, { recursive: true });
fs.writeFileSync(OUT, Buffer.concat([...chunks, cdBuf, eocd]));

const mb = (fs.statSync(OUT).size / 1048576).toFixed(2);
console.log('Wrote ' + path.relative(ROOT, OUT) + ' — ' + entries.length + ' files, ' + mb + ' MB' + (SLIM ? ' (slim)' : '') + '.');
