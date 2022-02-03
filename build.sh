
destination="/Users/rogerhyam/rhakhis-deploy/rhakhis";

# move the ui
cp -r ../wfo-backbone-ui/wfo/build ${destination}/ui

# move the needed directories from the api
cp -r include ${destination}/api/include
cp -r scripts ${destination}/api/scripts
cp -r www ${destination}/api/www
cp -r composer.json ${destination}/api/composer.json
cp -r composer.lock ${destination}/api/composer.lock
cp -r config.php ${destination}/api/config.php



