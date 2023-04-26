#!/bin/sh

docker run -it --rm \
--volumes-from hello-login-wordpress-1 \
--network container:hello-login-wordpress-1 \
-e WORDPRESS_DB_USER=hellouser \
-e WORDPRESS_DB_PASSWORD=hellopass \
-e WORDPRESS_DB_NAME=hellodb \
-e WORDPRESS_DB_HOST=db \
wordpress:cli "$@"
