<?
print_r($_POST["similar_colors"]);
?>
<!DOCTYPE html>
<html>
 <head>
  <title>Differentiate Similar Color Lego Parts for Colorblindness</title>
  <script src="../php/ldraw_to_array.php"></script>
  <script src="../js/daltonize.js"></script>
  <script src="../js/ColorMatrix.js"></script>
  <script src="../js/delta_e.js"></script>
  <script>
  function find_in_arrays(haystack, needle) {
	for (var x = 0; x < haystack.length; x++)
		if (haystack[x].indexOf(needle) !== -1)
			return x;
	return false;
  }

  function add_color_pair(similar, first, second) {
  	var idx = find_in_arrays(similar, first);
	if (idx !== false) {
		if (similar[idx].indexOf(second) === -1)
			similar[idx].push(second);
	} else {
		similar.push([first, second]);
	}
  }

  var similar = [[]];
  for (var x = 0; x < ldraw_colors.length; x++) {
  	for (var y = x + 1; y < ldraw_colors.length; y++) {
		if (cie1994(color_transform(ldraw_colors[x].RGB, "Deuteranomaly"), color_transform(ldraw_colors[y].RGB, "Deuteranomaly")) < 10)
			add_color_pair(similar, ldraw_colors[x].LD, ldraw_colors[y].LD);
	}
  }
  function set_form_value() {
  	document.getElementById("similar_colors").value = JSON.stringify(similar);
  }
  </script>
 </head>
 <body>
 <form method="post" action="">
  <input type="hidden" name="similar_colors" id="similar_colors">
  <input type="submit" onclick="set_form_value();">
 </form>
 </body>
</html>
