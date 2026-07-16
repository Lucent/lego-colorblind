/* Native autocomplete via a dynamically filled <datalist>. The browser
   renders its regular dropdown; option label shows the full set line,
   option value inserts just the set number. Set numbers match by
   prefix, names by substring. Limitation: browsers only show options
   the typed text is a substring of, so once a comma commits one set,
   no suggestions appear for the next — type further numbers manually. */

let sets = null;

async function load() {
	sets ??= (await fetch("data/sets.json")).json().catch(() => []);
	return sets;
}

export function attach(input, datalist, onPick) {
	input.addEventListener("input", async () => {
		// picking from the list (or typing a full number) arrives as an
		// input event whose value is exactly an offered set number
		if (input.value !== "" && [...datalist.options].some(o => o.value === input.value)) {
			onPick?.();
			return;
		}

		const term = input.value.trim().toLowerCase();
		if (term === "" || term.includes(",")) {
			datalist.replaceChildren();
			return;
		}

		const found = [];
		for (const line of await load()) {
			const space = line.indexOf(" ");
			if (line.slice(0, space).toLowerCase().startsWith(term)
					|| line.slice(space + 1).toLowerCase().includes(term))
				if (found.push(line) === 15) break;
		}

		datalist.replaceChildren(...found.map(line => {
			const option = document.createElement("option");
			option.value = line.match(/^\S+/)[0];
			option.label = line;
			return option;
		}));
	});
}
