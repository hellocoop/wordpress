#!/bin/sh

docker run -it --rm \
--volumes-from hello-login-wordpress-1 \
--network container:hello-login-wordpress-1 \
-e WORDPRESS_DB_USER=exampleuser \
-e WORDPRESS_DB_PASSWORD=examplepass \
-e WORDPRESS_DB_NAME=exampledb \
-e WORDPRESS_DB_HOST=db \
wordpress:cli "$@"
