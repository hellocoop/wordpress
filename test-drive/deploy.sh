#!/usr/bin/env bash

DOCKER_VOLUME=/var/lib/docker/volumes/wordpress_wordpress/_data/wp-content/plugins/hello-login/

cd wordpress || exit

sudo mkdir -p $DOCKER_VOLUME
#sudo cp -v -u CHANGELOG.md docker-compose.wp-env.yml docker-compose.yml hello-login.php readme.txt $DOCKER_VOLUME
sudo cp -v -u CHANGELOG.md hello-login.php readme.txt $DOCKER_VOLUME
sudo cp -r -v -u css includes languages $DOCKER_VOLUME
sudo chown -R www-data:www-data $DOCKER_VOLUME

