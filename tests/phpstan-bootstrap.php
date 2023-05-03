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
defined( 'WP_LANG_DIR' ) || define( 'WP_LANG_DIR', 'wordpress/src/wp-includes/languages/' );

defined( 'COOKIE_DOMAIN' ) || define( 'COOKIE_DOMAIN', 'localhost' );
defined( 'COOKIEPATH' ) || define( 'COOKIEPATH', '/');

// Define Plugin Globals.
defined( 'HELLO_LOGIN_CLIENT_ID' ) || define( 'HELLO_LOGIN_CLIENT_ID', bin2hex( random_bytes( 32 ) ) );
defined( 'HELLO_LOGIN_ENDPOINT_LOGIN_URL' ) || define( 'HELLO_LOGIN_ENDPOINT_LOGIN_URL', 'https://wallet.hello.coop/authorize' );
defined( 'HELLO_LOGIN_ENDPOINT_TOKEN_URL' ) || define( 'HELLO_LOGIN_ENDPOINT_TOKEN_URL', 'https://wallet.hello.coop/oauth/token' );
defined( 'HELLO_LOGIN_ENDPOINT_INVITE_URL' ) || define( 'HELLO_LOGIN_ENDPOINT_INVITE_URL', 'https://wallet.hello.coop/invite' );
defined( 'HELLO_LOGIN_ENDPOINT_INTROSPECT_URL' ) || define( 'HELLO_LOGIN_ENDPOINT_INTROSPECT_URL', 'https://wallet.hello.coop/oauth/introspect' );
defined( 'HELLO_LOGIN_CLIENT_SCOPE' ) || define( 'HELLO_LOGIN_CLIENT_SCOPE', '' );
defined( 'HELLO_LOGIN_LINK_EXISTING_USERS' ) || define( 'HELLO_LOGIN_LINK_EXISTING_USERS', 1 );
defined( 'HELLO_LOGIN_CREATE_IF_DOES_NOT_EXIST' ) || define( 'HELLO_LOGIN_CREATE_IF_DOES_NOT_EXIST', 1 );
defined( 'HELLO_LOGIN_REDIRECT_USER_BACK' ) || define( 'HELLO_LOGIN_REDIRECT_USER_BACK', 1 );
