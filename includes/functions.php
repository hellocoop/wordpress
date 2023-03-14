<?php
/**
 * Global Hellō Login functions.
 *
 * @package   Hello_Login
 * @author    Marius Scurtescu <marius.scurtescu@hello.coop>
 * @copyright 2022 Hello Coop
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GPL-2.0+
 */

/**
 * Enqueue Hellō specific CSS and JavaScript.
 *
 * @return void
 */
function hello_login_enqueue_scripts_and_styles() {
	wp_enqueue_script( 'hello-button', 'https://cdn.hello.coop/js/hello-btn.js', array(), Hello_Login::VERSION );
	wp_enqueue_style( 'hello-button', 'https://cdn.hello.coop/css/hello-btn.css', array(), Hello_Login::VERSION, 'all' );
	wp_enqueue_style( 'hello-login-hello-button', plugin_dir_url( __DIR__ ) . 'css/styles.css', array(), Hello_Login::VERSION, 'all' );
}

add_action( 'wp_enqueue_scripts', 'hello_login_enqueue_scripts_and_styles' );
add_action( 'login_enqueue_scripts', 'hello_login_enqueue_scripts_and_styles' );
add_action( 'admin_enqueue_scripts', 'hello_login_enqueue_scripts_and_styles' );

/**
 * Create the full URL of the auth request start endpoint. Redirecting to this URL will start the sign-in process.
 *
 * @param string $redirect_to_path the path where to redirect after sign in.
 * @param string $scope_set The type of interaction for this auth request. Could be 'auth' (default) or 'update_email').
 * @return string
 */
function create_auth_request_start_url( string $redirect_to_path, string $scope_set = 'auth' ): string {
	return site_url( '?hello-login=start&redirect_to_path=' . rawurlencode( $redirect_to_path ) . '&scope_set=' . rawurlencode( $scope_set ) . '&_cc=' . microtime( true ) );
}
