# FOR USE ON DEV Machine 

# This script should be copied to /var/wfo-list and run from there
# It will be used when it, and the rest of the code, doesn't exist.
# it also needs to be in the same place as the other sync_from and sync_to scripts
rsync -Pav -e "ssh -i ~/.ssh/wfo-aws-01.pem" --delete  ubuntu@wfo-staging.rbge.info:/var/wfo-list/rhakhis/api/data/db_dumps ../data/