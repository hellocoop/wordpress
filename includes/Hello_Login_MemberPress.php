<?php
/**
 * Hello Login integration with MemberPress.
 *
 * @package   Hello_Login
 * @category  Login
 * @author    Marius Scurtescu <marius.scurtescu@hello.coop>
 * @copyright 2023  Hello Identity Co-op
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GPL-2.0+
 */

/**
 * Hello Login integration with MemberPress. Implements login related MemberPress actions.
 *
 * @see https://memberpress.com/      The “All-In-One” Membership Plugin for WordPress
 * @see https://docs.memberpress.com/ MemberPress User Manual
 *
 * @package Hello_Login
 */
class Hello_Login_MemberPress {

	/**
	 * Plugin logger.
	 *
	 * @var Hello_Login_Option_Logger
	 */
	private Hello_Login_Option_Logger $logger;

	/**
	 * The login form and its button generation logic.
	 *
	 * @var Hello_Login_Login_Form
	 */
	private Hello_Login_Login_Form $login_form;

	/**
	 * The class constructor.
	 *
	 * @param Hello_Login_Option_Logger $logger     The plugin logger instance.
	 * @param Hello_Login_Login_Form    $login_form The login form and its button generation logic.
	 */
	public function __construct( Hello_Login_Option_Logger $logger, Hello_Login_Login_Form $login_form ) {
		$this->logger     = $logger;
		$this->login_form = $login_form;
	}

	/**
	 * Hook the client into WordPress.
	 *
	 * @param Hello_Login_Option_Logger   $logger     The plugin logger instance.
	 * @param Hello_Login_Option_Settings $settings   The plugin settings instance.
	 * @param Hello_Login_Login_Form      $login_form The login form and its button generation logic.
	 *
	 * @return Hello_Login_MemberPress
	 */
	public static function register( Hello_Login_Option_Logger $logger, Hello_Login_Option_Settings $settings, Hello_Login_Login_Form $login_form ): Hello_Login_MemberPress {
		$configured = ! empty( $settings->client_id );

		$member_press  = new self( $logger, $login_form );

		if ( $configured ) {
			if ( $settings->memberpress_enable_login ) {
				add_action( 'mepr-login-form-before-submit', array( $member_press, 'login_form_button_action' ) );
			}

			if ( $settings->memberpress_enable_registration ) {
				add_action( 'mepr-checkout-before-submit', array( $member_press, 'checkout_button_action' ) );
			}

			if ( $settings->memberpress_enable_account ) {
				add_action( 'mepr_account_home', array( $member_press, 'account_home_action' ) );
			}
		}

		return $member_press;
	}

	/**
	 * Implements the mepr-login-form-before-submit MemberPress action.
	 */
	public function login_form_button_action() {
		$this->logger->log( 'mepr-login-form-before-submit hook was called', 'hello-memberpress' );

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $this->login_form->make_login_button();
	}

	/**
	 * Implements the mepr-checkout-before-submit MemberPress action.
	 */
	public function checkout_button_action() {
		$this->logger->log( 'mepr-checkout-before-submit hook was called', 'hello-memberpress' );

		if ( is_user_logged_in() ) {
			return;
		}

		$atts = array(
			'redirect_to' => get_permalink(),
		);

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $this->login_form->make_login_button( $atts );
	}

	/**
	 * Implement the mepr_account_home MemberPress action.
	 */
	public function account_home_action() {
		$this->logger->log( 'mepr_account_home hook was called', 'hello-memberpress' );
		$hello_user_id    = Hello_Login_Users::get_hello_sub();
		$redirect_to_path = Hello_Login_Util::extract_path_and_query( get_permalink() );
		$link_url         = create_auth_request_start_url( $redirect_to_path );
		$unlink_url       = wp_nonce_url( site_url( '?hello-login=unlink&redirect_to_path=' . rawurlencode( $redirect_to_path ) ), 'unlink' . get_current_user_id() . $redirect_to_path );
		?>
		<h2>Hellō</h2>
		<table>
			<tr>
				<th>This Account</th>
				<td>
					<?php if ( empty( $hello_user_id ) ) { ?>
						<button type="button" class="hello-btn" data-label="ō&nbsp;&nbsp;&nbsp;Link with Hellō" onclick="parent.location='<?php print esc_js( $link_url ); ?>'"></button>
					<?php } else { ?>
						<button type="button" class="button" onclick="parent.location='<?php print esc_js( $unlink_url ); ?>'">ō&nbsp;&nbsp;&nbsp;Unlink from Hellō</button>
					<?php } ?>
				</td>
			</tr>
		</table>
		<?php
	}
}
