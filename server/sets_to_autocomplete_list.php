<?php
header("Content-Type: application/json");
$fp = fopen(__DIR__ . "/../data/sets.csv", "r");
$autocomplete_list = [];

$line = fgetcsv($fp); // Burn first line
while (($line = fgetcsv($fp)) !== FALSE) {
	list($set_id, $descr, $year, $theme, $pieces) = $line;
	$autocomplete_list[] = [$pieces, $set_id . " " . $descr . " (" . $year . ")"];
}
array_multisort(array_column($autocomplete_list, 0), SORT_DESC, $autocomplete_list);
$autocomplete = array_column($autocomplete_list, 1);

echo json_encode($autocomplete);
?>
