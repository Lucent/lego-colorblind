<!DOCTYPE html>
<html>
 <head>
  <title>Differentiate Similar Color Lego Parts for Colorblindness</title>
  <link href="css/screen-default.css" rel="stylesheet" type="text/css">
  <meta name="viewport" content="width=device-width, initial-scale=1">
 </head>
 <body>
<?
require_once "php/color_difference.class.php";
require_once "php/rebrick_colors_to_array.php";
require_once "apikey.php";
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

if (array_key_exists("type", $_GET))
	if (array_key_exists($_GET["type"], $similar_colors))
		$similar_color_bank = $similar_colors[$_GET["type"]];

if (array_key_exists("set", $_GET)) {
	if (strpos($_GET["set"], "-"))
		$set_number = $_GET["set"];
	else
		$set_number = $_GET["set"] . "-1";

	$set_json = json_decode(get_set_json($set_number, $api_key), true);
	if ($set_json === FALSE) {
		echo "Invalid set ID";
		exit;
	} else
		$set = $set_json[0];
}
?>
<h1>Find parts that occur in multiple similar colors</h1>
<form method="get" action=".">
<?
echo "<h1><img src='" . $set["set_img_url"] .  "'>" . htmlspecialchars_decode($set["descr"]);
?>
 <input type="text" name="set" placeholder="Set ID" value="<?= $set_number ?>">
</h1>
Show colors that might be confused
<select name="type">
<?
foreach ($similar_colors as $blindness_type=>$color_set)
	echo "<option value='$blindness_type'", $_GET["type"] == $blindness_type ? " selected" : "", ">$blindness_type</option>\n";
?>
</select>
<input type="submit" value="Show similarly colored parts">
</form>

<?
function make_similar_color_list($colors) {
	global $ldraw_colors;
	$THRESHOLD = 20;
	$similar_lists = [];
	for ($x = 0; $x < count($colors); $x++) {
		for ($y = $x + 1; $y < count($colors); $y++) {
			$color_difference = (new color_difference())->deltaECIE2000($ldraw_colors[$colors[$x]]["RGBA"], $ldraw_colors[$colors[$y]]["RGBA"]);
//			echo "comparing ", $ldraw_colors[$colors[$x]]["Name"], " and ", $ldraw_colors[$colors[$y]]["Name"], " diff: ", $color_difference, "\n";
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

$parts_bydesign = [];
// Arrange all parts by design, exclude extras
foreach ($set["parts"] as $part)
	if ($part["type"] === 1)
		$parts_bydesign[$part["part_id"]][] = $part;

// Get rid of parts only in one color
foreach ($parts_bydesign as $key=>&$design)
	if (count($design) === 1)
		unset($parts_bydesign[$key]);

// Make similar color banks for each part
foreach ($parts_bydesign as $design) {
	$similar_color_lists = make_similar_color_list(array_column($design, "ldraw_color_id"));
	if (count($similar_color_lists)) {
		echo "\n<h2>" . $design[0]["part_name"] . "</h2>\n";
		foreach($similar_color_lists as $color_list) {
			echo "<div>\n";
			foreach ($design as $part) {
				if (in_array($part["ldraw_color_id"], $color_list) === TRUE)
					echo "<figure><img src='" . $part["part_img_url"] . "'><figcaption>" . $part["color_name"] . " (" .  $part["qty"] . ")</figcaption></figure>\n";
			}
			echo "</div>\n";
		}
	}
}





/*

foreach ($similar_color_bank as $color_pair_idx=>$color_pairs) {
	foreach ($set["parts"] as $part) {
		if (in_array($part["ldraw_color_id"], $color_pairs) && $part["type"] == 1)
			$parts_bydesign[$color_pair_idx][$part["part_id"]][] = $part;
	}
}

foreach ($parts_bydesign as $color_pair) {
	foreach($color_pair as $part) {
		echo "\n<h2>" . $part[0]["part_name"] . "</h2>\n";
		foreach ($part as $color)
			echo "<div><img src='" . $color["part_img_url"] . "'><br><span>" . $color["color_name"] . " (" .  $color["qty"] . ")</span></div>\n";
	}
}*/
?>
 </body>
</html>
