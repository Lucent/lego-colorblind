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
require_once "php/functions.php";
require_once "apikey.php";

if (array_key_exists("type", $_GET))
	if (array_key_exists($_GET["type"], $blindnesses))
		$similar_color_bank = $_GET["type"];

if (array_key_exists("dark", $_GET))
	if (is_numeric($_GET["dark"]))
		$darken_factor = $_GET["dark"];

if (array_key_exists("set", $_GET)) {
	if (strpos($_GET["set"], ","))
		$sets = explode(",", $_GET["set"]);
	else
		$sets = explode(" ", $_GET["set"]);

	$set_numbers = [];
	foreach ($sets as $set)
		$set_numbers[] = clean_set_number($set);

	$set = [];
	foreach ($set_numbers as $set_number) {
		$set_json = json_decode(get_set_json($set_number, $api_key), true);
		if ($set_json === FALSE)
			echo "Invalid set ID ", $set_number;
		else
			$set[] = $set_json[0];
	}
}

$parts_byelement = [];
foreach ($set as $set_json) {
	foreach ($set_json["parts"] as $part) {
		if ($part["type"] === 1) {
			if (array_key_exists($part["element_id"], $parts_byelement))
				$parts_byelement[$part["element_id"]]["qty"] += $part["qty"];
			else
				$parts_byelement[$part["element_id"]] = $part;
		}
	}
}

$parts_bydesign = [];
foreach ($parts_byelement as $part)
	$parts_bydesign[$part["part_id"]][] = $part;
?>
<h1>Find parts that occur in multiple similar colors</h1>
<form method="get" action=".">
 <h2>
<? foreach ($set as $set_json) { ?>
  <img src="<?= $set_json["set_img_url"] ?>"><?= htmlspecialchars_decode($set_json["descr"]) ?>
<? } ?>
  <input type="text" name="set" placeholder="Set ID" value="<?= implode(" ", $set_numbers) ?>">
 </h2>
Show colors that might be confused with
<select name="type">
<?
foreach ($blindnesses as $blindness_type=>$color_set)
	echo "<option value='$blindness_type'", $_GET["type"] == $blindness_type ? " selected" : "", ">$blindness_type</option>\n";
?>
</select>.<br>
<input type="checkbox" name="dark" id="Dark" value="50" <?= array_key_exists("dark", $_GET) ? "checked" : "" ?>> <label for="Dark">Simulate dim lighting</label><br>
<input type="submit" value="Show similarly colored parts">
</form>

<?
// Get rid of parts only in one color
foreach ($parts_bydesign as $key=>&$design)
	if (count($design) === 1)
		unset($parts_bydesign[$key]);

// Make similar color banks for each part
foreach ($parts_bydesign as $design) {
	$similar_color_lists = make_similar_color_list($similar_color_bank, array_column($design, "ldraw_color_id"));
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
?>
 </body>
</html>
