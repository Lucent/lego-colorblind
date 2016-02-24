<!DOCTYPE html>
<html>
 <head>
  <title>Differentiate Similar Color Lego Parts for Colorblindness</title>
  <link href="css/screen-default.css" rel="stylesheet" type="text/css">
  <meta name="viewport" content="width=device-width, initial-scale=1">
 </head>
 <body>
<?
require "apikey.php";
$similar_colors = [
	"Families" =>
	[[0,83,256],[1,3,23,273,85,33,110,34,137,406],[2,34],[4,216,450,484,36,178,134,324,495,37,334,114,297,82,66,350,490],[5,69,330,37,294,114,335,495,450,63,178,81,334,297,179,504,16,24,375,494],[6,70,320,60,148,87,76,449,493,129,16,24],[7,30,71,378,44,135,179,80,496,504,52,150,375,16,24,294,494],[8,10,22,26,72,373,40,35,60,87,76,449,16,24,493,148,129,63,44,179,504,81],[9,20,118,323,43,39,52,150,375,31,494,151,503,383,183,378,80,47,79,117,67,511],[11,73,112,232,313,379,41,61,62,137,322],[12,25,27,84,92,115,335,351,366,38,57,54,42,37,334,142,297,82,114,66,125,191,462,46,65,490,450,178,350,495,45,226,326,503,21],[13,18,19,29,68,77,78,100,120,326,351,45,142,21,125,151,503,383,294,375,494,183],[14,18,25,68,125,191,226,366,462,38,57,54,46,42,45,142,82,65,490],[15,151,183,79,117,67],[17,29,151,503,383,150,294,375,494],[28,330,35,63,179,81,504],[74,212,44,52,135,80],[86,40,60,134,87],[89,110,112,321,322,41,137],[272,64,83,406,256],[288,64,83,256],[132,133,75,32]],
	"Rebrickable" => [
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
	],
	"Deuteranomaly" => [[0,83,132,133,75,32,256],[1,23,85,89,110,33,273,112,137,321,406],[2,34],[3,379,76,493],[4,216,36,324],[5,69,37,114],[6,70],[7,71,378,135,179,80,496,504,150,294,375,494,16,24],[8,72,40,60,87,76,16,24,493,148],[9,20,31,118,323,43,39,150,375,494,151,383,503,47,183,79,117,67,511],[11,232,313,41,61,322],[12,92,335,450,178,495],[13,29,100,351,45,150,375,494],[14,27,68,115,125,191,226,462,54,46,142,65,490,42,78,120,326,151,503,82,66,21,366,38,57,297,334,178],[15,151,183,79,117,67],[17,503,294,375,494],[18,19,68,78,120,326,21],[22,129,449],[25,366,462,38,57,297,82],[26,373,63,44],[28,179,81,504,16,24],[30,44],[73,232,313,322,41,61],[74,378],[77,100,151,503,383,494],[84,450,178,350,495],[86,40,60,134],[212,232,52,80,135,496],[272,64,83,256],[330,178,81],[484,134]]
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
		return false;
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

$similar_color_bank = $similar_colors["Families"];
if (array_key_exists("type", $_GET))
	if (array_key_exists($_GET["type"], $similar_colors))
		$similar_color_bank = $similar_colors[$_GET["type"]];

if (array_key_exists("set", $_GET)) {
	if (strpos($_GET["set"], "-"))
		$set_number = $_GET["set"];
	else
		$set_number = $_GET["set"] . "-1";

	$set_json = json_decode(get_set_json($set_number, $api_key), true);
	if ($set_json === false) {
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
<input type="radio" name="type" value="Deuteranomaly" id="Deuteranomaly" <?= ($_GET["type"] == "Deuteranomaly" ? "checked" : "") ?>><label for="Deuteranomaly">by someone red-green colorblind</label>
<input type="radio" name="type" value="Families" id="Families" <?= ($_GET["type"] == "Families" ? "checked" : "") ?>><label for="Families">in printed manuals</label>
<input type="radio" name="type" value="Rebrickable" id="Rebrickable" <?= ($_GET["type"] == "Rebrickable" ? "checked" : "") ?>><label for="Rebrickable">Rebrickable</label>
<input type="submit" value="Show similarly colored parts">
</form>

<?
$parts_bydesign = [];
foreach ($similar_color_bank as $color_pair_idx=>$color_pairs) {
	foreach ($set["parts"] as $part) {
		if (in_array($part["ldraw_color_id"], $color_pairs) && $part["type"] == 1)
			$parts_bydesign[$color_pair_idx][$part["part_id"]][] = $part;
	}
}

// Clear out parts that aren't in multiple confusing colors
foreach ($parts_bydesign as $key1=>$color_pair) {
	foreach ($color_pair as $key2=>$part) {
		if (count($part) === 1)
			unset($parts_bydesign[$key1][$key2]);
	}
}

foreach ($parts_bydesign as $color_pair) {
	foreach($color_pair as $part) {
		echo "\n<h2>" . $part[0]["part_name"] . "</h2>\n";
		foreach ($part as $color)
			echo "<div><img src='" . $color["part_img_url"] . "'><br><span>" . $color["color_name"] . " (" .  $color["qty"] . ")</span></div>\n";
	}
}
?>
 </body>
</html>
