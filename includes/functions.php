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

/**
 * Check if the current user (if any) is linked to Hellō.
 *
 * @return bool
 */
function hello_login_is_user_linked(): bool {
	if ( is_user_logged_in() ) {
		$hello_user_id = Hello_Login_Users::get_hello_sub();

		return ! empty( $hello_user_id );
	}

	return false;
}

add_filter( 'body_class', 'hello_login_body_class', 999 );
add_filter( 'admin_body_class', 'hello_login_admin_body_class', 999 );

/**
 * Add Hellō specific CSS classes to the body tag.
 *
 * @param array $classes List of classes to add to.
 *
 * @return array
 */
function hello_login_body_class( array $classes ): array {
	if ( hello_login_is_user_linked() ) {
		$classes[] = 'hello-login-linked';
	}

	return $classes;
}

/**
 * Add Hellō specific CSS classes to the body tag of the admin interface.
 *
 * @param string $classes Space separated list of classes to add to.
 *
 * @return string
 */
function hello_login_admin_body_class( string $classes ): string {
	if ( hello_login_is_user_linked() ) {
		$classes .= ' hello-login-linked';
	}

	return $classes;
}
