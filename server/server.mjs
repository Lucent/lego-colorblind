/* Zero-dependency server: static files, a caching proxy for the two
   Rebrickable set endpoints (the API key stays server-side), and a
   self-refreshing pipeline that turns Rebrickable's published CSV dumps
   into the colors/autocomplete JSON the client uses. Everything derived
   lives in CACHE_DIR; the repo mounts read-only. */

import http from "node:http";
import path from "node:path";
import { fileURLToPath } from "node:url";
import { gunzipSync } from "node:zlib";
import { mkdir, readFile, writeFile, rename, stat } from "node:fs/promises";
import { createReadStream } from "node:fs";

const ROOT = path.dirname(path.dirname(fileURLToPath(import.meta.url)));
const PUBLIC = path.join(ROOT, "public");
const CACHE = process.env.CACHE_DIR ?? path.join(ROOT, "cache");
const PORT = process.env.PORT ?? 8080;
const API_KEY = process.env.REBRICKABLE_API_KEY;

const API_TTL = 28 * 86400 * 1000;      // set inventories change rarely
const DATA_TTL = 7 * 86400 * 1000;      // colors/sets CSV dumps

const MIME = {
	".html": "text/html; charset=utf-8", ".css": "text/css", ".js": "text/javascript",
	".mjs": "text/javascript", ".json": "application/json", ".svg": "image/svg+xml",
	".ico": "image/x-icon", ".png": "image/png"
};

await mkdir(path.join(CACHE, "api"), { recursive: true });

/* ---- Rebrickable CSV dumps → derived JSON ---- */

function parseCsv(text) {
	const rows = [];
	let row = [], field = "", quoted = false;
	for (let i = 0; i < text.length; i++) {
		const c = text[i];
		if (quoted) {
			if (c === '"' && text[i + 1] === '"') { field += '"'; i++; }
			else if (c === '"') quoted = false;
			else field += c;
		} else if (c === '"') quoted = true;
		else if (c === ",") { row.push(field); field = ""; }
		else if (c === "\n") { row.push(field); rows.push(row); row = []; field = ""; }
		else if (c !== "\r") field += c;
	}
	if (field !== "" || row.length) { row.push(field); rows.push(row); }
	return rows.slice(1); // drop header
}

async function fresh(file, ttl) {
	const s = await stat(file).catch(() => null);
	return s && Date.now() - s.mtimeMs < ttl;
}

async function writeAtomic(file, data) {
	await writeFile(file + ".tmp", data);
	await rename(file + ".tmp", file);
}

async function fetchCsv(name) {
	const res = await fetch(`https://cdn.rebrickable.com/media/downloads/${name}.csv.gz`);
	if (!res.ok) throw new Error(`${name}.csv.gz: HTTP ${res.status}`);
	return parseCsv(gunzipSync(Buffer.from(await res.arrayBuffer())).toString("utf8"));
}

async function refreshData() {
	const colorsFile = path.join(CACHE, "colors.json");
	if (!await fresh(colorsFile, DATA_TTL)) {
		const colors = {};
		for (const [id, name, rgb, trans] of await fetchCsv("colors"))
			colors[id] = { name, rgb, trans: trans === "t" || trans === "True" };
		await writeAtomic(colorsFile, JSON.stringify(colors));
		console.log(`refreshed colors.json (${Object.keys(colors).length} colors)`);
	}

	const setsFile = path.join(CACHE, "sets.json");
	if (!await fresh(setsFile, DATA_TTL)) {
		const sets = (await fetchCsv("sets"))
			.map(([num, name, year, , parts]) => [Number(parts), `${num} ${name} (${year})`])
			.sort((a, b) => b[0] - a[0])
			.map(([, label]) => label);
		await writeAtomic(setsFile, JSON.stringify(sets));
		console.log(`refreshed sets.json (${sets.length} sets)`);
	}
}

/* ---- Rebrickable API proxy with file cache ---- */

async function rebrickable(url) {
	const res = await fetch(url, { headers: { Authorization: `key ${API_KEY}` } });
	if (!res.ok) throw Object.assign(new Error(`upstream HTTP ${res.status}`), { status: res.status });
	return res.json();
}

// Slim inventories to the fields the client renders; follow pagination
// so sets with >1000 part lines arrive whole (the PHP version dropped them).
async function fetchSet(setNum, parts) {
	const base = `https://rebrickable.com/api/v3/lego/sets/${setNum}/`;
	if (!parts) {
		const d = await rebrickable(base);
		return { set_num: d.set_num, name: d.name, year: d.year,
		         set_img_url: d.set_img_url, set_url: d.set_url, num_parts: d.num_parts };
	}
	let url = base + "parts/?page_size=1000", results = [];
	while (url) {
		const page = await rebrickable(url);
		results.push(...page.results.map(p => ({
			element_id: p.element_id, quantity: p.quantity, is_spare: p.is_spare,
			part: { part_num: p.part.part_num, name: p.part.name, part_img_url: p.part.part_img_url },
			color: { id: p.color.id }
		})));
		url = page.next;
	}
	return { results };
}

async function apiSet(setNum, parts) {
	const cacheFile = path.join(CACHE, "api", setNum + (parts ? ".parts" : "") + ".json");
	if (await fresh(cacheFile, API_TTL))
		return readFile(cacheFile);
	const data = JSON.stringify(await fetchSet(setNum, parts));
	await writeAtomic(cacheFile, data);
	return data;
}

/* ---- HTTP ---- */

function send(res, status, body, type = "application/json", maxAge = 3600) {
	res.writeHead(status, { "Content-Type": type, "Cache-Control": `max-age=${maxAge}` });
	res.end(body);
}

const server = http.createServer(async (req, res) => {
	const url = new URL(req.url, "http://localhost");
	try {
		const api = url.pathname.match(/^\/api\/set\/([a-zA-Z0-9._-]{1,32})(\/parts)?$/);
		if (api) {
			send(res, 200, await apiSet(api[1], !!api[2]));
			return;
		}
		if (url.pathname === "/data/colors.json" || url.pathname === "/data/sets.json") {
			const file = path.join(CACHE, path.basename(url.pathname));
			// cold boot: wait for the initial refresh instead of 404ing
			const body = await readFile(file).catch(async () => { await refreshing; return readFile(file); });
			send(res, 200, body);
			return;
		}

		let file = path.normalize(path.join(PUBLIC, url.pathname === "/" ? "index.html" : url.pathname));
		const ext = path.extname(file);
		if (!file.startsWith(PUBLIC + path.sep) || !MIME[ext]) {
			send(res, 404, '{"detail":"not found"}');
			return;
		}
		await stat(file);
		res.writeHead(200, { "Content-Type": MIME[ext],
			"Cache-Control": ext === ".html" ? "no-cache" : "max-age=3600" });
		createReadStream(file).pipe(res);
	} catch (e) {
		if (e.status === 404 || e.code === "ENOENT")
			send(res, 404, '{"detail":"not found"}', "application/json", 60);
		else {
			console.error(req.url, e.message);
			send(res, 502, '{"detail":"upstream error"}', "application/json", 0);
		}
	}
});

let refreshing = refreshData().catch(e => console.error("refresh:", e.message));
setInterval(() => refreshing = refreshData().catch(e => console.error("refresh:", e.message)), 86400 * 1000);

server.listen(PORT, () => console.log(`listening on :${PORT}, cache in ${CACHE}`));
