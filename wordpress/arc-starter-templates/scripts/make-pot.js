#!/usr/bin/env node
/* ARC Starter Templates — dependency-free .pot generator.
 *
 * Scans every PHP file for gettext calls on the plugin text domain and
 * writes languages/arc-starter-templates.pot. Covers the call shapes used
 * in this codebase: __(), _e(), _x(), _ex(), _n(), _nx(), the esc_*()
 * variants and the *_noop() forms. Dynamic strings (variables) are
 * skipped, matching wp i18n make-pot behaviour.
 *
 * Usage: node scripts/make-pot.js
 */
'use strict';

const fs = require('fs');
const path = require('path');

const ROOT = path.resolve(__dirname, '..');
const DOMAIN = 'arc-starter-templates';
const OUT = path.join(ROOT, 'languages', DOMAIN + '.pot');

// Function name -> argument positions.
const SIGS = {
	'__': { id: 0, domain: 1 },
	'_e': { id: 0, domain: 1 },
	'_x': { id: 0, ctxt: 1, domain: 2 },
	'_ex': { id: 0, ctxt: 1, domain: 2 },
	'_n': { id: 0, plural: 1, domain: 3 },
	'_nx': { id: 0, plural: 1, ctxt: 3, domain: 4 },
	'esc_html__': { id: 0, domain: 1 },
	'esc_html_e': { id: 0, domain: 1 },
	'esc_html_x': { id: 0, ctxt: 1, domain: 2 },
	'esc_attr__': { id: 0, domain: 1 },
	'esc_attr_e': { id: 0, domain: 1 },
	'esc_attr_x': { id: 0, ctxt: 1, domain: 2 },
	'_n_noop': { id: 0, plural: 1, domain: 2 },
	'_nx_noop': { id: 0, plural: 1, ctxt: 2, domain: 3 },
};

// Longest first so alternation never prefers a suffix of a longer name.
const FN_RE = new RegExp(
	'(^|[^a-zA-Z0-9_])(' + Object.keys(SIGS).sort((a, b) => b.length - a.length).join('|') + ')\\s*\\(',
	'g'
);

function* walk(dir) {
	for (const ent of fs.readdirSync(dir, { withFileTypes: true })) {
		if (ent.name === 'node_modules' || ent.name === '.git') continue;
		const p = path.join(dir, ent.name);
		if (ent.isDirectory()) yield* walk(p);
		else if (ent.name.endsWith('.php')) yield p;
	}
}

// Splits a balanced (...) argument list into raw argument strings.
function splitArgs(src, openIdx) {
	const args = [];
	let depth = 0, start = openIdx + 1, i = start, quote = null;
	for (; i < src.length; i++) {
		const c = src[i];
		if (quote) {
			if (c === '\\') i++;
			else if (c === quote) quote = null;
			continue;
		}
		if (c === "'" || c === '"') { quote = c; continue; }
		if (c === '(' || c === '[') depth++;
		else if (c === ')' || c === ']') {
			if (depth === 0) { args.push(src.slice(start, i)); return { args, end: i }; }
			depth--;
		} else if (c === ',' && depth === 0) {
			args.push(src.slice(start, i));
			start = i + 1;
		}
	}
	return { args, end: i };
}

// Decodes a PHP single/double-quoted literal, or returns null for
// non-literal arguments (variables, function calls…).
function phpString(raw) {
	const m = /^\s*(['"])((?:\\.|(?!\1)[^\\])*)\1\s*$/s.exec(raw);
	if (!m) return null;
	const body = m[2];
	return body.replace(/\\(.)/g, (s, ch) => {
		switch (ch) {
			case 'n': return '\n';
			case 't': return '\t';
			case 'r': return '\r';
			case 'v': return '\v';
			case 'f': return '\f';
			case 'e': return '\x1b';
			default: return ch; // \' \" \\ \$ and anything else unescape to the char.
		}
	});
}

function poEscape(str) {
	return str.replace(/\\/g, '\\\\').replace(/"/g, '\\"').replace(/\t/g, '\\t')
		.replace(/\r/g, '\\r').replace(/\n/g, '\\n');
}

// msgid|ctxt|plural -> { ctxt, msgid, plural, refs:Set }
const entries = new Map();

for (const file of walk(ROOT)) {
	const rel = path.relative(ROOT, file).replace(/\\/g, '/');
	const src = fs.readFileSync(file, 'utf8');
	FN_RE.lastIndex = 0;
	let m;
	while ((m = FN_RE.exec(src))) {
		const fn = m[2];
		const openIdx = m.index + m[0].length - 1;
		const { args, end } = splitArgs(src, openIdx);
		FN_RE.lastIndex = end; // Skip nested calls inside the argument list.
		const spec = SIGS[fn];
		const lit = (i) => (i === undefined || i >= args.length) ? null : phpString(args[i]);
		const msgid = lit(spec.id);
		const domain = lit(spec.domain);
		if (msgid === null || domain !== DOMAIN) continue;
		const ctxt = lit(spec.ctxt);
		const plural = lit(spec.plural);
		const key = ctxt + '\x00' + msgid + '\x00' + plural;
		if (!entries.has(key)) entries.set(key, { ctxt, msgid, plural, refs: new Set() });
		const line = src.slice(0, m.index).split('\n').length;
		entries.get(key).refs.add(rel + ':' + line);
	}
}

const sorted = [...entries.values()].sort((a, b) => {
	const fa = [...a.refs][0], fb = [...b.refs][0];
	return fa === fb ? a.msgid.localeCompare(b.msgid) : fa.localeCompare(fb);
});

const header = [
	'# Copyright (C) ' + new Date().getFullYear() + ' Ash River Collective',
	'# This file is distributed under the GPL-2.0-or-later.',
	'msgid ""',
	'msgstr ""',
	'"Project-Id-Version: ARC Starter Templates ' + require('../package.json').version + '\\n"',
	'"Report-Msgid-Bugs-To: https://ashrivercollective.com\\n"',
	'"POT-Creation-Date: ' + new Date().toISOString().replace('T', ' ').slice(0, 16) + '+0000\\n"',
	'"PO-Revision-Date: YEAR-MO-DA HO:MI+ZONE\\n"',
	'"Last-Translator: FULL NAME <EMAIL@ADDRESS>\\n"',
	'"Language-Team: LANGUAGE <LL@li.org>\\n"',
	'"MIME-Version: 1.0\\n"',
	'"Content-Type: text/plain; charset=UTF-8\\n"',
	'"Content-Transfer-Encoding: 8bit\\n"',
	'"X-Generator: scripts/make-pot.js (dependency-free)\\n"',
	'"X-Domain: ' + DOMAIN + '\\n"',
	'',
];

const chunks = [header.join('\n')];
for (const e of sorted) {
	const lines = [...e.refs].sort().map((r) => '#: ' + r);
	if (e.ctxt !== null) lines.push('msgctxt "' + poEscape(e.ctxt) + '"');
	lines.push('msgid "' + poEscape(e.msgid) + '"');
	if (e.plural !== null) {
		lines.push('msgid_plural "' + poEscape(e.plural) + '"');
		lines.push('msgstr[0] ""', 'msgstr[1] ""');
	} else {
		lines.push('msgstr ""');
	}
	chunks.push(lines.join('\n'));
}

fs.mkdirSync(path.dirname(OUT), { recursive: true });
fs.writeFileSync(OUT, chunks.join('\n\n') + '\n');
console.log('Wrote ' + path.relative(ROOT, OUT) + ' — ' + sorted.length + ' strings.');
