<pre><?php
$fp = fopen("../data/codes.txt", "r");
$parts_bydesign = [];

$line = fgetcsv($fp); // Burn first line
while (($line = fgetcsv($fp, 0, "\t")) !== FALSE) {
	list($design, $color, $item) = $line;
	if ($color !== "(Not Applicable)")
		$parts_bydesign[$item] = ["Color" => $color, "Design" => $design];
}

$fh = fopen("../data/piecesorder.csv", "r");
$line = fgetcsv($fh); // Burn first line
while (($line = fgetcsv($fh, 0, "\t")) !== FALSE) {
	list($item, $qty) = $line;
	echo $item, "\t", $qty, "\t", $parts_bydesign[$item]["Design"], "\t", $parts_bydesign[$item]["Color"] , "\n";
}

?></pre>
