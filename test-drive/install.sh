#!/bin/sh

# copy the hello-login plugin files into WordPress container, to the plugin folder
docker exec hello-login-wordpress-1 mkdir -p /var/www/html/wp-content/plugins/hello-login
docker exec hello-login-wordpress-1 mkdir -p /var/www/html/wp-content/mu-plugins
docker cp ../readme.txt hello-login-wordpress-1:/var/www/html/wp-content/plugins/hello-login/
docker cp ../hello-login.php hello-login-wordpress-1:/var/www/html/wp-content/plugins/hello-login/
docker cp ../css hello-login-wordpress-1:/var/www/html/wp-content/plugins/hello-login/
docker cp ../js hello-login-wordpress-1:/var/www/html/wp-content/plugins/hello-login/
docker cp ../includes hello-login-wordpress-1:/var/www/html/wp-content/plugins/hello-login/
docker cp ../languages hello-login-wordpress-1:/var/www/html/wp-content/plugins/hello-login/
docker cp ../tools/local-env/mu-plugins hello-login-wordpress-1:/var/www/html/wp-content/mu-plugins/
docker exec hello-login-wordpress-1 chown -R www-data:www-data /var/www/html/wp-content/plugins/hello-login
