<?php
/**
 * Phpstan bootstrap file.
 *
 * @package   Hello_Login
 * @author    Jonathan Daggerhart <jonathan@daggerhart.com>
 * @author    Tim Nolte <tim.nolte@ndigitals.com>
 * @copyright 2015-2020 daggerhart
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GPL-2.0+
 * @link      https://github.com/daggerhart
 */

// Define WordPress language directory.
define( 'WP_LANG_DIR', 'wordpress/src/wp-includes/languages/' );

define( 'COOKIE_DOMAIN', 'localhost' );
define( 'COOKIEPATH', '/');

// Define Plugin Globals.
define( 'HELLO_LOGIN_CLIENT_ID', bin2hex( random_bytes( 32 ) ) );
define( 'HELLO_LOGIN_ENDPOINT_LOGIN_URL', 'https://wallet.hello.coop/authorize' );
define( 'HELLO_LOGIN_ENDPOINT_TOKEN_URL', 'https://wallet.hello.coop/oauth/token' );
define( 'HELLO_LOGIN_CLIENT_SCOPE', '' );
define( 'HELLO_LOGIN_LINK_EXISTING_USERS', 1 );
define( 'HELLO_LOGIN_CREATE_IF_DOES_NOT_EXIST', 1 );
define( 'HELLO_LOGIN_REDIRECT_USER_BACK', 1 );
