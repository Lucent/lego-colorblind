<html>
 <head>
  <title>Differentiate Similar Color Lego Parts for Colorblindness</title>
  <link href="css/screen-default.css" rel="stylesheet" type="text/css">
 </head>
 <body>
<?
require "apikey.php";
$similar_colors = [
	[2, 70, 308, 320],
	[14, 25, 27]
];

if (array_key_exists("set", $_GET)) {
	if (strpos($_GET["set"], "-"))
		$set_number = $_GET["set"];
	else
		$set_number = $_GET["set"] . "-1";
} else {
?>
<h1>Find parts that occur in multiple similar colors</h1>
<form method="get" action=".">
 <input type="text" name="set" placeholder="Set ID">
 <input type="submit" value="Show similar colors">
</form>
<?
	exit;
}

$request_params = [
	"key" => $api_key,
	"format" => "json",
	"set" => $set_number
];
$request = "http://rebrickable.com/api/get_set_parts?" . http_build_query($request_params);
$set_json = json_decode(file_get_contents($request), true);
if (count($set_json) === 0) {
	echo "Invalid set ID";
	exit;
}

$set = $set_json[0];
echo "<h1>" . $set["descr"] . "</h1>\n\n";

$parts_bydesign = [];
foreach ($similar_colors as $color_pair_idx=>$color_pairs) {
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
//	print_r($color_pair);
	foreach($color_pair as $part) {
		echo "\n<h2>" . $part[0]["part_name"] . "</h2>\n";
		foreach ($part as $color)
			echo "<div><img src='" . $color["part_img_url"] . "'><br><span>" . $color["color_name"] . " (" .  $color["qty"] . ")</span></div>\n";
	}
}
?>
 </body>
</html>
