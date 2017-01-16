<?php
$similar_colors["Rebrickable"] = [
	[-1, 0, 32, 64, 75, 132, 133, 9999],
	[1, 3, 9, 11, 23, 33, 41, 43, 52, 61, 73, 85, 89, 110, 112, 137, 143, 212, 232, 272, 313, 321, 322, 379, 1001, 1003],
	[2, 10, 17, 21, 27, 34, 35, 42, 62, 74, 81, 115, 118, 120, 158, 288, 294, 323, 326, 378, 1002],
	[4, 5, 12, 13, 20, 22, 26, 29, 30, 31, 36, 45, 63, 69, 77, 100, 114, 129, 216, 230, 236, 320, 335, 351, 373, 1007],
	[6, 28, 70, 84, 86, 92, 134, 178, 308, 450],
	[14, 18, 19, 25, 46, 54, 57, 68, 78, 82, 125, 142, 182, 191, 226, 297, 334, 366, 462, 484],
	[15, 47, 79, 117, 151, 183, 1000],
	[1006],
	[7, 8, 40, 60, 71, 72, 76, 80, 135, 148, 150, 179, 383, 503],
	[1004, 1005]
];

$blindness_matrix = [
	"normal vision" => [1,0,0,0,0, 0,1,0,0,0, 0,0,1,0,0, 0,0,0,1,0],
	"protanopia" => [0.567,0.433,0,0,0, 0.558,0.442,0,0,0, 0,0.242,0.758,0,0, 0,0,0,1,0],
	"protanomaly" => [0.817,0.183,0,0,0, 0.333,0.667,0,0,0, 0,0.125,0.875,0,0, 0,0,0,1,0],
	"deuteranopia" => [0.625,0.375,0,0,0, 0.7,0.3,0,0,0, 0,0.3,0.7,0,0, 0,0,0,1,0],
	"deuteranomaly" => [0.8,0.2,0,0,0, 0.258,0.742,0,0,0, 0,0.142,0.858,0,0, 0,0,0,1,0],
	"tritanopia" => [0.95,0.05,0,0,0, 0,0.433,0.567,0,0, 0,0.475,0.525,0,0, 0,0,0,1,0],
	"tritanomaly" => [0.967,0.033,0,0,0, 0,0.733,0.267,0,0, 0,0.183,0.817,0,0, 0,0,0,1,0],
	"achromatopsia" => [0.299,0.587,0.114,0,0, 0.299,0.587,0.114,0,0, 0.299,0.587,0.114,0,0, 0,0,0,1,0],
	"achromatomaly" => [0.618,0.320,0.062,0,0, 0.163,0.775,0.062,0,0, 0.163,0.320,0.516,0,0, 0,0,0,1,0]
];

function clean_set_number($set) {
	$set = trim($set);
	if (strpos($set, "-"))
		return $set;
	else
		return $set . "-1";
}

function convert_color($o, $matrix) {
	global $blindness_matrix, $blindness_brian;
	if (array_key_exists($matrix, $blindness_matrix))
		return color_transform_matrix($o, $matrix);
	elseif (array_key_exists($matrix, $blindness_brian))
		return color_transform_brian($o, $matrix);
}

function color_transform_matrix($o, $matrix) {
	global $blindness_matrix;
	$bg = [255, 255, 255];
	$m = $blindness_matrix[$matrix];

    $r = (($o[0]*$m[0])+($o[1]*$m[1])+($o[2]*$m[2])+($o[3]*$m[3])+$m[4]);
    $g = (($o[0]*$m[5])+($o[1]*$m[6])+($o[2]*$m[7])+($o[3]*$m[8])+$m[9]);
    $b = (($o[0]*$m[10])+($o[1]*$m[11])+($o[2]*$m[12])+($o[3]*$m[13])+$m[14]);
    $a = (($o[0]*$m[15])+($o[1]*$m[16])+($o[2]*$m[17])+($o[3]*$m[18])+$m[19]);

	$r = $bg[0] + ($r - $bg[0]) * ($a / 255);
	$g = $bg[1] + ($g - $bg[1]) * ($a / 255);
	$b = $bg[2] + ($b - $bg[2]) * ($a / 255);

    return [$r<0?0:($r<255?$r:255), $g<0?0:($g<255?$g:255), $b<0?0:($b<255?$b:255), $a<0?0:($a<255?$a:255)];
}

function get_set_json($id, $api_key) {
	$cache_folder = "cache" . DIRECTORY_SEPARATOR;
	$request_params = [
		"key" => $api_key,
		"format" => "json",
		"set" => $id
	];

	$cache_file = $cache_folder . $id;
	if (file_exists($cache_file)) {
		$fh = fopen($cache_file, "r");
		$file_time = trim(fgets($fh));
		if ($file_time > strtotime("-1 week"))
			return fread($fh, filesize($cache_file));
		else {
			fclose($fh);
			unlink($cache_file);
		}
	}

	$request = "http://rebrickable.com/api/get_set_parts?" . http_build_query($request_params);
	$set_json = file_get_contents($request);
	write_cache_miss($cache_folder . "cache_miss", $id);

	if ($set_json == "NOSET")
		return FALSE;
	else {
		$fh = fopen($cache_file, "w");
		fwrite($fh, time() . "\n");
		fwrite($fh, $set_json);
		fclose($fh);
		return $set_json;
	}
}

