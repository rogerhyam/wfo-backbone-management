# FOR USE ON STAGING SERVER

# This script should be copied to /var/wfo-list and run from there
# It will be used when it, and the rest of the code, doesn't exist.
# it also needs to be in the same place as the other sync_from and sync_to scripts
mkdir -p /var/wfo-list/rhakhis/api 
cd /var/wfo-list/rhakhis/api
git stash
git pull
mkdir -p /var/wfo-list/rhakhis/api/data/db_dumps # on first install data dir needs to be chown'd to allow www-data write access
mkdir -p /var/wfo-list/rhakhis/api/www/downloads
mkdir -p /var/wfo-list/rhakhis/api/bulk/csv # on first install this needs to be chown'd to allow www-data write access

