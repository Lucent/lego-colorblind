<!DOCTYPE html>
<html lang="en">
 <head>
  <title>Find Similar Color Lego Parts for Colorblindness</title>
  <link href="css/screen-default.css" rel="stylesheet">
  <link href="awesomplete/awesomplete.css" rel="stylesheet">
  <script src="awesomplete/awesomplete.js" async></script>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script type="module" src="client/filter.js"></script>
  <script defer src="https://www.googletagmanager.com/gtag/js?id=UA-163754119-1"></script>
  <script>
  window.dataLayer = window.dataLayer || [];
  function gtag() { dataLayer.push(arguments); }
  gtag('js', new Date());
  gtag('config', 'UA-163754119-1');
  </script>
 </head>
 <body>
<?php
require_once "../server/color_difference.class.php";
require_once "../server/rebrick_colors_to_array.php";
require_once "../server/color-blind.php";
require_once "../server/functions.php";
require_once "../apikey.php";

$darken_factor = 0;
if (array_key_exists("lighting", $_GET))
	if (is_numeric($_GET["lighting"]))
		$darken_factor = $_GET["lighting"];

$get_type = "normal vision";
if (array_key_exists("type", $_GET))
	$get_type = $_GET["type"];

if (array_key_exists("set", $_GET)) {
	if (strpos($_GET["set"], ","))
		$get_sets = explode(",", $_GET["set"]);
	else
		$get_sets = explode(" ", $_GET["set"]);
	$get_sets = array_unique($get_sets);
} else {
	$get_sets = [];
}

$set_numbers = [];
foreach ($get_sets as $set)
	if (trim($set) !== "")
		$set_numbers[] = clean_set_number($set);

?>
<aside>
<h1>Find similar color parts in set</h1>
<form method="get" action=".">
 <div><input type="search" data-multiple name="set" placeholder="set name or number" value="<?= implode(",", $set_numbers) ?>"></div>
 <p><label for="Blindness">viewed with</label>
 <select name="type" id="Blindness">
<?php
foreach (array_merge($blindness_matrix, $blindness_brian) as $blindness_type=>$color_set)
	echo "  <option value='$blindness_type'", $get_type == $blindness_type ? " selected" : "", ">$blindness_type</option>\n";
?>
 </select> <label>under</label> <select name="lighting" id="Lighting"><option value="0">normal</option><option value="50" <?= $darken_factor >= 50 ? "selected" : "" ?>>dim</option></select> <label for="Lighting">light</label></p>
 <button type="submit">Show similar parts</button>
<!-- <button type="submit" name="view" value="colors">All colors in set</button> -->
</form>
</aside>
<br><progress id="Progress" max="100"></progress>
<?php
flush_output();
$sets = [];
foreach ($set_numbers as $set_number) {
	$set_json = get_set_json($set_number, $api_key, "parts/");
	if ($set_json !== FALSE)
		$sets[] = json_decode($set_json, true);
	else {
		echo "<h4>Set not found</h4>";
		set_progress("Progress", 100);
		echo '<script>document.getElementById("Progress").style.display = "none";</script>';
		exit;
	}
}
set_progress("Progress", 75);

$parts_byelement = [];
foreach ($sets as $single_set) {
	if (empty($single_set["results"])) {
		echo "<h4>No inventory available for {$single_set["descr"]} yet, sorry.</h4>\n";
		exit;
	} else foreach ($single_set["results"] as $part) {
		unset($part["part"]["external_ids"]);
		unset($part["color"]["external_ids"]);
		if ($part["is_spare"] != 1) {
			if (array_key_exists($part["element_id"], $parts_byelement))
				$parts_byelement[$part["element_id"]]["quantity"] += $part["quantity"];
			else
				$parts_byelement[$part["element_id"]] = $part;
		} else {
			if (array_key_exists($part["element_id"], $parts_byelement))
				@$parts_byelement[$part["element_id"]]["extra"] += $part["quantity"];
			else {
				// Extra piece that isn't also a normal piece: brick separator 4654448
			}
		}
	}
}

$parts_bydesign = []; $parts_bycolor = [];
foreach ($parts_byelement as $part) {
	$parts_bydesign[$part["part"]["part_num"]][] = $part;
	$parts_bycolor[$part["color"]["id"]][] = $part;
}

if (array_key_exists($get_type, array_merge($blindness_matrix, $blindness_brian)))
	$similar_color_bank = $get_type;
else {
	echo "<h4>Invalid color vision type selected.</h4>";
	exit;
}

/*
if (array_key_exists("set", $_GET) && count($set) === 0) {
	echo "<h4>No valid set IDs given.</h4>";
	exit;
}*/
foreach ($sets as $set_json) {
	$parts_count = count_parts($set_json["results"]);
?>
<h2>
<?php
	$descr_json = get_set_json($set_number, $api_key, "/");
	if ($descr_json !== FALSE)
		$description = json_decode($descr_json, true);
?>
 <img src="<?= $description["set_img_url"] ?>">
 <span><big><?= $description["set_num"] ?></big><br><a href="<?= $description["set_url"] ?>"><?= htmlspecialchars_decode($description["name"]) ?> (<?= $description["year"] ?>)</a><br>
 <?= /* $description["num_parts"] . " / " . */ $parts_count[0] . "<sup>+" . $parts_count[1]  ?></sup> parts<sup>extra</parts></span>
</h2>
<?php
}
set_progress("Progress", 100);

$view = "parts";
if ($get_sets == []) { ?>
	<p>Does colorblindness make it difficult to see whether parts in a set are actually different colors?<br><br>
	Enter a set name or number to see a list of parts (and their count) in colors similar enough to be confusing for the selected color deficiency. <a href="/set=10197-1">Try a sample</a>.</p>
<?php
} else if (array_key_exists("view", $_GET) && $_GET["view"] === "colors")
	show_similar_colors($parts_bycolor, $similar_color_bank);
else
	show_similar_colored_parts($parts_bydesign, $similar_color_bank);
?>

<script>document.getElementById("Progress").style.display = "none";</script>
<footer>
<addr>Created by <a href="//dayah.com">Michael Dayah</a>. Contact color at brick.design.<br>Parts retreived from <a href="https://rebrickable.com/api/">Rebrickable API</a>. Browser testing done with <a href="https://www.browserstack.com/"><img src="browserstack.svg" style="height: 2em;"></a></addr>
</footer>

 </body>
</html>