function write_cache_miss($file, $set_id) {
	$fh = fopen($file, "a");
	fwrite($fh, time() . "\t" . $set_id . "\n");
	fclose($fh);
}

function make_similar_color_list($bank, $colors) {
	global $ldraw_colors;
	// Use 20 and 10197 to diagnose chaining, 6 pairs of chains
	// 13 gets dark green and red brown in deuter brian
	$THRESHOLD = 13;
	$similar_lists = [];
	for ($x = 0; $x < count($colors); $x++) {
		for ($y = $x + 1; $y < count($colors); $y++) {
			$color1 = $ldraw_colors[$colors[$x]]["RGBA"];
			$color2 = $ldraw_colors[$colors[$y]]["RGBA"];
			$color_difference = (new color_difference())->deltaECIE2000(convert_color($color1, $bank), convert_color($color2, $bank));
//			echo "comparing ", implode(",", $color1), " and ", implode(",", $color2) , " diff: ", $color_difference, "\n";
			if ($color_difference < $THRESHOLD)
				add_color_pair($similar_lists, $colors[$x], $colors[$y]);
		}
	}
	return $similar_lists;
}

function find_in_arrays($haystack, $needle) {
	for ($x = 0; $x < count($haystack); $x++)
		if (array_search($needle, $haystack[$x]) !== FALSE)
			return $x;
	return FALSE;
}

function add_color_pair(&$result, $first, $second) {
	global $ldraw_colors;
	if (abs($ldraw_colors[$first]["RGBA"][3] - $ldraw_colors[$second]["RGBA"][3]) >= 127)
		return;

	$first_found = find_in_arrays($result, $first);
	$second_found = find_in_arrays($result, $second);

	if ($first_found !== FALSE && $second_found !== FALSE && $first_found !== $second_found) {
		$combined = array_unique(array_merge($result[$first_found], $result[$second_found]));
		array_splice($result, $first_found, 1);
		array_splice($result, $second_found, 1);
		$result[] = $combined;
	} elseif ($first_found !== FALSE) {
		if (array_search($second, $result[$first_found]) === FALSE)
			$result[$first_found][] = $second;
	} elseif ($second_found !== FALSE) {
		if (array_search($first, $result[$second_found]) === FALSE)
			$result[$second_found][] = $first;
	} else {
		$result[] = [$first, $second];
	}
}

function count_parts($parts) {
	$regular = 0;
	$extra = 0;

	foreach($parts as $part) {
		if ($part["type"] === 1)
			$regular += $part["qty"];
		else
			$extra += $part["qty"];
	}

	return [$regular, $extra];
}

function rgb2hex($rgb) {
	return '#' . sprintf('%02x', $rgb[0]) . sprintf('%02x', $rgb[1]) . sprintf('%02x', $rgb[2]);
}

function show_similar_colored_parts($parts_bydesign, $similar_color_bank) {
	// Get rid of parts only in one color
	foreach ($parts_bydesign as $key=>&$design)
		if (count($design) === 1)
			unset($parts_bydesign[$key]);

	// Make similar color banks for each part
	$confusing_parts_count = 0;
	foreach ($parts_bydesign as $design) {
		$similar_color_lists = make_similar_color_list($similar_color_bank, array_column($design, "ldraw_color_id"));
		if (count($similar_color_lists)) {
			echo "\n<h3>" . $design[0]["part_name"] . "</h3>\n";
			foreach ($similar_color_lists as $color_list) {
				echo "<section>\n";
				foreach ($design as $part) {
					if (in_array($part["ldraw_color_id"], $color_list) === TRUE) {
						echo "<figure><img src='" . $part["part_img_url"] . "'><figcaption>" . $part["color_name"] . " (" .  $part["qty"];
						if (array_key_exists("extra", $part))
							echo "<sup>+" . $part["extra"] . "</sup>";
						echo ")</figcaption></figure>\n";
					}
				}
				echo "</section>\n";
			}
			$confusing_parts_count++;
		}
	}

	if (empty($parts_bydesign))
		echo "<h3>Each part design in this set occurs in a unique color.</h3>\n";
	elseif ($confusing_parts_count === 0)
		echo "<h3>No parts in this set occur in similar, confusing colors for the chosen color vision type and lighting.</h3>\n";
}

function show_similar_colors($parts_bycolor, $similar_color_bank) {
	global $ldraw_colors;
	foreach ($parts_bycolor as &$color_arr)
		$color_arr["qty"] = array_sum(array_column($color_arr, "qty"));

	$similar_color_lists = make_similar_color_list($similar_color_bank, array_keys($parts_bycolor));
	foreach ($similar_color_lists as $color_list) {
		echo "<section>\n";
		foreach ($color_list as $ldraw_color) {
			$color = rgb2hex($ldraw_colors[$ldraw_color]["RGBA"]);
			echo "<figure><div style='height: 100px; width: 100px; background-color: $color;'></div><figcaption>" . $ldraw_colors[$ldraw_color]["Name"] . " (" . $parts_bycolor[$ldraw_color]["qty"];
//			if (array_key_exists("extra", $part))
//				echo "<sup>+" . $part["extra"] . "</sup>";
			echo ")</figcaption></figure>\n";
		}
		echo "</section>\n";
	}
}
