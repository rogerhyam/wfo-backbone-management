# FOR USE ON STAGING AND POSSIBLY LIVE SERVER

# This script should be copied to /var/wfo-list and run from there
# It will be used when it, and the rest of the code, doesn't exist.
# it also needs to be in the same place as the other sync_from and sync_to scripts
# This takes the latest daily backup  of the db 
# and overwrites the local promethius database with it.
filename=$(ls -tp ../data/db_dumps | grep -v /$ | head -1)
filepath="../data/db_dumps/${filename}"
mysql -e "DROP DATABASE IF EXISTS promethius"
mysql -e "CREATE DATABASE promethius"
start=$(date +"%H:%M:%S")
echo "This may take a while. Starting at $start"
gunzip < $filepath | mysql promethius
end=$(date +"%H:%M:%S")
echo "Finished at $end"
echo "All done!"
