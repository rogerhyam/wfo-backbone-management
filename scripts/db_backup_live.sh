# will backup the live database as specified in the config file.
# suitable for being run by cron
databasename=$(php db_live.php)
today=$(date +"%Y-%m-%d-%H-%M-%S")
filename="../data/db_dumps/${databasename}_${today}.sql"
mysqldump $databasename > $filename
echo $filename
#sed -i .bak 's/utf8mb4_0900_ai_ci/utf8mb4_general_ci/g' $filename
#rm "${filename}.bak"
gzip $filename

# no longer create a symbolic link - we don't use it.
#rm "../data/db_dumps/latest.sql.gz"
# ln -s "${filename}.gz" "../data/db_dumps/latest.sql.gz"

# move it to the hidden place so the sandbox can pick it up
dir_name=$(php db_hidden_dir.php)
dir_path="../www/downloads/${dir_name}/"
mkdir -p $dir_path
cp  "${filename}.gz" "${dir_path}latest.sql.gz"

# prevent backups filling disk
find ../data/db_dumps -type f -mtime +45 -delete


