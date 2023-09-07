# Script to start at just gone midnight on the solstice day
# this will create the main files needed from the db

# BEFORE: Comment out the backup in the crontab or it will overlap with these scripts
# AFTER: Uncomment the backup in the crontab so it can carry on every day.
# AFTER: Import the solr file - delete it if there is a test one there.
# curl -X POST -H 'Content-Type: application/json' 'http://localhost:8983/solr/wfo/update' --data-binary '{"delete":{"query":"classification_id_s:2022-12"} }' --user wfo:****
# curl -X POST -H 'Content-Type: application/json' 'http://localhost:8983/solr/wfo/update' --data-binary '{"commit":{} }' --user wfo:****
# curl -X POST -H 'Content-type:application/json' 'http://localhost:8983/solr/wfo/update?commit=true' --data-binary @plant_list_2022-12.json  --user wfo:****

echo "Backing Up"

# run the backup
sh ./db_backup_live.sh

# generate the plant list - 90 mins
echo "Generating Plant List"
php -d memory_limit=3G gen_plant_list.php

# generate the catalogue of life data package file - 90 mins
echo "Generating CoLDP"
php -d memory_limit=3G gen_coldp.php

# Mapping file from IPNI to WFO - 10 mins
echo "Generating IPNI Mapping File"
php gen_ipni_to_wfo.php

# remove the dwc files - we are going to recreate them
echo "Generating CoLDP"
rm ../www/downloads/dwc/*.zip

# Uber DwC file - 120mins?
php -d memory_limit=5G gen_uber_dwc_file.php

# R Package file from uber file 10 mins
php gen_uber_R_dwc_file.php

# family files - creates 20 families each time
php -d memory_limit=2048M gen_family_dwc_file.php
php -d memory_limit=2048M gen_family_dwc_file.php
php -d memory_limit=2048M gen_family_dwc_file.php
php -d memory_limit=2048M gen_family_dwc_file.php
php -d memory_limit=2048M gen_family_dwc_file.php
php -d memory_limit=2048M gen_family_dwc_file.php
php -d memory_limit=2048M gen_family_dwc_file.php
php -d memory_limit=2048M gen_family_dwc_file.php
php -d memory_limit=2048M gen_family_dwc_file.php
php -d memory_limit=2048M gen_family_dwc_file.php








