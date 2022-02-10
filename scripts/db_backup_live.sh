
# will backup the live database as specified in the config file.
# suitable for being run by cron
databasename=$(php db_live.php)
today=$(date +"%Y-%m-%d-%H-%M-%S")
filename="../data/db_dumps/${databasename}_${today}.sql.gz"
mysqldump $databasename | gzip > $filename
rm "../data/db_dumps/latest.sql.gz"
ln -s $filename "../data/db_dumps/latest.sql.gz"

