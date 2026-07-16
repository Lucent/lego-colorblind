/* Main app. DOM strategy (see Ptable): figures and design articles are
   built ONCE per set load; the type/severity/lighting controls recompute
   scores and regroup by MOVING those nodes and updating CSS order and
   swatch colors in place. No re-render, no diffing. */

import { simulate } from "./simulate.mjs";
import { labFromSrgb } from "./color.mjs";
import { similarGroups } from "./groups.mjs";
import { attach } from "./autocomplete.mjs";

const $ = id => document.getElementById(id);
const form = document.querySelector("form");

let colors = {};      // color id → {name, rgb: [r,g,b] pre-composited, trans}
let designs = [];     // {article, parts: [{colorId, figure}], score}

/* Old bookmarked URLs used full names; map them onto type+severity. */
const LEGACY_TYPES = {
	protanopia: ["protan", 100], protanomaly: ["protan", 60], "protanopia 2": ["protan", 100],
	deuteranopia: ["deutan", 100], deuteranomaly: ["deutan", 60], "deuteranopia 2": ["deutan", 100],
	tritanopia: ["tritan", 100], tritanomaly: ["tritan", 60], "tritanopia 2": ["tritan", 100],
	achromatopsia: ["achromat", 100], achromatomaly: ["achromat", 60]
};

function h4(text) {
	const el = document.createElement("h4");
	el.textContent = text;
	return el;
}

function controls() {
	return {
		type: $("Blindness").value,
		severity: $("Severity").valueAsNumber / 100,
		darken: Number($("Lighting").value)
	};
}

function cleanSetNumber(set) {
	set = set.trim();
	return set.includes("-") ? set : set + "-1";
}

function setNumbers() {
	return [...new Set($("Set").value.split(/[, ]/).map(s => s.trim()).filter(Boolean))]
		.map(cleanSetNumber);
}

function rgbOf(id) {
	return colors[id]?.rgb ?? [128, 128, 128];
}

function hex([r, g, b]) {
	return "#" + [r, g, b].map(v => Math.round(v).toString(16).padStart(2, "0")).join("");
}

/* ---- realtime recompute: move nodes, update swatches, reorder ---- */

function recompute() {
	const { type, severity, darken } = controls();
	$("SeverityOut").value = Math.round(severity * 100) + "%";

	const labs = new Map(), simHex = new Map();
	for (const id of new Set(designs.flatMap(d => d.parts.map(p => p.colorId)))) {
		const sim = simulate(type, severity, rgbOf(id));
		const lab = labFromSrgb(sim);
		lab[0] = Math.max(0, lab[0] - darken);
		labs.set(id, lab);
		simHex.set(id, hex(sim));
	}

	let anyShown = false;
	for (const design of designs) {
		for (const part of design.parts)
			part.figure.querySelector(".swatch").style.backgroundColor = simHex.get(part.colorId);

		const ids = design.parts.map(p => p.colorId);
		const groups = similarGroups(ids, labs, id => colors[id]?.trans ?? false);
		design.wrapper.hidden = !groups.length;
		if (!groups.length) continue;
		anyShown = true;

		const inner = design.article;
		while (inner.children.length > groups.length) inner.lastElementChild.remove();
		while (inner.children.length < groups.length) inner.append(document.createElement("section"));

		design.score = groups[0].dE;
		design.wrapper.style.order = Math.round(design.score * 100);
		groups.forEach((group, i) => {
			const section = inner.children[i];
			section.dataset.de = "ΔE " + group.dE.toFixed(1);
			section.append(...group.members.map(m => design.parts[m].figure));
		});
	}

	if (designs.length)
		$("Message").replaceChildren(...anyShown ? [] :
			[h4("No parts in this set occur in similar, confusing colors for the chosen color vision type, severity, and lighting.")]);
	syncUrl();
}

function syncUrl() {
	const { type, severity, darken } = controls();
	const params = new URLSearchParams({ set: setNumbers().join(","), type,
		severity: Math.round(severity * 100), lighting: darken });
	history.replaceState(null, "", setNumbers().length ? "?" + params : ".");
}

/* ---- one-time DOM construction per set load ---- */

function buildFigure(part) {
	const figure = document.createElement("figure");
	const img = document.createElement("img");
	if (part.part.part_img_url) img.src = part.part.part_img_url;
	img.alt = "";
	img.loading = "lazy";
	const caption = document.createElement("figcaption");
	const swatch = document.createElement("i");
	swatch.className = "swatch";
	swatch.title = "simulated color";
	const extra = part.extra ? `+${part.extra}` : "";
	caption.append(swatch, ` ${colors[part.color.id]?.name ?? part.color.id} (${part.quantity}`);
	if (extra) {
		const sup = document.createElement("sup");
		sup.textContent = extra;
		caption.append(sup);
	}
	caption.append(")");
	figure.append(img, caption);
	return figure;
}

