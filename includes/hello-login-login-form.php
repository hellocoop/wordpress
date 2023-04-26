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
	 * Plugin settings object.
	 *
	 * @var Hello_Login_Option_Settings
	 */
	private Hello_Login_Option_Settings $settings;

	/**
	 * Plugin client wrapper instance.
	 *
	 * @var Hello_Login_Client_Wrapper
	 */
	private Hello_Login_Client_Wrapper $client_wrapper;

	/**
	 * The class constructor.
	 *
	 * @param Hello_Login_Option_Settings $settings       A plugin settings object instance.
	 * @param Hello_Login_Client_Wrapper  $client_wrapper A plugin client wrapper object instance.
	 */
	public function __construct( $settings, $client_wrapper ) {
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
	public static function register( Hello_Login_Option_Settings $settings, Hello_Login_Client_Wrapper $client_wrapper ) {
		$login_form = new self( $settings, $client_wrapper );

		$on_logged_out = isset( $_GET['loggedout'] ) && 'true' == $_GET['loggedout'];

		// Alter the login form as dictated by settings.
		if ( $on_logged_out ) {
			add_filter( 'login_messages', array( $login_form, 'handle_login_page' ), 99 );
		} else {
			add_filter( 'login_message', array( $login_form, 'handle_login_page' ), 99 );
		}

		// Alter the comment reply links.
		add_filter( 'comment_reply_link_args', array( $login_form, 'filter_comment_reply_link_args' ), 99, 3 );
		add_filter( 'comment_reply_link', array( $login_form, 'filter_comment_reply_link' ), 99, 4 );
		add_filter( 'comment_form_defaults', array( $login_form, 'filter_comment_form_defaults' ), 99 );

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
			&& ( ! empty( $_GET['force_redirect'] ) )
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
	public function handle_login_page( string $message ): string {

		if ( isset( $_GET['login-error'] ) ) {
			$error_message = ! empty( $_GET['message'] ) ? sanitize_text_field( wp_unslash( $_GET['message'] ) ) : 'Unknown error.';
			$message .= $this->make_error_output( sanitize_text_field( wp_unslash( $_GET['login-error'] ) ), $error_message );
		}

		$configured = ! empty( $this->settings->client_id );
		$on_lost_password = isset( $_GET['action'] ) && 'lostpassword' == $_GET['action'];
		$on_register = isset( $_GET['action'] ) && 'register' == $_GET['action'];

		if ( $configured && ! $on_lost_password && ! $on_register ) {
			// Login button is appended to existing messages in case of error.
			$atts = array(
				'redirect_to' => $this->extract_redirect_to(),
			);

			$message .= $this->make_login_button( $atts );

			// Login form toggle is appended right after the button.
			$message .= $this->make_login_form_toggle();
		}

		return $message;
	}

	/**
	 * Implements the filter comment_reply_link_args.
	 *
	 * @param array      $args    Comment reply link arguments. See get_comment_reply_link()
	 *                            for more information on accepted arguments.
	 * @param WP_Comment $comment The object of the comment being replied to.
	 * @param WP_Post    $post    The WP_Post object.
	 *
	 * @return array Modified list of args.
	 */
	public function filter_comment_reply_link_args( array $args, WP_Comment $comment, WP_Post $post ): array {
		$configured = ! empty( $this->settings->client_id );

		if ( $configured ) {
			$args['login_text'] = 'Log in with Hellō to Reply';
		}

		return $args;
	}

	/**
	 * Implements the filter comment_reply_link.
	 *
	 * @param string     $link    The HTML markup for the comment reply link.
	 * @param array      $args    An array of arguments overriding the defaults.
	 * @param WP_Comment $comment The object of the comment being replied.
	 * @param WP_Post    $post    The WP_Post object.
	 *
	 * @return string The HTML code for the comment reply link.
	 */
	public function filter_comment_reply_link( string $link, array $args, WP_Comment $comment, WP_Post $post ): string {
		$configured = ! empty( $this->settings->client_id );

		if ( $configured && strpos( $link, 'comment-reply-login' ) !== false ) {
			$auth_request_start_url = 'href="' . esc_url( create_auth_request_start_url( Hello_Login_Util::extract_path_and_query( get_permalink() . '#respond' ) ) ) . '"';

			$link = preg_replace( '/href=[\'"][^\'"]+[\'"]/', $auth_request_start_url, $link );
		}

		return $link;
	}

	/**
	 * Implements the filter comment_reply_link.
	 *
	 * @param array $defaults The default comment form arguments.
	 *
	 * @return array Modified defaults.
	 */
	public function filter_comment_form_defaults( array $defaults ): array {
		$configured = ! empty( $this->settings->client_id );

		if ( $configured ) {
			$atts = array(
				'redirect_to' => get_permalink() . '#respond',
				'align' => 'left',
				'show_hint' => false,
				'label' => 'Log in with Hellō to post a comment',
			);

			$defaults['must_log_in'] = $this->make_login_button( $atts );
		}

		return $defaults;
	}

	/**
	 * Get the URL to redirect to after sign-in.
	 *
	 * @return string The URL to redirect to.
	 */
	public function extract_redirect_to(): string {
		if ( isset( $_GET['redirect_to'] ) ) {
			return sanitize_text_field( wp_unslash( $_GET['redirect_to'] ) );
		} else {
			return admin_url();
		}
	}

	/**
	 * Display an error message to the user.
	 *
	 * @param string $error_code    The error code.
	 * @param string $error_message The error message test.
	 *
	 * @return string
	 */
	public function make_error_output( string $error_code, string $error_message ): string {

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
	public function make_login_button( array $atts = array() ): string {
		$defaults = array(
			'align' => 'center',
			'show_hint' => true,
			'label' => 'Continue with Hellō',
		);

		$atts = wp_parse_args( $atts, $defaults );

		$redirect_to_path = '/';

		if ( isset( $atts['redirect_to'] ) ) {
			$redirect_to_path = Hello_Login_Util::extract_path_and_query( $atts['redirect_to'] );
		}

		$start_url = create_auth_request_start_url( $redirect_to_path );

		ob_start();
		?>
		<div class="hello-container" style="display: block; text-align: <?php print esc_html( $atts['align'] ); ?>;">
			<button class="hello-btn" onclick="parent.location='<?php print esc_js( $start_url ); ?>'" data-label="<?php print esc_html( 'ō&nbsp;&nbsp;&nbsp;' . $atts['label'] ); ?>"></button>
			<?php if ( $atts['show_hint'] ) { ?><button class="hello-about" style="text-align: <?php print esc_html( $atts['align'] ); ?>;"></button><?php } ?>
		</div>
		<?php

		return ob_get_clean();
	}

	/**
	 * Create a toggle for the login form.
	 *
	 * @return string
	 */
	public function make_login_form_toggle(): string {
		wp_enqueue_script( 'hello-username-password-form', plugin_dir_url( __DIR__ ) . 'js/scripts-login.js', array(), Hello_Login::VERSION );
		wp_enqueue_style( 'hello-username-password-form', plugin_dir_url( __DIR__ ) . 'css/styles-login.css', array(), Hello_Login::VERSION, 'all' );

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
