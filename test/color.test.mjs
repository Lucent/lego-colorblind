/* Run: node --test test/
   CIEDE2000 checked against Sharma, Wu & Dalal 2005 published test pairs.
   CVD simulation checked for identity at severity 0 and known invariants. */
import { test } from "node:test";
import assert from "node:assert/strict";
import { labFromSrgb, deltaE2000 } from "../public/client/color.mjs";
import { simulate } from "../public/client/simulate.mjs";

// [L1,a1,b1, L2,a2,b2, expected dE2000] — Sharma et al. test data, incl.
// the hue-averaging edge cases (pairs 7-14) that break naive ports.
const sharma = [
	[50, 2.6772, -79.7751, 50, 0, -82.7485, 2.0425],
	[50, 3.1571, -77.2803, 50, 0, -82.7485, 2.8615],
	[50, 2.8361, -74.0200, 50, 0, -82.7485, 3.4412],
	[50, -1.3802, -84.2814, 50, 0, -82.7485, 1.0000],
	[50, -1.1848, -84.8006, 50, 0, -82.7485, 1.0000],
	[50, -0.9009, -85.5211, 50, 0, -82.7485, 1.0000],
	[50, 0, 0, 50, -1, 2, 2.3669],
	[50, -1, 2, 50, 0, 0, 2.3669],
	[50, 2.4900, -0.0010, 50, -2.4900, 0.0009, 7.1792],
	[50, 2.4900, -0.0010, 50, -2.4900, 0.0010, 7.1792],
	[50, 2.4900, -0.0010, 50, -2.4900, 0.0011, 7.2195],
	[50, 2.4900, -0.0010, 50, -2.4900, 0.0012, 7.2195],
	[50, -0.0010, 2.4900, 50, 0.0009, -2.4900, 4.8045],
	[50, -0.0010, 2.4900, 50, 0.0011, -2.4900, 4.7461],
	[50, 2.5, 0, 50, 0, -2.5, 4.3065],
	[50, 2.5, 0, 73, 25, -18, 27.1492],
	[50, 2.5, 0, 61, -5, 29, 22.8977],
	[50, 2.5, 0, 56, -27, -3, 31.9030],
	[50, 2.5, 0, 58, 24, 15, 19.4535],
	[50, 2.5, 0, 50, 3.1736, 0.5854, 1.0000],
	[50, 2.5, 0, 50, 3.2972, 0, 1.0000],
	[50, 2.5, 0, 50, 1.8634, 0.5757, 1.0000],
	[50, 2.5, 0, 50, 3.2592, 0.3350, 1.0000],
	[60.2574, -34.0099, 36.2677, 60.4626, -34.1751, 39.4387, 1.2644],
	[63.0109, -31.0961, -5.8663, 62.8187, -29.7946, -4.0864, 1.2630],
	[61.2901, 3.7196, -5.3901, 61.4292, 2.2480, -4.9620, 1.8731],
	[35.0831, -44.1164, 3.7933, 35.0232, -40.0716, 1.5901, 1.8645],
	[22.7233, 20.0904, -46.6940, 23.0331, 14.9730, -42.5619, 2.0373],
	[36.4612, 47.8580, 18.3852, 36.2715, 50.5065, 21.2231, 1.4146],
	[90.8027, -2.0831, 1.4410, 91.1528, -1.6435, 0.0447, 1.4441],
	[90.9257, -0.5406, -0.9208, 88.6381, -0.8985, -0.7239, 1.5381],
	[6.7747, -0.2908, -2.4247, 5.8714, -0.0985, -2.2286, 0.6377],
	[2.0776, 0.0795, -1.1350, 0.9033, -0.0636, -0.5514, 0.9082]
];

test("CIEDE2000 matches all 33 Sharma reference pairs", () => {
	for (const [l1, a1, b1, l2, a2, b2, expected] of sharma) {
		const got = deltaE2000([l1, a1, b1], [l2, a2, b2]);
		assert.ok(Math.abs(got - expected) < 1e-4,
			`(${l1},${a1},${b1})~(${l2},${a2},${b2}): got ${got}, want ${expected}`);
	}
});

test("Lab conversion reference points", () => {
	assert.deepEqual(labFromSrgb([255, 255, 255]).map(v => Math.round(v) + 0), [100, 0, 0]);
	assert.deepEqual(labFromSrgb([0, 0, 0]).map(v => Math.round(v) + 0), [0, 0, 0]);
	const [l, a] = labFromSrgb([255, 0, 0]);
	assert.ok(Math.abs(l - 53.24) < 0.1 && Math.abs(a - 80.09) < 0.1, `red got L=${l} a=${a}`);
});

test("severity 0 and normal vision are identity", () => {
	for (const d of ["normal", "protan", "deutan", "tritan", "achromat"]) {
		const out = simulate(d, d === "normal" ? 1 : 0, [180, 40, 220]);
		assert.deepEqual(out.map(Math.round), [180, 40, 220], d);
	}
});

test("grays are fixed points of every deficiency", () => {
	for (const d of ["protan", "deutan", "tritan", "achromat"])
		for (const v of [0, 128, 255]) {
			const out = simulate(d, 1, [v, v, v]);
			for (const c of out)
				assert.ok(Math.abs(c - v) <= 1, `${d} gray ${v} -> ${out}`);
		}
});

test("full achromatopsia removes all chroma", () => {
	const [r, g, b] = simulate("achromat", 1, [200, 30, 90]);
	assert.ok(Math.abs(r - g) < 1e-9 && Math.abs(g - b) < 1e-9);
});

test("protanopia collapses red toward dark yellow-brown (R≈G)", () => {
	const [r, g] = simulate("protan", 1, [255, 0, 0]);
	assert.ok(Math.abs(r - g) < 1, `got ${r},${g}`);
});

test("severity interpolates monotonically", () => {
	const dist = s => {
		const out = simulate("deutan", s, [255, 0, 0]);
		return Math.hypot(out[0] - 255, out[1], out[2]);
	};
	assert.ok(dist(0.25) < dist(0.5) && dist(0.5) < dist(1));
});
