<?php
/**
 * Login form and login button handlong class.
 *
 * @package   Hello_Login
 * @category  Login
 * @author    Jonathan Daggerhart <jonathan@daggerhart.com>
 * @copyright 2015-2020 daggerhart
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GPL-2.0+
 */

/**
 * Hello_Login_Login_Form class.
 *
 * Login form and login button handlong.
 *
 * @package Hello_Login
 * @category  Login
 */
class Hello_Login_Login_Form {

	/**
	 * Plugin logger.
	 *
	 * @var Hello_Login_Option_Logger
	 */
	private $logger;

	/**
	 * Plugin settings object.
	 *
	 * @var Hello_Login_Option_Settings
	 */
	private $settings;

	/**
	 * Plugin client wrapper instance.
	 *
	 * @var Hello_Login_Client_Wrapper
	 */
	private $client_wrapper;

	/**
	 * The class constructor.
	 *
	 * @param Hello_Login_Option_Logger   $logger         Plugin logs.
	 * @param Hello_Login_Option_Settings $settings       A plugin settings object instance.
	 * @param Hello_Login_Client_Wrapper  $client_wrapper A plugin client wrapper object instance.
	 */
	public function __construct( $logger, $settings, $client_wrapper ) {
		$this->logger = $logger;
		$this->settings = $settings;
		$this->client_wrapper = $client_wrapper;
	}

	/**
	 * Create an instance of the Hello_Login_Login_Form class.
	 *
	 * @param Hello_Login_Option_Settings $settings       A plugin settings object instance.
	 * @param Hello_Login_Client_Wrapper  $client_wrapper A plugin client wrapper object instance.
	 *
	 * @return void
	 */
	public static function register( $logger, $settings, $client_wrapper ) {
		$login_form = new self( $logger, $settings, $client_wrapper );

		// Alter the login form as dictated by settings.
		add_filter( 'login_messages', array( $login_form, 'handle_login_page' ), 99 );

		// Add a shortcode for the login button.
		add_shortcode( 'hello_login_button', array( $login_form, 'make_login_button' ) );

		$login_form->handle_redirect_login_type_auto();
	}

	/**
	 * Auto Login redirect.
	 *
	 * @return void
	 */
	public function handle_redirect_login_type_auto() {

		if ( 'wp-login.php' == $GLOBALS['pagenow']
			&& ( 'auto' == $this->settings->login_type || ! empty( $_GET['force_redirect'] ) )
			// Don't send users to the IDP on logout or post password protected authentication.
			&& ( ! isset( $_GET['action'] ) || ! in_array( $_GET['action'], array( 'logout', 'postpass' ) ) )
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WP Login Form doesn't have a nonce.
			&& ! isset( $_POST['wp-submit'] ) ) {
			if ( ! isset( $_GET['login-error'] ) ) {
				wp_redirect( $this->client_wrapper->get_authentication_url() );
				exit;
			} else {
				add_action( 'login_footer', array( $this, 'remove_login_form' ), 99 );
			}
		}

	}

	/**
	 * Implements filter login_message.
	 *
	 * @param string $message The text message to display on the login page.
	 *
	 * @return string
	 */
	public function handle_login_page( $message ) {

		if ( isset( $_GET['login-error'] ) ) {
			$error_message = ! empty( $_GET['message'] ) ? sanitize_text_field( wp_unslash( $_GET['message'] ) ) : 'Unknown error.';
			$message .= $this->make_error_output( sanitize_text_field( wp_unslash( $_GET['login-error'] ) ), $error_message );
		}

		$configured = !empty($this->settings->client_id);
		$on_lost_password = isset( $_GET['action'] ) && 'lostpassword' == $_GET['action'];
		$on_register = isset( $_GET['action'] ) && 'register' == $_GET['action'];

		if ( $configured && ! $on_lost_password && ! $on_register) {
			// Login button is appended to existing messages in case of error.
			$atts = array(
				'redirect_to' => home_url(),
			);

			$message .= $this->make_login_button($atts);

			// Login form toggle is appended right after the button
			$message .= $this->make_login_form_toggle();
		}

		return $message;
	}

	/**
	 * Display an error message to the user.
	 *
	 * @param string $error_code    The error code.
	 * @param string $error_message The error message test.
	 *
	 * @return string
	 */
	public function make_error_output( $error_code, $error_message ) {

		ob_start();
		?>
		<div id="login_error"><?php // translators: %1$s is the error code from the IDP. ?>
			<strong><?php printf( esc_html__( 'ERROR (%1$s)', 'hello-login' ), esc_html( $error_code ) ); ?>: </strong>
			<?php print esc_html( $error_message ); ?>
		</div>
		<?php
		return wp_kses_post( ob_get_clean() );
	}

	/**
	 * Create a login button (link).
	 *
	 * @param array $atts Array of optional attributes to override login button
	 * functionality when used by shortcode.
	 *
	 * @return string
	 */
	public function make_login_button( $atts = array() ) {
		$redirect_to_path = '';

		if ( isset( $atts['redirect_to'] ) ) {
			$p = parse_url( $atts['redirect_to'] );

			$redirect_to_path = empty( $p['path'] ) ? '/' : $p['path'];

			if ( ! empty( $p['query'] ) ) {
				$redirect_to_path .= '?' . $p['query'];
			}
		}

		$api_url = rest_url( 'hello-login/v1/auth_url' );

		ob_start();
		?>
		<div class="hello-container" style="display: block; text-align: center;">
			<button class="hello-btn" onclick="navigateToHelloAuthRequestUrl('<?php print esc_js( $api_url ); ?>', '<?php print esc_js( $redirect_to_path ); ?>')">
				<?php print esc_html__( 'ō&nbsp;&nbsp;&nbsp;Continue with Hellō', 'hello-login' ); ?>
			</button>
			<button class="hello-about"></button>
		</div>
		<?php

		return ob_get_clean();
	}

	/**
	 * Create a toggle for the login form.
	 *
	 * @return string
	 */
	public function make_login_form_toggle() {
		wp_enqueue_script( 'hello-username-password-form', plugin_dir_url( __DIR__ ) . 'js/scripts-login.js' );
		wp_enqueue_style( 'hello-username-password-form', plugin_dir_url( __DIR__ ) . 'css/styles-login.css' );

		ob_start();
		?>
		<div style="display: flex; align-items: center; justify-content: center; margin: 25px auto;">
			<span style="position: absolute; background-color: #f0f0f1; width: 40px; height: 20px; z-index: 40; text-align: center; font-size: 14px;">OR</span>
			<div style="height: 2px; width: 100%; background-color: black; opacity: 0.1;"></div>
		</div>
		<?php

		return ob_get_clean();
	}

	/**
	 * Removes the login form from the HTML DOM
	 *
	 * @return void
	 */
	public function remove_login_form() {
		?>
		<script type="text/javascript">
			(function() {
				var loginForm = document.getElementById("user_login").form;
				var parent = loginForm.parentNode;
				parent.removeChild(loginForm);
			})();
		</script>
		<?php
	}

}