function buildHeader(descr, counts) {
	const h2 = document.createElement("h2");
	const img = document.createElement("img");
	img.src = descr.set_img_url;
	img.alt = "";
	const span = document.createElement("span");
	const big = document.createElement("big");
	big.textContent = descr.set_num;
	const a = document.createElement("a");
	a.href = descr.set_url;
	a.textContent = `${descr.name} (${descr.year})`;
	const sup = document.createElement("sup");
	sup.textContent = "+" + counts[1];
	span.append(big, document.createElement("br"), a, document.createElement("br"),
		`${counts[0]}`, sup, " parts");
	h2.append(img, span);
	return h2;
}

async function fetchJson(url) {
	const res = await fetch(url);
	if (!res.ok) throw new Error(res.status === 404 ? "notfound" : "upstream");
	return res.json();
}

async function loadSets() {
	const numbers = setNumbers();
	designs = [];
	$("Headers").replaceChildren();
	$("Results").replaceChildren();
	$("Message").replaceChildren();
	if (!numbers.length) {
		const p = document.createElement("p");
		const a = document.createElement("a");
		a.href = "?set=10197-1";
		a.textContent = "Try a sample";
		p.append("Does colorblindness make it difficult to see whether parts in a set are actually different colors?",
			document.createElement("br"), document.createElement("br"),
			"Enter a set name or number to see a list of parts (and their count) in colors similar enough" +
			" to be confusing for the selected color deficiency. ", a, ".");
		$("Message").replaceChildren(p);
		return;
	}

	const progress = $("Progress");
	progress.hidden = false;
	progress.value = 5;

	try {
		colors = await fetchJson("data/colors.json");
		for (const c of Object.values(colors)) {
			c.rgb = [1, 3, 5].map(i => parseInt(c.rgb.slice(i - 1, i + 1), 16));
			// composite transparent colors over a white background
			if (c.trans) c.rgb = c.rgb.map(v => 255 + (v - 255) * 0.5);
		}

		const byElement = new Map();
		let done = 0;
		for (const number of numbers) {
			const [descr, inventory] = await Promise.all([
				fetchJson(`api/set/${encodeURIComponent(number)}`),
				fetchJson(`api/set/${encodeURIComponent(number)}/parts`)
			]);
			progress.value = 5 + 90 * ++done / numbers.length;

			if (!inventory.results.length) {
				$("Message").replaceChildren(h4(`No inventory available for ${descr.name} yet, sorry.`));
				return;
			}
			let regular = 0, extra = 0;
			for (const part of inventory.results) {
				part.is_spare ? extra += part.quantity : regular += part.quantity;
				if (part.element_id === null) continue;
				const known = byElement.get(part.element_id);
				if (!part.is_spare)
					known ? known.quantity += part.quantity : byElement.set(part.element_id, part);
				else if (known)
					known.extra = (known.extra ?? 0) + part.quantity;
			}
			$("Headers").append(buildHeader(descr, [regular, extra]));
		}

		const byDesign = Map.groupBy(byElement.values(), part => part.part.part_num);
		for (const parts of byDesign.values()) {
			if (parts.length < 2) continue;
			// h3 stays a SIBLING of article — it renders as a tab above the block
			const wrapper = document.createElement("div");
			wrapper.className = "design";
			wrapper.hidden = true;
			const h3 = document.createElement("h3");
			h3.textContent = parts[0].part.name;
			const article = document.createElement("article");
			wrapper.append(h3, article);
			$("Results").append(wrapper);
			designs.push({
				wrapper, article,
				parts: parts.map(part => ({ colorId: part.color.id, figure: buildFigure(part) }))
			});
		}

		if (!designs.length)
			$("Message").replaceChildren(h4("Each part design in this set occurs in a unique color."));
		recompute();
	} catch (e) {
		$("Message").replaceChildren(h4(e.message === "notfound"
			? "Set not found" : "Problem fetching set data, please try again."));
	} finally {
		progress.hidden = true;
	}
}

/* ---- wiring ---- */

function applyParams() {
	const params = new URLSearchParams(location.search);
	$("Set").value = (params.get("set") ?? "").split(/[, ]/).filter(Boolean).join(",");
	// default: deuteranomaly, the ~5%-of-men case (deutan at 60% severity)
	let type = params.get("type") ?? "deutan", severity = params.get("severity");
	if (LEGACY_TYPES[type]) [type, severity] = [LEGACY_TYPES[type][0], severity ?? LEGACY_TYPES[type][1]];
	$("Blindness").value = [...$("Blindness").options].some(o => o.value === type) ? type : "deutan";
	$("Severity").value = severity ?? 60;
	$("Lighting").value = params.get("lighting") === "50" ? "50" : "0";
}

form.addEventListener("submit", e => {
	e.preventDefault();
	syncUrl();
	loadSets();
});

for (const id of ["Blindness", "Severity", "Lighting"])
	$(id).addEventListener("input", recompute);

window.addEventListener("popstate", () => { applyParams(); loadSets(); });

attach($("Set"), $("Sets"), () => loadSets());

applyParams();
loadSets();
