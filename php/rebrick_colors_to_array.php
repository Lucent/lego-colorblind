<?
$fp = fopen("data/colors.csv", "r");
$regexp = '/^(\d+),([^,]+),#(\w{6})/';
$ldraw_colors = [];

while (($line = fgets($fp)) !== FALSE) {
	if (preg_match($regexp, $line, $matches) == 1) {
		list($r, $g, $b) = sscanf($matches[3], "%02x%02x%02x");
		if (array_key_exists(4, $matches))
			$a = (int) $matches[4];
		else
			$a = 255;
		$ldraw_colors[(int) $matches[1]] = ["RGBA" => [$r, $g, $b, $a], "Name" => $matches[2]];
	}
}
?>
