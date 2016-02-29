<!DOCTYPE html>
<html>
 <head>
  <title>Differentiate Similar Color Lego Parts for Colorblindness</title>
  <script src="js/awesomplete/awesomplete.js" async></script>
  <script>
window.onload = function() {
var ajax = new XMLHttpRequest();
ajax.open("GET", "php/sets_to_autocomplete_list.php", true);
ajax.onload = function() {
	var list = JSON.parse(ajax.responseText);
	new Awesomplete(document.querySelector("input[data-multiple]"), {
		maxItems: 25,
		autoFirst: true,

		filter: function(text, input) {
			var regex = /^([^\s]+)\s([^(]+)/;
			var parts = text.match(regex);
			input = input.match(/[^,]*$/)[0].trim();
			// Only match set numbers from the start, but match words anywhere
			return parts[1].indexOf(input) === 0 ||
				parts[2].toLowerCase().indexOf(input.toLowerCase()) !== -1;
		},

		replace: function(text) {
			var before = this.input.value.match(/^.+,\s*|/)[0];
			// On select remove everything but the set number
			this.input.value = before + text.match(/^[^\s]+/)[0] + ", ";
		},

		sort: function(a, b) {
			// Sort by year released, descending
			return parseInt(b.substr(-6, 4), 10) - parseInt(a.substr(-6, 4), 10);
		},

		list: list
	});
};
ajax.send();
};
  </script>
  <link href="js/awesomplete/awesomplete.css" rel="stylesheet" type="text/css">
  <link href="css/screen-default.css" rel="stylesheet" type="text/css">
  <meta name="viewport" content="width=device-width, initial-scale=1">
 </head>
 <body>
<?
require_once "php/color_difference.class.php";
require_once "php/rebrick_colors_to_array.php";
require_once "php/color-blind.php";
require_once "php/functions.php";
require_once "apikey.php";

if (array_key_exists("type", $_GET))
//	if (array_key_exists($_GET["type"], $blindnesses))
		$similar_color_bank = $_GET["type"];

if (array_key_exists("dark", $_GET))
	if (is_numeric($_GET["dark"]))
		$darken_factor = $_GET["dark"];

if (array_key_exists("set", $_GET)) {
	if (strpos($_GET["set"], ","))
		$sets = explode(",", $_GET["set"]);
	else
		$sets = explode(" ", $_GET["set"]);
	$sets = array_unique($sets);

	$set_numbers = [];
	foreach ($sets as $set)
		if (trim($set) !== "")
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
	if (empty($set_json["parts"]))
		echo "No inventory for ", $set_json["descr"], "\n";
	else foreach ($set_json["parts"] as $part) {
		if ($part["type"] === 1) {
			if (array_key_exists($part["element_id"], $parts_byelement))
				$parts_byelement[$part["element_id"]]["qty"] += $part["qty"];
			else
				$parts_byelement[$part["element_id"]] = $part;
		} else {
			if (array_key_exists($part["element_id"], $parts_byelement))
				$parts_byelement[$part["element_id"]]["extra"] += $part["qty"];
			else {
				// Extra piece that isn't also a normal piece: brick separator 4654448
			}
		}
	}
}

$parts_bydesign = [];
foreach ($parts_byelement as $part)
	$parts_bydesign[$part["part_id"]][] = $part;
?>
<h1>Find parts that occur in multiple similar colors</h1>
<form method="get" action=".">
  <input type="text" data-multiple name="set" placeholder="Set ID" value="<?= implode(",", $set_numbers) ?>"><br>
Show colors that might be confused with
<select name="type">
<?
foreach (array_merge($blindness_matrix, $blindness_brian) as $blindness_type=>$color_set)
	echo "<option value='$blindness_type'", $_GET["type"] == $blindness_type ? " selected" : "", ">$blindness_type</option>\n";
?>
</select>.<br>
<input type="checkbox" name="dark" id="Dark" value="50" <?= array_key_exists("dark", $_GET) ? "checked" : "" ?>> <label for="Dark">Simulate dim lighting</label><br>
<input type="submit" value="Show similarly colored parts">
</form><br>
<? foreach ($set as $set_json) {
	$parts_count = count_parts($set_json["parts"]);
?>
<h2 style="float: left;">
 <img src="<?= $set_json["set_img_url"] ?>">
 <span style="float: right;"><?= $set_json["set_id"] ?><br><?= htmlspecialchars_decode($set_json["descr"]) ?><br><?= $parts_count[0] . "<sup>+" . $parts_count[1]  ?></sup> pieces</span>
</h2>
<? } ?>
<br style="clear: both;">
<?
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
		foreach($similar_color_lists as $color_list) {
			echo "<div>\n";
			foreach ($design as $part) {
				if (in_array($part["ldraw_color_id"], $color_list) === TRUE) {
					echo "<figure><img src='" . $part["part_img_url"] . "'><figcaption>" . $part["color_name"] . " (" .  $part["qty"];
					if (array_key_exists("extra", $part))
						echo "<sup>+" . $part["extra"] . "</sup>";
					echo ")</figcaption></figure>\n";
				}
			}
			echo "</div>\n";
		}
		$confusing_parts_count++;
	}
}

if (empty($parts_bydesign))
	echo "<h3>Each part in this set occurs in only one color.</h3>";
elseif ($confusing_parts_count === 0)
	echo "<h3>No parts in this set occur in similar, confusing colors for the chosen color vision type and lighting.</h3>";
?>
 </body>
</html>
