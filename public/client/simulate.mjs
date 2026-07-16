/* Color vision deficiency simulation.

   Ported from libDaltonLens (public domain, https://daltonlens.org),
   libDaltonLens.c @ master, by Nicolas Burrus. Math explained at
   https://daltonlens.org/understanding-cvd-simulation/

   Viénot 1999 for protan/deutan, Brettel 1997 for tritan (Viénot is
   inaccurate for tritanopia). Achromatopsia is a luminance projection
   (Rec. 709 Y in linear RGB), which libDaltonLens does not cover.

   All matrices operate in linear RGB. Severity 0..1 linearly
   interpolates toward the fully dichromatic projection. */

function linearFromSrgb(v) {
	v /= 255;
	return v < 0.04045 ? v / 12.92 : ((v + 0.055) / 1.055) ** 2.4;
}

function srgbFromLinear(v) {
	if (v <= 0) return 0;
	if (v >= 1) return 255;
	return v < 0.0031308 ? v * 12.92 * 255 : 255 * (v ** (1 / 2.4) * 1.055 - 0.055);
}

const vienot = {
	protan: [0.11238, 0.88762, 0.00000,
	         0.11238, 0.88762, -0.00000,
	         0.00401, -0.00401, 1.00000],
	deutan: [0.29275, 0.70725, 0.00000,
	         0.29275, 0.70725, -0.00000,
	         -0.02234, 0.02234, 1.00000]
};

// Brettel projects onto one of two half-planes chosen by the sign of a
// dot product with the separation plane normal, all precomputed in RGB.
const brettel = {
	tritan: {
		plane1: [1.01277, 0.13548, -0.14826,
		         -0.01243, 0.86812, 0.14431,
		         0.07589, 0.80500, 0.11911],
		plane2: [0.93678, 0.18979, -0.12657,
		         0.06154, 0.81526, 0.12320,
		         -0.37562, 1.12767, 0.24796],
		normal: [0.03901, -0.02788, -0.01113]
	}
};

function apply(m, [r, g, b]) {
	return [m[0] * r + m[1] * g + m[2] * b,
	        m[3] * r + m[4] * g + m[5] * b,
	        m[6] * r + m[7] * g + m[8] * b];
}

/* Simulate CVD on an sRGB color. deficiency: "normal" | "protan" |
   "deutan" | "tritan" | "achromat". severity: 0..1. rgb: [r,g,b] 0-255.
   Returns [r,g,b] 0-255 (unrounded). */
export function simulate(deficiency, severity, rgb) {
	if (deficiency === "normal" || severity <= 0)
		return rgb.slice(0, 3);

	const lin = rgb.slice(0, 3).map(linearFromSrgb);
	let cvd;
	if (deficiency === "achromat") {
		const y = 0.2126 * lin[0] + 0.7152 * lin[1] + 0.0722 * lin[2];
		cvd = [y, y, y];
	} else if (deficiency === "tritan") {
		const { plane1, plane2, normal } = brettel.tritan;
		const dot = lin[0] * normal[0] + lin[1] * normal[1] + lin[2] * normal[2];
		cvd = apply(dot >= 0 ? plane1 : plane2, lin);
	} else {
		cvd = apply(vienot[deficiency], lin);
	}

	return cvd.map((v, i) => srgbFromLinear(v * severity + lin[i] * (1 - severity)));
}
