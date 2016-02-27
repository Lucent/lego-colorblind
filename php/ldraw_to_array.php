<?
$fp = fopen("../data/ldconfig.ldr", "r");
$regexp = '/!COLOUR\s(\w+)\s+CODE\s+(\d+)\s+VALUE\s+#(\w{6})\s+EDGE\s+#\w{6}(\s+ALPHA\s+(\d+))?/';
$regexp = '/!COLOUR\s(\w+)\s+CODE\s+(\d+)\s+VALUE\s+#(\w{6})\s+EDGE\s+#\w{6}(\s+ALPHA\s+(\d+))?/';
$ldraw_colors = [];

while (($line1 = fgets($fp)) !== FALSE && ($line2 = fgets($fp)) !== FALSE) {
	echo $line1 . $line2;
	if (preg_match($regexp, $line1 . $line2, $matches) == 1) {
		list($r, $g, $b) = sscanf($matches[3], "%02x%02x%02x");
		if (array_key_exists(5, $matches))
			$a = (int) $matches[5];
		else
			$a = 255;
		$ldraw_colors[(int) $matches[2]] = ["RGBA" => [$r, $g, $b, $a], "Name" => $matches[1]];
	}
}

//echo "var ldraw_colors = " . json_encode($colors) . ";";
?>
