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
	 * @param Hello_Login_Option_Logger $logger     The plugin logger instance.
	 * @param Hello_Login_Login_Form    $login_form The login form and its button generation logic.
	 *
	 * @return Hello_Login_MemberPress
	 */
	public static function register( Hello_Login_Option_Logger $logger, Hello_Login_Login_Form $login_form ): Hello_Login_MemberPress {
		$member_press  = new self( $logger, $login_form );

		add_action( 'mepr-login-form-before-submit', array( $member_press, 'login_form_button' ) );
		add_action( 'mepr-checkout-before-submit', array( $member_press, 'checkout_button' ) );
		// TODO: Investigate if mepr_account_home is needed as well.

		return $member_press;
	}

	/**
	 * Implements the mepr-login-form-before-submit MemberPress action.
	 */
	public function login_form_button() {
		$this->logger->log( 'mepr-login-form-before-submit hook was called', 'hello-memberpress' );

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $this->login_form->make_login_button();
	}

	/**
	 * Implements the mepr-checkout-before-submit MemberPress action.
	 */
	public function checkout_button() {
		$this->logger->log( 'mepr-checkout-before-submit hook was called', 'hello-memberpress' );

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $this->login_form->make_login_button();
	}
}
