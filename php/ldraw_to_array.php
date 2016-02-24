<?
//header("Content-Type: application/json");
$fp = fopen("ldcfgalt.ldr", "r");
$regexp = '/!COLOUR\s(\w+)\s+CODE\s+(\d+)\s+VALUE\s+#(\w{6})\s+EDGE\s+#\w{6}(\s+ALPHA\s+(\d+))?/';
$colors = [];

while (($line = fgets($fp)) !== false)
	if (preg_match($regexp, $line, $matches) == 1) {
		list($r, $g, $b) = sscanf($matches[3], "%02x%02x%02x");
		$colors[] = ["LD" => (int) $matches[2], "RGBA" => [$r, $g, $b, $matches[5] ? (int) $matches[5] : 255], "Name" => $matches[1]];
	}

echo "var ldraw_colors = " . json_encode($colors) . ";";
?>
