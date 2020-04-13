<?php
// wget colors.csv.gz from rebrickable
$fp = fopen("data/colors.csv", "r");
$ldraw_colors = [];
$line = fgetcsv($fp); // Burn first line
while (($line = fgetcsv($fp, 0, ",")) !== FALSE) {
	list($id, $name, $rgb, $trans) = $line;
	list($r, $g, $b) = sscanf($rgb, "%02x%02x%02x");
	if ($trans === "t")
		$a = 128;
	else
		$a = 255;
	$ldraw_colors[(int) $id] = ["RGBA" => [$r, $g, $b, $a], "Name" => $name];
}
?>
