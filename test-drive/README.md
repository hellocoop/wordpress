# Test Drive

## Local Testing

Use `docker compose` (or the older `docker-compose`) to run the containers required for local testing. The `up.sh` and
`down.sh` are simple wrappers around `docker compose` commands that also open a browser and prune volumes.

Once the containers are running use the following URLs:
* http://localhost:8080/ - WordPress instance
* http://localhost:8025/ - MailHog web interface
* http://localhost:8081/ - phpMyAdmin web interface

Once the WordPress instance has basic configuration and an admin account you can install the latest released Hellō Login
plugin from the WordPress plugin repo.

You can run the `install.sh` script to copy local plugin files into the docker container.

## WordPress CLI

Use the wp-cli.sh script to run [WordPress CLI](https://wp-cli.org/) commands. Command reference at
https://developer.wordpress.org/cli/commands/

### Examples ###
List users:
```shell
./wp-cli.sh user list
```

Get Hellō user id for user 1:
```shell
./wp-cli.sh user meta get 1 hello-login-subject-identity
```

Unlink user 1 from Hellō:
```shell
./wp-cli.sh user meta delete 1 hello-login-subject-identity
```

Get the Hellō Login plugin settings:
```shell
./wp-cli.sh option get hello_login_settings
```

Get the client id configured for the Hellō Login plugin:
```shell
./wp-cli.sh option pluck hello_login_settings client_id
```

Delete all settings for the Hellō Login plugin:
```shell
./wp-cli.sh option delete hello_login_settings
```

Delete only the client id for the Hellō Login plugin settings:
```shell
./wp-cli.sh option patch delete hello_login_settings client_id
```

Get the plugin logs:
```shell
./wp-cli.sh option get hello-login-logs
```

Delete the plugin logs:
```shell
./wp-cli.sh option delete hello-login-logs
```

Drop into a PHP shell:
```shell
./wp-cli.sh shell
```

## Docker Cheat Sheet

### Bash Session Inside a Container

Start a bash session inside the WordPress container:
```shell
docker exec -it hello-login-wordpress-1 bash
```

* WordPress root folder: `/var/www/html/`
* plugin folder: `/var/www/html/wp-content/plugins/`

### Copy Out of a Container

Copy a plugin out of the WordPress directory:
```shell
docker cp hello-login-wordpress-1:/var/www/html/wp-content/plugins/akismet ./
```

### Copy Into a Container

Make the target directory:
```shell
docker exec hello-login-wordpress-1 mkdir -p /var/www/html/wp-content/plugins/hello-login
```

Copy a file inside the container:
```shell
docker cp ../readme.txt hello-login-wordpress-1:/var/www/html/wp-content/plugins/hello-login/
```

Copy a folder inside the container:
```shell
docker cp ../includes hello-login-wordpress-1:/var/www/html/wp-content/plugins/hello-login/
```

Change the owner such that WordPress can edit or delete:
```shell
docker exec hello-login-wordpress-1 chown -R www-data:www-data /var/www/html/wp-content/plugins/hello-login
```

## phpMyAdmin

Docker compose has a container that runs phpMyAdmin, connected to the MySQL database.

* navigate to http://localhost:8081
* login with:
  * Server: `db`
  * Username: `hellouser`
  * Password: `hellopass`
* browse and query the `hellodb` database


## MailHog

Docker compose has a container that runs MailHog. WordPress requires an SMTP plugin in order to send emails to MailHog.

* install the "WP Mail SMTP by WPForms" plugin
* start the setup wizard
  * Choose Your SMTP Mailer: "Other SMTP"
  * SMTP Host: `mailhog`
  * Encryption: "None"
  * SMPT Port: 1025
  * Auto TLS: disable
  * Authentication: disable
* navigate to http://localhost:8025/ 
* you should see an email with subject: "WP Mail SMTP Automatic Email Test"
