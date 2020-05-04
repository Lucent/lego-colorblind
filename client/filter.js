function filter(line, input) {
	const autocomplete_noyear = line.slice(0, -7);
	const regex = /^([^\s]+)\s(.*)$/;
	const parts = autocomplete_noyear.match(regex);
	input = input.match(/[^,]*$/)[0].trim();
	// Only match set numbers from the start, but match words anywhere
	return parts[1].indexOf(input) === 0 ||
		parts[2].toLowerCase().indexOf(input.toLowerCase()) !== -1;
}

function replace(text) {
	const before = this.input.value.match(/^.+,\s*|/)[0];
	// On select remove everything but the set number
	this.input.value = before + text.match(/^[^\s]+/)[0];
}

async function init() {
	const sets_file = await fetch("cache/set_autocomplete_list.json");
	const sets_json = await sets_file.json();

	new Awesomplete(document.querySelector("input[data-multiple]"), {
		maxItems: 15,
		autoFirst: true,
		filter: filter,
		replace: replace,
		sort: false,
		list: sets_json
	});
}

init();
