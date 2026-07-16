/* sRGB → CIELAB (D65, 2° observer) and the CIEDE2000 color difference.
   Implementation follows Sharma, Wu & Dalal 2005, "The CIEDE2000
   Color-Difference Formula: Implementation Notes"; verified against
   their published test pairs. */

export function labFromSrgb([r, g, b]) {
	[r, g, b] = [r, g, b].map(v => {
		v /= 255;
		return v <= 0.04045 ? v / 12.92 : ((v + 0.055) / 1.055) ** 2.4;
	});

	const x = (r * 0.412453 + g * 0.357580 + b * 0.180423) / 0.95047;
	const y = (r * 0.212671 + g * 0.715160 + b * 0.072169);
	const z = (r * 0.019334 + g * 0.119193 + b * 0.950227) / 1.08883;

	const f = t => t > 0.008856 ? Math.cbrt(t) : t * 7.787 + 16 / 116;
	const [fx, fy, fz] = [x, y, z].map(f);

	return [fy * 116 - 16, (fx - fy) * 500, (fy - fz) * 200];
}

const rad = d => d * Math.PI / 180;
const deg = r => r * 180 / Math.PI;

export function deltaE2000([l1, a1, b1], [l2, a2, b2]) {
	const avgC = (Math.hypot(a1, b1) + Math.hypot(a2, b2)) / 2;
	const g = (1 - Math.sqrt(avgC ** 7 / (avgC ** 7 + 25 ** 7))) / 2;
	const a1p = a1 * (1 + g), a2p = a2 * (1 + g);
	const c1p = Math.hypot(a1p, b1), c2p = Math.hypot(a2p, b2);

	let h1p = c1p === 0 ? 0 : deg(Math.atan2(b1, a1p));
	if (h1p < 0) h1p += 360;
	let h2p = c2p === 0 ? 0 : deg(Math.atan2(b2, a2p));
	if (h2p < 0) h2p += 360;

	let dhp = h2p - h1p;
	if (c1p * c2p === 0)
		dhp = 0;
	else if (dhp > 180)
		dhp -= 360;
	else if (dhp < -180)
		dhp += 360;

	const dLp = l2 - l1;
	const dCp = c2p - c1p;
	const dHp = 2 * Math.sqrt(c1p * c2p) * Math.sin(rad(dhp) / 2);

	let avgHp;
	if (c1p * c2p === 0)
		avgHp = h1p + h2p;
	else if (Math.abs(h1p - h2p) <= 180)
		avgHp = (h1p + h2p) / 2;
	else if (h1p + h2p < 360)
		avgHp = (h1p + h2p) / 2 + 180;
	else
		avgHp = (h1p + h2p) / 2 - 180;

	const avgLp = (l1 + l2) / 2;
	const avgCp = (c1p + c2p) / 2;

	const t = 1 - 0.17 * Math.cos(rad(avgHp - 30)) + 0.24 * Math.cos(rad(2 * avgHp))
	        + 0.32 * Math.cos(rad(3 * avgHp + 6)) - 0.20 * Math.cos(rad(4 * avgHp - 63));
	const sL = 1 + 0.015 * (avgLp - 50) ** 2 / Math.sqrt(20 + (avgLp - 50) ** 2);
	const sC = 1 + 0.045 * avgCp;
	const sH = 1 + 0.015 * avgCp * t;
	const rT = -2 * Math.sqrt(avgCp ** 7 / (avgCp ** 7 + 25 ** 7))
	         * Math.sin(rad(60 * Math.exp(-(((avgHp - 275) / 25) ** 2))));

	return Math.sqrt((dLp / sL) ** 2 + (dCp / sC) ** 2 + (dHp / sH) ** 2
	                 + rT * (dCp / sC) * (dHp / sH));
}
