<pre><?php
$fp = fopen("../data/codes.txt", "r");
$parts_bydesign = [];

$line = fgetcsv($fp); // Burn first line
while (($line = fgetcsv($fp, 0, "\t")) !== FALSE) {
	list($design, $color, $item) = $line;
	if ($color !== "(Not Applicable)") {
		$parts_bydesign["ID=" . $design][$color][] = $item;
		$parts_byid[$item] = [$design, $color];
	}
}
array_multisort(array_map("count", $parts_bydesign), SORT_DESC, $parts_bydesign);
foreach ($parts_bydesign as $design=>$colors) {
	if (count($colors) > 45) {
		echo "<a href='http://brickset.com/parts/design-", substr($design, 3), "'>", $design, "</a> in ", count($colors), " colors\n";
		echo implode(",", array_keys($colors)), "\n\n";
	}
}
?></pre>
