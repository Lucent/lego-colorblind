<?php
header("Content-Type: application/json");
$fp = fopen("../data/sets.csv", "r");
$autocomplete_list = [];

$line = fgetcsv($fp); // Burn first line
while (($line = fgetcsv($fp)) !== FALSE) {
	list($set_id, $descr, $year, $theme, $pieces) = $line;
	$autocomplete_list[] = $set_id . " [" . $descr . " (" . $year . ")]";
//		"id" => $set_id,
//		"name" => $descr,
//		"year" => $year
}

echo json_encode($autocomplete_list);
?>
