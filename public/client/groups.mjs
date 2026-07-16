/* Union-find grouping of confusable colors. A pair is confusable when
   the CIEDE2000 distance between their simulated appearances is under
   THRESHOLD — except across the transparent/solid boundary, since a
   trans and an opaque brick are distinguishable by more than hue.
   Returns groups sorted most confusing first (smallest min ΔE). */

import { deltaE2000 } from "./color.mjs";

export const THRESHOLD = 13;

export function similarGroups(colorIds, labs, isTrans) {
	const parent = colorIds.map((_, i) => i);
	const find = i => parent[i] === i ? i : (parent[i] = find(parent[i]));

	const pairs = [];
	for (let x = 0; x < colorIds.length; x++)
		for (let y = x + 1; y < colorIds.length; y++) {
			if (isTrans(colorIds[x]) !== isTrans(colorIds[y])) continue;
			const d = deltaE2000(labs.get(colorIds[x]), labs.get(colorIds[y]));
			if (d < THRESHOLD) {
				parent[find(x)] = find(y);
				pairs.push([x, d]);
			}
		}

	const groups = new Map();
	for (const [x, d] of pairs) {
		const root = find(x);
		if (!groups.has(root)) groups.set(root, { members: [], dE: Infinity });
		groups.get(root).dE = Math.min(groups.get(root).dE, d);
	}
	for (let i = 0; i < colorIds.length; i++)
		groups.get(find(i))?.members.push(i);

	return [...groups.values()].sort((a, b) => a.dE - b.dE);
}
