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
defined( 'OIDC_CLIENT_ID' ) || define( 'OIDC_CLIENT_ID', bin2hex( random_bytes( 32 ) ) );
defined( 'OIDC_ENDPOINT_LOGIN_URL' ) || define( 'OIDC_ENDPOINT_LOGIN_URL', 'https://wallet.hello.coop/authorize' );
defined( 'OIDC_ENDPOINT_TOKEN_URL' ) || define( 'OIDC_ENDPOINT_TOKEN_URL', 'https://wallet.hello.coop/oauth/token' );
defined( 'OIDC_CLIENT_SCOPE' ) || define( 'OIDC_CLIENT_SCOPE', '' );
defined( 'OIDC_LINK_EXISTING_USERS' ) || define( 'OIDC_LINK_EXISTING_USERS', 1 );
defined( 'OIDC_CREATE_IF_DOES_NOT_EXIST' ) || define( 'OIDC_CREATE_IF_DOES_NOT_EXIST', 1 );
defined( 'OIDC_REDIRECT_USER_BACK' ) || define( 'OIDC_REDIRECT_USER_BACK', 1 );
