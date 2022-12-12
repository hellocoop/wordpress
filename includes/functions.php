<?php
/**
 * Global Hellō Login functions.
 *
 * @package   Hello_Login
 * @author    Marius Scurtescu <marius.scurtescu@hello.coop>
 * @copyright 2022 Hello Coop
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GPL-2.0+
 */

function hello_login_enqueue_scripts_and_styles() {
	wp_enqueue_script( 'hello-button', 'https://cdn.hello.coop/js/hello-btn.js', array(), Hello_Login::VERSION );
	wp_enqueue_script( 'auth-url', plugin_dir_url( __DIR__ ) . 'js/auth_url.js', array(), Hello_Login::VERSION );
	wp_enqueue_style( 'hello-button', 'https://cdn.hello.coop/css/hello-btn.css', array(), Hello_Login::VERSION, 'all' );
}

add_action( 'wp_enqueue_scripts', 'hello_login_enqueue_scripts_and_styles' );
add_action( 'login_enqueue_scripts', 'hello_login_enqueue_scripts_and_styles' );
add_action( 'admin_enqueue_scripts', 'hello_login_enqueue_scripts_and_styles' );

function hello_login_user_profile( $profileuser ) {
	$api_url = rest_url( 'hello-login/v1/auth_url' );
	$hello_user_id = get_user_meta( $profileuser->ID, 'hello-login-subject-identity', true );
	?>
	<h2>Hellō Login</h2>
	<table class="form-table">
		<tr>
			<th>Hellō Wallet</th>
			<td>
				<?php if ( empty( $hello_user_id ) ) { ?>
					<button class="hello-btn" data-label="ō&nbsp;&nbsp;&nbsp;Link Hellō" onclick="navigateToHelloAuthRequestUrl('<?php print esc_js( $api_url ); ?>', '')"></button>
				<?php } else { ?>
					<button class="hello-btn" data-label="ō&nbsp;&nbsp;&nbsp;Unlink Hellō" onclick="navigateToHelloAuthRequestUrl('<?php print esc_js( $api_url ); ?>', '')"></button>
				<?php } ?>
			</td>
		</tr>
	</table>
	<?php
}

add_action( 'show_user_profile', 'hello_login_user_profile' );
