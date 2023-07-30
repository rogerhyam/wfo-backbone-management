# FOR USE ON STAGING SERVER

# This script should be copied to /var/wfo-list and run from there
# It will be used when it, and the rest of the code, doesn't exist.
# it also needs to be in the same place as the other sync_from and sync_to scripts
# This takes the latest daily backup  of the db 
# and overwrites the local promethius database with it.

echo "Restoring Rhakhis Bulk database"
filepath="rhakhis/api/data/db_bulk_dumps/rhakhis_bulk.sql.gz"
mysql -e "DROP DATABASE IF EXISTS rhakhis_bulk"
mysql -e "CREATE DATABASE rhakhis_bulk"
start=$(date +"%H:%M:%S")
echo "This may take a while. Starting at $start"
gunzip < $filepath | mysql rhakhis_bulk
end=$(date +"%H:%M:%S")
echo "Finished at $end"
echo "All done!"
