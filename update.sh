wget -nv -O data/sets.csv.gz https://cdn.rebrickable.com/media/downloads/sets.csv.gz
gunzip -f data/sets.csv.gz
php server/sets_to_autocomplete_list.php > public/set_autocomplete_list.json 

wget -nv -O data/colors.csv.gz https://cdn.rebrickable.com/media/downloads/colors.csv.gz
gunzip -f data/colors.csv.gz
