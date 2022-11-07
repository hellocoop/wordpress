<?php
/**
 * Global OIDCG functions.
 *
 * @package   OpenID_Connect_Generic
 * @author    Jonathan Daggerhart <jonathan@daggerhart.com>
 * @copyright 2015-2020 daggerhart
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GPL-2.0+
 */

/**
 * Return a single use authentication URL.
 *
 * @return string
 */
function oidcg_get_authentication_url() {
	return \OpenID_Connect_Generic::instance()->client_wrapper->get_authentication_url();
}

/**
 * Refresh a user claim and update the user metadata.
 *
 * @param WP_User $user             The user object.
 * @param array   $token_response   The token response.
 *
 * @return WP_Error|array
 */
function oidcg_refresh_user_claim( $user, $token_response ) {
	return \OpenID_Connect_Generic::instance()->client_wrapper->refresh_user_claim( $user, $token_response );
}

function hellocoop_enqueue_scripts_and_styles() {
	wp_enqueue_script( 'hello-button', 'https://cdn.hello.coop/js/hello-btn.js' );
	wp_enqueue_style( 'hello-button', 'https://cdn.hello.coop/css/hello-btn.css' );
}

add_action( 'wp_enqueue_scripts', 'hellocoop_enqueue_scripts_and_styles' );
add_action( 'login_enqueue_scripts', 'hellocoop_enqueue_scripts_and_styles' );
add_action( 'admin_enqueue_scripts', 'hellocoop_enqueue_scripts_and_styles' );
