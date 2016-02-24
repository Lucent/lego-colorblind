<?
if (!empty($_POST)) {
	$similar_file = "../cache" . DIRECTORY_SEPARATOR . "similar_colors.php";
	$fh = fopen($similar_file, "w");
	fwrite($fh, "<?\n");
	foreach ($_POST as $group_name=>$color_group)
		fwrite($fh, "\$similar_colors[\"" . $group_name . "\"] = " . $color_group . ";\n");
	fwrite($fh, "\n?>");
	fclose($fh);
}
?>
<!DOCTYPE html>
<html>
 <head>
  <title>Differentiate Similar Color Lego Parts for Colorblindness</title>
  <script src="../php/ldraw_to_array.php"></script>
  <script src="../js/daltonize.js"></script>
  <script src="../js/delta_e.js"></script>
  <script>
  var THRESHOLD = 7;
  function find_in_arrays(haystack, needle) {
	for (var x = 0; x < haystack.length; x++)
		if (haystack[x].indexOf(needle) !== -1)
			return x;
	return false;
  }

  function remove_duplicates(arr) {
  }

  function add_color_pair(similar, first, second) {
  	var first_found = find_in_arrays(similar, first);
  	var second_found = find_in_arrays(similar, second);

	if (first_found !== false && second_found !== false && first_found !== second_found) {
		var combined = similar[first_found].concat(similar[second_found]);
		var uniquearr = combined.filter(function(item, pos, self) {
			return self.indexOf(item) == pos;
		});
		similar.splice(first_found, 1);
		similar.splice(second_found, 1);
		similar.push(uniquearr);
	}
	else if (first_found !== false) {
		if (similar[first_found].indexOf(second) === -1)
			similar[first_found].push(second);
	} else if (second_found !== false) {
		if (similar[second_found].indexOf(first) === -1)
			similar[second_found].push(first);
	} else {
		similar.push([first, second]);
	}
  }

  function make_similar_colors(blindness_type) {
  	var similar = [];
	for (var x = 0; x < ldraw_colors.length; x++) {
	  	for (var y = x + 1; y < ldraw_colors.length; y++) {
			if (cie1994(color_transform(ldraw_colors[x].RGBA, blindness_type), color_transform(ldraw_colors[y].RGBA, blindness_type)) < THRESHOLD)
				add_color_pair(similar, ldraw_colors[x].LD, ldraw_colors[y].LD);
		}
	}
	return similar;
  }
  function set_form_value() {
  	var input;
	for (var blind_type in blindnesses) {
		input = document.createElement("input");
		input.type = "hidden";
		input.name = blind_type;
		input.value = JSON.stringify(make_similar_colors(blind_type));
		document.getElementById("blindness").appendChild(input);
	}
  }
  </script>
 </head>
 <body>
 <form method="post" action="" id="blindness">
  <input type="submit" onclick="set_form_value();">
 </form>
 </body>
</html>
