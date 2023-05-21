# Must Use Plugins

Simple plugins which are used in local development and which are installed as Must Use Plugins
into `wp-content/mu-plugins` instead of `wp-content/plugins`.

In WordPress navigate to Plugins page and click the "Must-Use" filter at the top to see these plugins, they are not
listed otherwise.

For more details see:
https://wordpress.org/documentation/article/must-use-plugins/


## Hellō Login Constants

Copy `hello-login-constants.php.sample` to `hello-login-constants.php` or similar PHP file and set the required
constants in order to override defaults.

The complete list of constants is in `Hello_Login_Option_Settings::environment_settings` and the default values
are in `Hello_Login::bootstrap`.


## MailHog PhpMailer Setup

Configures the PHP mailer to connect to a locally running instance of [MailHog](https://github.com/mailhog/MailHog).
