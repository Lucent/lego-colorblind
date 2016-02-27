<?
// Paste https://rebrickable.com/colors into Excel, remove the first 3 columns, and save as CSV.
$fp = fopen("data/colors.csv", "r");
$regexp = '/^(\d+),([^,]+),#(\w{6})/';
$ldraw_colors = [];

while (($line = fgets($fp)) !== FALSE) {
	if (preg_match($regexp, $line, $matches) == 1) {
		list($r, $g, $b) = sscanf($matches[3], "%02x%02x%02x");
		if (strpos($matches[2], "Trans") !== FALSE)
			$a = 128;
		else
			$a = 255;
		$ldraw_colors[(int) $matches[1]] = ["RGBA" => [$r, $g, $b, $a], "Name" => $matches[2]];
	}
}
?>
