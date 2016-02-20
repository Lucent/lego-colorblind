<pre><?
require "apikey.php";
$similar_colors = [
	[2, 70, 308, 320]
];

if (array_key_exists("set", $_GET)) {
	if (strpos($_GET["set"], "-"))
		$set_number = $_GET["set"];
	else
		$set_number = $_GET["set"] . "-1";
} else {
?>
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
//echo $request;
$set_json = json_decode(file_get_contents($request), true);
if (strlen($set_json) === 0) {
	echo "Invalid set ID";
	exit;
}

$set = $set_json[0];
echo $set["descr"] . "\n\n";

$unique_similar_colors = call_user_func_array("array_merge", $similar_colors);
//print_r($similar_colors);
foreach ($set["parts"] as $part) {
	if (in_array($part["ldraw_color_id"], $unique_similar_colors) && $part["type"] == 1)
		$parts_bydesign[$part["part_id"]][] = $part;
}

foreach ($parts_bydesign as $part) {
	if (count($part) > 1) {
//		print_r($part);
		echo $part[0]["part_name"] . " comes in ";
		$colors = [];
		foreach ($part as $color)
			$colors[] = $color["color_name"];
		echo implode(" and ", $colors) . "\n";
	}
}
?></pre>
