# this was used for building at the start of the project
# but is defunct with move to staging server
destination="/Users/rogerhyam/rhakhis-deploy/rhakhis";

# move the ui
rm -r ${destination}/ui/*
cp -r ../wfo-backbone-ui/wfo/build/* ${destination}/ui

# move the needed directories from the api
echo "include scripts"
cp -r include ${destination}/api
echo "moving scripts"
cp -r scripts ${destination}/api
echo "moving www"
rsync -av --progress --delete www ${destination}/api --exclude downloads
echo "moving bulk"
rsync -av --progress --delete bulk ${destination}/api --exclude csv
echo "moving files"
cp composer.json ${destination}/api/composer.json
cp composer.lock ${destination}/api/composer.lock
cp config.php ${destination}/api/config.php



