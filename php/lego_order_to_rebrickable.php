<pre><?php
require_once "../apikey.php";
require_once "find_parts_in_most_colors.php";

function get_loose_parts($api_key, $user_hash) {
	$request_params = [
		"key" => $api_key,
		"format" => "json",
		"hash" => $user_hash,
		"partlist_id" => 6
	];

	$request = "http://rebrickable.com/api/get_user_parts?" . http_build_query($request_params);
	$parts_json = json_decode(file_get_contents($request), true);

	print_r($parts_json);
}

function order_to_rebrickable($filename, $api_key, $user_hash) {
	global $parts_byid;

	$fp = fopen("../data/colors-simple.csv", "r");
	while (($line = fgetcsv($fp)) !== FALSE) {
		list($id, $color_name) = $line;
		$color_byname[$color_name] = $id;
	}

	$parts_to_add = [];
	if (file_exists($filename)) {
		$fp = fopen($filename, "r");
		while (($line = fgetcsv($fp, 0, "\t")) !== FALSE) {
			list($id, $qty) = $line;
			$parts_to_add[] = $parts_byid[$id][0] . " " . $color_byname[$parts_byid[$id][1]] . " " . $qty;
		}
	}

	$request_params = [
		"key" => $api_key,
		"format" => "csv",
		"hash" => $user_hash,
		"partlist_id" => 8,
		"parts" => implode(",", $parts_to_add)
	];
	print_r($request_params);
	$request = "http://rebrickable.com/api/set_user_parts?" . http_build_query($request_params);
	echo file_get_contents($request);
}

order_to_rebrickable("../data/pieces_order.tsv", $api_key, $user_hash);
?></pre>
